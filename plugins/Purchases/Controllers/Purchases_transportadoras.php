<?php

namespace Purchases\Controllers;

use App\Controllers\Security_Controller;

class Purchases_transportadoras extends Security_Controller
{
    private $Purchases_transportadoras_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Purchases_transportadoras_model = model('Purchases\\Models\\Purchases_transportadoras_model');
    }

    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        return $this->template->rander('Purchases\\Views\\transportadoras\\index');
    }

    public function list_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $options = array(
            'company_id' => $this->_get_company_id()
        );

        $list_data = $this->Purchases_transportadoras_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form()
    {
        if (!$this->_has_manage_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$this->request->getPost('id');
        $view_data['model_info'] = $this->Purchases_transportadoras_model->get_one($id);
        return $this->template->view('Purchases\\Views\\transportadoras\\modal_form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'name' => 'required'
        ));

        $id = (int)$this->request->getPost('id');
        $data = array(
            'company_id' => $this->_get_company_id(),
            'name' => trim((string)$this->request->getPost('name')),
            'email' => trim((string)$this->request->getPost('email')),
            'phone' => trim((string)$this->request->getPost('phone')),
            'tax_id' => trim((string)$this->request->getPost('tax_id')),
            'address' => trim((string)$this->request->getPost('address')),
            'updated_at' => get_my_local_time()
        );

        if (!$id) {
            $data['created_at'] = get_my_local_time();
            $data['created_by'] = $this->login_user->id;
        }

        $save_id = $this->Purchases_transportadoras_model->ci_save($data, $id);
        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $new_id = $id ? $id : (is_int($save_id) ? $save_id : db_connect('default')->insertID());
        $row = $this->Purchases_transportadoras_model->get_one($new_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $new_id,
            'message' => app_lang('record_saved')
        ));
    }

    public function delete()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        $ok = $this->Purchases_transportadoras_model->delete($id);
        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    private function _make_row($data)
    {
        $actions = modal_anchor(get_uri('purchases_transportadoras/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $data->id,
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            'title' => app_lang('delete'),
            'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $data->id,
            'data-action-url' => get_uri('purchases_transportadoras/delete'),
            'data-action' => 'delete-confirmation'
        ));

        $address = $data->address ? $data->address : '-';
        if (strlen($address) > 80) {
            $address = substr($address, 0, 80) . '...';
        }

        return array(
            esc($data->name),
            esc($data->email ? $data->email : '-'),
            esc($data->phone ? $data->phone : '-'),
            esc($data->tax_id ? $data->tax_id : '-'),
            esc($address),
            $actions
        );
    }

    private function _get_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return $this->login_user->company_id;
        }

        return get_default_company_id();
    }

    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_view') == '1'
            || get_array_value($permissions, 'purchases_manage') == '1'
            || get_array_value($permissions, 'purchases_approve') == '1';
    }

    private function _has_manage_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_manage') == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
    }
}
