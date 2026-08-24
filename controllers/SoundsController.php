<?php
require_once APP_ROOT . '/controllers/BaseController.php';

class SoundsController extends BaseController
{
    private string $soundsPath = '/var/lib/asterisk/sounds';
    private string $customPath = '/var/lib/asterisk/sounds/custom';
    private array $allowedExts = ['gsm', 'wav', 'mp3', 'ulaw', 'alaw', 'g722', 'sln'];

    public function index(): void
    {
        $lang     = $this->get('lang', 'en');
        $sounds   = $this->getSoundFiles($lang);
        $custom   = $this->getSoundFiles('custom');
        $langs    = $this->getAvailableLangs();

        $this->view('sounds.index', [
            'title'  => '🔊 Sound Files',
            'sounds' => $sounds,
            'custom' => $custom,
            'langs'  => $langs,
            'lang'   => $lang,
        ]);
    }

    public function upload(): void
    {
        $this->requireOperator();

        if (empty($_FILES['soundfile']['tmp_name'])) {
            $this->flash('danger', 'Geen bestand geselecteerd.');
            redirect('?page=sounds');
        }

        $origName = $_FILES['soundfile']['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $this->allowedExts)) {
            $this->flash('danger', "Ongeldig bestandstype. Toegestaan: " . implode(', ', $this->allowedExts));
            redirect('?page=sounds');
        }

        // Maak custom map aan
        if (!is_dir($this->customPath)) {
            mkdir($this->customPath, 0755, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $destFile = $this->customPath . '/' . $safeName . '.' . $ext;

        if (move_uploaded_file($_FILES['soundfile']['tmp_name'], $destFile)) {
            chmod($destFile, 0644);
            chown($destFile, 'asterisk');
            $this->flash('success', "Soundfile '{$safeName}' geüpload.");
        } else {
            $this->flash('danger', 'Upload mislukt — controleer rechten op ' . $this->customPath);
        }

        redirect('?page=sounds');
    }

    public function delete(): void
    {
        $this->requireOperator();
        $file = basename($this->get('file', ''));
        $path = $this->customPath . '/' . $file;

        if (file_exists($path) && strpos(realpath($path), realpath($this->customPath)) === 0) {
            unlink($path);
            $this->flash('success', "Soundfile '{$file}' verwijderd.");
        } else {
            $this->flash('danger', 'Bestand niet gevonden of niet toegestaan.');
        }

        redirect('?page=sounds');
    }

    // API endpoint voor sound picker in queue form
    public function list(): void
    {
        $lang   = $this->get('lang', 'en');
        $sounds = $this->getSoundFiles($lang);
        $custom = $this->getSoundFiles('custom');

        header('Content-Type: application/json');
        echo json_encode([
            'sounds' => $sounds,
            'custom' => $custom,
        ]);
        exit;
    }

    private function getSoundFiles(string $lang): array
    {
        $path  = $this->soundsPath . '/' . $lang;
        $files = [];

        if (!is_dir($path)) return $files;

        foreach (glob($path . '/*.{' . implode(',', $this->allowedExts) . '}', GLOB_BRACE) as $file) {
            $files[] = pathinfo($file, PATHINFO_FILENAME);
        }

        sort($files);
        return $files;
    }

    private function getAvailableLangs(): array
    {
        $langs = [];
        foreach (glob($this->soundsPath . '/*', GLOB_ONLYDIR) as $dir) {
            $langs[] = basename($dir);
        }
        return $langs;
    }
}
