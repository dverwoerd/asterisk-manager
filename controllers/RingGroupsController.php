<?php
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskConfig.php';

class RingGroupsController extends BaseController
{
    public function index(): void
    {
        $groups = Database::fetchAll("SELECT rg.*, COUNT(rgm.id) as member_count FROM ring_groups rg LEFT JOIN ring_group_members rgm ON rg.id=rgm.ring_group_id GROUP BY rg.id ORDER BY rg.number");
        $this->view('ring_groups.index', ['title' => t('ring_groups'), 'groups' => $groups]);
    }

    public function add(): void
    {
        $extensions = Database::fetchAll("SELECT extension, full_name FROM extensions WHERE enabled=1 ORDER BY extension");
        $this->view('ring_groups.form', ['title' => t('add_new').' '.t('ring_group'), 'group' => $this->defaults(), 'members' => [], 'extensions' => $extensions, 'action' => 'add']);
    }

    public function post_add(): void
    {
        $this->requireOperator();
        $data = $this->collectFormData();
        $id = Database::insert('ring_groups', $data);
        $this->saveMembers($id);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=ring_groups');
    }

    public function edit(): void
    {
        $id = $this->id();
        $group = Database::fetchOne("SELECT * FROM ring_groups WHERE id=?", [$id]);
        if (!$group) redirect('?page=ring_groups');
        $members = Database::fetchAll("SELECT * FROM ring_group_members WHERE ring_group_id=? ORDER BY order_num", [$id]);
        $extensions = Database::fetchAll("SELECT extension, full_name FROM extensions WHERE enabled=1 ORDER BY extension");
        $this->view('ring_groups.form', ['title' => t('edit').' '.t('ring_group'), 'group' => $group, 'members' => $members, 'extensions' => $extensions, 'action' => 'edit']);
    }

    public function post_edit(): void
    {
        $this->requireOperator();
        $id = $this->id();
        $data = $this->collectFormData();
        Database::update('ring_groups', $data, 'id=?', [$id]);
        Database::delete('ring_group_members', 'ring_group_id=?', [$id]);
        $this->saveMembers($id);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=ring_groups');
    }

    public function delete(): void
    {
        $this->requireOperator();
        Database::delete('ring_groups', 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('deleted'));
        redirect('?page=ring_groups');
    }

    private function collectFormData(): array
    {
        return [
            'number' => trim($this->post('number', '')),
            'name' => trim($this->post('name', '')),
            'strategy' => $this->post('strategy', 'ringall'),
            'ring_time' => (int)$this->post('ring_time', 20),
            'destination_type' => $this->post('destination_type', 'hangup'),
            'destination' => trim($this->post('destination', '')),
            'caller_id_prefix' => trim($this->post('caller_id_prefix', '')),
            'notes' => trim($this->post('notes', '')),
            'enabled' => $this->post('enabled', 0) ? 1 : 0,
        ];
    }

    private function saveMembers(int $groupId): void
    {
        $members = $this->post('members', []);
        foreach ($members as $i => $ext) {
            if (empty($ext)) continue;
            Database::insert('ring_group_members', [
                'ring_group_id' => $groupId,
                'extension' => $ext,
                'ring_time' => (int)($this->post('ring_times', [])[$i] ?? 20),
                'order_num' => $i,
            ]);
        }
    }

    private function defaults(): array
    {
        return ['number'=>'','name'=>'','strategy'=>'ringall','ring_time'=>20,
                'destination_type'=>'hangup','destination'=>'','caller_id_prefix'=>'',
                'notes'=>'','enabled'=>1];
    }

    private function applyAndReload(): void
    {
        try {
            $cfg = AsteriskConfig::fromSettings();
            $cfg->generateDialplan();
            $cfg->reload('pbx_config');
        } catch (Exception $e) { logError('RingGroup reload: '.$e->getMessage()); }
    }
}
