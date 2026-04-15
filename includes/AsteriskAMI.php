<?php
// ============================================================
// Asterisk AMI - Manager Interface Client
// ============================================================

class AsteriskAMI
{
    private $socket = null;
    private string $host;
    private int $port;
    private string $username;
    private string $secret;
    private bool $connected = false;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 5038,
        string $username = 'manager',
        string $secret = ''
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->secret = $secret;
    }

    public static function fromSettings(): self
    {
        return new self(
            Database::getSetting('asterisk_host', '127.0.0.1'),
            (int) Database::getSetting('asterisk_ami_port', 5038),
            Database::getSetting('asterisk_ami_user', 'manager'),
            Database::getSetting('asterisk_ami_secret', '')
        );
    }

    public function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
        if (!$this->socket) {
            logError("AMI connect failed: $errstr ($errno)");
            return false;
        }
        // Read banner
        fgets($this->socket, 4096);
        // Login
        $response = $this->sendAction([
            'Action'   => 'Login',
            'Username' => $this->username,
            'Secret'   => $this->secret,
        ]);
        $this->connected = ($response['Response'] ?? '') === 'Success';
        return $this->connected;
    }

    public function reprovisionEndpoint(string $extension): bool
    {
        if (!$this->socket) return false;
        // check-sync zonder reboot=true triggert alleen provisioning check
        $cmd  = "Action: PJSIPNotify
";
        $cmd .= "Endpoint: " . $extension . "
";
        $cmd .= "Variable: Event=check-sync

";
        fwrite($this->socket, $cmd);
        $response = '';
        while ($line = fgets($this->socket)) {
            $response .= $line;
            if (trim($line) === '') break;
        }
        return true;
    }

    public function rebootEndpoint(string $extension): bool
    {
        if (!$this->socket) return false;
        $cmd  = "Action: PJSIPNotify
";
        $cmd .= "Endpoint: " . $extension . "
";
        $cmd .= "Variable: Event=check-sync;reboot=true

";
        fwrite($this->socket, $cmd);
        $response = '';
        while ($line = fgets($this->socket)) {
            $response .= $line;
            if (trim($line) === '') break;
        }
        return strpos($response, 'Success') !== false || strpos($response, 'Message: NOTIFY') !== false;
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            $this->sendAction(['Action' => 'Logoff']);
            fclose($this->socket);
            $this->socket = null;
            $this->connected = false;
        }
    }

    public function sendAction(array $action): array
    {
        if (!$this->socket) return [];
        $packet = '';
        foreach ($action as $key => $val) {
            $packet .= "$key: $val\r\n";
        }
        $packet .= "\r\n";
        fwrite($this->socket, $packet);
        return $this->readResponse();
    }

    private function readResponse(): array
    {
        $response = [];
        $timeout = microtime(true) + 5;
        while (microtime(true) < $timeout) {
            $line = fgets($this->socket, 4096);
            if ($line === false || trim($line) === '') break;
            $line = trim($line);
            if (strpos($line, ': ') !== false) {
                [$key, $val] = explode(': ', $line, 2);
                $response[$key] = $val;
            }
        }
        return $response;
    }

    private function readFullResponse(): array
    {
        $events = [];
        $current = [];
        $timeout = microtime(true) + 5;
        while (microtime(true) < $timeout) {
            $line = fgets($this->socket, 4096);
            if ($line === false) break;
            $line = trim($line);
            if ($line === '') {
                if (!empty($current)) {
                    $events[] = $current;
                    $current = [];
                    // Check if this is the end marker
                    $lastEvent = end($events);
                    if (($lastEvent['Event'] ?? '') === 'RegistrationsComplete' ||
                        ($lastEvent['EventList'] ?? '') === 'Complete') {
                        break;
                    }
                }
            } elseif (strpos($line, ': ') !== false) {
                [$key, $val] = explode(': ', $line, 2);
                $current[$key] = $val;
            }
        }
        return $events;
    }

    // ---- High-level commands ----

    public function reload(string $module = ''): bool
    {
        $action = ['Action' => 'Reload'];
        if ($module) $action['Module'] = $module;
        $r = $this->sendAction($action);
        return ($r['Response'] ?? '') === 'Success' || ($r['Message'] ?? '') === 'Module Reloaded';
    }

    public function getChannels(): array
    {
        $this->sendAction(['Action' => 'CoreShowChannels']);
        return $this->readFullResponse();
    }

    public function getRegistrations(): array
    {
        $this->sendAction([
            'Action'  => 'PJSIPShowRegistrationsOutbound',
        ]);
        return $this->readFullResponse();
    }

    public function getEndpoints(): array
    {
        $this->sendAction(['Action' => 'PJSIPShowEndpoints']);
        return $this->readFullResponse();
    }

    public function getQueueStatus(?string $queue = null): array
    {
        $action = ['Action' => 'QueueStatus'];
        if ($queue) $action['Queue'] = $queue;
        $this->sendAction($action);
        return $this->readFullResponse();
    }

    public function originate(string $channel, string $exten, string $context, int $priority = 1, ?string $callerid = null): array
    {
        $action = [
            'Action'   => 'Originate',
            'Channel'  => $channel,
            'Exten'    => $exten,
            'Context'  => $context,
            'Priority' => $priority,
            'Async'    => 'true',
        ];
        if ($callerid) $action['CallerID'] = $callerid;
        return $this->sendAction($action);
    }

    public function hangupChannel(string $channel): array
    {
        return $this->sendAction([
            'Action'  => 'Hangup',
            'Channel' => $channel,
        ]);
    }

    public function getSystemStatus(): array
    {
        return $this->sendAction(['Action' => 'CoreStatus']);
    }

    public function getCoreSettings(): array
    {
        return $this->sendAction(['Action' => 'CoreSettings']);
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    // ---- Queue operations ----

    public function addQueueMember(string $queue, string $interface, int $penalty = 0): array
    {
        return $this->sendAction([
            'Action'    => 'QueueAdd',
            'Queue'     => $queue,
            'Interface' => $interface,
            'Penalty'   => $penalty,
        ]);
    }

    public function removeQueueMember(string $queue, string $interface): array
    {
        return $this->sendAction([
            'Action'    => 'QueueRemove',
            'Queue'     => $queue,
            'Interface' => $interface,
        ]);
    }

    public function pauseQueueMember(string $queue, string $interface, bool $paused, string $reason = ''): array
    {
        return $this->sendAction([
            'Action'    => 'QueuePause',
            'Queue'     => $queue,
            'Interface' => $interface,
            'Paused'    => $paused ? 'true' : 'false',
            'Reason'    => $reason,
        ]);
    }
}
