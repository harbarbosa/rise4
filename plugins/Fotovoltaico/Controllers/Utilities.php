<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;

/**
 * Controller de distribuidoras (utilities).
 */
class Utilities extends Security_Controller
{
    /** @var \Fotovoltaico\Models\Fv_utilities_model */
    private $utilities_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->utilities_model = model('Fotovoltaico\\Models\\Fv_utilities_model');
    }

    /**
     * Tela de distribuidoras.
     */
    public function index()
    {
        return $this->template->rander('Fotovoltaico\\Views\\utilities\\index');
    }

    /**
     * Lista distribuidoras em JSON.
     */
    public function list_data()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_utilities');
        $rows = $db->table($table)
            ->select('id,name,uf,code')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->_make_row($row);
        }

        return $this->response->setJSON(array('data' => $data));
    }

    /**
     * Modal de criação/edição.
     */
    public function modal_form()
    {
        $this->validate_submitted_data(array('id' => 'numeric'));
        $id = (int)$this->request->getPost('id');
        $utility = $id ? $this->utilities_model->get_one($id) : null;

        return $this->template->view('Fotovoltaico\\Views\\utilities\\modal_form', array(
            'utility' => $utility
        ));
    }

    /**
     * Salva distribuidora.
     */
    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'name' => 'required'
        ));

        $id = (int)$this->request->getPost('id');
        $data = array(
            'name' => trim((string)$this->request->getPost('name')),
            'uf' => strtoupper(trim((string)$this->request->getPost('uf'))),
            'code' => trim((string)$this->request->getPost('code'))
        );

        $save_id = $this->utilities_model->ci_save($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $row_id = $id ? $id : $save_id;
        $row = $this->utilities_model->get_one($row_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $row_id,
            'message' => app_lang('record_saved')
        ));
    }

    /**
     * Exclui distribuidora.
     */
    public function delete()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));
        $id = (int)$this->request->getPost('id');

        $db = db_connect('default');
        $table = $db->prefixTable('fv_utilities');
        $deleted = $db->table($table)->delete(array('id' => $id));

        return $this->response->setJSON(array('success' => $deleted ? true : false, 'message' => app_lang('record_deleted')));
    }

    /**
     * Monta linha de distribuidora.
     */
    private function _make_row($row)
    {
        $actions = modal_anchor(get_uri('fotovoltaico/utilities_modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $row->id,
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            'title' => app_lang('delete'),
            'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $row->id,
            'data-action-url' => get_uri('fotovoltaico/utilities_delete'),
            'data-action' => 'delete-confirmation'
        ));
        $actions .= ' ' . anchor(get_uri('fotovoltaico/tariffs/' . $row->id), app_lang('fv_tariffs'), array(
            'class' => 'btn btn-sm btn-outline-primary'
        ));

        return array(
            esc($row->name),
            esc($row->uf ?? '-'),
            esc($row->code ?? '-'),
            $actions
        );
    }
}
