<?php
// ============================================================
// CustomersController
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';

class CustomersController extends BaseController
{
    public function index(): void
    {
        $customers = Database::fetchAll("SELECT c.*, rp.name as rate_plan_name FROM customers c LEFT JOIN rate_plans rp ON c.rate_plan_id=rp.id WHERE c.active=1 ORDER BY c.company_name, c.contact_name");
        $this->view('customers.index', ['title' => t('customers'), 'customers' => $customers]);
    }

    public function add(): void
    {
        $ratePlans = Database::fetchAll("SELECT id, name FROM rate_plans WHERE active=1 ORDER BY name");
        $this->view('customers.form', ['title' => t('add_new').' '.t('customer'), 'customer' => $this->defaults(), 'ratePlans' => $ratePlans, 'action' => 'add']);
    }

    public function post_add(): void
    {
        $this->requireOperator();
        Database::insert('customers', $this->collectFormData());
        $this->flash('success', t('saved'));
        redirect('?page=customers');
    }

    public function edit(): void
    {
        $customer  = Database::fetchOne("SELECT * FROM customers WHERE id=?", [$this->id()]);
        $ratePlans = Database::fetchAll("SELECT id, name FROM rate_plans WHERE active=1 ORDER BY name");
        $this->view('customers.form', ['title' => t('edit').' '.t('customer'), 'customer' => $customer, 'ratePlans' => $ratePlans, 'action' => 'edit']);
    }

    public function post_edit(): void
    {
        $this->requireOperator();
        Database::update('customers', $this->collectFormData(), 'id=?', [$this->id()]);
        $this->flash('success', t('saved'));
        redirect('?page=customers');
    }

    public function delete(): void
    {
        $this->requireOperator();
        Database::update('customers', ['active' => 0], 'id=?', [$this->id()]);
        $this->flash('success', t('deleted'));
        redirect('?page=customers');
    }

    private function collectFormData(): array
    {
        return [
            'company_name'       => trim($this->post('company_name', '')),
            'contact_name'       => trim($this->post('contact_name', '')),
            'email'              => trim($this->post('email', '')),
            'phone'              => trim($this->post('phone', '')),
            'address'            => trim($this->post('address', '')),
            'city'               => trim($this->post('city', '')),
            'postal_code'        => trim($this->post('postal_code', '')),
            'country'            => trim($this->post('country', 'Netherlands')),
            'vat_number'         => trim($this->post('vat_number', '')),
            'rate_plan_id'       => (int)$this->post('rate_plan_id', 1),
            'extensions_csv'     => trim($this->post('extensions_csv', '')),
            'notes'              => trim($this->post('notes', '')),
        ];
    }

    private function defaults(): array
    {
        return ['company_name'=>'','contact_name'=>'','email'=>'','phone'=>'',
                'address'=>'','city'=>'','postal_code'=>'','country'=>'Netherlands',
                'vat_number'=>'','rate_plan_id'=>1,'extensions_csv'=>'','notes'=>'','active'=>1];
    }
}
