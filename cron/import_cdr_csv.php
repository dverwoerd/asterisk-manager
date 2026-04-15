#!/usr/bin/env php
<?php
// ============================================================
// CDR CSV Import Script
// Importeert CDR records vanuit Asterisk Master.csv
// Uitvoeren: php /var/www/asterisk-manager/cron/import_cdr_csv.php
// Cron: */5 * * * * www-data php /var/www/asterisk-manager/cron/import_cdr_csv.php
// ============================================================

define('APP_ROOT', dirname(__DIR__));
$_SERVER['HTTP_HOST'] = 'localhost';

require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/AsteriskAMI.php';

$csvFile = '/var/log/asterisk/cdr-csv/Master.csv';
$logFile = APP_ROOT . '/logs/cdr_import.log';

function logMsg(string $msg): void
{
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

// Controleer of CSV bestand bestaat
if (!file_exists($csvFile)) {
    logMsg("ERROR: CDR CSV bestand niet gevonden: $csvFile");
    logMsg("Zorg dat cdr_csv.so geladen is in Asterisk.");
    exit(1);
}

// Haal de laatste geïmporteerde calldate op
try {
    $lastImport = Database::fetchOne("SELECT MAX(calldate) as last FROM cdr_records");
    $since      = $lastImport['last'] ?? '2000-01-01 00:00:00';
} catch (Exception $e) {
    logMsg("ERROR: Database verbinding mislukt: " . $e->getMessage());
    exit(1);
}

logMsg("Start CDR import. Laatste import: $since");

// Lees CSV bestand
// Asterisk Master.csv formaat:
// "calldate","clid","src","dst","dcontext","channel","dstchannel","lastapp","lastdata","duration","billsec","disposition","amaflags","accountcode","uniqueid","userfield"
$handle = fopen($csvFile, 'r');
if (!$handle) {
    logMsg("ERROR: Kan CSV bestand niet openen: $csvFile");
    exit(1);
}

$count  = 0;
$errors = 0;
$skipped = 0;

while (($row = fgetcsv($handle, 4096, ',')) !== false) {
    // Verwacht minimaal 16 kolommen
    if (count($row) < 11) {
        $skipped++;
        continue;
    }

    // Kolom mapping (Asterisk CDR CSV volgorde)
    $calldate   = trim($row[0],  '"');
    $clid       = trim($row[1],  '"');
    $src        = trim($row[2],  '"');
    $dst        = trim($row[3],  '"');
    $dcontext   = trim($row[4],  '"');
    $channel    = trim($row[5],  '"');
    $dstchannel = trim($row[6],  '"');
    $lastapp    = trim($row[7],  '"');
    $lastdata   = trim($row[8],  '"');
    $duration   = (int)trim($row[9],  '"');
    $billsec    = (int)trim($row[10], '"');
    $disposition = trim($row[11] ?? 'ANSWERED', '"');
    $amaflags   = (int)trim($row[12] ?? '0', '"');
    $accountcode = trim($row[13] ?? '', '"');
    $uniqueid   = trim($row[14] ?? uniqid(), '"');
    $userfield  = trim($row[15] ?? '', '"');

    // Sla records over die al geïmporteerd zijn
    if ($calldate <= $since) {
        $skipped++;
        continue;
    }

    // Sla lege of ongeldige records over
    if (empty($calldate) || $calldate === 'calldate') {
        $skipped++;
        continue;
    }

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
                $duration, $billsec, $disposition, $amaflags,
                $accountcode, $uniqueid, $userfield,
            ]
        );
        $count++;
    } catch (Exception $e) {
        $errors++;
        logMsg("Fout bij importeren: " . $e->getMessage() . " | Row: " . implode(',', $row));
    }
}

fclose($handle);

logMsg("Import klaar: $count nieuw, $skipped overgeslagen, $errors fouten");

// Bereken kosten voor nieuwe records
if ($count > 0) {
    logMsg("Kosten berekenen voor nieuwe records...");
    require_once APP_ROOT . '/controllers/CDRController.php';
    $costed = CDRController::applyRatesToCDR();
    logMsg("Kosten berekend voor $costed records");
}
