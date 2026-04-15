<?php
// ============================================================
// InboundRoutesController
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskConfig.php';

class InboundRoutesController extends BaseController
{
    public function index(): void
    {
        $routes = Database::fetchAll("SELECT * FROM inbound_routes ORDER BY priority DESC, did");
        $this->view('routes.inbound', ['title' => t('inbound_routes'), 'routes' => $routes]);
    }
    public function add(): void
    {
        $this->view('routes.inbound_form', ['title' => t('add_new').' '.t('inbound_routes'), 'route' => $this->defaults(), 'action' => 'add']);
    }
    public function post_add(): void
    {
        $this->requireOperator();
        Database::insert('inbound_routes', $this->collectFormData());
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=inbound_routes');
    }
    public function edit(): void
    {
        $route = Database::fetchOne("SELECT * FROM inbound_routes WHERE id=?", [$this->id()]);
        $this->view('routes.inbound_form', ['title' => t('edit').' '.t('inbound_routes'), 'route' => $route, 'action' => 'edit']);
    }
    public function post_edit(): void
    {
        $this->requireOperator();
        Database::update('inbound_routes', $this->collectFormData(), 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=inbound_routes');
    }
    public function delete(): void
    {
        $this->requireOperator();
        Database::delete('inbound_routes', 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('deleted'));
        redirect('?page=inbound_routes');
    }
    private function collectFormData(): array
    {
        return [
            'did' => trim($this->post('did','')),
            'cid_number' => trim($this->post('cid_number','')),
            'description' => trim($this->post('description','')),
            'destination_type' => $this->post('destination_type','extension'),
            'destination' => trim($this->post('destination','')),
            'priority' => (int)$this->post('priority',0),
            'notes' => trim($this->post('notes','')),
            'enabled' => $this->post('enabled',0) ? 1 : 0,
        ];
    }
    private function defaults(): array
    {
        return ['did'=>'','cid_number'=>'','description'=>'',
                'destination_type'=>'extension','destination'=>'','priority'=>0,'notes'=>'','enabled'=>1];
    }
    private function applyAndReload(): void
    {
        try { $cfg = AsteriskConfig::fromSettings(); $cfg->generateDialplan(); $cfg->reload('pbx_config'); }
        catch (Exception $e) { logError($e->getMessage()); }
    }
}
