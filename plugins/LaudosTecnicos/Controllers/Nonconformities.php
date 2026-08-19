<?php

namespace LaudosTecnicos\Controllers;

class Nonconformities extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureNonconformitiesAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\nonconformities\\index', array(
            'can_manage_nonconformities' => \LaudosTecnicos\Plugin::canManageNonconformities($this->login_user),
            'can_manage_risk_matrix' => \LaudosTecnicos\Plugin::canManageRiskMatrix($this->login_user),
            'can_manage_action_plans' => \LaudosTecnicos\Plugin::canManageActionPlans($this->login_user),
            'nc_statuses' => $this->nonconformityStatuses(),
            'classification_options' => laudostecnicos_risk_classification_options(),
            'plan_statuses' => $this->planStatuses(),
            'dashboard_stats' => $this->nonconformities_model->get_dashboard_stats(),
            'plan_stats' => $this->action_plans_model->get_dashboard_stats(),
            'risk_cards' => $this->riskCards(),
        ));
    }

    public function list_data()
    {
        $this->ensureNonconformitiesAccess();

        $rows = $this->nonconformities_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => trim((string) $this->request->getPost('status')),
            'classification' => trim((string) $this->request->getPost('classification')),
            'client_id' => (int) $this->request->getPost('client_id'),
            'laudo_id' => (int) $this->request->getPost('laudo_id'),
            'inspection_id' => (int) $this->request->getPost('inspection_id'),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/nao-conformidades/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= ' ' . modal_anchor(get_uri('laudostecnicos/nao-conformidades/plan_modal_form'), "<i data-feather='tool' class='icon-16'></i>", array('title' => app_lang('laudostecnicos_action_plans'), 'class' => 'btn btn-sm btn-outline-primary', 'data-post-nonconformity_id' => $row->id));

            $result[] = array(
                esc($row->code),
                esc($row->title),
                esc($row->client_name ?: '-'),
                esc($row->laudo_title ?: '-'),
                esc($row->inspection_code ?: '-'),
                $this->classificationBadge((string) ($row->classification ?: 'observacao')),
                esc($row->risk_level ?: '-'),
                $this->statusBadge((string) ($row->status ?: 'open')),
                esc($row->suggested_deadline ?? '-'),
                esc($row->responsible_name ?: '-'),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function plans_list_data()
    {
        $this->ensureActionPlansAccess();

        $rows = $this->action_plans_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => trim((string) $this->request->getPost('status')),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/nao-conformidades/plan_modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= ' ' . js_anchor("<i data-feather='refresh-cw' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-info ms-1', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/nao-conformidades/sync_task/' . $row->id), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->code ?: '-'),
                esc($row->nc_code ?: '-'),
                esc($row->nc_title ?: '-'),
                esc($row->action ?: '-'),
                esc($row->responsible_name ?: '-'),
                esc($row->deadline ?: '-'),
                $this->planStatusBadge((string) ($row->status ?: 'draft')),
                esc($row->task_title ?: '-'),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function matrix_list_data()
    {
        $this->ensureRiskMatrixAccess();

        $table = $this->db->prefixTable('laudo_risk_matrix');
        $rows = $this->db->table($table)->where('deleted', 0)->orderBy('sort', 'ASC')->orderBy('probability', 'ASC')->orderBy('impact', 'ASC')->get()->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/nao-conformidades/matrix_modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $result[] = array(
                esc($row->name),
                (int) $row->probability,
                (int) $row->impact,
                (int) $row->result,
                $this->classificationBadge((string) ($row->classification ?: 'observacao')),
                '<span class="badge" style="background:' . esc($row->color ?: '#6c757d') . ';">' . esc($row->color ?: '#6c757d') . '</span>',
                esc($row->suggested_deadline_days ?: 0),
                $row->is_default ? app_lang('yes') : app_lang('no'),
                $row->is_active ? app_lang('yes') : app_lang('no'),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureNonconformitiesAccess();

        $model_info = $id ? $this->nonconformities_model->get_one((int) $id) : (object) array(
            'id' => '',
            'code' => '',
            'title' => '',
            'description' => '',
            'client_id' => '',
            'laudo_id' => '',
            'inspection_id' => '',
            'location_text' => '',
            'sector' => '',
            'equipment_id' => '',
            'checklist_id' => '',
            'checklist_item_id' => '',
            'norm_id' => '',
            'evidence_json' => '[]',
            'photos_json' => '[]',
            'classification' => 'observacao',
            'probability' => 1,
            'impact' => 1,
            'risk_level' => '',
            'risk_color' => '',
            'recommendation' => '',
            'suggested_deadline' => '',
            'responsible_id' => '',
            'validator_id' => '',
            'status' => 'open',
            'identified_at' => get_current_utc_time(),
            'corrected_at' => '',
            'correction_evidence_json' => '[]',
            'correction_comments' => '',
        );

        return $this->template->view('LaudosTecnicos\\Views\\nonconformities\\modal_form', array(
            'model_info' => $model_info,
            'classification_options' => laudostecnicos_risk_classification_options(),
            'nc_statuses' => $this->nonconformityStatuses(),
            'clients_dropdown' => model('App\\Models\\Clients_model')->get_id_and_text_dropdown(array('company_name')),
            'laudos_rows' => $this->laudos_model->get_details(array())->getResult(),
            'inspections_rows' => $this->inspections_model->get_details(array())->getResult(),
            'equipments_dropdown' => $this->equipments_model->get_active_dropdown(true),
            'checklists_dropdown' => $this->checklists_model->get_active_dropdown(true),
            'norms_dropdown' => $this->norms_model->get_active_dropdown(true),
            'responsibles_dropdown' => model('App\\Models\\Users_model')->get_id_and_text_dropdown(array('first_name', 'last_name'), array('deleted' => 0, 'user_type' => 'staff')),
        ));
    }

    public function plan_modal_form($id = 0)
    {
        $this->ensureActionPlansAccess();

        $model_info = $id ? $this->action_plans_model->get_one((int) $id) : (object) array(
            'id' => '',
            'nonconformity_id' => (int) $this->request->getPost('nonconformity_id'),
            'code' => '',
            'action' => '',
            'motive' => '',
            'location_text' => '',
            'responsible_id' => '',
            'company_name' => '',
            'method' => '',
            'deadline' => '',
            'estimated_cost' => '',
            'priority' => 'medium',
            'status' => 'draft',
            'evidence_json' => '[]',
            'completion_date' => '',
            'validator_id' => '',
            'auto_create_task' => 1,
            'task_sync_enabled' => 1,
            'what_field' => '',
            'why_field' => '',
            'where_field' => '',
            'when_field' => '',
            'who_field' => '',
            'how_field' => '',
            'how_much_field' => '',
            'task_id' => '',
        );

        return $this->template->view('LaudosTecnicos\\Views\\nonconformities\\plan_modal_form', array(
            'model_info' => $model_info,
            'plan_statuses' => $this->planStatuses(),
            'nc_dropdown' => $this->nonconformities_model->get_details(array())->getResult(),
            'responsibles_dropdown' => model('App\\Models\\Users_model')->get_id_and_text_dropdown(array('first_name', 'last_name'), array('deleted' => 0, 'user_type' => 'staff')),
            'priorities_dropdown' => array('low' => 'Baixa', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Critica'),
        ));
    }

    public function matrix_modal_form($id = 0)
    {
        $this->ensureRiskMatrixAccess();

        $model_info = $id ? $this->db->table($this->db->prefixTable('laudo_risk_matrix'))->where('id', (int) $id)->get()->getRow() : (object) array(
            'id' => '',
            'name' => '',
            'category_id' => '',
            'probability' => 1,
            'impact' => 1,
            'result' => 1,
            'classification' => 'observacao',
            'color' => '#6c757d',
            'suggested_deadline_days' => 30,
            'is_default' => 0,
            'sort' => 0,
            'is_active' => 1,
        );

        return $this->template->view('LaudosTecnicos\\Views\\nonconformities\\matrix_modal_form', array(
            'model_info' => $model_info,
            'classification_options' => laudostecnicos_risk_classification_options(),
            'categories_dropdown' => $this->categories_model->get_active_dropdown(true),
        ));
    }

    public function save()
    {
        $this->ensureNonconformitiesAccess();

        $id = (int) $this->request->getPost('id');
        $data = $this->collectNcPayload();
        if ($data['title'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $category_id = 0;
        if (!empty($data['laudo_id'])) {
            $laudo = $this->laudos_model->get_one((int) $data['laudo_id']);
            $category_id = (int) ($laudo->category_id ?? 0);
        }

        $risk = $this->nonconformities_model->resolve_risk((int) $data['probability'], (int) $data['impact'], $category_id);
        if (empty($data['risk_level'])) {
            $data['risk_level'] = $risk['risk_level'];
        }
        if (empty($data['risk_color'])) {
            $data['risk_color'] = $risk['risk_color'];
        }
        if (empty($data['suggested_deadline']) && !empty($risk['deadline_days'])) {
            $data['suggested_deadline'] = date('Y-m-d', strtotime('+' . (int) $risk['deadline_days'] . ' days'));
        }

        $ok = $this->nonconformities_model->save_from_post($data + array('updated_by' => (int) ($this->login_user->id ?? 0)), $id ?: null);
        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function save_plan()
    {
        $this->ensureActionPlansAccess();

        $id = (int) $this->request->getPost('id');
        $data = $this->collectPlanPayload();
        if (!$data['nonconformity_id'] || $data['action'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $data['updated_by'] = (int) ($this->login_user->id ?? 0);
        if (!$id) {
            $data['created_by'] = (int) ($this->login_user->id ?? 0);
        }

        $saved_id = $this->action_plans_model->save_from_post($data, $id ?: null);
        if ($saved_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $plan_id = $id ?: (is_numeric($saved_id) ? (int) $saved_id : 0);
        if (!$plan_id && $ok === true) {
            $plan_id = (int) $this->action_plans_model->db->insertID();
        }

        if (!empty($data['auto_create_task']) || !empty($data['task_id'])) {
            $this->action_plans_model->sync_task_from_plan(array(
                'plan_id' => $plan_id,
                'task_title' => $data['task_title'] ?: $data['action'],
                'task_description' => $data['task_description'] ?: $data['motive'],
                'assigned_to' => $data['responsible_id'],
                'deadline' => $data['deadline'],
                'priority_id' => $data['priority_id'],
                'client_id' => $data['client_id'],
                'project_id' => $data['project_id'],
                'created_by' => (int) ($this->login_user->id ?? 0),
                'sync_source' => 'plan',
            ));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function save_matrix()
    {
        $this->ensureRiskMatrixAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'name' => trim((string) $this->request->getPost('name')),
            'category_id' => (int) $this->request->getPost('category_id'),
            'probability' => (int) $this->request->getPost('probability'),
            'impact' => (int) $this->request->getPost('impact'),
            'result' => (int) $this->request->getPost('result'),
            'classification' => trim((string) $this->request->getPost('classification')),
            'color' => trim((string) $this->request->getPost('color')),
            'suggested_deadline_days' => (int) $this->request->getPost('suggested_deadline_days'),
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
            'sort' => (int) $this->request->getPost('sort'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        if ($data['name'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        if (!$id) {
            $data['created_by'] = (int) ($this->login_user->id ?? 0);
        }

        $table = $this->db->prefixTable('laudo_risk_matrix');
        if (!empty($data['is_default'])) {
            $this->db->table($table)->where('deleted', 0)->update(array('is_default' => 0, 'updated_at' => get_current_utc_time()));
        }

        $builder = $this->db->table($table);
        if ($id) {
            $ok = $builder->where('id', $id)->update(array_merge($data, array('updated_at' => get_current_utc_time())));
        } else {
            $data['created_at'] = get_current_utc_time();
            $data['updated_at'] = get_current_utc_time();
            $ok = $builder->insert($data);
        }

        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function create_task($id = 0)
    {
        $this->ensureActionPlansAccess();
        $ok = $this->action_plans_model->create_task_from_plan((int) $id, array('created_by' => (int) ($this->login_user->id ?? 0)));
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function sync_task($id = 0)
    {
        $this->ensureActionPlansAccess();
        $plan = $this->action_plans_model->get_one((int) $id);
        if (!$plan || !$plan->id || !$plan->task_id) {
            return $this->response->setJSON(array('success' => false));
        }

        $task = model('App\\Models\\Tasks_model')->get_one((int) $plan->task_id);
        if (!$task || !$task->id) {
            return $this->response->setJSON(array('success' => false));
        }

        $ok = $this->action_plans_model->sync_from_task(array(
            'task_id' => (int) $task->id,
            'title' => $task->title ?? '',
            'description' => $task->description ?? '',
            'deadline' => $task->deadline ?? '',
            'assigned_to' => $task->assigned_to ?? 0,
            'priority_id' => $task->priority_id ?? 0,
            'status_id' => $task->status_id ?? 0,
        ));

        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function validate($id = 0)
    {
        $this->ensureNonconformitiesAccess();
        $status = trim((string) $this->request->getPost('status')) ?: 'validated';
        $data = array(
            'status' => $status,
            'validator_id' => (int) ($this->login_user->id ?? 0),
            'corrected_at' => $status === 'validated' ? get_current_utc_time() : trim((string) $this->request->getPost('corrected_at')),
            'correction_comments' => trim((string) $this->request->getPost('correction_comments')),
            'correction_evidence_json' => trim((string) $this->request->getPost('correction_evidence_json')),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        $ok = $this->nonconformities_model->save_from_post($data, (int) $id);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function delete()
    {
        $this->ensureNonconformitiesAccess();
        $id = (int) $this->request->getPost('id');
        $plan_exists = $this->action_plans_model->get_by_nc($id);
        if ($plan_exists) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $ok = $this->nonconformities_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    private function collectNcPayload(): array
    {
        return array(
            'code' => trim((string) $this->request->getPost('code')),
            'title' => trim((string) $this->request->getPost('title')),
            'description' => trim((string) $this->request->getPost('description')),
            'client_id' => (int) $this->request->getPost('client_id'),
            'laudo_id' => (int) $this->request->getPost('laudo_id'),
            'inspection_id' => (int) $this->request->getPost('inspection_id'),
            'location_text' => trim((string) $this->request->getPost('location_text')),
            'sector' => trim((string) $this->request->getPost('sector')),
            'equipment_id' => (int) $this->request->getPost('equipment_id'),
            'checklist_id' => (int) $this->request->getPost('checklist_id'),
            'checklist_item_id' => (int) $this->request->getPost('checklist_item_id'),
            'norm_id' => (int) $this->request->getPost('norm_id'),
            'evidence_json' => trim((string) $this->request->getPost('evidence_json')),
            'photos_json' => trim((string) $this->request->getPost('photos_json')),
            'classification' => trim((string) $this->request->getPost('classification')),
            'probability' => (int) $this->request->getPost('probability'),
            'impact' => (int) $this->request->getPost('impact'),
            'risk_level' => trim((string) $this->request->getPost('risk_level')),
            'risk_color' => trim((string) $this->request->getPost('risk_color')),
            'recommendation' => trim((string) $this->request->getPost('recommendation')),
            'suggested_deadline' => trim((string) $this->request->getPost('suggested_deadline')),
            'responsible_id' => (int) $this->request->getPost('responsible_id'),
            'validator_id' => (int) $this->request->getPost('validator_id'),
            'status' => trim((string) $this->request->getPost('status')) ?: 'open',
            'identified_at' => trim((string) $this->request->getPost('identified_at')) ?: get_current_utc_time(),
            'corrected_at' => trim((string) $this->request->getPost('corrected_at')),
            'correction_evidence_json' => trim((string) $this->request->getPost('correction_evidence_json')),
            'correction_comments' => trim((string) $this->request->getPost('correction_comments')),
        );
    }

    private function collectPlanPayload(): array
    {
        return array(
            'nonconformity_id' => (int) $this->request->getPost('nonconformity_id'),
            'code' => trim((string) $this->request->getPost('code')),
            'action' => trim((string) $this->request->getPost('action')),
            'motive' => trim((string) $this->request->getPost('motive')),
            'location_text' => trim((string) $this->request->getPost('location_text')),
            'responsible_id' => (int) $this->request->getPost('responsible_id'),
            'company_name' => trim((string) $this->request->getPost('company_name')),
            'method' => trim((string) $this->request->getPost('method')),
            'deadline' => trim((string) $this->request->getPost('deadline')),
            'estimated_cost' => trim((string) $this->request->getPost('estimated_cost')),
            'priority' => trim((string) $this->request->getPost('priority')) ?: 'medium',
            'status' => trim((string) $this->request->getPost('status')) ?: 'draft',
            'evidence_json' => trim((string) $this->request->getPost('evidence_json')),
            'completion_date' => trim((string) $this->request->getPost('completion_date')),
            'validator_id' => (int) $this->request->getPost('validator_id'),
            'auto_create_task' => $this->request->getPost('auto_create_task') ? 1 : 0,
            'task_sync_enabled' => $this->request->getPost('task_sync_enabled') ? 1 : 0,
            'what_field' => trim((string) $this->request->getPost('what_field')),
            'why_field' => trim((string) $this->request->getPost('why_field')),
            'where_field' => trim((string) $this->request->getPost('where_field')),
            'when_field' => trim((string) $this->request->getPost('when_field')),
            'who_field' => trim((string) $this->request->getPost('who_field')),
            'how_field' => trim((string) $this->request->getPost('how_field')),
            'how_much_field' => trim((string) $this->request->getPost('how_much_field')),
            'task_id' => (int) $this->request->getPost('task_id'),
            'task_title' => trim((string) $this->request->getPost('task_title')),
            'task_description' => trim((string) $this->request->getPost('task_description')),
            'assigned_to' => (int) $this->request->getPost('assigned_to'),
            'priority_id' => (int) $this->request->getPost('priority_id'),
            'client_id' => (int) $this->request->getPost('client_id'),
            'project_id' => (int) $this->request->getPost('project_id'),
        );
    }

    private function nonconformityStatuses(): array
    {
        return laudostecnicos_nonconformity_status_labels();
    }

    private function planStatuses(): array
    {
        return laudostecnicos_action_plan_status_labels();
    }

    private function classificationBadge(string $classification): string
    {
        $map = laudostecnicos_risk_palette();
        $color = get_array_value($map, $classification) ?: '#6c757d';
        $label = laudostecnicos_risk_classification_options()[$classification] ?? $classification;
        return '<span class="badge" style="background:' . esc($color) . ';">' . esc($label) . '</span>';
    }

    private function statusBadge(string $status): string
    {
        $map = array(
            'open' => 'bg-danger',
            'analysis' => 'bg-warning text-dark',
            'awaiting_correction' => 'bg-secondary',
            'in_correction' => 'bg-primary',
            'corrected' => 'bg-info text-dark',
            'awaiting_validation' => 'bg-warning text-dark',
            'validated' => 'bg-success',
            'rejected' => 'bg-dark',
            'canceled' => 'bg-light text-dark',
        );

        $class = get_array_value($map, $status) ?: 'bg-secondary';
        return '<span class="badge ' . $class . '">' . esc($this->nonconformityStatuses()[$status] ?? $status) . '</span>';
    }

    private function planStatusBadge(string $status): string
    {
        $map = array(
            'draft' => 'bg-secondary',
            'open' => 'bg-danger',
            'in_progress' => 'bg-primary',
            'waiting' => 'bg-warning text-dark',
            'done' => 'bg-info text-dark',
            'validated' => 'bg-success',
            'canceled' => 'bg-dark',
        );

        $class = get_array_value($map, $status) ?: 'bg-secondary';
        return '<span class="badge ' . $class . '">' . esc($this->planStatuses()[$status] ?? $status) . '</span>';
    }

    private function riskCards(): array
    {
        $nonconformity_stats = $this->nonconformities_model->get_dashboard_stats();
        $plan_stats = $this->action_plans_model->get_dashboard_stats();

        return array(
            array('key' => 'open', 'title' => 'NCs abertas', 'value' => (int) get_array_value($nonconformity_stats, 'open'), 'class' => 'bg-danger text-white'),
            array('key' => 'critical', 'title' => 'Criticas', 'value' => (int) get_array_value($nonconformity_stats, 'critical'), 'class' => 'bg-dark text-white'),
            array('key' => 'expired', 'title' => 'Vencidas', 'value' => (int) get_array_value($nonconformity_stats, 'expired'), 'class' => 'bg-warning text-dark'),
            array('key' => 'corrected', 'title' => 'Corrigidas', 'value' => (int) get_array_value($nonconformity_stats, 'corrected'), 'class' => 'bg-success text-white'),
            array('key' => 'awaiting_validation', 'title' => 'Aguardando validacao', 'value' => (int) get_array_value($nonconformity_stats, 'awaiting_validation'), 'class' => 'bg-info text-white'),
            array('key' => 'delayed_action_plans', 'title' => 'Planos atrasados', 'value' => (int) get_array_value($plan_stats, 'late'), 'class' => 'bg-danger text-white'),
            array('key' => 'avg_correction_days', 'title' => 'Media correcao', 'value' => (float) get_array_value($nonconformity_stats, 'avg_correction_days'), 'class' => 'bg-primary text-white'),
        );
    }
}
