<?php
// ============================================================
// OutboundRoutesController
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskConfig.php';

class OutboundRoutesController extends BaseController
{
    public function index(): void
    {
        $routes = Database::fetchAll("SELECT * FROM outbound_routes ORDER BY priority DESC, name");
        foreach ($routes as &$r) {
            $r['patterns'] = Database::fetchAll("SELECT * FROM outbound_route_dial_patterns WHERE route_id=?", [$r['id']]);
            $r['trunks']   = Database::fetchAll(
                "SELECT t.name FROM trunks t JOIN outbound_route_trunks ort ON t.id=ort.trunk_id WHERE ort.route_id=? ORDER BY ort.order_num",
                [$r['id']]
            );
        }
        $this->view('routes.outbound', ['title' => t('outbound_routes'), 'routes' => $routes]);
    }

    public function add(): void
    {
        $trunks = Database::fetchAll("SELECT * FROM trunks WHERE enabled=1 ORDER BY name");
        $this->view('routes.outbound_form', [
            'title'    => t('add_new') . ' ' . t('outbound_routes'),
            'route'    => ['name'=>'','priority'=>0,'emergency'=>0,'notes'=>'','enabled'=>1],
            'patterns' => [],
            'routeTrunks' => [],
            'trunks'   => $trunks,
            'action'   => 'add',
        ]);
    }

    public function post_add(): void
    {
        $this->requireOperator();
        $id = Database::insert('outbound_routes', [
            'name'      => trim($this->post('name', '')),
            'priority'  => (int)$this->post('priority', 0),
            'emergency' => $this->post('emergency', 0) ? 1 : 0,
            'notes'     => trim($this->post('notes', '')),
            'enabled'   => $this->post('enabled', 0) ? 1 : 0,
        ]);
        $this->savePatterns($id);
        $this->saveTrunks($id);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=outbound_routes');
    }

    public function edit(): void
    {
        $id    = $this->id();
        $route = Database::fetchOne("SELECT * FROM outbound_routes WHERE id=?", [$id]);
        if (!$route) redirect('?page=outbound_routes');
        $patterns    = Database::fetchAll("SELECT * FROM outbound_route_dial_patterns WHERE route_id=?", [$id]);
        $routeTrunks = Database::fetchAll("SELECT trunk_id FROM outbound_route_trunks WHERE route_id=? ORDER BY order_num", [$id]);
        $trunks      = Database::fetchAll("SELECT * FROM trunks WHERE enabled=1 ORDER BY name");
        $this->view('routes.outbound_form', [
            'title'      => t('edit') . ' ' . t('outbound_routes'),
            'route'      => $route,
            'patterns'   => $patterns,
            'routeTrunks'=> array_column($routeTrunks, 'trunk_id'),
            'trunks'     => $trunks,
            'action'     => 'edit',
        ]);
    }

    public function post_edit(): void
    {
        $this->requireOperator();
        $id = $this->id();
        Database::update('outbound_routes', [
            'name'      => trim($this->post('name', '')),
            'priority'  => (int)$this->post('priority', 0),
            'emergency' => $this->post('emergency', 0) ? 1 : 0,
            'notes'     => trim($this->post('notes', '')),
            'enabled'   => $this->post('enabled', 0) ? 1 : 0,
        ], 'id=?', [$id]);
        Database::delete('outbound_route_dial_patterns', 'route_id=?', [$id]);
        Database::delete('outbound_route_trunks', 'route_id=?', [$id]);
        $this->savePatterns($id);
        $this->saveTrunks($id);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=outbound_routes');
    }

    public function delete(): void
    {
        $this->requireOperator();
        Database::delete('outbound_routes', 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('deleted'));
        redirect('?page=outbound_routes');
    }

    private function savePatterns(int $routeId): void
    {
        $patterns = $this->post('patterns', []);
        $prepends = $this->post('prepends', []);
        $prefixes = $this->post('prefixes', []);
        foreach ($patterns as $i => $pattern) {
            if (empty($pattern)) continue;
            Database::insert('outbound_route_dial_patterns', [
                'route_id'      => $routeId,
                'match_pattern' => trim($pattern),
                'prepend'       => trim($prepends[$i] ?? ''),
                'prefix'        => trim($prefixes[$i] ?? ''),
            ]);
        }
    }

    private function saveTrunks(int $routeId): void
    {
        $trunkIds = $this->post('trunk_ids', []);
        foreach ($trunkIds as $order => $trunkId) {
            if (empty($trunkId)) continue;
            Database::insert('outbound_route_trunks', [
                'route_id'  => $routeId,
                'trunk_id'  => (int)$trunkId,
                'order_num' => $order,
            ]);
        }
    }

    private function applyAndReload(): void
    {
        try {
            $cfg = AsteriskConfig::fromSettings();
            $cfg->generateDialplan();
            $cfg->reload('pbx_config');
        } catch (Exception $e) { logError('OutboundRoute reload: ' . $e->getMessage()); }
    }
}
