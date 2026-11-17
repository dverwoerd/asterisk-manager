<?php
// ============================================================
// TarievenController: tarievenbeheer voor de gespreksspecificaties.
//   - Starttarief, nationaal en mobiel (instellingen in de settings-tabel)
//   - Internationale / 0900-tarieven per prefix (tabel bel_tarieven)
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';

class TarievenController extends BaseController
{
    public function index(): void
    {
        $zoek = trim((string)$this->get('q', ''));
        try {
            if ($zoek !== '') {
                $like   = '%' . $zoek . '%';
                $rijen  = Database::fetchAll(
                    "SELECT * FROM bel_tarieven WHERE prefix LIKE ? OR land LIKE ? OR regio LIKE ? ORDER BY land, prefix LIMIT 500",
                    [$zoek . '%', $like, $like]);
            } else {
                $rijen = Database::fetchAll("SELECT * FROM bel_tarieven ORDER BY land, prefix LIMIT 500");
            }
            $totaal = (int)(Database::fetchOne("SELECT COUNT(*) AS n FROM bel_tarieven")['n'] ?? 0);
            $tabel  = true;
        } catch (Exception $e) {
            $rijen = []; $totaal = 0; $tabel = false;
        }
        $this->view('tarieven.index', [
            'title'  => t('nav_tarieven'), 'rijen' => $rijen, 'totaal' => $totaal, 'tabel' => $tabel, 'zoek' => $zoek,
            'inst'   => [
                'start'     => getSetting('billing_starttarief', '0.065'),
                'nationaal' => getSetting('billing_tarief_nationaal', '0.025'),
                'mobiel'    => getSetting('billing_tarief_mobiel', '0.065'),
                'btw'       => getSetting('default_tax_rate', '21'),
            ],
        ]);
    }

    public function post_instellingen(): void
    {
        $this->requireOperator();
        foreach (['start' => 'billing_starttarief', 'nationaal' => 'billing_tarief_nationaal', 'mobiel' => 'billing_tarief_mobiel'] as $f => $key) {
            Database::setSetting($key, $this->decimaal($this->post($f, '0')));
        }
        Database::setSetting('default_tax_rate', (string)(float)str_replace(',', '.', (string)$this->post('btw', '21')));
        $this->flash('success', t('saved'));
        redirect('?page=tarieven');
    }

    public function post_opslaan(): void
    {
        $this->requireOperator();
        $prefix = preg_replace('/\D/', '', (string)$this->post('prefix', ''));
        $oud    = preg_replace('/\D/', '', (string)$this->post('oud_prefix', ''));
        if ($prefix === '') { $this->flash('error', 'Prefix ontbreekt.'); redirect('?page=tarieven'); }

        $data = [
            'prefix' => $prefix,
            'tarief' => $this->decimaal($this->post('tarief', '0')),
            'land'   => trim((string)$this->post('land', '')),
            'regio'  => trim((string)$this->post('regio', '')),
        ];
        if ($oud !== '' && $oud !== $prefix) Database::delete('bel_tarieven', 'prefix=?', [$oud]);
        Database::query(
            "INSERT INTO bel_tarieven (prefix, tarief, land, regio) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE tarief=VALUES(tarief), land=VALUES(land), regio=VALUES(regio)",
            array_values($data));
        $this->flash('success', t('saved'));
        redirect('?page=tarieven&q=' . urlencode($prefix));
    }

    public function delete(): void
    {
        $this->requireOperator();
        Database::delete('bel_tarieven', 'prefix=?', [preg_replace('/\D/', '', (string)$this->get('prefix', ''))]);
        $this->flash('success', t('deleted'));
        redirect('?page=tarieven');
    }

    private function decimaal(mixed $v): string
    {
        return number_format((float)str_replace(',', '.', (string)$v), 4, '.', '');
    }
}
