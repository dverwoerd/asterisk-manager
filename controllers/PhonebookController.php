<?php
require_once APP_ROOT . '/controllers/BaseController.php';

class PhonebookController extends BaseController
{
    public function index(): void
    {
        $groups = Database::fetchAll(
            "SELECT pg.*, COUNT(pc.id) as contact_count
             FROM phonebook_groups pg
             LEFT JOIN phonebook_contacts pc ON pg.id = pc.group_id
             GROUP BY pg.id
             ORDER BY pg.name"
        );
        $this->view('phonebook.index', [
            'title'  => 'Company Adresboek',
            'groups' => $groups,
        ]);
    }

    public function add_group(): void
    {
        $this->view('phonebook.group_form', [
            'title'  => 'Nieuwe Groep',
            'group'  => ['name' => '', 'description' => ''],
            'action' => 'add_group',
        ]);
    }

    public function post_add_group(): void
    {
        $this->requireOperator();
        Database::insert('phonebook_groups', [
            'name'        => trim($this->post('name', '')),
            'description' => trim($this->post('description', '')),
        ]);
        $this->flash('success', 'Groep aangemaakt.');
        redirect('?page=phonebook');
    }

    public function edit_group(): void
    {
        $group = Database::fetchOne("SELECT * FROM phonebook_groups WHERE id=?", [$this->id()]);
        if (!$group) redirect('?page=phonebook');
        $this->view('phonebook.group_form', [
            'title'  => 'Groep bewerken',
            'group'  => $group,
            'action' => 'edit_group',
        ]);
    }

    public function post_edit_group(): void
    {
        $this->requireOperator();
        Database::update('phonebook_groups', [
            'name'        => trim($this->post('name', '')),
            'description' => trim($this->post('description', '')),
        ], 'id=?', [$this->id()]);
        $this->flash('success', 'Groep bijgewerkt.');
        redirect('?page=phonebook');
    }

    public function delete_group(): void
    {
        $this->requireOperator();
        Database::delete('phonebook_groups', 'id=?', [$this->id()]);
        $this->flash('success', 'Groep verwijderd.');
        redirect('?page=phonebook');
    }

    public function contacts(): void
    {
        $group = Database::fetchOne("SELECT * FROM phonebook_groups WHERE id=?", [$this->id()]);
        if (!$group) redirect('?page=phonebook');

        $contacts = Database::fetchAll(
            "SELECT * FROM phonebook_contacts WHERE group_id=? ORDER BY last_name, first_name",
            [$this->id()]
        );
        $this->view('phonebook.contacts', [
            'title'    => 'Contacten — ' . $group['name'],
            'group'    => $group,
            'contacts' => $contacts,
        ]);
    }

    public function add_contact(): void
    {
        $group = Database::fetchOne("SELECT * FROM phonebook_groups WHERE id=?", [$this->get('group_id', 0)]);
        if (!$group) redirect('?page=phonebook');
        $this->view('phonebook.contact_form', [
            'title'   => 'Nieuw Contact',
            'group'   => $group,
            'contact' => $this->defaults(),
            'action'  => 'add_contact',
        ]);
    }

    public function post_add_contact(): void
    {
        $this->requireOperator();
        $groupId = (int)$this->post('group_id', 0);
        Database::insert('phonebook_contacts', $this->collectContactData($groupId));
        $this->flash('success', 'Contact toegevoegd.');
        redirect('?page=phonebook&action=contacts&id=' . $groupId);
    }

    public function edit_contact(): void
    {
        $contact = Database::fetchOne("SELECT * FROM phonebook_contacts WHERE id=?", [$this->id()]);
        if (!$contact) redirect('?page=phonebook');
        $group = Database::fetchOne("SELECT * FROM phonebook_groups WHERE id=?", [$contact['group_id']]);
        $this->view('phonebook.contact_form', [
            'title'   => 'Contact bewerken',
            'group'   => $group,
            'contact' => $contact,
            'action'  => 'edit_contact',
        ]);
    }

    public function post_edit_contact(): void
    {
        $this->requireOperator();
        $contact = Database::fetchOne("SELECT group_id FROM phonebook_contacts WHERE id=?", [$this->id()]);
        Database::update('phonebook_contacts', $this->collectContactData($contact['group_id']), 'id=?', [$this->id()]);
        $this->flash('success', 'Contact bijgewerkt.');
        redirect('?page=phonebook&action=contacts&id=' . $contact['group_id']);
    }

    public function delete_contact(): void
    {
        $this->requireOperator();
        $contact = Database::fetchOne("SELECT group_id FROM phonebook_contacts WHERE id=?", [$this->id()]);
        Database::delete('phonebook_contacts', 'id=?', [$this->id()]);
        $this->flash('success', 'Contact verwijderd.');
        redirect('?page=phonebook&action=contacts&id=' . ($contact['group_id'] ?? 0));
    }

    // Genereer Yealink XML telefoonboek
    public function xml(): void
    {
        $groupId  = (int)$this->get('group_id', 0);
        $contacts = Database::fetchAll(
            "SELECT * FROM phonebook_contacts WHERE group_id=? ORDER BY last_name, first_name",
            [$groupId]
        );
        $group = Database::fetchOne("SELECT name FROM phonebook_groups WHERE id=?", [$groupId]);

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="phonebook.xml"');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<YealinkIPPhoneDirectory>' . "\n";

        foreach ($contacts as $c) {
            $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            if ($c['company'] && !$name) $name = $c['company'];
            if (!$name) continue;

            echo '  <DirectoryEntry>' . "\n";
            echo '    <Name>' . htmlspecialchars($name) . '</Name>' . "\n";
            if ($c['phone_mobile']) {
                echo '    <Telephone label="Mobiel">' . htmlspecialchars($c['phone_mobile']) . '</Telephone>' . "\n";
            }
            if ($c['phone_work']) {
                echo '    <Telephone label="Werk">' . htmlspecialchars($c['phone_work']) . '</Telephone>' . "\n";
            }
            if ($c['phone_home']) {
                echo '    <Telephone label="Thuis">' . htmlspecialchars($c['phone_home']) . '</Telephone>' . "\n";
            }
            echo '  </DirectoryEntry>' . "\n";
        }

        echo '</YealinkIPPhoneDirectory>' . "\n";
        exit;
    }

    // Import contacten vanuit CSV
    public function import(): void
    {
        $group = Database::fetchOne("SELECT * FROM phonebook_groups WHERE id=?", [$this->get('group_id', 0)]);
        if (!$group) redirect('?page=phonebook');
        $this->view('phonebook.import', [
            'title' => 'Importeer Contacten',
            'group' => $group,
        ]);
    }

    public function post_import(): void
    {
        $this->requireOperator();
        $groupId = (int)$this->post('group_id', 0);

        if (empty($_FILES['csv_file']['tmp_name'])) {
            $this->flash('danger', 'Geen bestand geselecteerd.');
            redirect('?page=phonebook&action=import&group_id=' . $groupId);
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $count  = 0;
        $first  = true;

        while (($row = fgetcsv($handle, 1024, ';')) !== false) {
            if ($first) { $first = false; continue; } // skip header
            if (count($row) < 2) continue;

            Database::insert('phonebook_contacts', [
                'group_id'     => $groupId,
                'first_name'   => trim($row[0] ?? ''),
                'last_name'    => trim($row[1] ?? ''),
                'company'      => trim($row[2] ?? ''),
                'phone_mobile' => trim($row[3] ?? ''),
                'phone_work'   => trim($row[4] ?? ''),
                'phone_home'   => trim($row[5] ?? ''),
                'email'        => trim($row[6] ?? ''),
            ]);
            $count++;
        }
        fclose($handle);

        $this->flash('success', "$count contacten geïmporteerd.");
        redirect('?page=phonebook&action=contacts&id=' . $groupId);
    }

    private function collectContactData(int $groupId): array
    {
        return [
            'group_id'     => $groupId,
            'first_name'   => trim($this->post('first_name', '')),
            'last_name'    => trim($this->post('last_name', '')),
            'company'      => trim($this->post('company', '')),
            'phone_mobile' => trim($this->post('phone_mobile', '')),
            'phone_work'   => trim($this->post('phone_work', '')),
            'phone_home'   => trim($this->post('phone_home', '')),
            'email'        => trim($this->post('email', '')),
            'notes'        => trim($this->post('notes', '')),
        ];
    }

    private function defaults(): array
    {
        return [
            'first_name'   => '',
            'last_name'    => '',
            'company'      => '',
            'phone_mobile' => '',
            'phone_work'   => '',
            'phone_home'   => '',
            'email'        => '',
            'notes'        => '',
        ];
    }
}
