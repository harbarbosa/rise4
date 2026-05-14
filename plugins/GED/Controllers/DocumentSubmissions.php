<?php

namespace GED\Controllers;

class DocumentSubmissions extends GedBaseController
{
    private $Document_submissions_model;
    private $Documents_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct();
        $this->Document_submissions_model = model('GED\\Models\\Ged_document_submissions_model');
        $this->Documents_model = model('GED\\Models\\Ged_documents_model');
        $this->Users_model = model('App\\Models\\Users_model');
    }

    public function index()
    {
        if (!$this->_has_manage_submissions_permission()) {
            app_redirect('forbidden');
        }

        return $this->template->rander('GED\\Views\\submissions\\index', array(
            'can_manage' => $this->_has_manage_submissions_permission(),
            'can_create' => $this->_has_manage_submissions_permission(),
            'can_edit' => $this->_has_manage_submissions_permission(),
            'can_delete' => $this->_has_manage_submissions_permission(),
            'can_view_documents' => $this->_has_view_permission(),
            'documents_dropdown' => $this->_get_documents_dropdown(),
        ));
    }

    public function list_data()
    {
        if (!$this->_has_manage_submissions_permission()) {
            return $this->_json_permission_denied();
        }

        $items = $this->Document_submissions_model->get_details($this->_get_filters_from_request())->getResult();
        $result = array();
        foreach ($items as $item) {
            $result[] = $this->_make_row($item);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $id = (int) $id;
        if (!$id) {
            $id = (int) $this->request->getPost('id');
        }
        if ($id) {
            if (!$this->_has_manage_submissions_permission()) {
                app_redirect('forbidden');
            }
        } elseif (!$this->_has_manage_submissions_permission()) {
            app_redirect('forbidden');
        }

        $model_info = $id ? $this->Document_submissions_model->get_one_with_details($id) : (object) array(
            'id' => 0,
            'document_id' => 0,
            'submitted_at' => '',
            'notes' => '',
            'owner_type' => '',
            'employee_id' => 0,
            'document_ids' => array()
        );

        if ($id && $model_info) {
            $model_info->owner_type = (($model_info->document_owner_type ?? '') === 'employee') ? 'employee' : 'company';
            $model_info->employee_id = (int) ($model_info->document_employee_id ?? 0);
            $model_info->document_ids = array((int) ($model_info->document_id ?? 0));
        }

        $employees_dropdown = array();
        $available_documents_dropdown = array();
        if ($id) {
            $employees_dropdown = $this->Users_model->get_dropdown_list(array('first_name', 'last_name'), 'id', array(
                'deleted' => 0,
                'status' => 'active',
                'user_type' => 'staff'
            ));

            $documents_suggestion = $this->Documents_model->get_valid_documents_suggestion($model_info->owner_type, (int) ($model_info->employee_id ?? 0));
            $available_documents_dropdown = array();
            foreach ($documents_suggestion as $suggestion) {
                if (!empty($suggestion['id'])) {
                    $available_documents_dropdown[$suggestion['id']] = $suggestion['text'];
                }
            }
        }

        $view_data = array(
            'model_info' => $model_info,
            'is_edit_mode' => $id ? true : false,
            'employees_dropdown' => $employees_dropdown,
            'available_documents_dropdown' => $available_documents_dropdown,
            'owner_types_dropdown' => array(
                '' => '-',
                'company' => 'Empresa',
                'employee' => 'Funcionario'
            )
        );

        return $this->template->view('GED\\Views\\submissions\\modal_form', $view_data);
    }

    public function view($id = 0)
    {
        if (!$this->_has_manage_submissions_permission() && !$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $item = $this->Document_submissions_model->get_one_with_details((int) $id);
        if (!$item) {
            show_404();
        }

        return $this->template->rander('GED\\Views\\submissions\\view', array(
            'model_info' => $item,
            'can_edit' => $this->_has_manage_submissions_permission(),
            'can_delete' => $this->_has_manage_submissions_permission(),
            'can_view_documents' => $this->_can_view_linked_documents(),
            'document_status_meta' => $this->_get_document_status_meta($item),
            'irregular_meta' => $this->_get_irregular_meta($item),
        ));
    }

    public function save()
    {
        if (!$this->_has_manage_submissions_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int) $this->request->getPost('id');
        $submitted_at = trim((string) $this->request->getPost('submitted_at'));
        $is_edit_mode = $id > 0;

        if ($submitted_at !== '') {
            $submitted_at = str_replace('T', ' ', $submitted_at);
            if (strlen($submitted_at) === 16) {
                $submitted_at .= ':00';
            }
        }
        if ($is_edit_mode) {
            $document_ids = $this->request->getPost('document_ids');
            $document_ids = is_array($document_ids) ? array_values(array_unique(array_filter(array_map('intval', $document_ids)))) : array();
            if (!count($document_ids)) {
                $document_id = (int) $this->request->getPost('document_id');
                if ($document_id) {
                    $document_ids = array($document_id);
                }
            }

            $document_id = (int) get_array_value($document_ids, 0);
            $document = $this->Documents_model->get_one_with_details($document_id);
            if (!$document) {
                return $this->response->setJSON(array('success' => false, 'message' => 'Documento nao encontrado.'));
            }

            $data = array(
                'document_id' => $document_id,
                'submitted_at' => $submitted_at ?: null,
                'notes' => trim((string) $this->request->getPost('notes')),
                'updated_by' => $this->login_user->id,
                'updated_at' => get_my_local_time(),
            );

            $save_id = $this->Document_submissions_model->save_submission($data, $id);
            if (!$save_id) {
                return $this->response->setJSON(array('success' => false, 'message' => 'Nao foi possivel salvar o envio.'));
            }

            $row = $this->Document_submissions_model->get_one_with_details($save_id);
            return $this->response->setJSON(array(
                'success' => true,
                'data' => $this->_make_row($row),
                'id' => $save_id,
                'message' => 'Envio atualizado com sucesso.'
            ));
        }

        $owner_type = trim((string) $this->request->getPost('owner_type'));
        if (!in_array($owner_type, array('company', 'employee'), true)) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Selecione o tipo de proprietario.'));
        }

        $owner_id = 0;
        if ($owner_type === 'employee') {
            $owner_id = (int) $this->request->getPost('employee_id');
            if (!$owner_id) {
                return $this->response->setJSON(array('success' => false, 'message' => 'Selecione um funcionario.'));
            }
        }

        $document_ids = $this->request->getPost('document_ids');
        $document_ids = is_array($document_ids) ? array_values(array_unique(array_filter(array_map('intval', $document_ids)))) : array();
        if (!count($document_ids)) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Selecione ao menos um documento.'));
        }

        $documents = array();
        foreach ($document_ids as $document_id) {
            $document = $this->Documents_model->get_one_with_details($document_id);
            if (!$document) {
                return $this->response->setJSON(array('success' => false, 'message' => 'Documento nao encontrado.'));
            }

            if ($document->status !== 'valid') {
                return $this->response->setJSON(array('success' => false, 'message' => 'Selecione apenas documentos validos.'));
            }

            if ($document->owner_type !== $owner_type) {
                return $this->response->setJSON(array('success' => false, 'message' => 'Os documentos selecionados nao pertencem ao tipo informado.'));
            }

            if ($owner_type === 'employee' && (int) $document->employee_id !== $owner_id) {
                return $this->response->setJSON(array('success' => false, 'message' => 'Os documentos selecionados nao pertencem ao funcionario informado.'));
            }

            $documents[] = $document;
        }

        $db = db_connect('default');
        $db->transBegin();

        $saved_ids = array();
        $saved_rows = array();
        foreach ($documents as $document) {
            $data = array(
                'document_id' => (int) $document->id,
                'submitted_at' => $submitted_at ?: null,
                'notes' => trim((string) $this->request->getPost('notes')),
                'created_by' => $this->login_user->id,
                'created_at' => get_my_local_time(),
                'updated_by' => $this->login_user->id,
                'updated_at' => get_my_local_time(),
            );

            $save_id = $this->Document_submissions_model->save_submission($data, 0);
            if (!$save_id) {
                $db->transRollback();
                return $this->response->setJSON(array('success' => false, 'message' => 'Nao foi possivel salvar o envio.'));
            }

            $saved_ids[] = $save_id;
            $row = $this->Document_submissions_model->get_one_with_details($save_id);
            $saved_rows[] = $this->_make_row($row);
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(array('success' => false, 'message' => 'Nao foi possivel salvar o envio.'));
        }

        $db->transCommit();

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $saved_rows,
            'id' => $saved_ids,
            'message' => count($saved_ids) > 1 ? 'Envios criados com sucesso.' : 'Envio criado com sucesso.'
        ));
    }

    public function delete()
    {
        if (!$this->_has_manage_submissions_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => 'ID invalido.'));
        }

        $item = $this->Document_submissions_model->get_one_with_details($id);
        if (!$item) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Envio nao encontrado.'));
        }

        $ok = $this->Document_submissions_model->delete($id);
        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'message' => $ok ? 'Envio excluido com sucesso.' : 'Nao foi possivel excluir o envio.'
        ));
    }

    private function _make_row($data)
    {
        $document_status_meta = $this->_get_document_status_meta($data);
        $irregular_meta = $this->_get_irregular_meta($data);

        $document_link = esc($data->document_title ?: '-');
        if ($this->_can_view_linked_documents() && !empty($data->document_id)) {
            $document_link = anchor(get_uri('ged/documents/view/' . $data->document_id), esc($data->document_title ?: '-'));
        }

        $owner_label = '-';
        if (($data->document_owner_type ?? '') === 'employee') {
            $owner_label = $data->employee_name ?: '-';
        } elseif (($data->document_owner_type ?? '') === 'company') {
            $owner_label = 'Empresa';
        }

        $actions = '';
        if ($this->_has_manage_submissions_permission()) {
            $actions .= modal_anchor(get_uri('ged/submissions/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                'title' => 'Editar',
                'class' => 'edit btn btn-sm btn-outline-secondary me-1',
                'data-post-id' => $data->id
            ));
            $actions .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => 'Excluir',
                'class' => 'btn btn-sm btn-outline-danger',
                'data-id' => $data->id,
                'data-action-url' => get_uri('ged/submissions/delete'),
                'data-action' => 'delete-confirmation'
            ));
        }

        return array(
            $document_link,
            esc($data->document_type_name ?: '-'),
            esc($owner_label),
            esc($data->submitted_at ? format_to_date($data->submitted_at, false) : '-'),
            $document_status_meta['html'],
            $irregular_meta['html'],
            $actions
        );
    }

    private function _get_filters_from_request()
    {
        return array(
            'document_id' => (int) $this->request->getPost('document_id'),
            'search' => trim((string) $this->request->getPost('search')),
        );
    }

    private function _resolve_document_status($document)
    {
        if (!$document) {
            return 'pending';
        }

        $status = (string) ($document->status ?? 'pending');
        $expiration_date = trim((string) ($document->expiration_date ?? ''));

        if ($status === 'archived') {
            return 'archived';
        }

        if (!$expiration_date) {
            return $status === 'pending' ? 'pending' : 'valid';
        }

        $expiration_status = get_expiration_status($expiration_date);
        if ($expiration_status === 'expired') {
            return 'expired';
        }

        if (in_array($expiration_status, array('expiring_30', 'expiring_15', 'expiring_7', 'expires_today'), true)) {
            return 'expiring';
        }

        return 'valid';
    }

    private function _get_document_status_meta($data)
    {
        $status = $this->_resolve_document_status($data);
        return array(
            'status' => $status,
            'html' => get_document_status_label($status),
        );
    }

    private function _get_irregular_meta($data)
    {
        $document_status = $this->_resolve_document_status($data);
        if (in_array($document_status, array('expired', 'expiring'), true)) {
            return array(
                'html' => "<span class='badge bg-warning text-dark'>Irregular</span>",
                'is_irregular' => true
            );
        }

        return array(
            'html' => "<span class='badge bg-success'>Regular</span>",
            'is_irregular' => false
        );
    }

    private function _get_documents_dropdown()
    {
        $dropdown = array('' => '-');
        $rows = $this->Documents_model->get_details()->getResult();
        foreach ($rows as $row) {
            $dropdown[$row->id] = $row->title;
        }

        return $dropdown;
    }

    public function get_employees_suggestion()
    {
        if (!$this->_has_manage_submissions_permission()) {
            return $this->_json_permission_denied();
        }

        $employees = $this->Users_model->get_dropdown_list(array('first_name', 'last_name'), 'id', array(
            'deleted' => 0,
            'status' => 'active',
            'user_type' => 'staff'
        ));

        $suggestion = array(array('id' => '', 'text' => '-'));
        foreach ($employees as $id => $name) {
            $suggestion[] = array('id' => $id, 'text' => $name);
        }

        echo json_encode($suggestion);
    }

    public function get_owner_documents_suggestion($owner_type = '', $owner_id = 0)
    {
        if (!$this->_has_manage_submissions_permission()) {
            return $this->_json_permission_denied();
        }

        $owner_type = trim((string) $owner_type);
        if (!in_array($owner_type, array('company', 'employee'), true)) {
            echo json_encode(array(array('id' => '', 'text' => '-')));
            return;
        }

        validate_numeric_value($owner_id);

        $documents = $this->Documents_model->get_valid_documents_suggestion($owner_type, (int) $owner_id);
        echo json_encode($documents);
    }

    private function _can_view_linked_documents()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_view_documents') == '1';
    }
}
