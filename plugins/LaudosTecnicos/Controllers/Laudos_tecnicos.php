<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudos_model;
use LaudosTecnicos\Models\Laudo_types_model;
use LaudosTecnicos\Models\Laudo_categories_model;
use LaudosTecnicos\Models\Laudo_status_model;
use LaudosTecnicos\Models\Laudo_status_transitions_model;
use LaudosTecnicos\Models\Laudo_status_history_model;
use LaudosTecnicos\Models\Laudos_settings_model;

class Laudos_tecnicos extends Security_Controller
{
    protected $Laudos_model;
    protected $Laudo_types_model;
    protected $Laudo_categories_model;
    protected $Laudo_status_model;
    protected $Laudo_status_transitions_model;
    protected $Laudo_status_history_model;
    protected $Laudos_settings_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        
        $this->Laudos_model = model('LaudosTecnicos\Models\Laudos_model');
        $this->Laudo_types_model = model('LaudosTecnicos\Models\Laudo_types_model');
        $this->Laudo_categories_model = model('LaudosTecnicos\Models\Laudo_categories_model');
        $this->Laudo_status_model = model('LaudosTecnicos\Models\Laudo_status_model');
        $this->Laudo_status_transitions_model = model('LaudosTecnicos\Models\Laudo_status_transitions_model');
        $this->Laudo_status_history_model = model('LaudosTecnicos\Models\Laudo_status_history_model');
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
            'can_edit' => $this->_has_edit_permission()
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
        $status_list = \LaudosTecnicos\Plugin::statusList();
        
        return array(
            $data->id,
            $data->title,
            $data->type_name ?? '-',
            $data->category_name ?? '-',
            $data->company_name ?? '-',
            '<span class="badge bg-' . $this->_get_status_color($data->status) . '">' . ($status_list[$data->status] ?? $data->status) . '</span>',
            $data->created_at,
            modal_anchor(get_uri('laudos_tecnicos/modal_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit'))) 
                . js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('class' => 'delete', 'title' => app_lang('delete'), 'data-id' => $data->id))
        );
    }

    private function _get_status_color($status)
    {
        $colors = array(
            'draft' => 'secondary',
            'requested' => 'primary',
            'waiting_schedule' => 'secondary',
            'scheduled' => 'info',
            'inspecting' => 'warning',
            'collection_done' => 'warning',
            'waiting_info' => 'secondary',
            'elaborating' => 'primary',
            'pending_review' => 'warning',
            'correcting' => 'warning',
            'pending_approval' => 'warning',
            'approved' => 'info',
            'signed' => 'info',
            'issued' => 'success',
            'sent' => 'success',
            'accepted' => 'success',
            'rejected' => 'danger',
            'expired' => 'danger',
            'canceled' => 'dark'
        );
        return $colors[$status] ?? 'secondary';
    }

    // ==================== CATEGORIAS ====================
    public function categorias()
    {
        if (!$this->_has_permission('laudos_manage_categories')) {
            app_redirect('forbidden');
        }

        return $this->template->rander('LaudosTecnicos\Views\categorias\index');
    }

    public function categorias_list_data()
    {
        if (!$this->_has_permission('laudos_manage_categories')) {
            return $this->_json_permission_denied();
        }

        $options = array(
            'search' => $this->request->getPost('search'),
            'status' => $this->request->getPost('status')
        );

        $list_data = $this->Laudo_categories_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_categoria_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _make_categoria_row($data)
    {
        return array(
            $data->id,
            '<span class="badge" style="background-color: ' . ($data->color ?? '#3788d8') . '">' . ($data->code ?? '') . '</span>',
            $data->name,
            $data->description ?? '-',
            '<span class="badge bg-' . ($data->status ? 'success' : 'secondary') . '">' . ($data->status ? 'Ativo' : 'Inativo') . '</span>',
            $data->sort_order ?? '-',
            $data->created_at,
            $this->_get_categoria_actions($data)
        );
    }

    private function _get_categoria_actions($data)
    {
        $actions = modal_anchor(get_uri('laudos_tecnicos/categoria_modal_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit')));
        
        if (!$this->Laudo_categories_model->has_links($data->id)) {
            $actions .= js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('class' => 'delete', 'title' => app_lang('delete'), 'data-id' => $data->id));
        }
        
        $actions .= ' <a href="' . get_uri('laudos_tecnicos/toggle_categoria/' . $data->id) . '" class="btn btn-default btn-sm" title="' . ($data->status ? 'Inativar' : 'Ativar') . '"><i data-feather="' . ($data->status ? 'eye-off' : 'eye') . '" class="icon-16"></i></a>';
        
        return $actions;
    }

    public function categoria_modal_form($id = 0)
    {
        if (!$this->_has_permission('laudos_manage_categories')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->Laudo_categories_model->get_one($id);
        }

        return $this->template->view('LaudosTecnicos\Views\categorias\modal_form', $view_data);
    }

    public function save_categoria()
    {
        if (!$this->_has_permission('laudos_manage_categories')) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'name' => $this->request->getPost('name'),
            'code' => strtoupper($this->request->getPost('code')),
            'description' => $this->request->getPost('description'),
            'color' => $this->request->getPost('color'),
            'icon' => $this->request->getPost('icon'),
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
            'status' => $this->request->getPost('status') ? 1 : 0,
            'created_by' => $this->login_user->id
        );

        $save_id = $this->Laudo_categories_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function toggle_categoria($id)
    {
        if (!$this->_has_permission('laudos_manage_categories')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $categoria = $this->Laudo_categories_model->get_one($id);
        
        if (!$categoria) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $data = array('status' => $categoria->status ? 0 : 1);
        $this->Laudo_categories_model->save($data, $id);

        return $this->response->setJSON(array('success' => true));
    }

    public function delete_categoria($id)
    {
        if (!$this->_has_permission('laudos_manage_categories')) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        
        if ($this->Laudo_categories_model->has_links($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Esta categoria não pode ser excluída pois possui vínculos com laudos.'));
        }

        if ($this->Laudo_categories_model->delete($id)) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function reorder_categorias()
    {
        if (!$this->_has_permission('laudos_manage_categories')) {
            return $this->_json_permission_denied();
        }

        $order = $this->request->getPost('order');
        if (!$order || !is_array($order)) {
            return $this->response->setJSON(array('success' => false));
        }

        foreach ($order as $index => $id) {
            $this->Laudo_categories_model->save(array('sort_order' => $index + 1), $id);
        }

        return $this->response->setJSON(array('success' => true));
    }

    // ==================== TIPOS ====================
    public function tipos()
    {
        if (!$this->_has_permission('laudos_manage_types')) {
            app_redirect('forbidden');
        }

        $categorias = $this->Laudo_categories_model->get_dropdown();
        
        $view_data = array(
            'categorias' => $categorias
        );

        return $this->template->rander('LaudosTecnicos\Views\tipos\index', $view_data);
    }

    public function tipos_list_data()
    {
        if (!$this->_has_permission('laudos_manage_types')) {
            return $this->_json_permission_denied();
        }

        $list_data = $this->Laudo_types_model->get_details()->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_tipo_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _make_tipo_row($data)
    {
        $has_category = !empty($data->category_id);
        
        return array(
            $data->id,
            $data->name,
            $data->code ?? '-',
            $has_category ? $data->category_name : '-',
            $data->prefix ?? '-',
            ($data->validity_days ?? '-') . ' dias',
            '<span class="badge bg-' . ($data->status ? 'success' : 'secondary') . '">' . ($data->status ? 'Ativo' : 'Inativo') . '</span>',
            modal_anchor(get_uri('laudos_tecnicos/tipo_modal_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit'))) 
                . js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('class' => 'delete', 'title' => app_lang('delete'), 'data-id' => $data->id))
        );
    }

    public function tipo_modal_form($id = 0)
    {
        if (!$this->_has_permission('laudos_manage_types')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->Laudo_types_model->get_one($id);
        }
        
        $view_data['categorias'] = $this->Laudo_categories_model->get_dropdown();

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
            'code' => strtoupper($this->request->getPost('code')),
            'description' => $this->request->getPost('description'),
            'category_id' => $this->request->getPost('category_id') ?: null,
            'prefix' => strtoupper($this->request->getPost('prefix')),
            'validity_days' => $this->request->getPost('validity_days') ?: null,
            'require_technician' => $this->request->getPost('require_technician') ? 1 : 0,
            'require_review' => $this->request->getPost('require_review') ? 1 : 0,
            'require_approval' => $this->request->getPost('require_approval') ? 1 : 0,
            'require_signature' => $this->request->getPost('require_signature') ? 1 : 0,
            'require_inspection' => $this->request->getPost('require_inspection') ? 1 : 0,
            'require_equipment' => $this->request->getPost('require_equipment') ? 1 : 0,
            'allow_mobile' => $this->request->getPost('allow_mobile') ? 1 : 0,
            'status' => $this->request->getPost('status') ? 1 : 0,
            'created_by' => $this->login_user->id
        );

        $save_id = $this->Laudo_types_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== STATUS ====================
    public function status()
    {
        if (!$this->_has_permission('laudos_manage_status')) {
            app_redirect('forbidden');
        }

        return $this->template->rander('LaudosTecnicos\Views\status\index');
    }

    public function status_list_data()
    {
        if (!$this->_has_permission('laudos_manage_status')) {
            return $this->_json_permission_denied();
        }

        $list_data = $this->Laudo_status_model->get_details()->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_status_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _make_status_row($data)
    {
        return array(
            $data->id,
            '<span class="badge" style="background-color: ' . $data->color . '">' . $data->code . '</span>',
            $data->name,
            '<span class="badge bg-' . ($data->is_initial ? 'success' : 'secondary') . '">' . ($data->is_initial ? 'Inicial' : '-') . '</span>',
            '<span class="badge bg-' . ($data->is_final ? 'info' : 'secondary') . '">' . ($data->is_final ? 'Final' : '-') . '</span>',
            '<span class="badge bg-' . ($data->is_cancel ? 'danger' : 'secondary') . '">' . ($data->is_cancel ? 'Cancelamento' : '-') . '</span>',
            $data->sort_order ?? '-',
            '<span class="badge bg-' . ($data->active ? 'success' : 'secondary') . '">' . ($data->active ? 'Ativo' : 'Inativo') . '</span>',
            modal_anchor(get_uri('laudos_tecnicos/status_modal_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit')))
        );
    }

    public function status_modal_form($id = 0)
    {
        if (!$this->_has_permission('laudos_manage_status')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->Laudo_status_model->get_one($id);
        }

        return $this->template->view('LaudosTecnicos\Views\status\modal_form', $view_data);
    }

    public function save_status()
    {
        if (!$this->_has_permission('laudos_manage_status')) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'name' => $this->request->getPost('name'),
            'code' => strtolower($this->request->getPost('code')),
            'description' => $this->request->getPost('description'),
            'color' => $this->request->getPost('color'),
            'icon' => $this->request->getPost('icon'),
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
            'is_initial' => $this->request->getPost('is_initial') ? 1 : 0,
            'is_final' => $this->request->getPost('is_final') ? 1 : 0,
            'is_cancel' => $this->request->getPost('is_cancel') ? 1 : 0,
            'allow_edit' => $this->request->getPost('allow_edit') ? 1 : 0,
            'allow_delete' => $this->request->getPost('allow_delete') ? 1 : 0,
            'allow_issue' => $this->request->getPost('allow_issue') ? 1 : 0,
            'require_comment' => $this->request->getPost('require_comment') ? 1 : 0,
            'active' => $this->request->getPost('active') ? 1 : 0
        );

        $save_id = $this->Laudo_status_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== TRANSIÇÕES ====================
    public function transicoes()
    {
        if (!$this->_has_permission('laudos_manage_transitions')) {
            app_redirect('forbidden');
        }

        $status_list = $this->Laudo_status_model->get_active();
        
        $view_data = array(
            'status_list' => $status_list
        );

        return $this->template->rander('LaudosTecnicos\Views\transicoes\index', $view_data);
    }

    public function transicoes_list_data()
    {
        if (!$this->_has_permission('laudos_manage_transitions')) {
            return $this->_json_permission_denied();
        }

        $from_status_id = $this->request->getPost('from_status_id');
        
        $options = array();
        if ($from_status_id) {
            $options['from_status_id'] = $from_status_id;
        }
        
        $list_data = $this->Laudo_status_transitions_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = array(
                $data->id,
                $data->from_status_name,
                '<i data-feather="arrow-right" class="icon-16"></i>',
                $data->to_status_name,
                $data->sort_order ?? '-',
                '<span class="badge bg-' . ($data->active ? 'success' : 'secondary') . '">' . ($data->active ? 'Ativo' : 'Inativo') . '</span>',
                '<span class="badge bg-' . ($data->require_comment ? 'warning' : 'secondary') . '">' . ($data->require_comment ? 'Sim' : 'Não') . '</span>',
                '<span class="badge bg-' . ($data->notify ? 'info' : 'secondary') . '">' . ($data->notify ? 'Sim' : 'Não') . '</span>',
                js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('class' => 'delete', 'title' => app_lang('delete'), 'data-id' => $data->id))
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function transicao_modal_form()
    {
        if (!$this->_has_permission('laudos_manage_transitions')) {
            app_redirect('forbidden');
        }

        $status_list = $this->Laudo_status_model->get_active();
        
        $view_data = array(
            'status_list' => $status_list
        );

        return $this->template->view('LaudosTecnicos\Views\transicoes\modal_form', $view_data);
    }

    public function save_transicao()
    {
        if (!$this->_has_permission('laudos_manage_transitions')) {
            return $this->_json_permission_denied();
        }

        $from_status_id = $this->request->getPost('from_status_id');
        $to_status_id = $this->request->getPost('to_status_id');
        
        // Verificar se transição já existe
        if ($this->Laudo_status_transitions_model->can_transition(
            $this->Laudo_status_model->get_one($from_status_id)->code,
            $this->Laudo_status_model->get_one($to_status_id)->code
        )) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Esta transição já existe'));
        }

        $data = array(
            'from_status_id' => $from_status_id,
            'to_status_id' => $to_status_id,
            'sort_order' => $this->request->getPost('sort_order') ?: 0,
            'require_comment' => $this->request->getPost('require_comment') ? 1 : 0,
            'notify' => $this->request->getPost('notify') ? 1 : 0,
            'create_task' => $this->request->getPost('create_task') ? 1 : 0,
            'active' => $this->request->getPost('active') ? 1 : 0
        );

        $save_id = $this->Laudo_status_transitions_model->save($data, 0);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function delete_transicao($id)
    {
        if (!$this->_has_permission('laudos_manage_transitions')) {
            return $this->_json_permission_denied();
        }

        if ($this->Laudo_status_transitions_model->delete($id)) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== CONFIGURAÇÕES ====================
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

    // ==================== PERMISSÕES ====================
    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_view') == '1';
    }

    private function _has_create_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_create') == '1';
    }

    private function _has_edit_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_edit') == '1';
    }

    private function _has_manage_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_edit') == '1';
    }

    private function _has_settings_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_settings') == '1';
    }

    private function _has_permission($permission)
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), $permission) == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setStatusCode(403)->setJSON(array('success' => false, 'message' => app_lang('access_denied')));
    }
}