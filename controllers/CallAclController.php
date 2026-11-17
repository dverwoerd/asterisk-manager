<?php
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskConfig.php';

/** Bel-rechten: groepen van toestellen die alleen onderling mogen bellen. */
class CallAclController extends BaseController
{
    public function index(): void
    {
        $groups = Database::fetchAll(
            "SELECT g.*, GROUP_CONCAT(m.extension ORDER BY m.extension SEPARATOR ', ') AS members
             FROM call_acl_groups g LEFT JOIN call_acl_members m ON m.group_id=g.id
             GROUP BY g.id ORDER BY g.name");
        $this->view('call_acl.index', ['title' => t('nav_call_acl'), 'groups' => $groups]);
    }

    public function add(): void
    {
        $this->view('call_acl.form', [
            'title' => t('add_new') . ' ' . t('nav_call_acl'), 'group' => $this->defaults(),
            'members' => [], 'extensions' => $this->extensions(), 'action' => 'add']);
    }

    public function post_add(): void
    {
        $this->requireOperator();
        $id = Database::insert('call_acl_groups', $this->collectFormData());
        $this->saveMembers($id);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=call_acl');
    }

    public function edit(): void
    {
        $id    = $this->id();
        $group = Database::fetchOne("SELECT * FROM call_acl_groups WHERE id=?", [$id]);
        if (!$group) redirect('?page=call_acl');
        $members = array_column(Database::fetchAll(
            "SELECT extension FROM call_acl_members WHERE group_id=?", [$id]), 'extension');
        $this->view('call_acl.form', [
            'title' => t('edit') . ' ' . t('nav_call_acl'), 'group' => $group,
            'members' => $members, 'extensions' => $this->extensions(), 'action' => 'edit']);
    }

    public function post_edit(): void
    {
        $this->requireOperator();
        $id = $this->id();
        Database::update('call_acl_groups', $this->collectFormData(), 'id=?', [$id]);
        Database::delete('call_acl_members', 'group_id=?', [$id]);
        $this->saveMembers($id);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=call_acl');
    }

    public function delete(): void
    {
        $this->requireOperator();
        Database::delete('call_acl_groups', 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('deleted'));
        redirect('?page=call_acl');
    }

    private function extensions(): array
    {
        return Database::fetchAll("SELECT extension, full_name FROM extensions WHERE enabled=1 ORDER BY extension");
    }

    private function collectFormData(): array
    {
        $extras = preg_split('/[\s,;]+/', (string)$this->post('allowed_extras', ''), -1, PREG_SPLIT_NO_EMPTY);
        $extras = array_filter($extras, fn($e) => preg_match('/^[0-9*#]+$/', $e));
        return [
            'name'           => trim($this->post('name', '')),
            'allowed_extras' => implode(',', $extras),
            'notes'          => trim($this->post('notes', '')),
            'enabled'        => $this->post('enabled', 0) ? 1 : 0,
        ];
    }

    private function saveMembers(int $groupId): void
    {
        foreach (array_unique((array)$this->post('members', [])) as $ext) {
            if ($ext === '' || !preg_match('/^[0-9]+$/', $ext)) continue;
            Database::insert('call_acl_members', ['group_id' => $groupId, 'extension' => $ext]);
        }
    }

    private function defaults(): array
    {
        return ['name' => '', 'allowed_extras' => '', 'notes' => '', 'enabled' => 1];
    }

    private function applyAndReload(): void
    {
        try {
            $cfg = AsteriskConfig::fromSettings();
            $cfg->generateDialplan();
            $cfg->reload('pbx_config');
        } catch (Exception $e) { logError('CallAcl reload: ' . $e->getMessage()); }
    }
}
