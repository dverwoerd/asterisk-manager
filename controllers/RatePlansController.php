<?php
require_once APP_ROOT . '/controllers/BaseController.php';

class RatePlansController extends BaseController
{
    public function index(): void
    {
        $plans = Database::fetchAll("SELECT rp.*, COUNT(rpr.id) as rate_count FROM rate_plans rp LEFT JOIN rate_plan_rates rpr ON rp.id=rpr.plan_id GROUP BY rp.id ORDER BY rp.name");
        $this->view('rate_plans.index', ['title' => t('rate_plans'), 'plans' => $plans]);
    }

    public function view_plan(): void
    {
        $id   = $this->id();
        $plan = Database::fetchOne("SELECT * FROM rate_plans WHERE id=?", [$id]);
        if (!$plan) redirect('?page=rate_plans');
        $search = $this->get('search', '');
        $sql    = "SELECT * FROM rate_plan_rates WHERE plan_id=?";
        $params = [$id];
        if ($search) { $sql .= " AND (destination_name LIKE ? OR prefix LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $sql .= " ORDER BY LENGTH(prefix) DESC, destination_name";
        $rates  = Database::fetchAll($sql, $params);
        $this->view('rate_plans.view', [
            'title'  => t('rate_plan') . ': ' . $plan['name'],
            'plan'   => $plan,
            'rates'  => $rates,
            'search' => $search,
        ]);
    }

    public function add(): void
    {
        $this->view('rate_plans.form', [
            'title'  => t('add_new') . ' ' . t('rate_plan'),
            'plan'   => ['name'=>'','description'=>'','currency'=>'EUR','billing_increment'=>60,'minimum_duration'=>0,'connection_fee'=>0,'active'=>1],
            'action' => 'add',
        ]);
    }

    public function post_add(): void
    {
        $this->requireOperator();
        Database::insert('rate_plans', $this->collectPlanData());
        $this->flash('success', t('saved'));
        redirect('?page=rate_plans');
    }

    public function edit(): void
    {
        $plan = Database::fetchOne("SELECT * FROM rate_plans WHERE id=?", [$this->id()]);
        $this->view('rate_plans.form', ['title' => t('edit').' '.t('rate_plan'), 'plan' => $plan, 'action' => 'edit']);
    }

    public function post_edit(): void
    {
        $this->requireOperator();
        Database::update('rate_plans', $this->collectPlanData(), 'id=?', [$this->id()]);
        $this->flash('success', t('saved'));
        redirect('?page=rate_plans');
    }

    public function delete(): void
    {
        $this->requireAdmin();
        Database::delete('rate_plans', 'id=?', [$this->id()]);
        $this->flash('success', t('deleted'));
        redirect('?page=rate_plans');
    }

    // -- Rates (individual entries within a plan) --

    public function add_rate(): void
    {
        $planId = (int)$this->get('plan_id', 0);
        $this->view('rate_plans.rate_form', [
            'title'  => 'Add Rate',
            'rate'   => ['plan_id'=>$planId,'destination_name'=>'','prefix'=>'','rate_per_minute'=>0,'connection_fee'=>0,'billing_increment'=>60,'time_start'=>'00:00:00','time_end'=>'23:59:59','days_of_week'=>'1234567','notes'=>''],
            'planId' => $planId,
            'action' => 'add_rate',
        ]);
    }

    public function post_add_rate(): void
    {
        $this->requireOperator();
        $planId = (int)$this->post('plan_id', 0);
        Database::insert('rate_plan_rates', $this->collectRateData($planId));
        $this->flash('success', t('saved'));
        redirect("?page=rate_plans&action=view_plan&id=$planId");
    }

    public function edit_rate(): void
    {
        $rate = Database::fetchOne("SELECT * FROM rate_plan_rates WHERE id=?", [$this->id()]);
        $this->view('rate_plans.rate_form', [
            'title'  => 'Edit Rate',
            'rate'   => $rate,
            'planId' => $rate['plan_id'],
            'action' => 'edit_rate',
        ]);
    }

    public function post_edit_rate(): void
    {
        $this->requireOperator();
        $rate   = Database::fetchOne("SELECT plan_id FROM rate_plan_rates WHERE id=?", [$this->id()]);
        $planId = $rate['plan_id'] ?? 0;
        Database::update('rate_plan_rates', $this->collectRateData($planId), 'id=?', [$this->id()]);
        $this->flash('success', t('saved'));
        redirect("?page=rate_plans&action=view_plan&id=$planId");
    }

    public function delete_rate(): void
    {
        $this->requireOperator();
        $rate = Database::fetchOne("SELECT plan_id FROM rate_plan_rates WHERE id=?", [$this->id()]);
        Database::delete('rate_plan_rates', 'id=?', [$this->id()]);
        $this->flash('success', t('deleted'));
        redirect("?page=rate_plans&action=view_plan&id=" . ($rate['plan_id'] ?? 0));
    }

    public function import_rates(): void
    {
        $this->requireOperator();
        $planId = (int)$this->post('plan_id', 0);
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('danger', 'Upload failed.');
            redirect("?page=rate_plans&action=view_plan&id=$planId");
        }
        $handle  = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header  = fgetcsv($handle); // skip header row
        $count   = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;
            Database::insert('rate_plan_rates', [
                'plan_id'          => $planId,
                'destination_name' => trim($row[0]),
                'prefix'           => trim($row[1]),
                'rate_per_minute'  => (float)str_replace(',', '.', $row[2]),
                'connection_fee'   => (float)str_replace(',', '.', $row[3] ?? 0),
                'billing_increment'=> (int)($row[4] ?? 60),
            ]);
            $count++;
        }
        fclose($handle);
        $this->flash('success', "Imported $count rates successfully.");
        redirect("?page=rate_plans&action=view_plan&id=$planId");
    }

    private function collectPlanData(): array
    {
        return [
            'name'              => trim($this->post('name', '')),
            'description'       => trim($this->post('description', '')),
            'currency'          => $this->post('currency', 'EUR'),
            'billing_increment' => (int)$this->post('billing_increment', 60),
            'minimum_duration'  => (int)$this->post('minimum_duration', 0),
            'connection_fee'    => (float)str_replace(',', '.', $this->post('connection_fee', 0)),
            'active'            => $this->post('active', 0) ? 1 : 0,
        ];
    }

    private function collectRateData(int $planId): array
    {
        return [
            'plan_id'           => $planId,
            'destination_name'  => trim($this->post('destination_name', '')),
            'prefix'            => trim($this->post('prefix', '')),
            'rate_per_minute'   => (float)str_replace(',', '.', $this->post('rate_per_minute', 0)),
            'connection_fee'    => (float)str_replace(',', '.', $this->post('connection_fee', 0)),
            'billing_increment' => (int)$this->post('billing_increment', 60),
            'time_start'        => $this->post('time_start', '00:00:00'),
            'time_end'          => $this->post('time_end', '23:59:59'),
            'days_of_week'      => $this->post('days_of_week', '1234567'),
            'notes'             => trim($this->post('notes', '')),
        ];
    }
}
