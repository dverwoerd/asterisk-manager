<?php
require_once APP_ROOT . '/controllers/BaseController.php';

class InvoicesController extends BaseController
{
    public function index(): void
    {
        $status   = $this->get('status', '');
        $sql      = "SELECT i.*, c.company_name, c.contact_name FROM invoices i LEFT JOIN customers c ON i.customer_id=c.id";
        $params   = [];
        if ($status) { $sql .= " WHERE i.status=?"; $params[] = $status; }
        $sql .= " ORDER BY i.created_at DESC";
        $invoices = Database::fetchAll($sql, $params);
        $this->view('invoices.index', ['title' => t('invoices'), 'invoices' => $invoices, 'status' => $status]);
    }

    public function generate(): void
    {
        $customers = Database::fetchAll("SELECT id, company_name, contact_name, rate_plan_id, extensions_csv FROM customers WHERE active=1 ORDER BY company_name, contact_name");
        $this->view('invoices.generate', [
            'title'     => t('generate_invoice'),
            'customers' => $customers,
            'dateFrom'  => date('Y-m-01'),
            'dateTo'    => date('Y-m-t'),
        ]);
    }

    public function post_generate(): void
    {
        $this->requireOperator();
        $customerId = (int)$this->post('customer_id', 0);
        $dateFrom   = $this->post('date_from', date('Y-m-01'));
        $dateTo     = $this->post('date_to', date('Y-m-t'));
        $taxRate    = (float)$this->post('tax_rate', getSetting('default_tax_rate', 21));

        $customer = Database::fetchOne("SELECT * FROM customers WHERE id=?", [$customerId]);
        if (!$customer) {
            $this->flash('danger', 'Customer not found.');
            redirect('?page=invoices&action=generate');
        }

        $extensions = array_filter(array_map('trim', explode(',', $customer['extensions_csv'] ?? '')));
        if (empty($extensions)) {
            $this->flash('danger', 'Customer has no extensions assigned.');
            redirect('?page=invoices&action=generate');
        }

        // Zoek ook de callerid nummers van de extensies (DID nummers)
        // Want in de CDR staat src als DID nummer (bijv 31318478226) niet als extensienummer (101)
        $extSearchTerms = $extensions;
        foreach ($extensions as $ext) {
            $extRow = Database::fetchOne(
                "SELECT callerid_number FROM extensions WHERE extension=? AND enabled=1",
                [$ext]
            );
            if (!empty($extRow['callerid_number'])) {
                // Voeg DID nummer toe zonder landcode varianten
                $cid = preg_replace('/\D/', '', $extRow['callerid_number']);
                $extSearchTerms[] = $cid;
                // Ook zonder landcode prefix (bijv 0318478226 naast 31318478226)
                if (str_starts_with($cid, '31')) {
                    $extSearchTerms[] = '0' . substr($cid, 2);
                }
            }
        }
        $extSearchTerms = array_unique(array_filter($extSearchTerms));

        $placeholders = implode(',', array_fill(0, count($extSearchTerms), '?'));
        $cdrRecords   = Database::fetchAll(
            "SELECT * FROM cdr_records
             WHERE src IN ($placeholders)
             AND DATE(calldate) BETWEEN ? AND ?
             AND disposition='ANSWERED'
             AND billsec > 0
             AND invoiced = 0
             AND dcontext = 'from-internal'
             AND dst REGEXP '^[0-9]{4,}$'
             ORDER BY calldate",
            [...$extSearchTerms, $dateFrom, $dateTo]
        );

        if (empty($cdrRecords)) {
            $this->flash('danger', 'No billable CDR records found for this period.');
            redirect('?page=invoices&action=generate');
        }

        // Group by destination
        $groups = [];
        foreach ($cdrRecords as $cdr) {
            $dest = $cdr['destination_name'] ?? 'Unknown';
            if (!isset($groups[$dest])) {
                $groups[$dest] = ['calls' => 0, 'seconds' => 0, 'cost' => 0, 'ids' => []];
            }
            $groups[$dest]['calls']++;
            $groups[$dest]['seconds'] += $cdr['billsec'];
            $groups[$dest]['cost']    += (float)($cdr['cost'] ?? 0);
            $groups[$dest]['ids'][]   = $cdr['id'];
        }

        $subtotal = array_sum(array_column($groups, 'cost'));
        $taxAmt   = round($subtotal * $taxRate / 100, 2);
        $total    = $subtotal + $taxAmt;
        $dueDate  = date('Y-m-d', strtotime('+30 days'));

        $invoiceId = Database::insert('invoices', [
            'invoice_number' => generateInvoiceNumber(),
            'customer_id'    => $customerId,
            'period_start'   => $dateFrom,
            'period_end'     => $dateTo,
            'issue_date'     => date('Y-m-d'),
            'due_date'       => $dueDate,
            'subtotal'       => round($subtotal, 2),
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmt,
            'total'          => round($total, 2),
            'currency'       => getSetting('default_currency', 'EUR'),
            'status'         => 'draft',
        ]);

        foreach ($groups as $dest => $grp) {
            $minutes = round($grp['seconds'] / 60, 2);
            Database::insert('invoice_items', [
                'invoice_id'  => $invoiceId,
                'description' => "Calls to $dest (" . $grp['calls'] . " calls)",
                'quantity'    => $minutes,
                'unit'        => 'minutes',
                'unit_price'  => $minutes > 0 ? round($grp['cost'] / $minutes, 4) : 0,
                'total'       => round($grp['cost'], 2),
                'cdr_ids'     => json_encode($grp['ids']),
            ]);
        }

        $allIds = array_merge(...array_column($groups, 'ids'));
        if ($allIds) {
            $ph = implode(',', array_fill(0, count($allIds), '?'));
            Database::query("UPDATE cdr_records SET invoiced=1, invoice_id=? WHERE id IN ($ph)", [$invoiceId, ...$allIds]);
        }

        $this->flash('success', t('invoice_generated'));
        redirect("?page=invoices&action=detail&id=$invoiceId");
    }

    // Renamed from view() to detail() to avoid conflict with BaseController::view()
    public function detail(): void
    {
        $id      = $this->id();
        $invoice = Database::fetchOne(
            "SELECT i.*, c.company_name, c.contact_name, c.address, c.vat_number, c.email
             FROM invoices i LEFT JOIN customers c ON i.customer_id=c.id WHERE i.id=?",
            [$id]
        );
        if (!$invoice) redirect('?page=invoices');
        $items   = Database::fetchAll("SELECT * FROM invoice_items WHERE invoice_id=?", [$id]);
        $company = [
            'name'    => getSetting('company_name'),
            'address' => getSetting('company_address'),
            'vat'     => getSetting('company_vat'),
            'email'   => getSetting('company_email'),
            'phone'   => getSetting('company_phone'),
        ];
        $this->view('invoices.view', [
            'title'   => t('invoice') . ' ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'items'   => $items,
            'company' => $company,
        ]);
    }

    public function update_status(): void
    {
        $this->requireOperator();
        $id     = $this->id();
        $status = $this->post('status', 'draft');
        Database::update('invoices', ['status' => $status], 'id=?', [$id]);
        $this->flash('success', t('saved'));
        redirect("?page=invoices&action=detail&id=$id");
    }

    public function delete(): void
    {
        $this->requireAdmin();
        $invoice = Database::fetchOne("SELECT id FROM invoices WHERE id=?", [$this->id()]);
        if ($invoice) {
            Database::query("UPDATE cdr_records SET invoiced=0, invoice_id=NULL WHERE invoice_id=?", [$this->id()]);
            Database::delete('invoices', 'id=?', [$this->id()]);
        }
        $this->flash('success', t('deleted'));
        redirect('?page=invoices');
    }

    public function print(): void
    {
        $id      = $this->id();
        $invoice = Database::fetchOne(
            "SELECT i.*, c.company_name, c.contact_name, c.address, c.vat_number, c.email
             FROM invoices i LEFT JOIN customers c ON i.customer_id=c.id WHERE i.id=?",
            [$id]
        );
        $items   = Database::fetchAll("SELECT * FROM invoice_items WHERE invoice_id=?", [$id]);
        $company = [
            'name'    => getSetting('company_name'),
            'address' => getSetting('company_address'),
            'vat'     => getSetting('company_vat'),
            'email'   => getSetting('company_email'),
            'phone'   => getSetting('company_phone'),
        ];
        require APP_ROOT . '/views/invoices/print.php';
        exit;
    }

    public function pdf(): void
    {
        $id      = $this->id();
        $invoice = Database::fetchOne(
            "SELECT i.*, c.company_name, c.contact_name, c.address, c.vat_number, c.email
             FROM invoices i LEFT JOIN customers c ON i.customer_id=c.id WHERE i.id=?",
            [$id]
        );
        if (!$invoice) redirect('?page=invoices');

        $items   = Database::fetchAll("SELECT * FROM invoice_items WHERE invoice_id=?", [$id]);
        $company = [
            'name'    => getSetting('company_name'),
            'address' => getSetting('company_address'),
            'vat'     => getSetting('company_vat'),
            'email'   => getSetting('company_email'),
            'phone'   => getSetting('company_phone'),
        ];

        try {
            require_once APP_ROOT . '/includes/InvoicePDF.php';
            InvoicePDF::download($invoice, $items, $company);
        } catch (Exception $e) {
            $this->flash('danger', 'PDF genereren mislukt: ' . $e->getMessage());
            redirect('?page=invoices&action=detail&id=' . $id);
        }
    }
}
