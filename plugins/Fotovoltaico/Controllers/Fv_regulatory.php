<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;

/**
 * CRUD de perfis regulatórios.
 */
class Fv_regulatory extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_admin();
    }

    public function index()
    {
        return $this->template->rander('Fotovoltaico\\Views\\regulatory\\index');
    }

    public function list_data()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_regulatory_profiles');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(['data' => []]);
        }

        $rows = $db->table($table)->orderBy('id', 'DESC')->get()->getResult();
        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                $row->id,
                esc($row->name),
                esc($row->description ?? '-'),
                $row->is_active ? app_lang('yes') : app_lang('no'),
                modal_anchor(get_uri('fotovoltaico/regulatory_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [
                    'title' => app_lang('edit'),
                    'data-post-id' => $row->id,
                    'class' => 'btn btn-sm btn-outline-secondary'
                ])
            ];
        }

        return $this->response->setJSON(['data' => $data]);
    }

    public function modal_form()
    {
        $this->validate_submitted_data(['id' => 'numeric']);
        $id = (int)$this->request->getPost('id');

        $db = db_connect('default');
        $table = $db->prefixTable('fv_regulatory_profiles');
        $row = $id ? $db->table($table)->where('id', $id)->get()->getRow() : null;

        return $this->template->view('Fotovoltaico\\Views\\regulatory\\modal_form', ['item' => $row]);
    }

    public function save()
    {
        $this->validate_submitted_data(['id' => 'numeric', 'name' => 'required']);
        $id = (int)$this->request->getPost('id');

        $data = [
            'name' => trim((string)$this->request->getPost('name')),
            'description' => trim((string)$this->request->getPost('description')),
            'rules_json' => trim((string)$this->request->getPost('rules_json')),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db = db_connect('default');
        $table = $db->prefixTable('fv_regulatory_profiles');
        if ($id) {
            $db->table($table)->where('id', $id)->update($data);
        } else {
            $db->table($table)->insert($data);
            $id = $db->insertID();
        }

        return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved'), 'id' => $id]);
    }
}
