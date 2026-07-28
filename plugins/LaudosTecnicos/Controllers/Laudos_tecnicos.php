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

    // ==================== DASHBOARD ====================
    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $company_id = $this->_get_company_id();
        $counts = $this->Laudos_model->get_counts_by_status($company_id);
        $priority_counts = $this->Laudos_model->get_counts_by_priority($company_id);

        $view_data = array(
            'counts' => $counts,
            'priority_counts' => $priority_counts,
            'can_create' => $this->_has_create_permission(),
            'can_manage' => $this->_has_manage_permission(),
            'status_list' => $this->Laudo_status_model->get_dropdown()
        );

        return $this->template->rander('LaudosTecnicos\Views\dashboard\index', $view_data);
    }

    // ==================== LISTAGEM ====================
    public function laudos()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $types_dropdown = $this->Laudo_types_model->get_dropdown();
        $categories_dropdown = $this->Laudo_categories_model->get_dropdown();
        $status_list = $this->Laudo_status_model->get_dropdown();
        
        $view_data = array(
            'types_dropdown' => $types_dropdown,
            'categories_dropdown' => $categories_dropdown,
            'status_list' => $status_list,
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
            'laudo_type_id' => $this->request->getPost('laudo_type_id'),
            'category_id' => $this->request->getPost('category_id'),
            'client_id' => $this->request->getPost('client_id'),
            'project_id' => $this->request->getPost('project_id'),
            'technician_id' => $this->request->getPost('technician_id'),
            'priority' => $this->request->getPost('priority'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date' => $this->request->getPost('end_date'),
            'validity_status' => $this->request->getPost('validity_status')
        );

        $list_data = $this->Laudos_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_laudo_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _make_laudo_row($data)
    {
        $priority_class = '';
        $priority_labels = array('low' => 'secondary', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger');
        $priority_class = $priority_labels[$data->priority] ?? 'info';
        
        return array(
            $data->id,
            $data->laudo_number ?? $data->id,
            'v' . $data->version,
            $data->title,
            $data->company_name ?? '-',
            $data->project_title ?? '-',
            $data->type_name ?? '-',
            $data->category_name ?? '-',
            $data->technician_name ?? '-',
            $data->request_date ?? '-',
            $data->inspection_date ?? '-',
            $data->issue_date ?? '-',
            $data->valid_until ?? '-',
            '<span class="badge bg-' . $this->_get_status_color($data->status) . '">' . ($data->status) . '</span>',
            '<span class="badge bg-' . $priority_class . '">' . ucfirst($data->priority ?? 'normal') . '</span>',
            $this->_get_laudo_actions($data)
        );
    }

    private function _get_laudo_actions($data)
    {
        $actions = '';
        
        // Visualizar
        $actions .= '<a href="' . get_uri('laudos_tecnicos/view/' . $data->id) . '" class="btn btn-default btn-sm" title="Visualizar"><i data-feather="eye" class="icon-16"></i></a> ';
        
        // Editar
        if ($this->_has_edit_permission() || $data->status === 'draft') {
            $actions .= modal_anchor(get_uri('laudos_tecnicos/modal_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit btn btn-default btn-sm', 'title' => app_lang('edit'))) . ' ';
        }
        
        // Duplicar
        if ($this->_has_create_permission()) {
            $actions .= '<a href="' . get_uri('laudos_tecnicos/duplicate/' . $data->id) . '" class="btn btn-default btn-sm" title="Duplicar"><i data-feather="copy" class="icon-16"></i></a> ';
        }
        
        // Excluir (apenas rascunho)
        if (($this->_has_edit_permission() || $this->_has_delete_permission()) && in_array($data->status, ['draft', 'requested'])) {
            $actions .= js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('class' => 'delete btn btn-danger btn-sm', 'title' => app_lang('delete'), 'data-id' => $data->id, 'data-action-url' => get_uri('laudos_tecnicos/delete/' . $data->id), 'data-action' => 'delete-confirmation'));
        }
        
        return $actions;
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

    private function _get_priority_color($priority)
    {
        $colors = array(
            'low' => 'secondary',
            'normal' => 'info',
            'high' => 'warning',
            'urgent' => 'danger'
        );
        return $colors[$priority] ?? 'info';
    }

    // ==================== FORMULÁRIO ====================
    public function modal_form($id = 0)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->Laudos_model->get_one($id);
        }

        // Dropdowns
        $view_data['types_dropdown'] = $this->Laudo_types_model->get_dropdown();
        $view_data['categories_dropdown'] = $this->Laudo_categories_model->get_dropdown();
        $view_data['status_list'] = $this->Laudo_status_model->get_dropdown();
        
        // carregar modelos do RISE
        $clients_model = model('App\Models\Clients_model');
        $view_data['clients_dropdown'] = $clients_model->get_dropdown();
        
        $projects_model = model('App\Models\Projects_model');
        $view_data['projects_dropdown'] = $projects_model->get_dropdown();
        
        $users_model = model('App\Models\Users_model');
        $view_data['team_dropdown'] = $users_model->get_dropdown();

        return $this->template->view('LaudosTecnicos\Views\laudos\modal_form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_create_permission() && !$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        // Identificação
        $data = array(
            'title' => $this->request->getPost('title'),
            'laudo_type_id' => $this->request->getPost('laudo_type_id') ?: null,
            'category_id' => $this->request->getPost('category_id') ?: null,
            'client_id' => $this->request->getPost('client_id') ?: null,
            'contact_id' => $this->request->getPost('contact_id') ?: null,
            'project_id' => $this->request->getPost('project_id') ?: null,
            'custom_code' => $this->request->getPost('custom_code'),
            'priority' => $this->request->getPost('priority') ?: 'normal',
            
            // Endereço
            'address' => $this->request->getPost('address'),
            'city' => $this->request->getPost('city'),
            'state' => $this->request->getPost('state'),
            'location' => $this->request->getPost('location'),
            
            // Datas
            'request_date' => $this->request->getPost('request_date') ?: date('Y-m-d'),
            'scheduled_date' => $this->request->getPost('scheduled_date'),
            'inspection_date' => $this->request->getPost('inspection_date'),
            
            // Responsáveis
            'commercial_responsible_id' => $this->request->getPost('commercial_responsible_id') ?: null,
            'technician_id' => $this->request->getPost('technician_id') ?: null,
            'reviewer_id' => $this->request->getPost('reviewer_id') ?: null,
            'approver_id' => $this->request->getPost('approver_id') ?: null,
            
            // Conteúdo técnico
            'objective' => $this->request->getPost('objective'),
            'scope' => $this->request->getPost('scope'),
            'methodology' => $this->request->getPost('methodology'),
            'assumptions' => $this->request->getPost('assumptions'),
            'limitations' => $this->request->getPost('limitations'),
            'installation_description' => $this->request->getPost('installation_description'),
            'results' => $this->request->getPost('results'),
            'diagnosis' => $this->request->getPost('diagnosis'),
            'conclusion' => $this->request->getPost('conclusion'),
            'recommendations' => $this->request->getPost('recommendations'),
            
            // Observações
            'description' => $this->request->getPost('description'),
            'observations' => $this->request->getPost('observations'),
            'internal_notes' => $this->request->getPost('internal_notes'),
            'client_observations' => $this->request->getPost('client_observations'),
            
            // Info complementares
            'tags' => $this->request->getPost('tags'),
            'cost_center' => $this->request->getPost('cost_center'),
            'proposal_number' => $this->request->getPost('proposal_number'),
            'contract_number' => $this->request->getPost('contract_number'),
            'external_reference' => $this->request->getPost('external_reference'),
            'confidentiality' => $this->request->getPost('confidentiality') ? 1 : 0,
            
            'created_by' => $this->login_user->id
        );

        // Se novo laudo, definir status inicial
        if (!$id) {
            $data['status'] = 'draft';
        }

        $save_id = $this->Laudos_model->save($data, $id);

        if ($save_id) {
            // Registrar na timeline se vinculado a projeto
            if (!empty($data['project_id']) && $id) {
                log_notification('laudo_updated', array('laudo_id' => $save_id), $this->login_user->id);
            } elseif (!empty($data['project_id']) && !$id) {
                log_notification('laudo_created', array('laudo_id' => $save_id), $this->login_user->id);
            }
            
            return $this->response->setJSON(array('success' => true, 'data' => $save_id, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== VISUALIZAÇÃO ====================
    public function view($id)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $laudo = $this->Laudos_model->get_one($id);
        
        if (!$laudo) {
            app_redirect('laudos_tecnicos/laudos');
        }

        // Histórico de status
        $status_history = $this->Laudo_status_history_model->get_details(array('laudo_id' => $id))->getResult();
        
        // Transições disponíveis
        $available_transitions = $this->Laudo_status_transitions_model->get_transitions_from($laudo->status);

        $view_data = array(
            'laudo' => $laudo,
            'status_history' => $status_history,
            'available_transitions' => $available_transitions,
            'status_list' => $this->Laudo_status_model->get_dropdown(),
            'can_edit' => $this->_has_edit_permission(),
            'can_change_status' => $this->_has_permission('laudos_change_status'),
            'is_admin' => $this->login_user->is_admin
        );

        return $this->template->rander('LaudosTecnicos\Views\laudos\view', $view_data);
    }

    public function change_status($id)
    {
        if (!$this->_has_permission('laudos_change_status')) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        $new_status = $this->request->getPost('status');
        $comment = $this->request->getPost('comment');

        if (!$new_status) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Status inválido'));
        }

        $result = $this->Laudos_model->change_status($id, $new_status, $this->login_user->id, $comment);

        return $this->response->setJSON($result);
    }

    // ==================== DUPLICAÇÃO ====================
    public function duplicate($id)
    {
        if (!$this->_has_create_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $new_id = $this->Laudos_model->duplicate($id);

        if ($new_id) {
            app_redirect('laudos_tecnicos/view/' . $new_id);
        } else {
            app_redirect('laudos_tecnicos/laudos');
        }
    }

    // ==================== EXCLUSÃO ====================
    public function delete($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;

        if ($this->Laudos_model->delete($id)) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('laudos_cannot_delete')));
    }

    public function delete_data($id)
    {
        return $this->delete($id);
    }

    // ==================== BUSCA ====================
    public function search()
    {
        $term = $this->request->getGet('term');
        $limit = (int)$this->request->getGet('limit') ?: 20;
        
        $results = $this->Laudos_model->search($term, $limit);
        
        return $this->response->setJSON($results);
    }

    // ==================== RELATÓRIO ====================
    public function export()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $options = array(
            'status' => $this->request->getGet('status'),
            'laudo_type_id' => $this->request->getGet('laudo_type_id'),
            'category_id' => $this->request->getGet('category_id'),
            'client_id' => $this->request->getGet('client_id'),
            'start_date' => $this->request->getGet('start_date'),
            'end_date' => $this->request->getGet('end_date')
        );

        $laudos = $this->Laudos_model->get_details($options)->getResult();

        // Gerar CSV simples
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=laudos_export_' . date('Ymd') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Número', 'Título', 'Cliente', 'Tipo', 'Status', 'Data Solicitação', 'Data Emissão', 'Validade'));
        
        foreach ($laudos as $laudo) {
            fputcsv($output, array(
                $laudo->laudo_number ?? $laudo->id,
                $laudo->title,
                $laudo->company_name ?? '-',
                $laudo->type_name ?? '-',
                $laudo->status,
                $laudo->request_date ?? '-',
                $laudo->issue_date ?? '-',
                $laudo->valid_until ?? '-'
            ));
        }
        
        fclose($output);
        exit;
    }

    // ==================== API LAUDOS POR CLIENTE/PROJETO ====================
    public function client_laudos($client_id)
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $laudos = $this->Laudos_model->get_laudos_for_client($client_id);
        return $this->response->setJSON($laudos);
    }

    public function project_laudos($project_id)
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $laudos = $this->Laudos_model->get_laudos_for_project($project_id);
        return $this->response->setJSON($laudos);
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

    private function _has_delete_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_delete_draft') == '1';
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
        return array(
            $data->id,
            $data->name,
            $data->code ?? '-',
            $data->category_name ?? '-',
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

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_comeu')));
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
}