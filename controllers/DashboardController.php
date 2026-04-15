<?php
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskAMI.php';

class DashboardController extends BaseController
{
    public function index(): void
    {
        $stats = [
            'total_extensions'  => Database::count('extensions', 'enabled=1'),
            'total_trunks'      => Database::count('trunks', 'enabled=1'),
            'total_queues'      => Database::count('queues', 'enabled=1'),
            'total_ring_groups' => Database::count('ring_groups', 'enabled=1'),
            'calls_today'       => Database::count('cdr_records', "DATE(calldate)=CURDATE()"),
            'calls_month'       => Database::count('cdr_records', "MONTH(calldate)=MONTH(NOW()) AND YEAR(calldate)=YEAR(NOW())"),
            'answered_today'    => Database::count('cdr_records', "DATE(calldate)=CURDATE() AND disposition='ANSWERED'"),
        ];

        $recentCalls = Database::fetchAll(
            "SELECT * FROM cdr_records ORDER BY calldate DESC LIMIT 10"
        );

        // AMI status
        $amiStatus      = ['connected' => false];
        $activeChannels = 0;
        try {
            $ami = AsteriskAMI::fromSettings();
            if ($ami->connect()) {
                $coreSettings   = $ami->getCoreSettings();
                $amiStatus      = [
                    'connected' => true,
                    'version'   => $coreSettings['AsteriskVersion'] ?? 'Unknown',
                ];
                $channels       = $ami->getChannels();
                $activeChannels = max(0, count($channels) - 2);
                $ami->disconnect();
            }
        } catch (Exception $e) {
            $amiStatus = ['connected' => false];
        }

        // Call chart
        $callChart = Database::fetchAll(
            "SELECT DATE(calldate) as day, COUNT(*) as total,
             SUM(disposition='ANSWERED') as answered,
             SUM(disposition='NO ANSWER') as no_answer
             FROM cdr_records
             WHERE calldate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(calldate)
             ORDER BY day"
        );

        // Geregistreerde toestellen
        $endpoints = $this->getEndpoints();

        $this->view('dashboard.index', [
            'title'          => t('dashboard_title'),
            'stats'          => $stats,
            'recentCalls'    => $recentCalls,
            'amiStatus'      => $amiStatus,
            'activeChannels' => $activeChannels,
            'callChart'      => $callChart,
            'endpoints'      => $endpoints,
        ]);
    }

    private function getEndpoints(): array
    {
        $endpoints = [];

        try {
            // Asterisk CLI uitvoeren - probeer direct dan sudo
            $asteriskCmd = '/usr/sbin/asterisk';
            $testOutput  = shell_exec("$asteriskCmd -rx 'core show version' 2>/dev/null");
            if (empty($testOutput)) {
                $asteriskCmd = 'sudo /usr/sbin/asterisk';
            }

            // Haal alle contacts op
            $contactsOutput = shell_exec("$asteriskCmd -rx 'pjsip show contacts' 2>/dev/null");

            // Haal provisioning data op
            $phones   = Database::fetchAll(
                "SELECT pp.*, e.extension, e.full_name
                 FROM provision_phones pp
                 JOIN extensions e ON pp.extension_id = e.id"
            );
            $phoneMap = [];
            foreach ($phones as $p) {
                $phoneMap[$p['extension']] = $p;
            }

            // Parse contacts output
            // Format: "  Contact:  101/sip:101@172.16.5.92:64730;ob  hash  NonQual  -nan"
            $contactLines = [];
            foreach (explode("\n", $contactsOutput ?? '') as $line) {
                if (!preg_match('/^\s+Contact:\s+(\d+)\/(sip:[^@]+@[\d.]+:\d+[^\s]*)\s+(\S+)\s+(\S+)\s+(.+)$/', $line, $m)) {
                    continue;
                }
                $ext        = $m[1];
                $contactUri = $m[2];
                $hash       = $m[3];
                $status     = $m[4];
                $rttRaw     = trim($m[5]);

                // Parse IP en poort uit de contact URI
                preg_match('/@([\d.]+):(\d+)/', $contactUri, $ipMatch);
                $ip   = $ipMatch[1] ?? '-';
                $port = $ipMatch[2] ?? '-';

                $rtt = ($rttRaw === '-nan' || $rttRaw === '') ? '-' : round((float)$rttRaw, 1) . ' ms';

                $contactLines[$ext] = [
                    'uri'    => $contactUri,
                    'hash'   => $hash,
                    'status' => $status,
                    'rtt'    => $rtt,
                    'ip'     => $ip,
                    'port'   => $port,
                ];
            }

            // Haal User-Agent (model/firmware) op per contact
            foreach ($contactLines as $ext => $contact) {
                // pjsip show contact <aor>/<uri>
                $contactDetail = shell_exec(
                    "$asteriskCmd -rx 'pjsip show contact $ext/{$contact['uri']}' 2>/dev/null"
                );

                $userAgent = '-';
                $firmware  = '-';

                if ($contactDetail) {
                    // Zoek User Agent in de output
                    if (preg_match('/User\s*Agent\s*:\s*(.+)/i', $contactDetail, $ua)) {
                        $userAgent = trim($ua[1]);
                        // Parseer firmware versie uit User-Agent
                        // Yealink: "Yealink SIP-T46U 66.86.0.25"
                        // Yealink: "Yealink SIP-T41S 66.86.0.180"
                        if (preg_match('/Yealink\s+SIP-(\S+)\s+([\d.]+)/i', $userAgent, $fw)) {
                            $userAgent = 'Yealink ' . $fw[1];
                            $firmware  = $fw[2];
                        } elseif (preg_match('/(\S+)\s+([\d.]+\.\d+\.\d+)/i', $userAgent, $fw)) {
                            $firmware  = $fw[2];
                        }
                    }
                }

                $phone      = $phoneMap[$ext] ?? null;
                $endpoints[] = [
                    'extension'  => $ext,
                    'ip'         => $contact['ip'],
                    'port'       => $contact['port'],
                    'status'     => $contact['status'],
                    'rtt'        => $contact['rtt'],
                    'user_agent' => $userAgent,
                    'firmware'   => $firmware,
                    'model'      => $phone['model']           ?? ($userAgent !== '-' ? $userAgent : '-'),
                    'full_name'  => $phone['full_name']       ?? $ext,
                    'mac'        => $phone['mac_address']     ?? '-',
                    'last_prov'  => $phone['last_provision']  ?? null,
                ];
            }

        } catch (Exception $e) {
            // Stil falen
        }

        return $endpoints;
    }
}
