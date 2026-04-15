<?php
// ============================================================
// QueuesController
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';
require_once APP_ROOT . '/includes/AsteriskConfig.php';

class QueuesController extends BaseController
{
    public function index(): void
    {
        $queues = Database::fetchAll("SELECT q.*, COUNT(qm.id) as member_count FROM queues q LEFT JOIN queue_members qm ON q.id=qm.queue_id GROUP BY q.id ORDER BY q.name");
        $this->view('queues.index', ['title' => t('queues'), 'queues' => $queues]);
    }

    public function add(): void
    {
        $extensions = Database::fetchAll("SELECT extension, full_name FROM extensions WHERE enabled=1 ORDER BY extension");
        $this->view('queues.form', ['title' => t('add_new').' '.t('queue'), 'queue' => $this->defaults(), 'members' => [], 'extensions' => $extensions, 'action' => 'add']);
    }

    public function post_add(): void
    {
        $this->requireOperator();
        $data = $this->collectFormData();
        $id = Database::insert('queues', $data);
        $this->saveMembers($id);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=queues');
    }

    public function edit(): void
    {
        $id = $this->id();
        $queue = Database::fetchOne("SELECT * FROM queues WHERE id=?", [$id]);
        if (!$queue) { redirect('?page=queues'); }
        $members = Database::fetchAll("SELECT * FROM queue_members WHERE queue_id=? ORDER BY penalty", [$id]);
        $extensions = Database::fetchAll("SELECT extension, full_name FROM extensions WHERE enabled=1 ORDER BY extension");
        $this->view('queues.form', ['title' => t('edit').' '.t('queue'), 'queue' => $queue, 'members' => $members, 'extensions' => $extensions, 'action' => 'edit']);
    }

    public function post_edit(): void
    {
        $this->requireOperator();
        $id = $this->id();
        $data = $this->collectFormData();
        Database::update('queues', $data, 'id=?', [$id]);
        Database::delete('queue_members', 'queue_id=?', [$id]);
        $this->saveMembers($id);
        $this->applyAndReload();
        $this->flash('success', t('saved'));
        redirect('?page=queues');
    }

    public function delete(): void
    {
        $this->requireOperator();
        Database::delete('queues', 'id=?', [$this->id()]);
        $this->applyAndReload();
        $this->flash('success', t('deleted'));
        redirect('?page=queues');
    }

    private function collectFormData(): array
    {
        return [
            'name' => trim($this->post('name', '')),
            'number' => trim($this->post('number', '')),
            'strategy' => $this->post('strategy', 'ringall'),
            'timeout' => (int)$this->post('timeout', 15),
            'wrapup_time' => (int)$this->post('wrapup_time', 5),
            'max_callers' => (int)$this->post('max_callers', 0),
            'announce_hold_time' => $this->post('announce_hold_time', 'yes'),
            'announce_position' => $this->post('announce_position', 'yes'),
            'announce_frequency' => (int)$this->post('announce_frequency', 30),
            'music_on_hold' => $this->post('music_on_hold', 'default'),
            'join_announcement' => trim($this->post('join_announcement', '')),
            'caller_id_prefix' => trim($this->post('caller_id_prefix', '')),
            'timeout_destination_type' => $this->post('timeout_destination_type', 'hangup'),
            'timeout_destination' => trim($this->post('timeout_destination', '')),
            'notes' => trim($this->post('notes', '')),
            'enabled' => $this->post('enabled', 0) ? 1 : 0,
        ];
    }

    private function saveMembers(int $queueId): void
    {
        $members = $this->post('members', []);
        foreach ($members as $i => $ext) {
            if (empty($ext)) continue;
            Database::insert('queue_members', [
                'queue_id' => $queueId,
                'extension' => $ext,
                'penalty' => (int)($this->post('penalties', [])[$i] ?? 0),
            ]);
        }
    }

    private function defaults(): array
    {
        return ['name'=>'','number'=>'','strategy'=>'ringall','timeout'=>15,'wrapup_time'=>5,
                'max_callers'=>0,'announce_hold_time'=>'yes','announce_position'=>'yes',
                'announce_frequency'=>30,'music_on_hold'=>'default','join_announcement'=>'',
                'caller_id_prefix'=>'','timeout_destination_type'=>'hangup',
                'timeout_destination'=>'','notes'=>'','enabled'=>1];
    }

    private function applyAndReload(): void
    {
        try {
            $cfg = AsteriskConfig::fromSettings();
            $cfg->generateQueues();
            $cfg->generateDialplan();
            $cfg->reload('app_queue');
        } catch (Exception $e) { logError('Queue reload: '.$e->getMessage()); }
    }
}
