<?php
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskConfig.php';

class DialplanController extends BaseController
{
    public function index(): void
    {
        $contexts = Database::fetchAll("SELECT c.*, COUNT(e.id) as entry_count FROM dialplan_contexts c LEFT JOIN dialplan_entries e ON c.id=e.context_id GROUP BY c.id ORDER BY c.name");
        $this->view('dialplan.index', ['title' => t('dialplan'), 'contexts' => $contexts]);
    }

    public function context(): void
    {
        $id      = $this->id();
        $context = Database::fetchOne("SELECT * FROM dialplan_contexts WHERE id=?", [$id]);
        if (!$context) redirect('?page=dialplan');
        $entries = Database::fetchAll("SELECT * FROM dialplan_entries WHERE context_id=? ORDER BY extension, priority", [$id]);
        $this->view('dialplan.context', [
            'title'   => t('dialplan') . ': ' . $context['name'],
            'context' => $context,
            'entries' => $entries,
        ]);
    }

    public function add_context(): void
    {
        $this->view('dialplan.context_form', [
            'title'   => 'Add Context',
            'context' => ['name' => '', 'description' => ''],
            'action'  => 'add_context',
        ]);
    }

    public function post_add_context(): void
    {
        $this->requireOperator();
        Database::insert('dialplan_contexts', [
            'name'        => trim($this->post('name', '')),
            'description' => trim($this->post('description', '')),
        ]);
        $this->flash('success', t('saved'));
        redirect('?page=dialplan');
    }

    public function delete_context(): void
    {
        $this->requireOperator();
        Database::delete('dialplan_contexts', 'id=?', [$this->id()]);
        $this->flash('success', t('deleted'));
        redirect('?page=dialplan');
    }

    public function add_entry(): void
    {
        $contextId = (int)$this->get('context_id', 0);
        $this->view('dialplan.entry_form', [
            'title'     => 'Add Dialplan Entry',
            'entry'     => ['context_id'=>$contextId,'extension'=>'','priority'=>1,'application'=>'','app_data'=>'','notes'=>''],
            'contextId' => $contextId,
            'action'    => 'add_entry',
        ]);
    }

    public function post_add_entry(): void
    {
        $this->requireOperator();
        $contextId = (int)$this->post('context_id', 0);
        Database::insert('dialplan_entries', [
            'context_id'  => $contextId,
            'extension'   => trim($this->post('extension', '')),
            'priority'    => (int)$this->post('priority', 1),
            'application' => trim($this->post('application', '')),
            'app_data'    => trim($this->post('app_data', '')),
            'notes'       => trim($this->post('notes', '')),
        ]);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=dialplan&action=context&id=' . $contextId);
    }

    public function delete_entry(): void
    {
        $this->requireOperator();
        $entry = Database::fetchOne("SELECT context_id FROM dialplan_entries WHERE id=?", [$this->id()]);
        Database::delete('dialplan_entries', 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('deleted'));
        redirect('?page=dialplan&action=context&id=' . ($entry['context_id'] ?? 0));
    }

    public function reload_all(): void
    {
        $this->requireOperator();
        try {
            $cfg = AsteriskConfig::fromSettings();
            $cfg->generatePJSIP();
            $cfg->generateQueues();
            $cfg->generateDialplan();
            $ok = $cfg->reload();
            $this->flash($ok ? 'success' : 'danger', t($ok ? 'reload_success' : 'reload_failed'));
        } catch (Exception $e) {
            $this->flash('danger', t('reload_failed') . ': ' . $e->getMessage());
        }
        redirect('?page=dialplan');
    }

    private function applyAndReload(): void
    {
        try {
            $cfg = AsteriskConfig::fromSettings();
            $cfg->generateDialplan();
            $cfg->reload('pbx_config');
        } catch (Exception $e) { logError('Dialplan reload: ' . $e->getMessage()); }
    }
}
