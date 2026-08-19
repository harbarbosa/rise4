<?php

namespace LaudosTecnicos\Controllers;

class Laudos extends LaudosTecnicos_Base_Controller
{
    public function index($context_type = '', $context_id = 0)
    {
        $this->ensureLaudosAccess();

        $context_type = trim((string) $context_type);
        $context_id = (int) $context_id;
        $view_data = $this->get_index_view_data($context_type, $context_id);

        return $this->template->rander('LaudosTecnicos\\Views\\laudos\\index', $view_data);
    }

    public function context_tab($context_type = '', $context_id = 0)
    {
        $this->ensureLaudosAccess();

        $context_type = trim((string) $context_type);
        $context_id = (int) $context_id;
        $view_data = $this->get_index_view_data($context_type, $context_id);

        return $this->template->view('LaudosTecnicos\\Views\\laudos\\context', $view_data);
    }

    public function list_data($context_type = '', $context_id = 0)
    {
        $this->ensureLaudosAccess();

        $context_type = trim((string) $context_type);
        $context_id = (int) $context_id;

        $options = array(
            'search' => trim((string) $this->request->getPost('search')),
            'client_id' => (int) $this->request->getPost('client_id'),
            'contact_id' => (int) $this->request->getPost('contact_id'),
            'project_id' => (int) $this->request->getPost('project_id'),
            'type_id' => (int) $this->request->getPost('type_id'),
            'category_id' => (int) $this->request->getPost('category_id'),
            'responsible_id' => (int) $this->request->getPost('responsible_id'),
            'commercial_responsible_id' => (int) $this->request->getPost('commercial_responsible_id'),
            'reviewer_id' => (int) $this->request->getPost('reviewer_id'),
            'approver_id' => (int) $this->request->getPost('approver_id'),
            'status' => trim((string) $this->request->getPost('status')),
            'priority' => trim((string) $this->request->getPost('priority')),
            'unit_name' => trim((string) $this->request->getPost('unit_name')),
            'request_start_date' => trim((string) $this->request->getPost('request_start_date')),
            'request_end_date' => trim((string) $this->request->getPost('request_end_date')),
            'validity_start_date' => trim((string) $this->request->getPost('validity_start_date')),
            'validity_end_date' => trim((string) $this->request->getPost('validity_end_date')),
        );

        if ($context_type === 'client' && $context_id) {
            $options['client_id'] = $context_id;
        }

        if ($context_type === 'project' && $context_id) {
            $options['project_id'] = $context_id;
        }

        $rows = $this->laudos_model->get_details($options)->getResult();
        $result = array();
        foreach ($rows as $row) {
            $result[] = $this->makeTableRow($row);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureLaudosAccess();

        $id = (int) $id;
        $model_info = $id ? $this->laudos_model->get_one_with_details($id) : null;

        if (!$model_info) {
            $model_info = (object) array(
                'id' => '',
                'number' => '',
                'custom_code' => '',
                'revision' => '00',
                'title' => '',
                'type_id' => '',
                'category_id' => '',
                'client_id' => $this->request->getPost('client_id') ?: '',
                'project_id' => $this->request->getPost('project_id') ?: '',
                'task_id' => $this->request->getPost('task_id') ?: '',
                'contact_id' => $this->request->getPost('contact_id') ?: '',
                'contract_id' => $this->request->getPost('contract_id') ?: '',
                'proposal_id' => $this->request->getPost('proposal_id') ?: '',
                'service_order_id' => '',
                'unit_name' => '',
                'address' => '',
                'inspection_location' => '',
                'priority' => get_array_value(laudostecnicos_default_settings(), 'default_priority'),
                'status' => get_array_value(laudostecnicos_default_settings(), 'default_status'),
                'request_date' => '',
                'scheduled_date' => '',
                'visit_date' => '',
                'inspection_date' => '',
                'issue_date' => '',
                'validity_date' => '',
                'commercial_responsible_id' => '',
                'inspection_team' => '',
                'technical_responsible_id' => '',
                'reviewer_id' => '',
                'approver_id' => '',
                'objective' => '',
                'scope' => '',
                'methodology' => '',
                'premises' => '',
                'limitations' => '',
                'installation_description' => '',
                'results' => '',
                'diagnosis' => '',
                'conclusion' => '',
                'recommendations' => '',
                'internal_notes' => '',
                'tags' => '',
                'cost_center' => '',
                'proposal_number' => '',
                'contract_number' => '',
                'external_reference' => '',
                'confidentiality' => '',
                'client_observations' => '',
                'template_id' => '',
                'is_template_based' => 0,
                'deleted' => 0,
            );
        }

        $view_data = $this->get_form_view_data($model_info);
        return $this->template->view('LaudosTecnicos\\Views\\laudos\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureLaudosAccess();

        $id = (int) $this->request->getPost('id');
        if ($id) {
            if (!\LaudosTecnicos\Plugin::canEditLaudos($this->login_user)) {
                app_redirect('forbidden');
            }
        } else {
            if (!\LaudosTecnicos\Plugin::canCreateLaudos($this->login_user)) {
                app_redirect('forbidden');
            }
        }

        $data = $this->collectFormPayload();
        $data['updated_by'] = (int) ($this->login_user->id ?? 0);

        if (!$id) {
            $data['created_by'] = (int) ($this->login_user->id ?? 0);
        }

        $saved_id = $this->laudos_model->save_from_post($data, $id ?: null);
        if (!$saved_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $laudo_id = $id ?: (is_numeric($saved_id) ? (int) $saved_id : 0);
        $this->logAudit('laudo', $laudo_id, $id ? 'update' : 'create', 'Laudo salvo', array(), $data);

        $row = $this->makeTableRow($this->laudos_model->get_one_with_details($laudo_id));

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'id' => $laudo_id,
            'data' => $row,
            'redirect_to' => get_uri('laudostecnicos/laudos/view/' . $laudo_id),
        ));
    }

    public function view($id = 0)
    {
        $this->ensureLaudosAccess();

        $id = (int) $id;
        $model_info = $this->laudos_model->get_one_with_details($id);
        if (!$model_info || !$model_info->id) {
            show_404();
        }

        $view_data = $this->get_detail_view_data($model_info);
        return $this->template->rander('LaudosTecnicos\\Views\\laudos\\details', $view_data);
    }

    public function duplicate_modal_form($id = 0)
    {
        $this->ensureLaudosAccess();

        $view_data['model_info'] = $this->laudos_model->get_one_with_details((int) $id);
        if (!$view_data['model_info'] || !$view_data['model_info']->id) {
            show_404();
        }

        $view_data['copy_options'] = array(
            'copy_general' => 1,
            'copy_content' => 1,
            'copy_template' => 1,
            'copy_team' => 1,
            'copy_checklists' => 0,
            'copy_norms' => 0,
            'copy_equipments' => 0,
            'copy_photos' => 0,
        );

        return $this->template->view('LaudosTecnicos\\Views\\laudos\\duplicate_modal_form', $view_data);
    }

    public function duplicate()
    {
        $this->ensureLaudosAccess();

        $source_id = (int) $this->request->getPost('source_id');
        if (!$source_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $options = array(
            'copy_general' => $this->request->getPost('copy_general') ? 1 : 0,
            'copy_content' => $this->request->getPost('copy_content') ? 1 : 0,
            'copy_template' => $this->request->getPost('copy_template') ? 1 : 0,
            'copy_team' => $this->request->getPost('copy_team') ? 1 : 0,
            'copy_checklists' => $this->request->getPost('copy_checklists') ? 1 : 0,
            'copy_norms' => $this->request->getPost('copy_norms') ? 1 : 0,
            'copy_equipments' => $this->request->getPost('copy_equipments') ? 1 : 0,
            'copy_photos' => $this->request->getPost('copy_photos') ? 1 : 0,
            'created_by' => (int) ($this->login_user->id ?? 0),
        );

        $new_id = $this->laudos_model->duplicate($source_id, $options);
        if (!$new_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->logAudit('laudo', (int) $new_id, 'duplicate', 'Laudo duplicado a partir do #' . $source_id, array(), $options);

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'id' => (int) $new_id,
            'redirect_to' => get_uri('laudostecnicos/laudos/view/' . (int) $new_id),
        ));
    }

    public function change_status($id = 0)
    {
        $this->ensureLaudosAccess();
        if (!\LaudosTecnicos\Plugin::canChangeStatus($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $id;
        $to_status_code = trim((string) $this->request->getPost('to_status_code'));
        $comment = trim((string) $this->request->getPost('comment'));
        $source = trim((string) $this->request->getPost('source')) ?: 'web';

        if ($id <= 0 || $to_status_code === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $ok = $this->laudos_model->change_status($id, $to_status_code, (int) ($this->login_user->id ?? 0), $comment, $source);
        if (!$ok) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Transicao de status invalida.'));
        }

        $this->logAudit('laudo', $id, 'change_status', 'Status alterado para ' . $to_status_code, array(), array(
            'to_status_code' => $to_status_code,
            'comment' => $comment,
            'source' => $source,
        ));

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function delete()
    {
        $this->ensureLaudosAccess();

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $laudo = $this->laudos_model->get_one($id);
        if (!$laudo || !$laudo->id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        if (($laudo->status ?? '') !== 'draft' && !\LaudosTecnicos\Plugin::canDeleteDrafts($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Somente rascunhos podem ser excluidos.'));
        }

        if (($laudo->status ?? '') !== 'draft') {
            return $this->response->setJSON(array('success' => false, 'message' => 'Laudos emitidos ou em fluxo nao podem ser excluidos.'));
        }

        $ok = $this->laudos_model->delete($id);
        if (!$ok) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->logAudit('laudo', $id, 'delete', 'Laudo excluido logicamente', array('status' => $laudo->status ?? ''), array());

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
    }

    public function document($id = 0, $variant = 'full')
    {
        $this->ensureLaudosAccess();

        $id = (int) $id;
        $variant = laudostecnicos_normalize_document_variant((string) $variant);
        $model_info = $this->laudos_model->get_one_with_details($id);
        if (!$model_info || !$model_info->id) {
            show_404();
        }

        $document_version = $this->documents_model->get_latest_version($id);
        $view_data = $this->buildDocumentViewData($model_info, $variant, $document_version);
        $view_data['is_print_mode'] = false;
        $view_data['public_mode'] = false;

        return $this->template->rander('LaudosTecnicos\\Views\\laudos\\document', $view_data);
    }

    public function download_pdf($id = 0, $variant = 'full')
    {
        $this->ensureLaudosAccess();

        $id = (int) $id;
        $variant = laudostecnicos_normalize_document_variant((string) $variant);
        $model_info = $this->laudos_model->get_one_with_details($id);
        if (!$model_info || !$model_info->id) {
            show_404();
        }

        $document_version = $this->emitDocumentVersion($model_info, $variant, false);
        if (!$document_version) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $file_path = trim((string) ($document_version->pdf_path ?? ''));
        if (!$file_path || !is_file($file_path)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $this->documents_model->log_access(array(
            'laudo_id' => $id,
            'document_version_id' => (int) $document_version->id,
            'event_type' => 'download',
            'document_variant' => $variant,
            'downloaded' => 1,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_by' => (int) ($this->login_user->id ?? 0),
        ));

        log_notification('laudo_document_downloaded', array(
            'laudo_id' => $id,
            'document_version_id' => (int) $document_version->id,
            'document_variant' => $variant,
        ), (int) ($this->login_user->id ?? 0));

        $this->response->setHeader('Content-Type', 'application/pdf');
        $this->response->setHeader('Content-Disposition', 'inline; filename="' . basename($file_path) . '"');
        readfile($file_path);
        exit;
    }

    public function public_view($id = 0, $public_key = '')
    {
        $id = (int) $id;
        $public_key = trim((string) $public_key);
        if (!$id || $public_key === '') {
            show_404();
        }

        $model_info = $this->laudos_model->get_one_with_details($id);
        if (!$model_info || !$model_info->id) {
            show_404();
        }

        $document_version = $this->documents_model->get_version_by_key($id, $public_key);
        if (!$document_version || !$document_version->id) {
            show_404();
        }

        $share = $this->documents_model->get_share_by_token((string) ($document_version->share_token ?? ''));
        $view_data = $this->buildDocumentViewData($model_info, (string) ($document_version->variant ?? 'full'), $document_version);
        $view_data['public_mode'] = true;
        $view_data['public_key'] = $public_key;
        $view_data['share'] = $share;
        $view_data['auth_url'] = laudostecnicos_public_validation_url($id, $public_key);

        $this->documents_model->log_access(array(
            'laudo_id' => $id,
            'document_version_id' => (int) $document_version->id,
            'share_id' => (int) ($share->id ?? 0),
            'event_type' => 'view',
            'document_variant' => (string) ($document_version->variant ?? 'full'),
            'visitor_label' => (string) ($share->visitor_label ?? 'public'),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
        ));

        log_notification('laudo_document_viewed', array(
            'laudo_id' => $id,
            'document_version_id' => (int) $document_version->id,
            'share_token' => (string) ($document_version->share_token ?? ''),
        ), 0);

        return $this->template->rander('LaudosTecnicos\\Views\\laudos\\public_validation', $view_data);
    }

    public function share_modal_form($id = 0)
    {
        $this->ensureLaudosAccess();

        $model_info = $this->laudos_model->get_one_with_details((int) $id);
        if (!$model_info || !$model_info->id) {
            show_404();
        }

        $document_version = $this->documents_model->get_latest_version((int) $id);
        $share = $document_version ? $this->documents_model->get_share_by_token((string) ($document_version->share_token ?? '')) : null;

        return $this->template->view('LaudosTecnicos\\Views\\laudos\\share_modal_form', array(
            'model_info' => $model_info,
            'document_version' => $document_version,
            'share' => $share,
        ));
    }

    public function share_save($id = 0)
    {
        $this->ensureLaudosAccess();

        $id = (int) $id;
        $model_info = $this->laudos_model->get_one_with_details($id);
        if (!$model_info || !$model_info->id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $variant = laudostecnicos_normalize_document_variant((string) $this->request->getPost('variant'));
        $document_version = $this->emitDocumentVersion($model_info, $variant, false);
        if (!$document_version) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $password = trim((string) $this->request->getPost('password'));
        $payload = array(
            'laudo_id' => $id,
            'document_version_id' => (int) $document_version->id,
            'share_token' => laudostecnicos_generate_token(24),
            'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '',
            'visitor_label' => trim((string) $this->request->getPost('visitor_label')),
            'expires_at' => trim((string) $this->request->getPost('expires_at')),
            'max_accesses' => (int) $this->request->getPost('max_accesses'),
            'allow_download' => $this->request->getPost('allow_download') ? 1 : 0,
            'allow_comments' => $this->request->getPost('allow_comments') ? 1 : 0,
            'require_visitor_id' => $this->request->getPost('require_visitor_id') ? 1 : 0,
            'created_by' => (int) ($this->login_user->id ?? 0),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        $share_id = $this->documents_model->create_share_link($payload);
        if (!$share_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $share = $this->documents_model->get_share_by_token($payload['share_token']);
        $public_url = get_uri('laudostecnicos/laudos/share/' . $payload['share_token']);

        log_notification('laudo_document_sent', array(
            'laudo_id' => $id,
            'document_version_id' => (int) $document_version->id,
            'share_token' => $payload['share_token'],
        ), (int) ($this->login_user->id ?? 0));

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'share_url' => $public_url,
            'share_token' => $payload['share_token'],
            'share_id' => $share_id,
            'document_code' => $document_version->document_code ?? '',
            'public_validation_url' => laudostecnicos_public_validation_url($id, (string) ($document_version->public_key ?? '')),
        ));
    }

    public function share_view($token = '')
    {
        $token = trim((string) $token);
        if ($token === '') {
            show_404();
        }

        $share = $this->documents_model->get_share_by_token($token);
        if (!$share || !$share->id) {
            show_404();
        }

        if (!$this->documents_model->share_access_available($share)) {
            show_404();
        }

        $document_version = $this->documents_model->get_version((int) $share->document_version_id);
        if (!$document_version || !$document_version->id) {
            show_404();
        }

        $model_info = $this->laudos_model->get_one_with_details((int) $share->laudo_id);
        if (!$model_info || !$model_info->id) {
            show_404();
        }

        $password = trim((string) $this->request->getPost('password'));
        $password_required = !empty($share->password_hash);
        $password_validated = true;
        $password_error = '';
        if ($password_required) {
            $password_validated = $password !== '' && password_verify($password, (string) $share->password_hash);
            if (!$password_validated) {
                $password_error = $password === '' ? 'Digite a senha para continuar.' : 'Senha invalida.';
            }
        }

        $view_data = $this->buildDocumentViewData($model_info, (string) ($document_version->variant ?? 'full'), $document_version);
        $view_data['public_mode'] = true;
        $view_data['share'] = $share;
        $view_data['share_token'] = $token;
        $view_data['auth_url'] = get_uri('laudostecnicos/laudos/share/' . $token);
        $view_data['password_required'] = $password_required;
        $view_data['password_validated'] = $password_validated;
        $view_data['password_error'] = $password_error;

        if ($password_required && !$password_validated) {
            return $this->template->rander('LaudosTecnicos\\Views\\laudos\\public_validation', $view_data);
        }

        if ($password_required) {
            session()->set('laudostecnicos_share_auth_' . (int) $share->id, time());
        }

        if (!$this->documents_model->consume_access((int) $share->id)) {
            show_404();
        }

        $this->documents_model->log_access(array(
            'laudo_id' => (int) $share->laudo_id,
            'document_version_id' => (int) $document_version->id,
            'share_id' => (int) $share->id,
            'visitor_label' => (string) ($share->visitor_label ?: 'shared-link'),
            'event_type' => 'view',
            'document_variant' => (string) ($document_version->variant ?? 'full'),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
        ));

        log_notification('laudo_document_viewed', array(
            'laudo_id' => (int) $share->laudo_id,
            'document_version_id' => (int) $document_version->id,
            'share_token' => $token,
        ), 0);

        return $this->template->rander('LaudosTecnicos\\Views\\laudos\\public_validation', $view_data);
    }

    public function share_download($token = '')
    {
        $token = trim((string) $token);
        if ($token === '') {
            show_404();
        }

        $share = $this->documents_model->get_share_by_token($token);
        if (!$share || !$share->id || !$share->allow_download || !$this->documents_model->share_access_available($share)) {
            show_404();
        }

        if (!empty($share->password_hash) && !session()->get('laudostecnicos_share_auth_' . (int) $share->id)) {
            app_redirect('laudostecnicos/laudos/share/' . rawurlencode($token));
        }

        $document_version = $this->documents_model->get_version((int) $share->document_version_id);
        if (!$document_version || !$document_version->id) {
            show_404();
        }

        $file_path = trim((string) ($document_version->pdf_path ?? ''));
        if (!$file_path || !is_file($file_path)) {
            show_404();
        }

        if (!$this->documents_model->consume_access((int) $share->id)) {
            show_404();
        }

        $this->documents_model->log_access(array(
            'laudo_id' => (int) $share->laudo_id,
            'document_version_id' => (int) $document_version->id,
            'share_id' => (int) $share->id,
            'visitor_label' => (string) ($share->visitor_label ?: 'shared-link'),
            'event_type' => 'download',
            'document_variant' => (string) ($document_version->variant ?? 'full'),
            'downloaded' => 1,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
        ));

        log_notification('laudo_document_downloaded', array(
            'laudo_id' => (int) $share->laudo_id,
            'document_version_id' => (int) $document_version->id,
            'share_token' => $token,
        ), 0);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . basename($file_path) . '"');
        readfile($file_path);
        exit;
    }

    public function portal()
    {
        if (!$this->login_user || $this->login_user->user_type !== 'client') {
            app_redirect('forbidden');
        }

        $documents = $this->documents_model->get_portal_documents_for_client((int) ($this->login_user->client_id ?? 0));

        return $this->template->rander('LaudosTecnicos\\Views\\laudos\\portal', array(
            'documents' => $documents,
            'login_user' => $this->login_user,
        ));
    }

    public function portal_feedback()
    {
        $token = trim((string) $this->request->getPost('share_token'));
        if ($token === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $share = $this->documents_model->get_share_by_token($token);
        if (!$share || !$share->id || !$this->documents_model->share_access_available($share) || empty($share->allow_comments)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $laudo = $this->laudos_model->get_one_with_details((int) $share->laudo_id);
        $is_owner_client = $this->login_user && $this->login_user->user_type === 'client'
            && (int) ($this->login_user->client_id ?? 0) === (int) ($laudo->client_id ?? 0);
        if (!empty($share->password_hash) && !$is_owner_client && !session()->get('laudostecnicos_share_auth_' . (int) $share->id)) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Autenticacao necessaria.'));
        }

        $action = trim((string) $this->request->getPost('action')) ?: 'comment';
        if (!in_array($action, array('comment', 'received', 'accept', 'reject'), true)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }
        $comment = trim((string) $this->request->getPost('comment'));
        $visitor_label = trim((string) $this->request->getPost('visitor_label')) ?: (string) ($share->visitor_label ?: '');
        $visitor_email = trim((string) $this->request->getPost('visitor_email'));
        if (!empty($share->require_visitor_id) && $visitor_label === '') {
            return $this->response->setJSON(array('success' => false, 'message' => 'Informe sua identificacao.'));
        }
        if ($visitor_email !== '' && !filter_var($visitor_email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Informe um e-mail valido.'));
        }
        $accepted = $action === 'accept' ? 1 : 0;
        $rejected = $action === 'reject' ? 1 : 0;
        $evidence_json = $this->request->getPost('evidence_json');
        if (!is_string($evidence_json) || trim($evidence_json) === '') {
            $evidence_json = '[]';
        }

        $saved = $this->documents_model->save_feedback(array(
            'laudo_id' => (int) $share->laudo_id,
            'document_version_id' => (int) $share->document_version_id,
            'share_id' => (int) $share->id,
            'action' => $action,
            'comment' => $comment,
            'evidence_json' => $evidence_json,
            'visitor_label' => $visitor_label,
            'visitor_email' => $visitor_email,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_by' => (int) ($this->login_user->id ?? 0),
        ));

        if (!$saved) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->documents_model->log_access(array(
            'laudo_id' => (int) $share->laudo_id,
            'document_version_id' => (int) $share->document_version_id,
            'share_id' => (int) $share->id,
            'visitor_label' => $visitor_label,
            'event_type' => $action,
            'document_variant' => 'portal',
            'commented' => $action === 'comment' ? 1 : 0,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_by' => (int) ($this->login_user->id ?? 0),
        ));

        $notification_event = 'laudo_document_feedback_added';
        if ($accepted) {
            $notification_event = 'laudo_document_accepted';
        } else if ($rejected) {
            $notification_event = 'laudo_document_rejected';
        }

        log_notification($notification_event, array(
            'laudo_id' => (int) $share->laudo_id,
            'document_version_id' => (int) $share->document_version_id,
            'share_token' => $token,
            'comment' => $comment,
        ), (int) ($this->login_user->id ?? 0));

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    private function get_index_view_data(string $context_type, int $context_id)
    {
        $clients_model = model('App\\Models\\Clients_model');
        $projects_model = model('App\\Models\\Projects_model');
        $users_model = model('App\\Models\\Users_model');

        $context_label = '';
        $context_anchor = '';
        if ($context_type === 'client' && $context_id) {
            $client = $clients_model->get_one($context_id);
            $context_label = $client && $client->id ? $client->company_name : '';
            $context_anchor = get_uri('clients/view/' . $context_id . '/laudos');
        } else if ($context_type === 'project' && $context_id) {
            $project = $projects_model->get_one($context_id);
            $context_label = $project && $project->id ? $project->title : '';
            $context_anchor = get_uri('projects/view/' . $context_id . '/laudos');
        }

        return array(
            'context_type' => $context_type,
            'context_id' => $context_id,
            'context_label' => $context_label,
            'context_anchor' => $context_anchor,
            'can_create_laudos' => \LaudosTecnicos\Plugin::canCreateLaudos($this->login_user),
            'can_edit_laudos' => \LaudosTecnicos\Plugin::canEditLaudos($this->login_user),
            'can_change_status' => \LaudosTecnicos\Plugin::canChangeStatus($this->login_user),
            'can_delete_drafts' => \LaudosTecnicos\Plugin::canDeleteDrafts($this->login_user),
            'clients_dropdown' => $clients_model->get_id_and_text_dropdown(array('company_name')),
            'projects_dropdown' => $projects_model->get_id_and_text_dropdown(array('title')),
            'contacts_dropdown' => $users_model->get_id_and_text_dropdown(array('first_name', 'last_name'), array('deleted' => 0, 'user_type' => 'client')),
            'team_members_dropdown' => $users_model->get_id_and_text_dropdown(array('first_name', 'last_name'), array('deleted' => 0, 'user_type' => 'staff')),
            'types_dropdown' => $this->types_model->get_active_dropdown(true),
            'categories_dropdown' => $this->categories_model->get_active_dropdown(true),
            'statuses_dropdown' => $this->statuses_model->get_dropdown(true),
            'templates_dropdown' => $this->templates_model->get_active_dropdown(true),
            'priority_dropdown' => array(
                '' => '-',
                'low' => 'Baixa',
                'normal' => 'Normal',
                'high' => 'Alta',
                'urgent' => 'Urgente',
            ),
            'units_dropdown' => $this->laudos_model->get_units_dropdown(true),
        );
    }

    private function get_form_view_data($model_info)
    {
        $view_data = $this->get_index_view_data('', 0);
        $view_data['model_info'] = $model_info;
        $view_data['clients_dropdown'] = model('App\\Models\\Clients_model')->get_id_and_text_dropdown(array('company_name'));
        $view_data['projects_dropdown'] = model('App\\Models\\Projects_model')->get_id_and_text_dropdown(array('title'));
        $view_data['contacts_dropdown'] = model('App\\Models\\Users_model')->get_id_and_text_dropdown(array('first_name', 'last_name'), array('deleted' => 0, 'user_type' => 'client'));
        $view_data['team_members_dropdown'] = model('App\\Models\\Users_model')->get_id_and_text_dropdown(array('first_name', 'last_name'), array('deleted' => 0, 'user_type' => 'staff'));
        $view_data['types_dropdown'] = $this->types_model->get_active_dropdown(true);
        $view_data['categories_dropdown'] = $this->categories_model->get_active_dropdown(true);
        $view_data['statuses_dropdown'] = $this->statuses_model->get_dropdown(true);
        $view_data['templates_dropdown'] = $this->templates_model->get_active_dropdown(true);
        $view_data['priority_dropdown'] = array(
            '' => '-',
            'low' => 'Baixa',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        );
        $view_data['units_dropdown'] = $this->laudos_model->get_units_dropdown(true);

        return $view_data;
    }

    private function get_detail_view_data($model_info)
    {
        $history = $this->status_history_model->get_details(array('laudo_id' => $model_info->id))->getResult();
        $audit_logs = $this->audit_logs_model->get_details(array('entity_type' => 'laudo', 'entity_id' => $model_info->id))->getResult();
        $allowed_transitions = $this->transitions_model->get_allowed_transitions((string) ($model_info->status ?? ''));
        $document_version = $this->documents_model->get_latest_version((int) $model_info->id);

        return array(
            'model_info' => $model_info,
            'history_logs' => $history,
            'audit_logs' => $audit_logs,
            'allowed_transitions' => $allowed_transitions,
            'document_version' => $document_version,
            'can_edit_laudos' => \LaudosTecnicos\Plugin::canEditLaudos($this->login_user),
            'can_change_status' => \LaudosTecnicos\Plugin::canChangeStatus($this->login_user),
            'can_delete_drafts' => \LaudosTecnicos\Plugin::canDeleteDrafts($this->login_user),
        );
    }

    private function buildDocumentViewData($model_info, string $variant, $document_version = null)
    {
        $variant = laudostecnicos_normalize_document_variant($variant);
        $document_version = $document_version ?: (object) array();
        $settings = $this->settings_model->get_all_settings_with_defaults();
        $feedbacks = $document_version && !empty($document_version->id) ? $this->documents_model->get_feedbacks((int) $document_version->id) : array();
        $share = null;
        if ($document_version && !empty($document_version->share_token)) {
            $share = $this->documents_model->get_share_by_token((string) $document_version->share_token);
        }

        $safe_title = trim((string) ($model_info->title ?? 'Laudo'));
        $qr_payload = $document_version && !empty($document_version->public_key)
            ? laudostecnicos_public_validation_url((int) $model_info->id, (string) $document_version->public_key)
            : get_uri('laudostecnicos/laudos/view/' . (int) $model_info->id);

        return array(
            'model_info' => $model_info,
            'variant' => $variant,
            'variant_title' => get_array_value(laudostecnicos_document_variant_titles(), $variant) ?: 'Laudo completo',
            'settings' => $settings,
            'document_version' => $document_version,
            'public_mode' => false,
            'is_print_mode' => false,
            'share' => $share,
            'feedbacks' => $feedbacks,
            'qr_payload' => $qr_payload,
            'qr_svg_data_uri' => laudostecnicos_generate_qr_svg_data_uri($qr_payload),
            'client_logo_label' => trim((string) ($model_info->client_name ?? 'Cliente')),
            'document_code' => $document_version->document_code ?? laudostecnicos_build_document_code($model_info->number ?? '', $model_info->revision ?? '00', 1),
            'document_hash' => $document_version->document_hash ?? '',
            'document_url' => get_uri('laudostecnicos/laudos/document/' . (int) $model_info->id . '/' . $variant),
            'print_url' => get_uri('laudostecnicos/laudos/pdf/' . (int) $model_info->id . '/' . $variant),
            'share_url' => $share ? get_uri('laudostecnicos/laudos/share/' . (string) $share->share_token) : '',
            'public_validation_url' => $document_version && !empty($document_version->public_key) ? laudostecnicos_public_validation_url((int) $model_info->id, (string) $document_version->public_key) : '',
            'safe_title' => $safe_title,
        );
    }

    private function emitDocumentVersion($model_info, string $variant = 'full', bool $force_pdf = false)
    {
        $variant = laudostecnicos_normalize_document_variant($variant);
        if (!$model_info || empty($model_info->id)) {
            return false;
        }

        $existing = $this->documents_model->get_latest_version((int) $model_info->id);
        if ($existing && !$force_pdf && (string) ($existing->variant ?? '') === $variant && !empty($existing->pdf_path) && is_file($existing->pdf_path)) {
            return $existing;
        }

        $document_version = $this->documents_model->get_latest_version((int) $model_info->id);
        $version_number = $document_version && !empty($document_version->id) ? ((int) $document_version->id + 1) : 1;
        $settings = $this->settings_model->get_all_settings_with_defaults();
        $public_key = laudostecnicos_generate_token(24);
        $document_code = laudostecnicos_build_document_code($model_info->number ?? '', $model_info->revision ?? '00', $version_number);
        $view_data = $this->buildDocumentViewData($model_info, $variant, $existing ?: null);
        $html = laudostecnicos_render_document_html($view_data);
        $document_hash = sha1($html . '|' . $public_key . '|' . $variant . '|' . microtime(true));

        $file_name = 'laudo-' . (int) $model_info->id . '-' . $variant . '-' . $public_key . '.pdf';
        $file_path = laudostecnicos_document_storage_path() . $file_name;

        $pdf = new \App\Libraries\Pdf('laudo');
        $pdf->SetCreator('LaudosTecnicos');
        $pdf->SetAuthor($this->login_user->name ?? 'LaudosTecnicos');
        $pdf->SetTitle($document_code);
        $pdf->SetFont(trim((string) get_array_value($settings, 'pdf_font_family')) ?: 'helvetica', '', 10);
        $pdf->SetMargins(
            (int) (get_array_value($settings, 'pdf_margin_left') ?: 12),
            (int) (get_array_value($settings, 'pdf_margin_top') ?: 16),
            (int) (get_array_value($settings, 'pdf_margin_right') ?: 12)
        );
        $pdf->SetAutoPageBreak(true, (int) (get_array_value($settings, 'pdf_margin_bottom') ?: 14));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $orientation = strtoupper((string) (get_array_value($settings, 'pdf_orientation') ?: 'P')) === 'L' ? 'L' : 'P';
        $paper = strtoupper(trim((string) (get_array_value($settings, 'pdf_paper') ?: 'A4'))) ?: 'A4';
        $pdf->AddPage($orientation, $paper);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($file_path, 'F');

        $payload = array(
            'laudo_id' => (int) $model_info->id,
            'variant' => $variant,
            'document_code' => $document_code,
            'public_key' => $public_key,
            'document_hash' => $document_hash,
            'html_snapshot' => $html,
            'pdf_path' => $file_path,
            'pdf_file_name' => $file_name,
            'issued_at' => get_current_utc_time(),
            'issued_by' => (int) ($this->login_user->id ?? 0),
            'status_snapshot' => (string) ($model_info->status ?? ''),
            'revision_snapshot' => (string) ($model_info->revision ?? ''),
            'visibility' => 'public',
            'qr_payload' => laudostecnicos_public_validation_url((int) $model_info->id, $public_key),
            'share_token' => '',
            'share_expires_at' => null,
            'created_by' => (int) ($this->login_user->id ?? 0),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        $version_id = $this->documents_model->create_version($payload);
        if (!$version_id) {
            return false;
        }

        return $this->documents_model->get_version((int) $version_id);
    }

    private function collectFormPayload()
    {
        return array(
            'number' => trim((string) $this->request->getPost('number')),
            'custom_code' => trim((string) $this->request->getPost('custom_code')),
            'revision' => trim((string) $this->request->getPost('revision')),
            'title' => trim((string) $this->request->getPost('title')),
            'type_id' => (int) $this->request->getPost('type_id'),
            'category_id' => (int) $this->request->getPost('category_id'),
            'client_id' => (int) $this->request->getPost('client_id'),
            'project_id' => (int) $this->request->getPost('project_id'),
            'task_id' => (int) $this->request->getPost('task_id'),
            'contact_id' => (int) $this->request->getPost('contact_id'),
            'contract_id' => (int) $this->request->getPost('contract_id'),
            'proposal_id' => (int) $this->request->getPost('proposal_id'),
            'service_order_id' => (int) $this->request->getPost('service_order_id'),
            'unit_name' => trim((string) $this->request->getPost('unit_name')),
            'address' => trim((string) $this->request->getPost('address')),
            'inspection_location' => trim((string) $this->request->getPost('inspection_location')),
            'priority' => trim((string) $this->request->getPost('priority')),
            'status' => trim((string) $this->request->getPost('status')),
            'request_date' => trim((string) $this->request->getPost('request_date')),
            'scheduled_date' => trim((string) $this->request->getPost('scheduled_date')),
            'visit_date' => trim((string) $this->request->getPost('visit_date')),
            'inspection_date' => trim((string) $this->request->getPost('inspection_date')),
            'issue_date' => trim((string) $this->request->getPost('issue_date')),
            'validity_date' => trim((string) $this->request->getPost('validity_date')),
            'commercial_responsible_id' => (int) $this->request->getPost('commercial_responsible_id'),
            'inspection_team' => trim((string) $this->request->getPost('inspection_team')),
            'technical_responsible_id' => (int) $this->request->getPost('technical_responsible_id'),
            'reviewer_id' => (int) $this->request->getPost('reviewer_id'),
            'approver_id' => (int) $this->request->getPost('approver_id'),
            'objective' => trim((string) $this->request->getPost('objective')),
            'scope' => trim((string) $this->request->getPost('scope')),
            'methodology' => trim((string) $this->request->getPost('methodology')),
            'premises' => trim((string) $this->request->getPost('premises')),
            'limitations' => trim((string) $this->request->getPost('limitations')),
            'installation_description' => trim((string) $this->request->getPost('installation_description')),
            'results' => trim((string) $this->request->getPost('results')),
            'diagnosis' => trim((string) $this->request->getPost('diagnosis')),
            'conclusion' => trim((string) $this->request->getPost('conclusion')),
            'recommendations' => trim((string) $this->request->getPost('recommendations')),
            'internal_notes' => trim((string) $this->request->getPost('internal_notes')),
            'tags' => trim((string) $this->request->getPost('tags')),
            'cost_center' => trim((string) $this->request->getPost('cost_center')),
            'proposal_number' => trim((string) $this->request->getPost('proposal_number')),
            'contract_number' => trim((string) $this->request->getPost('contract_number')),
            'external_reference' => trim((string) $this->request->getPost('external_reference')),
            'confidentiality' => trim((string) $this->request->getPost('confidentiality')),
            'client_observations' => trim((string) $this->request->getPost('client_observations')),
            'template_id' => (int) $this->request->getPost('template_id'),
            'is_template_based' => $this->request->getPost('is_template_based') ? 1 : 0,
        );
    }

    private function makeTableRow($row)
    {
        if (!$row) {
            return array();
        }

        $status_badge = $this->renderStatusBadge((string) ($row->status ?? ''), (string) ($row->status_name ?? ''), (string) ($row->status_color ?? '#6c757d'), (string) ($row->status_icon ?? 'circle'));
        $title = anchor(get_uri('laudostecnicos/laudos/view/' . $row->id), esc($row->title ?: '-'), array('class' => 'fw-semibold'));

        $actions = modal_anchor(get_uri('laudostecnicos/laudos/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-secondary', 'title' => app_lang('edit')));
        $actions .= ' ' . modal_anchor(get_uri('laudostecnicos/laudos/duplicate_modal_form/' . $row->id), "<i data-feather='copy' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-primary', 'title' => 'Duplicar'));
        if (($row->status ?? '') === 'draft' && \LaudosTecnicos\Plugin::canDeleteDrafts($this->login_user)) {
            $actions .= ' ' . js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-danger delete', 'title' => app_lang('delete'), 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/laudos/delete'), 'data-action' => 'delete-confirmation'));
        }

        $responsible = trim((string) ($row->technical_responsible_name ?? ''));
        if ($responsible === '') {
            $responsible = trim((string) ($row->commercial_responsible_name ?? ''));
        }

        return array(
            $title,
            esc($row->revision ?: '00'),
            esc($row->client_name ?: '-'),
            esc($row->project_name ?: '-'),
            esc($row->type_name ?: '-'),
            esc($row->category_name ?: '-'),
            esc($responsible ?: '-'),
            esc($row->request_date ?: '-'),
            esc($row->inspection_date ?: '-'),
            esc($row->issue_date ?: '-'),
            esc($row->validity_date ?: '-'),
            $status_badge,
            esc($row->priority ?: '-'),
            $actions,
        );
    }

    private function renderStatusBadge(string $code, string $name, string $color, string $icon)
    {
        $label = $name !== '' ? $name : $code;
        $color = $color !== '' ? $color : '#6c757d';
        $icon_html = $icon !== '' ? "<i data-feather='" . esc($icon) . "' class='icon-14 me5'></i>" : '';

        return "<span class='badge text-white' style='background: " . esc($color) . ";'>" . $icon_html . esc($label ?: '-') . "</span>";
    }
}
