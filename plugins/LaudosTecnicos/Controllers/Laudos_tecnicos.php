<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudos_model;
use LaudosTecnicos\Models\Laudo_types_model;
use LaudosTecnicos\Models\Laudos_settings_model;

class Laudos_tecnicos extends Security_Controller
{
    protected $Laudos_model;
    protected $Laudo_types_model;
    protected $Laudos_settings_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        
        $this->Laudos_model = model('LaudosTecnicos\Models\Laudos_model');
        $this->Laudo_types_model = model('LaudosTecnicos\Models\Laudo_types_model');
        $this->Laudos_settings_model = model('LaudosTecnicos\Models\Laudos_settings_model');
        
        \LaudosTecnicos\Plugin::register();
    }

    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $company_id = $this->_get_company_id();
        $counts = $this->Laudos_model->get_counts_by_status($company_id);

        $view_data = array(
            'counts' => $counts,
            'can_create' => $this->_has_create_permission(),
            'can_manage' => $this->_has_manage_permission()
        );

        return $this->template->rander('LaudosTecnicos\Views\dashboard\index', $view_data);
    }

    public function laudos()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $types_dropdown = $this->Laudo_types_model->get_dropdown();
        
        $view_data = array(
            'types_dropdown' => $types_dropdown,
            'can_create' => $this->_has_create_permission(),
            'can_edit' => $this->_has_edit_permission(),
            'status_list' => \LaudosTecnicos\Plugin::statusList()
        );

        return $this->template->rander('LaudosTecnicos\Views\laudos\index', $view_data);
    }

    public function list_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $options = array(
            'search' => $this->request->getPost('search'),
            'status' => $this->request->getPost('status'),
            'laudo_type_id' => $this->request->getPost('laudo_type_id')
        );

        $list_data = $this->Laudos_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _make_row($data)
    {
        $status_class = '';
        switch ($data->status) {
            case 'draft':
                $status_class = 'secondary';
                break;
            case 'in_progress':
                $status_class = 'primary';
                break;
            case 'pending_review':
                $status_class = 'warning';
                break;
            case 'approved':
                $status_class = 'info';
                break;
            case 'issued':
                $status_class = 'success';
                break;
            case 'expired':
                $status_class = 'danger';
                break;
            case 'canceled':
                $status_class = 'dark';
                break;
        }

        return array(
            $data->id,
            $data->title,
            $data->type_name ?? '-',
            $data->category_name ?? '-',
            $data->company_name ?? '-',
            '<span class="badge bg-' . $status_class . '">' . app_lang('laudos_status_' . $data->status) . '</span>',
            $data->created_at,
            modal_anchor(get_uri('laudos_tecnicos/modal_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit'))) 
                . js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('class' => 'delete', 'title' => app_lang('delete'), 'data-id' => $data->id))
        );
    }

    public function tipos()
    {
        if (!$this->_has_permission('laudos_manage_types')) {
            app_redirect('forbidden');
        }

        return $this->template->rander('LaudosTecnicos\Views\tipos\index');
    }

    public function tipos_list_data()
    {
        if (!$this->_has_permission('laudos_manage_types')) {
            return $this->_json_permission_denied();
        }

        $list_data = $this->Laudo_types_model->get_details()->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = array(
                $data->id,
                $data->name,
                $data->prefix,
                $data->require_inspection ? '<i data-feather="check" class="icon-16 text-success"></i>' : '<i data-feather="x" class="icon-16 text-danger"></i>',
                $data->require_approval ? '<i data-feather="check" class="icon-16 text-success"></i>' : '<i data-feather="x" class="icon-16 text-danger"></i>',
                $data->validity_days ?? '-',
                modal_anchor(get_uri('laudos_tecnicos/tipo_modal_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit'))) 
                . js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('class' => 'delete', 'title' => app_lang('delete'), 'data-id' => $data->id))
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function tipo_modal_form($id = 0)
    {
        if (!$this->_has_permission('laudos_manage_types')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $model = $this->Laudo_types_model;

        $view_data = array();
        if ($id) {
            $view_data['model_info'] = $model->get_one($id);
        }

        return $this->template->view('LaudosTecnicos\Views\tipos\modal_form', $view_data);
    }

    public function save_tipo()
    {
        if (!$this->_has_permission('laudos_manage_types')) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'prefix' => strtoupper($this->request->getPost('prefix')),
            'require_inspection' => $this->request->getPost('require_inspection') ? 1 : 0,
            'require_approval' => $this->request->getPost('require_approval') ? 1 : 0,
            'validity_days' => $this->request->getPost('validity_days') ? (int)$this->request->getPost('validity_days') : null,
            'created_by' => $this->login_user->id
        );

        $save_id = $this->Laudo_types_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function configuracoes()
    {
        if (!$this->_has_settings_permission()) {
            app_redirect('forbidden');
        }

        $settings = $this->Laudos_settings_model->get_settings();

        $view_data = array(
            'settings' => $settings
        );

        return $this->template->rander('LaudosTecnicos\Views\settings\index', $view_data);
    }

    public function save_settings()
    {
        if (!$this->_has_settings_permission()) {
            return $this->_json_permission_denied();
        }

        $data = array(
            'module_name' => $this->request->getPost('module_name'),
            'laudo_prefix' => strtoupper($this->request->getPost('laudo_prefix')),
            'number_format' => $this->request->getPost('number_format'),
            'next_number' => (int)$this->request->getPost('next_number'),
            'primary_color' => $this->request->getPost('primary_color'),
            'timezone' => $this->request->getPost('timezone'),
            'language' => $this->request->getPost('language'),
            'date_format' => $this->request->getPost('date_format'),
            'module_active' => $this->request->getPost('module_active') ? 1 : 0,
            'enable_detailed_logs' => $this->request->getPost('enable_detailed_logs') ? 1 : 0,
            'default_validity_days' => (int)$this->request->getPost('default_validity_days'),
            'require_inspection' => $this->request->getPost('require_inspection') ? 1 : 0,
            'require_approval' => $this->request->getPost('require_approval') ? 1 : 0,
            'auto_notify_client' => $this->request->getPost('auto_notify_client') ? 1 : 0
        );

        $save = $this->Laudos_settings_model->save_settings($data);

        if ($save) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function templates()
    {
        return $this->template->rander('LaudosTecnicos\Views\templates\index');
    }

    public function inspecoes()
    {
        return $this->template->rander('LaudosTecnicos\Views\inspecoes\index');
    }

    // Permissões
    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }
        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'laudos_view') == '1';
    }

    private function _has_create_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }
        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'laudos_create') == '1';
    }

    private function _has_edit_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }
        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'laudos_edit') == '1';
    }

    private function _has_manage_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }
        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'laudos_edit') == '1';
    }

    private function _has_settings_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }
        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'laudos_settings') == '1';
    }

    private function _has_permission($permission)
    {
        if ($this->login_user->is_admin) {
            return true;
        }
        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, $permission) == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setStatusCode(403)->setJSON(array('success' => false, 'message' => app_lang('access_denied')));
    }
}