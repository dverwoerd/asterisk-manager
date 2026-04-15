<?php
// ============================================================
// TrunksController
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskConfig.php';

class TrunksController extends BaseController
{
    public function index(): void
    {
        $trunks = Database::fetchAll("SELECT * FROM trunks ORDER BY name");
        $this->view('trunks.index', ['title' => t('trunks'), 'trunks' => $trunks]);
    }
    public function add(): void
    {
        $this->view('trunks.form', ['title' => t('add_new').' '.t('trunk'), 'trunk' => $this->defaults(), 'action' => 'add']);
    }
    public function post_add(): void
    {
        $this->requireAdmin();
        $data = $this->collectFormData();
        Database::insert('trunks', $data);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=trunks');
    }
    public function edit(): void
    {
        $trunk = Database::fetchOne("SELECT * FROM trunks WHERE id=?", [$this->id()]);
        $this->view('trunks.form', ['title' => t('edit').' '.t('trunk'), 'trunk' => $trunk, 'action' => 'edit']);
    }
    public function post_edit(): void
    {
        $this->requireAdmin();
        $id = $this->id();
        $data = $this->collectFormData();
        if (empty($data['password'])) unset($data['password']);
        Database::update('trunks', $data, 'id=?', [$id]);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=trunks');
    }
    public function delete(): void
    {
        $this->requireAdmin();
        Database::delete('trunks', 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('deleted'));
        redirect('?page=trunks');
    }
    private function collectFormData(): array
    {
        return [
            'name' => trim($this->post('name','')),
            'trunk_type' => \$this->post('trunk_type','provider'),
            'type' => $this->post('type','pjsip'),
            'host' => trim($this->post('host','')),
            'port' => (int)$this->post('port',5060),
            'username' => trim($this->post('username','')),
            'password' => trim($this->post('password','')),
            'auth_type' => $this->post('auth_type','userpass'),
            'context' => $this->post('context','from-trunk'),
            'codecs' => implode(',', $this->post('codecs', ['ulaw','alaw'])),
            'max_channels' => (int)$this->post('max_channels',30),
            'outbound_cid' => trim($this->post('outbound_cid','')),
            'notes' => trim($this->post('notes','')),
            'enabled' => $this->post('enabled',0) ? 1 : 0,
        ];
    }
    private function defaults(): array
    {
        return ['name'=>'','trunk_type'=>'provider','type'=>'pjsip','host'=>'','port'=>5060,'username'=>'',
                'password'=>'','auth_type'=>'userpass','context'=>'from-trunk',
                'codecs'=>'ulaw,alaw,g722','max_channels'=>30,'outbound_cid'=>'','notes'=>'','enabled'=>1];
    }
    private function applyAndReload(): void
    {
        try {
            $cfg = AsteriskConfig::fromSettings();
            $cfg->generatePJSIP();
            $cfg->reload('res_pjsip');
        } catch (Exception $e) { logError('Trunk reload: '.$e->getMessage()); }
    }
}
