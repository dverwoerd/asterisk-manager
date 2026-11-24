<?php
// ============================================================
// Boekhouding - koppeling met de boekhouding (api.php)
//
// Stuurt de gespreksspecificatie van een klant over een maand naar de boekhouding. Daar wordt een
// factuur aangemaakt (of een eerdere conceptfactuur voor dezelfde klant en maand vervangen), met de
// specificatie-PDF als bijlage die meegaat met de factuurmail.
//
// Instellingen (tab Boekhouding bij Settings):
//   boekhouding_url          https://.../boekhouding/api.php
//   boekhouding_api_key      sleutel uit de boekhouding (tandwiel > Bedrijfsgegevens > Koppeling)
//   boekhouding_status       boekhouding | concept | definitief | definitief_mailen
//   boekhouding_regels       totaal (1 bedragregel, specificatie alleen in de PDF) | categorie (bedragregel per categorie)
//   boekhouding_toestel_artikel     artikelcode in de boekhouding voor toestellen; aantal = toestellen van de klant (leeg = geen regel)
//   boekhouding_toestel_omschrijving omschrijving van die regel, {maand} {aantal} {toestellen} (leeg = omschrijving van het artikel)
//   boekhouding_omschrijving tekst van de factuurregel, {maand} = "november 2026"
//   boekhouding_btw          VH (21%), VL, V0, VV, VEU, VEX
//   boekhouding_artikel      optioneel: artikelcode in de boekhouding (omzetrekening en BTW komen dan daarvandaan)
//   boekhouding_pdf          1 = specificatie-PDF meesturen
//   boekhouding_nul          1 = ook klanten met € 0,00 versturen
//   boekhouding_auto         1 = cron/boekhouding_push.php verstuurt elke maand automatisch
// ============================================================

class Boekhouding
{
    public static function instelling(string $key, string $standaard = ''): string
    {
        return (string)(Database::getSetting($key, $standaard) ?? $standaard);
    }

    public static function ingesteld(): bool
    {
        return self::instelling('boekhouding_url') !== '' && self::instelling('boekhouding_api_key') !== '';
    }

    public static function zorgTabel(): void
    {
        static $klaar = false;
        if ($klaar) return;
        Database::query("CREATE TABLE IF NOT EXISTS boekhouding_facturen (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            maand CHAR(7) NOT NULL,
            factuur_id INT NULL,
            factuurnummer VARCHAR(30) NULL,
            status VARCHAR(20) NOT NULL DEFAULT '',
            bedrag DECIMAL(12,2) NULL,
            bericht VARCHAR(500) NOT NULL DEFAULT '',
            gelukt TINYINT(1) NOT NULL DEFAULT 0,
            verstuurd_op DATETIME NOT NULL,
            UNIQUE KEY uniek_klant_maand (customer_id, maand)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $klaar = true;
    }

    /** Laatste verzending per klant voor een maand: [customer_id => rij] */
    public static function status(string $maand): array
    {
        try {
            self::zorgTabel();
            $uit = [];
            foreach (Database::fetchAll("SELECT * FROM boekhouding_facturen WHERE maand = ?", [$maand]) as $r) $uit[(int)$r['customer_id']] = $r;
            return $uit;
        } catch (Exception $e) {
            return [];
        }
    }

    /** Verbinding en sleutel testen. */
    public static function ping(): array
    {
        return self::http('GET', 'ping');
    }

    public static function maandLabel(string $maand): string
    {
        $namen = ['01' => 'januari', '02' => 'februari', '03' => 'maart', '04' => 'april', '05' => 'mei', '06' => 'juni',
            '07' => 'juli', '08' => 'augustus', '09' => 'september', '10' => 'oktober', '11' => 'november', '12' => 'december'];
        [$j, $m] = explode('-', $maand);
        return ($namen[$m] ?? $m) . ' ' . $j;
    }

    private static function euro(float $v): string
    {
        return '€ ' . number_format($v, 2, ',', '.');
    }

    /**
     * Bouwt het bericht voor de boekhouding uit een specificatie (GesprekspecificatiesController::bouwSpecificatie).
     */
    /** Toestellen (extensies) van een klant: de toestelnummers uit de klantkaart die als extensie bestaan en aan staan. */
    public static function toestellen(array $customer): array
    {
        $lijst = array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($customer['extensions_csv'] ?? ''))))));
        if (!$lijst) return [];
        try {
            $ph = implode(',', array_fill(0, count($lijst), '?'));
            $bestaand = array_column(Database::fetchAll("SELECT extension FROM extensions WHERE extension IN ($ph) AND enabled = 1", $lijst), 'extension');
            $alle = array_column(Database::fetchAll("SELECT extension FROM extensions WHERE extension IN ($ph)", $lijst), 'extension');
            // toestelnummers die niet in de extensielijst staan (bijv. extern beheerd) tellen ook mee; uitgeschakelde niet
            $lijst = array_values(array_filter($lijst, fn($e) => in_array($e, $bestaand, true) || !in_array($e, $alle, true)));
        } catch (Exception $e) {
            // extensietabel niet beschikbaar: de klantkaart is leidend
        }
        sort($lijst, SORT_NATURAL);
        return $lijst;
    }

    /** Factuurregel voor de toestellen, of null als er geen toestelartikel is ingesteld of de klant geen toestellen heeft. */
    public static function toestelRegel(array $customer, string $maand): ?array
    {
        $art = self::instelling('boekhouding_toestel_artikel');
        $toestellen = self::toestellen($customer);
        if ($art === '' || !$toestellen) return null;
        $oms = strtr(self::instelling('boekhouding_toestel_omschrijving'), [
            '{maand}' => self::maandLabel($maand), '{aantal}' => (string)count($toestellen), '{toestellen}' => implode(', ', $toestellen)]);
        return array_filter(['omschrijving' => trim($oms), 'artikel' => $art, 'aantal' => count($toestellen)], fn($v) => $v !== '');
    }

    public static function bericht(array $customer, string $maand, array $spec, ?string $pdf = null, bool $overschrijven = false): array
    {
        $label = self::maandLabel($maand);
        $oms   = str_replace('{maand}', $label, self::instelling('boekhouding_omschrijving', 'Gesprekskosten {maand}'));
        $btw   = self::instelling('boekhouding_btw', 'VH');
        $art   = self::instelling('boekhouding_artikel');
        $totaal = round((float)$spec['totaal']['kosten'], 2);

        $gespr = fn(int $n) => $n . ($n === 1 ? ' gesprek' : ' gesprekken');
        $regel = fn(string $o, float $prijs) => array_filter([
            'omschrijving' => $o, 'aantal' => 1, 'prijs' => round($prijs, 2),
            'btw_code' => $art === '' ? $btw : null, 'artikel' => $art !== '' ? $art : null,
        ], fn($v) => $v !== null);

        $regels = [];
        if (self::instelling('boekhouding_regels', 'totaal') === 'categorie') {
            foreach ($spec['categorieen'] as $cat => $c) {
                if ($c['aantal'] === 0 || round($c['kosten'], 2) == 0) continue;
                $regels[] = $regel("$oms - $cat (" . $gespr($c['aantal']) . ", {$c['minuten']} min)", $c['kosten']);
            }
            // afronding per categorie kan een cent afwijken van het totaal van de specificatie: verschil op de grootste regel
            $verschil = round($totaal - array_sum(array_column($regels, 'prijs')), 2);
            if ($regels && $verschil != 0) {
                $i = array_search(max(array_column($regels, 'prijs')), array_column($regels, 'prijs'), true);
                $regels[$i]['prijs'] = round($regels[$i]['prijs'] + $verschil, 2);
            }
            if (!$regels) $regels[] = $regel($oms, 0);
        } else {
            $regels[] = $regel($oms, $totaal);   // de specificatie zelf staat in de PDF-bijlage
        }
        // geen gesprekskosten: geen gespreksregel (tenzij "ook € 0,00 versturen" aan staat)
        if ($totaal == 0 && self::instelling('boekhouding_nul') !== '1') $regels = [];
        if ($t = self::toestelRegel($customer, $maand)) $regels[] = $t;
        if (!$regels) $regels[] = $regel($oms, 0);

        $naam = trim((string)($customer['company_name'] ?: $customer['contact_name']));
        $adres = trim(preg_replace('/\s*\R\s*/', ', ', (string)($customer['address'] ?? '')));
        $d = [
            'bron'       => 'asterisk-manager',
            'extern_id'  => 'asterisk:gesprekken:' . $customer['id'] . ':' . $maand,
            'referentie' => ucfirst($oms),
            'klant'      => [
                'extern_id'      => 'asterisk:klant:' . $customer['id'],
                'naam'           => $naam,
                'contactpersoon' => $customer['company_name'] ? (string)$customer['contact_name'] : '',
                'email'          => (string)($customer['email'] ?? ''),
                'telefoon'       => (string)($customer['phone'] ?? ''),
                'adres'          => $adres,
                'postcode'       => (string)($customer['postal_code'] ?? ''),
                'plaats'         => (string)($customer['city'] ?? ''),
                'land'           => (string)($customer['country'] ?? ''),
                'btw_nummer'     => (string)($customer['vat_number'] ?? ''),
            ],
            'regels'        => array_values($regels),
            'overschrijven' => $overschrijven,
        ];
        $status = self::instelling('boekhouding_status', 'boekhouding');
        if ($status === 'concept') $d['status'] = 'concept';
        if ($status === 'definitief' || $status === 'definitief_mailen') { $d['status'] = 'definitief'; $d['mailen'] = $status === 'definitief_mailen'; }
        if ($pdf !== null && $pdf !== '') {
            $d['bijlagen'] = [['naam' => 'Gespreksspecificatie ' . $label . '.pdf', 'type' => 'application/pdf', 'inhoud_base64' => base64_encode($pdf)]];
        }
        return $d;
    }

    /**
     * Verstuurt de specificatie van één klant over één maand. Geeft het antwoord van de boekhouding
     * (ok, actie, nummer, status, totaal_incl, melding) plus 'overgeslagen' als er niets te factureren is.
     */
    public static function verstuur(array $customer, string $maand, bool $overschrijven = false): array
    {
        if (!self::ingesteld()) throw new RuntimeException('De koppeling met de boekhouding is niet ingesteld (Settings > Boekhouding).');
        require_once APP_ROOT . '/controllers/BaseController.php';
        require_once APP_ROOT . '/controllers/GesprekspecificatiesController.php';
        $spec = (new GesprekspecificatiesController())->specificatieVoor($customer, $maand);

        $geenGesprekken = $spec['totaal']['aantal'] === 0 || (round($spec['totaal']['kosten'], 2) == 0 && self::instelling('boekhouding_nul') !== '1');
        if ($geenGesprekken && !self::toestelRegel($customer, $maand)) {
            return ['ok' => true, 'overgeslagen' => true, 'melding' => 'Geen kosten in ' . self::maandLabel($maand) . '; niet verstuurd.'];
        }

        $pdf = null;
        if (self::instelling('boekhouding_pdf', '1') === '1' && $spec['totaal']['aantal'] > 0) {
            require_once APP_ROOT . '/includes/GesprekPDF.php';
            $pdf = GesprekPDF::maak($customer, GesprekspecificatiesController::bedrijf(), ucfirst(self::maandLabel($maand)), $spec)->Output('', 'S');
        }

        try {
            $antwoord = self::http('POST', 'factuur', self::bericht($customer, $maand, $spec, $pdf, $overschrijven));
        } catch (Exception $e) {
            $antwoord = ['ok' => false, 'reden' => 'verbinding', 'melding' => $e->getMessage()];
        }
        self::bewaar((int)$customer['id'], $maand, $antwoord);
        return $antwoord;
    }

    private static function bewaar(int $customerId, string $maand, array $a): void
    {
        self::zorgTabel();
        $bericht = $a['ok'] ?? false
            ? 'Factuur ' . ($a['actie'] ?? 'aangemaakt') . ($a['gemaild'] ?? false ? ' en gemaild' : '') . (isset($a['mail_fout']) ? '; mailen mislukt: ' . $a['mail_fout'] : '')
            : (string)($a['melding'] ?? 'Onbekende fout');
        Database::query(
            "INSERT INTO boekhouding_facturen (customer_id, maand, factuur_id, factuurnummer, status, bedrag, bericht, gelukt, verstuurd_op)
             VALUES (?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE factuur_id = COALESCE(VALUES(factuur_id), factuur_id), factuurnummer = COALESCE(VALUES(factuurnummer), factuurnummer),
               status = IF(VALUES(status) = '', status, VALUES(status)), bedrag = COALESCE(VALUES(bedrag), bedrag),
               bericht = VALUES(bericht), gelukt = VALUES(gelukt), verstuurd_op = NOW()",
            [$customerId, $maand, $a['factuur_id'] ?? null, $a['nummer'] ?? null, (string)($a['status'] ?? ''),
             isset($a['totaal_incl']) ? (float)$a['totaal_incl'] : null, mb_substr($bericht, 0, 500), ($a['ok'] ?? false) ? 1 : 0]
        );
    }

    /** Alle actieve klanten met gesprekken in een maand versturen. */
    public static function verstuurAlle(string $maand, bool $overschrijven = false): array
    {
        $uit = ['verstuurd' => 0, 'overgeslagen' => 0, 'bestond_al' => 0, 'fouten' => []];
        foreach (Database::fetchAll("SELECT * FROM customers WHERE active = 1 ORDER BY company_name, contact_name") as $c) {
            $naam = $c['company_name'] ?: $c['contact_name'];
            try {
                $a = self::verstuur($c, $maand, $overschrijven);
            } catch (Exception $e) {
                $a = ['ok' => false, 'melding' => $e->getMessage()];
            }
            if (!empty($a['overgeslagen'])) $uit['overgeslagen']++;
            elseif ($a['ok'] ?? false) $uit['verstuurd']++;
            elseif (($a['reden'] ?? '') === 'bestaat_al') $uit['bestond_al']++;
            else $uit['fouten'][] = "$naam: " . ($a['melding'] ?? 'onbekende fout');
        }
        return $uit;
    }

    /** HTTP-aanroep naar api.php van de boekhouding. */
    public static function http(string $methode, string $actie, ?array $body = null): array
    {
        $url = rtrim(self::instelling('boekhouding_url'), '/');
        if ($url === '') throw new RuntimeException('Vul het adres van de boekhouding in.');
        if (!preg_match('#^https?://#i', $url)) throw new RuntimeException('Het adres van de boekhouding moet met https:// beginnen.');
        if (!str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.php')) $url .= '/api.php';
        $url .= (str_contains($url, '?') ? '&' : '?') . 'actie=' . rawurlencode($actie);
        $sleutel = self::instelling('boekhouding_api_key');
        $headers = ['Authorization: Bearer ' . $sleutel, 'X-Api-Key: ' . $sleutel, 'Accept: application/json'];
        $json = $body !== null ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        if ($json !== null) $headers[] = 'Content-Type: application/json';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => $methode,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 90,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            if ($json !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $tekst = curl_exec($ch);
            $code  = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $fout  = curl_error($ch);
            curl_close($ch);
            if ($tekst === false) throw new RuntimeException('Boekhouding niet bereikbaar: ' . $fout);
        } else {
            $ctx = stream_context_create(['http' => ['method' => $methode, 'header' => implode("\r\n", $headers),
                'content' => $json ?? '', 'timeout' => 90, 'ignore_errors' => true, 'follow_location' => 0]]);
            $tekst = @file_get_contents($url, false, $ctx);
            if ($tekst === false) throw new RuntimeException('Boekhouding niet bereikbaar op ' . $url);
            $code = preg_match('#^HTTP/\S+\s+(\d+)#', $http_response_header[0] ?? '', $m) ? (int)$m[1] : 0;
        }
        $data = json_decode((string)$tekst, true);
        if (!is_array($data)) {
            throw new RuntimeException("Onverwacht antwoord van de boekhouding (HTTP $code). Klopt het adres? Het moet eindigen op api.php.");
        }
        return $data;
    }
}
