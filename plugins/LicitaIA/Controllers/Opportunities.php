<?php

namespace LicitaIA\Controllers;

class Opportunities extends Licitaia_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();

        return $this->template->rander('LicitaIA\\Views\\opportunities\\index', array(
            'can_manage' => \LicitaIA\Plugin::canManageOpportunities($this->login_user),
            'status_dropdown' => $this->statusDropdown(),
            'sources_dropdown' => $this->sources_model->get_active_dropdown(true),
            'responsible_dropdown' => $this->getStaffDropdown(),
        ));
    }

    public function list_data()
    {
        $this->ensureAccess();

        $options = array(
            'status' => trim((string) $this->request->getPost('status')),
            'source_id' => (int) $this->request->getPost('source_id'),
            'responsible_user_id' => (int) $this->request->getPost('responsible_user_id'),
            'search' => trim((string) $this->request->getPost('search')),
        );

        $rows = $this->opportunities_model->get_details($options)->getResult();
        $result = array();
        foreach ($rows as $row) {
            $result[] = $this->makeRow($row);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        if (!\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        $model_info = $id ? $this->opportunities_model->get_one($id) : (object) array(
            'id' => 0,
            'title' => '',
            'description' => '',
            'public_body' => '',
            'edital_number' => '',
            'process_number' => '',
            'modality' => '',
            'object' => '',
            'submission_deadline' => '',
            'publication_date' => '',
            'opening_date' => '',
            'status' => 'new',
            'ai_status' => 'pending',
            'source_id' => '',
            'responsible_user_id' => '',
            'jurisdiction' => '',
            'city' => '',
            'state' => '',
            'estimated_value' => '',
            'document_url' => '',
            'original_link' => '',
            'notes' => '',
        );

        return $this->template->view('LicitaIA\\Views\\opportunities\\modal_form', array(
            'model_info' => $model_info,
            'sources_dropdown' => $this->sources_model->get_active_dropdown(true),
            'responsible_dropdown' => $this->getStaffDropdown(),
            'status_dropdown' => $this->statusDropdown(),
        ));
    }

    public function view($id = 0)
    {
        $this->ensureAccess();
        $id = (int) $id;
        if (!$id) {
            show_404();
        }

        $opportunity = $this->opportunities_model->get_details(array('id' => $id))->getRow();
        if (!$opportunity) {
            show_404();
        }

        return $this->template->rander('LicitaIA\\Views\\opportunities\\view', array(
            'opportunity' => $opportunity,
            'documents' => $this->documentsModel()->get_by_opportunity($id)->getResult(),
            'checklist_items' => $this->opportunity_checklist_model->get_by_opportunity($id)->getResult(),
            'checklist_progress' => $this->opportunity_checklist_model->get_progress($id),
            'checklist_documents_dropdown' => $this->getOpportunityDocumentsDropdown($id),
            'tasks' => $this->getOpportunityTasks($id),
            'ai_logs' => $this->aiLogModel()->get_all_where(array('opportunity_id' => $id, 'deleted' => 0), 20, 0, 'id', 'id, provider, model_name, request_type, status, created_at')->getResult(),
            'latest_report' => $this->reports_model->get_latest_by_opportunity($id, 'technical_opinion'),
            'status_dropdown' => $this->statusDropdown(),
            'responsible_dropdown' => $this->getStaffDropdown(),
            'can_manage' => \LicitaIA\Plugin::canManageOpportunities($this->login_user),
            'can_create_tasks' => $this->canCreateNativeTask(),
        ));
    }

    public function upload_document($opportunity_id = 0)
    {
        if (!\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            return $this->respondDocumentMessage(false, app_lang('forbidden'), 0);
        }

        $opportunity_id = (int) $opportunity_id;
        if (!$opportunity_id) {
            return $this->respondDocumentMessage(false, app_lang('error_occurred'), 0);
        }

        $opportunity = $this->opportunities_model->get_details(array('id' => $opportunity_id))->getRow();
        if (!$opportunity) {
            return $this->respondDocumentMessage(false, app_lang('error_occurred'), 0);
        }

        $uploaded_file = $this->request->getFile('document_file');
        if (!$uploaded_file || !$uploaded_file->isValid() || $uploaded_file->hasMoved()) {
            return $this->respondDocumentMessage(false, app_lang('error_occurred'), $opportunity_id);
        }

        $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp');
        $extension = strtolower((string) $uploaded_file->getClientExtension());
        if (!$extension || !in_array($extension, $allowed_extensions, true)) {
            return $this->respondDocumentMessage(false, app_lang('licitaia_document_type_not_allowed'), $opportunity_id);
        }

        $storage = $this->getDocumentStorageInfo($opportunity_id);
        if (!$storage['ok']) {
            return $this->respondDocumentMessage(false, $storage['message'], $opportunity_id);
        }

        $original_file_name = (string) $uploaded_file->getClientName();
        $stored_file_name = 'licitaia_' . date('YmdHis') . '_' . uniqid('', true) . '_' . $this->normalizeFileName($original_file_name);
        $destination = $storage['absolute_path'] . $stored_file_name;

        try {
            $uploaded_file->move($storage['absolute_path'], $stored_file_name, true);
        } catch (\Throwable $e) {
            return $this->respondDocumentMessage(false, app_lang('error_occurred'), $opportunity_id);
        }

        if (!is_file($destination)) {
            return $this->respondDocumentMessage(false, app_lang('error_occurred'), $opportunity_id);
        }

        $document_data = array(
            'opportunity_id' => $opportunity_id,
            'file_name' => $stored_file_name,
            'original_file_name' => $original_file_name,
            'file_path' => $stored_file_name,
            'mime_type' => (string) ($uploaded_file->getClientMimeType() ?: $uploaded_file->getMimeType()),
            'file_size' => (int) $uploaded_file->getSize(),
            'source_url' => trim((string) ($opportunity->original_link ?: $opportunity->document_url ?: '')),
            'extracted_text' => null,
            'status' => 'uploaded',
            'created_by' => (int) $this->login_user->id,
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
            'deleted' => 0,
        );

        $document_id = $this->documentsModel()->ci_save($document_data, 0);
        if (!$document_id) {
            @unlink($destination);
            return $this->respondDocumentMessage(false, app_lang('error_occurred'), $opportunity_id);
        }

        $extract_result = $this->extractDocumentText($destination, $extension);
        if (!empty($extract_result['success']) && trim((string) ($extract_result['text'] ?? '')) !== '') {
            $this->documentsModel()->save_extracted_text($document_id, $extract_result['text']);
        } else {
            $document_update = array(
                'status' => !empty($extract_result['needs_ocr']) ? 'pending_extraction' : 'uploaded',
                'updated_at' => get_my_local_time(),
            );
            $this->documentsModel()->ci_save($document_update, $document_id);
        }

        $message = !empty($extract_result['success'])
            ? app_lang('licitaia_document_uploaded_and_extracted')
            : (!empty($extract_result['needs_ocr']) ? app_lang('licitaia_document_uploaded_pending_ocr') : app_lang('licitaia_document_uploaded'));

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(array(
                'success' => true,
                'message' => $message,
            ));
        }

        return $this->respondDocumentMessage(true, $message, $opportunity_id);
    }

    public function delete_document($document_id = 0)
    {
        if (!\LicitaIA\Plugin::canDeleteRecords($this->login_user)) {
            return $this->respondDocumentMessage(false, app_lang('forbidden'), 0);
        }

        $document_id = (int) $document_id;
        if (!$document_id) {
            $document_id = (int) $this->request->getPost('id');
        }

        $document = $this->documentsModel()->get_one_with_details($document_id);
        if (!$document || !$document->id) {
            return $this->respondDocumentMessage(false, app_lang('error_occurred'), 0);
        }

        $storage = $this->getDocumentStorageInfo((int) $document->opportunity_id);
        $absolute_path = $storage['absolute_path'] . (string) $document->file_path;

        $ok = $this->documentsModel()->delete($document_id);
        if ($ok && is_file($absolute_path)) {
            @unlink($absolute_path);
        }

        if (!$this->request->isAJAX()) {
            return $this->respondDocumentMessage((bool) $ok, $ok ? app_lang('record_deleted') : app_lang('error_occurred'), (int) $document->opportunity_id);
        }

        return $this->response->setJSON(array(
            'success' => (bool) $ok,
            'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred'),
        ));
    }

    public function download_document($document_id = 0)
    {
        if (!\LicitaIA\Plugin::canViewOpportunities($this->login_user)) {
            app_redirect('forbidden');
        }

        $document_id = (int) $document_id;
        $document = $this->documentsModel()->get_one_with_details($document_id);
        if (!$document || !$document->id) {
            show_404();
        }

        $storage = $this->getDocumentStorageInfo((int) $document->opportunity_id);
        $absolute_path = $storage['absolute_path'] . (string) $document->file_path;
        if (!is_file($absolute_path)) {
            $source_url = trim((string) ($document->source_url ?? ''));
            if ($source_url !== '') {
                return redirect()->to($source_url);
            }

            show_404();
        }

        $download_name = $document->original_file_name ?: $document->file_name;
        return $this->response->download($absolute_path, null)->setFileName($download_name);
    }

    public function save()
    {
        if (!\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $title = trim((string) $this->request->getPost('title'));
        if ($title === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('licitaia_required_title')));
        }

        $original_link = trim((string) $this->request->getPost('original_link'));
        if ($original_link === '') {
            $original_link = trim((string) $this->request->getPost('document_url'));
        }

        $existing = $id ? $this->opportunities_model->get_details(array('id' => $id))->getRow() : null;

        $data = array(
            'title' => clean_data($title),
            'description' => clean_data($this->request->getPost('description')),
            'public_body' => clean_data($this->request->getPost('public_body')),
            'edital_number' => clean_data($this->request->getPost('edital_number')),
            'process_number' => clean_data($this->request->getPost('process_number')),
            'modality' => clean_data($this->request->getPost('modality')),
            'object' => clean_data($this->request->getPost('object')),
            'submission_deadline' => $this->normalizeDateInput($this->request->getPost('submission_deadline')),
            'publication_date' => $this->normalizeDateInput($this->request->getPost('publication_date')),
            'opening_date' => $this->normalizeDateInput($this->request->getPost('opening_date')),
            'status' => $this->normalizeStatus($this->request->getPost('status')),
            'ai_status' => trim((string) ($this->request->getPost('ai_status') ?: ($existing->ai_status ?? 'pending'))),
            'source_id' => (int) $this->request->getPost('source_id') ?: null,
            'responsible_user_id' => (int) $this->request->getPost('responsible_user_id') ?: null,
            'jurisdiction' => clean_data($this->request->getPost('jurisdiction')),
            'city' => clean_data($this->request->getPost('city')),
            'state' => clean_data($this->request->getPost('state')),
            'estimated_value' => (float) str_replace(',', '.', (string) $this->request->getPost('estimated_value')),
            'document_url' => clean_data($original_link),
            'original_link' => clean_data($original_link),
            'notes' => clean_data($this->request->getPost('notes')),
            'ai_summary' => clean_data($this->request->getPost('ai_summary')),
            'ai_risks' => clean_data($this->request->getPost('ai_risks')),
            'ai_requirements' => clean_data($this->request->getPost('ai_requirements')),
            'ai_recommendation' => clean_data($this->request->getPost('ai_recommendation')),
            'technical_score' => (float) str_replace(',', '.', (string) $this->request->getPost('technical_score')),
            'risk_level' => clean_data($this->request->getPost('risk_level')),
            'recommendation' => clean_data($this->request->getPost('recommendation')),
            'updated_at' => get_my_local_time(),
        );

        if (!$id) {
            $data['created_by'] = (int) $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->opportunities_model->ci_save($data, $id);
        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'id' => $save_id,
        ));
    }

    public function update_status()
    {
        if (!\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $status = $this->normalizeStatus($this->request->getPost('status'));
        $responsible_user_id = $this->request->getPost('responsible_user_id');
        $success = $this->opportunities_model->update_status($id, $status, $responsible_user_id);

        return $this->response->setJSON(array(
            'success' => (bool) $success,
            'message' => $success ? app_lang('record_saved') : app_lang('error_occurred'),
        ));
    }

    public function create_checklist()
    {
        if (!\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $created = $this->opportunity_checklist_model->create_default_checklist($id);

        return $this->response->setJSON(array(
            'success' => $created !== false,
            'message' => app_lang('record_saved'),
        ));
    }

    public function analyze_ai()
    {
        if (!\LicitaIA\Plugin::canUseAi($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $opportunity = $this->opportunities_model->get_details(array('id' => $id))->getRow();
        if (!$opportunity) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->aiLogModel()->log_request(array(
            'opportunity_id' => $id,
            'provider' => 'manual',
            'model_name' => 'pending',
            'request_type' => 'analysis',
            'request_json' => json_encode(array('title' => $opportunity->title, 'edital_number' => $opportunity->edital_number), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'processing',
            'created_by' => $this->login_user->id,
        ));

        $this->opportunities_model->update_ai_result($id, array(
            'ai_status' => 'processing',
            'ai_summary' => $opportunity->ai_summary ?? '',
            'ai_recommendation' => $opportunity->ai_recommendation ?? '',
        ));

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
        ));
    }

    public function generate_report()
    {
        if (!\LicitaIA\Plugin::canGenerateReport($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $opportunity = $this->opportunities_model->get_details(array('id' => $id))->getRow();
        if (!$opportunity) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $report_data = array(
            'opportunity_id' => $id,
            'report_type' => 'technical_opinion',
            'title' => 'Parecer técnico - ' . $opportunity->title,
            'generated_at' => get_my_local_time(),
            'created_by' => $this->login_user->id,
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
            'deleted' => 0,
        );
        $this->reports_model->ci_save($report_data, 0);

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
        ));
    }

    public function create_task($opportunity_id = 0, $task_type = '')
    {
        if (!$this->canCreateNativeTask()) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $opportunity_id = (int) $opportunity_id;
        $task_type = strtolower(trim((string) $task_type));
        $opportunity = $this->opportunities_model->get_details(array('id' => $opportunity_id))->getRow();
        if (!$opportunity) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $reference = $this->buildOpportunityReference($opportunity);
        $opportunity_link = $this->getOpportunityLink($opportunity_id);

        $task_templates = array(
            'analysis' => array(
                'title' => 'Analisar edital ' . $reference,
                'description' => "Revisar integralmente o edital e apontar viabilidade, riscos e oportunidades.\n\nOportunidade: " . $opportunity_link,
            ),
            'documentation' => array(
                'title' => 'Separar documentos ' . $reference,
                'description' => "Separar e conferir a documentacao exigida no edital.\n\nOportunidade: " . $opportunity_link,
            ),
            'proposal' => array(
                'title' => 'Montar proposta comercial ' . $reference,
                'description' => "Preparar proposta comercial, precificacao e anexos da oportunidade.\n\nOportunidade: " . $opportunity_link,
            ),
            'session' => array(
                'title' => 'Acompanhar sessao ' . $reference,
                'description' => "Monitorar a sessao publica, lances e comunicados vinculados ao edital.\n\nOportunidade: " . $opportunity_link,
            ),
        );

        if (!isset($task_templates[$task_type])) {
            $task_type = 'analysis';
        }

        $status_id = $this->getDefaultTaskStatusId();
        $assigned_to = (int) ($opportunity->responsible_user_id ?: $this->login_user->id);
        $deadline = $this->normalizeTaskDate($opportunity->submission_deadline ?: $opportunity->opening_date ?: $opportunity->publication_date);

        $task_data = array(
            'title' => $task_templates[$task_type]['title'],
            'description' => $task_templates[$task_type]['description'],
            'project_id' => 0,
            'milestone_id' => 0,
            'points' => 0,
            'status_id' => $status_id,
            'client_id' => 0,
            'lead_id' => 0,
            'invoice_id' => 0,
            'estimate_id' => 0,
            'order_id' => 0,
            'contract_id' => 0,
            'proposal_id' => 0,
            'expense_id' => 0,
            'subscription_id' => 0,
            'priority_id' => 0,
            'labels' => '',
            'start_date' => get_my_local_time('Y-m-d'),
            'deadline' => $deadline,
            'recurring' => 0,
            'repeat_every' => 0,
            'repeat_type' => null,
            'no_of_cycles' => 0,
            'assigned_to' => $assigned_to,
            'collaborators' => '',
            'context' => 'general',
            'created_date' => get_my_local_time(),
            'created_by' => $this->login_user->id,
            'opportunity_id' => $opportunity_id,
        );
        $task_id = $this->tasksModel()->ci_save($task_data, 0);

        if (!$task_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'task_id' => (int) $task_id,
            'redirect_url' => get_uri('tasks/view/' . (int) $task_id),
        ));
    }

    public function delete()
    {
        if (!\LicitaIA\Plugin::canDeleteRecords($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $success = $id > 0 ? $this->opportunities_model->delete($id) : false;

        return $this->response->setJSON(array(
            'success' => (bool) $success,
            'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred'),
        ));
    }

    private function canCreateNativeTask()
    {
        if (!$this->login_user || $this->login_user->user_type !== 'staff') {
            return false;
        }

        if (!empty($this->login_user->is_admin)) {
            return true;
        }

        return get_array_value($this->login_user->permissions, 'can_create_tasks') == '1';
    }

    private function tasksModel()
    {
        return model('App\\Models\\Tasks_model');
    }

    private function getDefaultTaskStatusId()
    {
        try {
            $status_model = model('App\\Models\\Task_status_model');
            foreach (array('to_do', 'todo', 'open', 'new') as $key) {
                $status = $status_model->get_one_where(array('key_name' => $key, 'deleted' => 0));
                if (!empty($status->id)) {
                    return (int) $status->id;
                }
            }

            $first = $status_model->get_details(array('hide_from_non_project_related_tasks' => 0))->getRow();
            if (!empty($first->id)) {
                return (int) $first->id;
            }
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA] Unable to resolve default task status: ' . $e->getMessage());
        }

        return 1;
    }

    private function normalizeTaskDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return get_my_local_time('Y-m-d');
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return get_my_local_time('Y-m-d');
    }

    private function normalizeDateInput($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $formats = array_unique(array_filter(array(
            get_setting('date_format') ?: 'Y-m-d',
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'Y.m.d',
        )));

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : '';
    }

    private function buildOpportunityReference($opportunity)
    {
        $reference = trim((string) ($opportunity->edital_number ?: $opportunity->notice_number ?: $opportunity->process_number ?: ''));
        return $reference !== '' ? $reference : ('#' . (int) $opportunity->id);
    }

    private function getOpportunityLink($opportunity_id)
    {
        return get_uri('licitaia/opportunities/view/' . (int) $opportunity_id);
    }

    private function makeRow($data)
    {
        $title = anchor(get_uri('licitaia/opportunities/view/' . (int) $data->id), esc($data->title ?: '-'), array('class' => 'text-default fw-semibold'));

        $status_badges = array(
            'new' => 'info',
            'analyzing' => 'warning',
            'waiting_decision' => 'secondary',
            'participate' => 'success',
            'not_participate' => 'dark',
            'proposal_in_progress' => 'primary',
            'sent' => 'primary',
            'won' => 'success',
            'lost' => 'danger',
            'canceled' => 'secondary',
        );

        $options = '';
        if (\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            $options .= anchor(get_uri('licitaia/opportunities/view/' . (int) $data->id), "<i data-feather='eye' class='icon-16'></i>", array('class' => 'action-icon', 'title' => app_lang('view')));
            $options .= modal_anchor(get_uri('licitaia/opportunities/modal_form/' . (int) $data->id), "<i data-feather='edit' class='icon-16'></i>", array('class' => 'action-icon', 'title' => app_lang('edit')));
            $options .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => (int) $data->id, 'data-action-url' => get_uri('licitaia/opportunities/delete'), 'data-action' => 'delete-confirmation'));
        }

        return array(
            $title,
            esc($data->public_body ?: '-'),
            esc($data->edital_number ?: '-'),
            esc($data->process_number ?: '-'),
            esc($data->modality ?: '-'),
            esc($data->source_name ?: '-'),
            '<span class="badge bg-' . esc(get_array_value($status_badges, $data->status, 'secondary')) . '">' . esc(app_lang('licitaia_status_' . $data->status)) . '</span>',
            esc($data->responsible_name ?: '-'),
            esc(!empty($data->submission_deadline) ? format_to_date($data->submission_deadline, false) : '-'),
            $options,
        );
    }

    private function getStaffDropdown()
    {
        try {
            $users_model = model('App\\Models\\Users_model');
            return $users_model->get_dropdown_list_with_blank_option(array('first_name', 'last_name'), '-', array('deleted' => 0, 'status' => 'active', 'user_type' => 'staff'));
        } catch (\Throwable $e) {
            return array('' => '-');
        }
    }

    private function normalizeStatus($status)
    {
        $status = trim((string) $status);
        if ($status === 'qualified') {
            $status = 'won';
        } elseif ($status === 'ignored') {
            $status = 'not_participate';
        }

        $allowed = array_keys($this->statusDropdown());
        if (!in_array($status, $allowed, true)) {
            return 'new';
        }

        return $status ?: 'new';
    }

    private function documentsModel()
    {
        return model(\LicitaIA\Models\Document_model::class);
    }

    private function getOpportunityDocumentsDropdown($opportunity_id)
    {
        $dropdown = array('' => '-');
        $documents = $this->documentsModel()->get_by_opportunity((int) $opportunity_id)->getResult();
        foreach ($documents as $document) {
            $label = $document->original_file_name ?: $document->file_name ?: ('#' . (int) $document->id);
            $dropdown[$document->id] = $label;
        }

        return $dropdown;
    }

    private function getOpportunityTasks($opportunity_id)
    {
        $opportunity_id = (int) $opportunity_id;
        if (!$opportunity_id) {
            return array();
        }

        $db = db_connect('default');
        $tasks_table = $db->prefixTable('tasks');
        $users_table = $db->prefixTable('users');
        $task_status_table = $db->prefixTable('task_status');

        $sql = "SELECT t.id, t.title, t.description, t.deadline, t.start_date, t.assigned_to, t.status_id, t.created_date,
                    CONCAT(IFNULL(u.first_name, ''), ' ', IFNULL(u.last_name, '')) AS assigned_to_name,
                    ts.title AS status_title
                FROM {$tasks_table} t
                LEFT JOIN {$users_table} u ON u.id = t.assigned_to
                LEFT JOIN {$task_status_table} ts ON ts.id = t.status_id
                WHERE t.deleted = 0 AND t.opportunity_id = {$opportunity_id}
                ORDER BY t.deadline IS NULL, t.deadline ASC, t.id DESC";

        return $db->query($sql)->getResult();
    }

    private function aiLogModel()
    {
        return model(\LicitaIA\Models\Ai_log_model::class);
    }

    private function getDocumentStorageInfo($opportunity_id)
    {
        $opportunity_id = (int) $opportunity_id;
        $base_path = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'licitaia' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'opportunity_' . $opportunity_id . DIRECTORY_SEPARATOR;

        if ($opportunity_id > 0 && !is_dir($base_path) && !@mkdir($base_path, 0755, true)) {
            return array(
                'ok' => false,
                'message' => app_lang('error_occurred'),
                'absolute_path' => $base_path,
            );
        }

        return array(
            'ok' => $opportunity_id > 0,
            'message' => $opportunity_id > 0 ? '' : app_lang('error_occurred'),
            'absolute_path' => $base_path,
        );
    }

    private function normalizeFileName($file_name)
    {
        $file_name = (string) $file_name;
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $base_name = pathinfo($file_name, PATHINFO_FILENAME);

        $base_name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base_name);
        $base_name = trim((string) $base_name, '-_.');
        if ($base_name === '') {
            $base_name = 'document';
        }

        if ($extension !== '') {
            $extension = preg_replace('/[^A-Za-z0-9]+/', '', $extension);
            return $base_name . '.' . strtolower($extension);
        }

        return $base_name;
    }

    private function extractDocumentText($file_path, $extension)
    {
        try {
            $extractor = new \LicitaIA\Libraries\Document_extractor();
            return $extractor->extract_text($file_path, $extension);
        } catch (\Throwable $e) {
            return array(
                'success' => false,
                'text' => '',
                'message' => $e->getMessage(),
                'needs_ocr' => in_array($extension, array('pdf', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'), true),
            );
        }
    }

    private function respondDocumentMessage($success, $message, $opportunity_id = 0)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(array(
                'success' => (bool) $success,
                'message' => $message,
            ));
        }

        session()->setFlashdata($success ? 'success_message' : 'error_message', $message);

        if ($opportunity_id > 0) {
            return app_redirect('licitaia/opportunities/view/' . (int) $opportunity_id);
        }

        return app_redirect('licitaia/opportunities');
    }
}
