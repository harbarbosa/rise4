<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudo_non_conformities_model;
use LaudosTecnicos\Models\Laudo_action_plans_model;

class Laudo_nonconformities extends Security_Controller
{
    protected $nc_model;
    protected $action_plan_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        
        $this->nc_model = model('LaudosTecnicos\Models\Laudo_non_conformities_model');
        $this->action_plan_model = model('LaudosTecnicos\Models\Laudo_action_plans_model');
    }

    // ==================== LISTAGEM ====================
    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $status_list = array(
            'open' => 'Aberta',
            'in_analysis' => 'Em análise',
            'waiting_correction' => 'Aguardando correção',
            'in_correction' => 'Em correção',
            'corrected' => 'Corrigida',
            'waiting_validation' => 'Aguardando validação',
            'validated' => 'Validada',
            'rejected' => 'Rejeitada',
            'canceled' => 'Cancelada'
        );

        $classification_list = array(
            'observation' => 'Observação',
            'improvement' => 'Oportunidade de melhoria',
            'low' => 'Baixa',
            'moderate' => 'Moderada',
            'high' => 'Alta',
            'critical' => 'Crítica',
            'emergential' => 'Emergencial'
        );

        $view_data = array(
            'status_list' => $status_list,
            'classification_list' => $classification_list
        );

        return $this->template->rander('LaudosTecnicos\Views\nonconformities\index', $view_data);
    }

    public function list_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $options = array(
            'search' => $this->request->getPost('search'),
            'status' => $this->request->getPost('status'),
            'classification' => $this->request->getPost('classification'),
            'risk_level' => $this->request->getPost('risk_level'),
            'laudo_id' => $this->request->getPost('laudo_id')
        );

        $list_data = $this->nc_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _make_row($data)
    {
        $class_colors = array(
            'observation' => 'secondary',
            'improvement' => 'info',
            'low' => 'success',
            'moderate' => 'warning',
            'high' => 'warning',
            'critical' => 'danger',
            'emergential' => 'dark'
        );

        $status_colors = array(
            'open' => 'danger',
            'in_analysis' => 'info',
            'waiting_correction' => 'warning',
            'in_correction' => 'primary',
            'corrected' => 'success',
            'waiting_validation' => 'warning',
            'validated' => 'success',
            'rejected' => 'dark',
            'canceled' => 'secondary'
        );
        
        return array(
            $data->id,
            $data->code,
            $data->title,
            $data->company_name ?? '-',
            '<span class="badge bg-' . ($class_colors[$data->classification] ?? 'secondary') . '">' . ucfirst($data->classification) . '</span>',
            '<span class="badge" style="background-color: ' . ($data->risk_color ?? '#198754') . '">N' . $data->risk_level . '</span>',
            '<span class="badge bg-' . ($status_colors[$data->status] ?? 'secondary') . '">' . $data->status . '</span>',
            $data->identified_at,
            $this->_get_actions($data)
        );
    }

    private function _get_actions($data)
    {
        return '<a href="' . get_uri('laudo_nonconformities/view/' . $data->id) . '" class="btn btn-default btn-sm" title="Visualizar"><i data-feather="eye" class="icon-16"></i></a> ' .
               modal_anchor(get_uri('laudo_nonconformities/form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit btn btn-default btn-sm', 'title' => 'Editar'));
    }

    // ==================== FORMULÁRIO ====================
    public function form($id = 0)
    {
        if (!$this->_has_edit_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->nc_model->get_one($id);
        }

        // Dropdowns
        $laudos_model = model('LaudosTecnicos\Models\Laudos_model');
        $laudos = $laudos_model->get_details()->getResult();
        $laudos_dropdown = array();
        foreach ($laudos as $l) {
            $laudos_dropdown[$l->id] = $l->laudo_number . ' - ' . substr($l->title, 0, 40);
        }
        
        $users_model = model('App\Models\Users_model');
        
        $view_data['laudos_dropdown'] = $laudos_dropdown;
        $view_data['team_dropdown'] = $users_model->get_dropdown();
        $view_data['classification_list'] = $this->_get_classifications();
        $view_data['status_list'] = $this->_get_status_list();

        return $this->template->view('LaudosTecnicos\Views\nonconformities\form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'laudo_id' => $this->request->getPost('laudo_id') ?: null,
            'client_id' => $this->request->getPost('client_id') ?: null,
            'location' => $this->request->getPost('location'),
            'sector' => $this->request->getPost('sector'),
            'classification' => $this->request->getPost('classification') ?: 'moderate',
            'probability' => $this->request->getPost('probability') ?: 2,
            'impact' => $this->request->getPost('impact') ?: 2,
            'recommendation' => $this->request->getPost('recommendation'),
            'responsible_id' => $this->request->getPost('responsible_id') ?: null,
            'status' => $this->request->getPost('status') ?: 'open',
            'identified_at' => $this->request->getPost('identified_at') ?: date('Y-m-d'),
            'suggested_deadline' => $this->request->getPost('suggested_deadline'),
            'created_by' => $this->login_user->id
        );

        $save_id = $this->nc_model->save($data, $id);

        if ($save_id) {
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
        $nc = $this->nc_model->get_one($id);
        
        if (!$nc) {
            app_redirect('laudo_nonconformities');
        }

        // Planos de ação
        $action_plans = $this->action_plan_model->get_for_nc($id);
        
        // Histórico
        $logs_model = model('LaudosTecnicos\Models\Laudo_nc_logs_model');
        $logs = $logs_model->get_for_nc($id);

        $view_data = array(
            'nc' => $nc,
            'action_plans' => $action_plans,
            'logs' => $logs,
            'classification_list' => $this->_get_classifications(),
            'status_list' => $this->_get_status_list()
        );

        return $this->template->rander('LaudosTecnicos\Views\nonconformities\view', $view_data);
    }

    // ==================== AÇÕES ====================
    public function update_status($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        $status = $this->request->getPost('status');
        $comment = $this->request->getPost('comment');

        $this->nc_model->update_status($id, $status, $this->login_user->id, $comment);

        return $this->response->setJSON(array('success' => true, 'message' => 'Status atualizado'));
    }

    public function validate($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        $comment = $this->request->getPost('comment');

        $this->nc_model->update_status($id, 'validated', $this->login_user->id, $comment);

        return $this->response->setJSON(array('success' => true, 'message' => 'Não conformidade validada'));
    }

    public function reject($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        $comment = $this->request->getPost('comment');

        $this->nc_model->update_status($id, 'rejected', $this->login_user->id, $comment);

        return $this->response->setJSON(array('success' => true, 'message' => 'Não conformidade rejeitada'));
    }

    // ==================== PLANOS DE AÇÃO ====================
    public function action_plan_form($nc_id = 0)
    {
        if (!$this->_has_edit_permission()) {
            app_redirect('forbidden');
        }

        $nc_id = (int)$nc_id;
        $view_data = array();
        
        if ($nc_id) {
            $view_data['nc_info'] = $this->nc_model->get_one($nc_id);
        }

        $users_model = model('App\Models\Users_model');
        $view_data['team_dropdown'] = $users_model->get_dropdown();

        return $this->template->view('LaudosTecnicos\Views\nonconformities\action_plan_form', $view_data);
    }

    public function action_plan_save()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'nc_id' => $this->request->getPost('nc_id'),
            'action' => $this->request->getPost('action'),
            'reason' => $this->request->getPost('reason'),
            'location' => $this->request->getPost('location'),
            'responsible_id' => $this->request->getPost('responsible_id') ?: null,
            'company_name' => $this->request->getPost('company_name'),
            'method' => $this->request->getPost('method'),
            'deadline' => $this->request->getPost('deadline'),
            'estimated_cost' => $this->request->getPost('estimated_cost') ?: null,
            'priority' => $this->request->getPost('priority') ?: 'normal',
            'status' => $this->request->getPost('status') ?: 'pending'
        );

        $save_id = $this->action_plan_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'data' => $save_id, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function action_plan_complete($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $evidence = $this->request->getPost('evidence');
        
        $this->action_plan_model->complete($id, $evidence);

        return $this->response->setJSON(array('success' => true, 'message' => 'Plano de ação concluído'));
    }

    public function action_plan_create_task($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $task_id = $this->action_plan_model->create_task($id);

        if ($task_id) {
            return $this->response->setJSON(array('success' => true, 'task_id' => $task_id, 'message' => 'Tarefa criada'));
        }

        return $this->response->setJSON(array('success' => false, 'message' => 'Erro ao criar tarefa'));
    }

    // ==================== DASHBOARD ====================
    public function get_stats()
    {
        $stats = $this->nc_model->get_stats();
        
        $result = array(
            'open' => 0,
            'critical' => 0,
            'overdue' => 0,
            'validated' => 0,
            'waiting_validation' => 0,
            'action_plans_overdue' => 0
        );

        foreach ($stats as $s) {
            if ($s->status === 'open' || $s->status === 'in_analysis' || $s->status === 'waiting_correction' || $s->status === 'in_correction') {
                $result['open'] += $s->count;
            }
            if ($s->classification === 'critical' || $s->classification === 'emergential') {
                $result['critical'] += $s->count;
            }
            if ($s->status === 'validated') {
                $result['validated'] += $s->count;
            }
            if ($s->status === 'waiting_validation') {
                $result['waiting_validation'] += $s->count;
            }
        }

        return $this->response->setJSON($result);
    }

    // ==================== HELPERS ====================
    private function _get_classifications()
    {
        return array(
            'observation' => 'Observação',
            'improvement' => 'Oportunidade de melhoria',
            'low' => 'Baixa',
            'moderate' => 'Moderada',
            'high' => 'Alta',
            'critical' => 'Crítica',
            'emergential' => 'Emergencial'
        );
    }

    private function _get_status_list()
    {
        return array(
            'open' => 'Aberta',
            'in_analysis' => 'Em análise',
            'waiting_correction' => 'Aguardando correção',
            'in_correction' => 'Em correção',
            'corrected' => 'Corrigida',
            'waiting_validation' => 'Aguardando validação',
            'validated' => 'Validada',
            'rejected' => 'Rejeitada',
            'canceled' => 'Cancelada'
        );
    }

    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_view') == '1';
    }

    private function _has_edit_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_edit') == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setStatusCode(403)->setJSON(array('success' => false, 'message' => app_lang('access_denied')));
    }
}