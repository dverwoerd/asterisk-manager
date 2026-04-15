#!/usr/bin/env php
<?php
// ============================================================
// CDR AGI Script
// Schrijft CDR direct naar de database na elk gesprek
// Pad: /var/www/asterisk-manager/agi/cdr_write.php
// ============================================================

define('APP_ROOT', dirname(__DIR__));
$_SERVER['HTTP_HOST'] = 'localhost';

require_once APP_ROOT . '/config.php';

// Lees AGI variabelen van Asterisk
$agi = [];
while (!feof(STDIN)) {
    $line = trim(fgets(STDIN));
    if ($line === '') break;
    if (strpos($line, ':') !== false) {
        [$key, $val] = explode(':', $line, 2);
        $agi[strtolower(trim($key))] = trim($val);
    }
}

// Haal CDR variabelen op via AGI
function agiGet(string $var): string
{
    fwrite(STDOUT, "GET VARIABLE CDR($var)\n");
    $response = trim(fgets(STDIN));
    if (preg_match('/\(([^)]*)\)/', $response, $m)) {
        return $m[1];
    }
    return '';
}

function agiExec(string $cmd): void
{
    fwrite(STDOUT, $cmd . "\n");
    fgets(STDIN);
}

// Lees CDR data
$calldate    = agiGet('start');
$src         = agiGet('src');
$dst         = agiGet('dst');
$dcontext    = agiGet('dcontext') ?: ($agi['agi_context'] ?? '');
$channel     = agiGet('channel') ?: ($agi['agi_channel'] ?? '');
$dstchannel  = agiGet('dstchannel');
$lastapp     = agiGet('lastapp');
$lastdata    = agiGet('lastdata');
$duration    = (int)agiGet('duration');
$billsec     = (int)agiGet('billsec');
$disposition = agiGet('disposition') ?: 'ANSWERED';
$accountcode = agiGet('accountcode');
$uniqueid    = agiGet('uniqueid') ?: ($agi['agi_uniqueid'] ?? uniqid());
$userfield   = agiGet('userfield');
$clid        = agiGet('clid') ?: ($agi['agi_callerid'] ?? '');

// Converteer calldate formaat
if ($calldate && !preg_match('/^\d{4}-\d{2}-\d{2}/', $calldate)) {
    $calldate = date('Y-m-d H:i:s', strtotime($calldate));
}
if (empty($calldate)) {
    $calldate = date('Y-m-d H:i:s');
}

// Schrijf naar database
try {
    Database::query(
        "INSERT IGNORE INTO cdr_records
         (calldate, clid, src, dst, dcontext, channel, dstchannel,
          lastapp, lastdata, duration, billsec, disposition,
          amaflags, accountcode, uniqueid, userfield)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [
            $calldate, $clid, $src, $dst, $dcontext,
            $channel, $dstchannel, $lastapp, $lastdata,
            $duration, $billsec, $disposition, 0,
            $accountcode, $uniqueid, $userfield,
        ]
    );
} catch (Exception $e) {
    error_log('CDR AGI write error: ' . $e->getMessage());
}

fwrite(STDOUT, "VERBOSE \"CDR written for $src -> $dst\" 1\n");
fgets(STDIN);
