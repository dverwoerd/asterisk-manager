<?php
// ============================================================
// CustomersController: klanten voor de gespreksspecificaties.
// Een klant is gekoppeld aan toestellen (extensions_csv); de gesprekken
// van die toestellen komen op de specificatie van de klant.
// ============================================================
require_once APP_ROOT . '/controllers/BaseController.php';

class CustomersController extends BaseController
{
    public function index(): void
    {
        $customers = Database::fetchAll("SELECT * FROM customers WHERE active=1 ORDER BY company_name, contact_name");
        $this->view('customers.index', ['title' => t('nav_customers'), 'customers' => $customers]);
    }

    public function add(): void
    {
        $this->view('customers.form', ['title' => t('add_new') . ' ' . t('customer'), 'customer' => $this->defaults(),
            'extensions' => $this->extensions(), 'action' => 'add']);
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
        $customer = Database::fetchOne("SELECT * FROM customers WHERE id=?", [$this->id()]);
        if (!$customer) redirect('?page=customers');
        $this->view('customers.form', ['title' => t('edit') . ' ' . t('customer'), 'customer' => $customer,
            'extensions' => $this->extensions(), 'action' => 'edit']);
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

    private function extensions(): array
    {
        return Database::fetchAll("SELECT extension, full_name FROM extensions WHERE enabled=1 ORDER BY extension");
    }

    private function collectFormData(): array
    {
        $exts = array_filter((array)$this->post('ext', []), fn($e) => preg_match('/^[0-9]+$/', (string)$e));
        // Extra (niet-geregistreerde) nummers, bv. een DID of oud toestel
        $extra = preg_split('/[\s,;]+/', (string)$this->post('extensions_extra', ''), -1, PREG_SPLIT_NO_EMPTY);
        $extra = array_filter($extra, fn($e) => preg_match('/^[0-9]+$/', $e));
        $all = array_values(array_unique(array_merge($exts, $extra)));
        sort($all, SORT_NATURAL);

        return [
            'company_name'   => trim($this->post('company_name', '')),
            'contact_name'   => trim($this->post('contact_name', '')),
            'email'          => trim($this->post('email', '')),
            'phone'          => trim($this->post('phone', '')),
            'address'        => trim($this->post('address', '')),
            'city'           => trim($this->post('city', '')),
            'postal_code'    => trim($this->post('postal_code', '')),
            'country'        => trim($this->post('country', 'Nederland')),
            'vat_number'     => trim($this->post('vat_number', '')),
            'extensions_csv' => implode(',', $all),
            'notes'          => trim($this->post('notes', '')),
        ];
    }

    private function defaults(): array
    {
        return ['company_name'=>'','contact_name'=>'','email'=>'','phone'=>'','address'=>'','city'=>'',
                'postal_code'=>'','country'=>'Nederland','vat_number'=>'','extensions_csv'=>'','notes'=>'','active'=>1];
    }
}
