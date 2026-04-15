<?php
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
$_SERVER['HTTP_HOST'] = 'localhost';
require_once APP_ROOT . '/config.php';

$logFile  = APP_ROOT . '/logs/cdr_import.log';
$lockFile = '/tmp/cdr_import.lock';

if (!is_dir(APP_ROOT . '/logs')) {
    mkdir(APP_ROOT . '/logs', 0775, true);
}

if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 55) {
    exit(0);
}
file_put_contents($lockFile, getmypid());

function logMsg($msg) {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

try {
    $lastSync = Database::fetchOne("SELECT MAX(calldate) as last FROM cdr_records");
    $since    = $lastSync['last'] ?? '2000-01-01 00:00:00';
    logMsg("Importeren vanaf: $since");

    $rows = Database::fetchAll(
        "SELECT * FROM cdr WHERE calldate > ? ORDER BY calldate LIMIT 1000",
        [$since]
    );
    logMsg("Records in cdr tabel: " . count($rows));

    $count = 0;
    foreach ($rows as $row) {
        $disposition = strtoupper(trim($row['disposition'] ?? ''));
        if (empty($disposition)) {
            $disposition = ($row['billsec'] > 0) ? 'ANSWERED' : 'NO ANSWER';
        }
        try {
            $result = Database::query(
                "INSERT IGNORE INTO cdr_records
                 (calldate, clid, src, dst, dcontext, channel, dstchannel,
                  lastapp, lastdata, duration, billsec, disposition,
                  amaflags, accountcode, uniqueid, userfield)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $row['calldate'],
                    $row['clid']        ?? '',
                    $row['src']         ?? '',
                    $row['dst']         ?? '',
                    $row['dcontext']    ?? '',
                    $row['channel']     ?? '',
                    $row['dstchannel']  ?? '',
                    $row['lastapp']     ?? '',
                    $row['lastdata']    ?? '',
                    (int)($row['duration']  ?? 0),
                    (int)($row['billsec']   ?? 0),
                    $disposition,
                    (int)($row['amaflags']  ?? 0),
                    $row['accountcode'] ?? '',
                    $row['uniqueid']    ?? uniqid(),
                    $row['userfield']   ?? '',
                ]
            );
            if ($result->rowCount() > 0) $count++;
        } catch (Exception $e) {
            logMsg("Row fout: " . $e->getMessage());
        }
    }

    logMsg("$count nieuwe records geimporteerd");

    if ($count > 0) {
        require_once APP_ROOT . '/controllers/BaseController.php';
        require_once APP_ROOT . '/controllers/CDRController.php';
        $costed = CDRController::applyRatesToCDR();
        logMsg("Kosten berekend voor $costed records");
    }

    $total = Database::fetchOne("SELECT COUNT(*) as cnt FROM cdr_records");
    logMsg("Totaal in database: " . ($total['cnt'] ?? 0) . " records");

} catch (Exception $e) {
    logMsg("FOUT: " . $e->getMessage());
}

if (file_exists($lockFile)) {
    unlink($lockFile);
}
