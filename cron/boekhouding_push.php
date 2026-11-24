<?php
// ============================================================
// Gespreksspecificaties naar de boekhouding sturen (facturen laten maken)
//
//   php cron/boekhouding_push.php            vorige maand, alleen als "elke maand automatisch" aan staat
//   php cron/boekhouding_push.php 2026-10    die maand, altijd (handmatig)
//
// Cronjob, de 1e van de maand om 06:00:
//   0 6 1 * * php /var/www/asterisk-manager/cron/boekhouding_push.php >> /var/www/asterisk-manager/logs/boekhouding.log 2>&1
//
// Definitieve facturen in de boekhouding worden niet overschreven; conceptfacturen van dezelfde klant en maand wel.
// ============================================================
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Alleen vanaf de opdrachtregel.\n"); }
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require_once dirname(__DIR__) . '/config.php';   // definieert APP_ROOT
require_once APP_ROOT . '/includes/Boekhouding.php';

$maand = $argv[1] ?? '';
$handmatig = $maand !== '';
if (!$handmatig) $maand = date('Y-m', strtotime('first day of last month'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $maand)) { fwrite(STDERR, "Gebruik: php cron/boekhouding_push.php [JJJJ-MM]\n"); exit(1); }

$stempel = '[' . date('Y-m-d H:i:s') . '] ';
if (!$handmatig && Boekhouding::instelling('boekhouding_auto') !== '1') {
    echo $stempel . "Automatisch versturen staat uit (Settings > Boekhouding).\n";
    exit(0);
}
if (!Boekhouding::ingesteld()) {
    echo $stempel . "Koppeling met de boekhouding is niet ingesteld.\n";
    exit(1);
}

$s = Boekhouding::verstuurAlle($maand, false);
echo $stempel . Boekhouding::maandLabel($maand) . ": {$s['verstuurd']} verstuurd, {$s['bestond_al']} al definitief, {$s['overgeslagen']} zonder kosten overgeslagen"
    . ($s['fouten'] ? ', ' . count($s['fouten']) . " fout(en):\n  " . implode("\n  ", $s['fouten']) : '') . "\n";
exit($s['fouten'] ? 1 : 0);
