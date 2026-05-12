<?php

namespace GED\Controllers;

class Documents extends GedBaseController
{
    private $Documents_model;
    private $Document_types_model;
    private $Suppliers_model;
    private $Ged_users_model;
    private $Ged_settings_model;

    public function __construct()
    {
        parent::__construct();
        $this->Documents_model = model('GED\\Models\\Ged_documents_model');
        $this->Document_types_model = model('GED\\Models\\Ged_document_types_model');
        $this->Suppliers_model = model('GED\\Models\\Ged_suppliers_model');
        $this->Ged_users_model = model('App\\Models\\Users_model');
        $this->Ged_settings_model = model('GED\\Models\\Ged_settings_model');
    }

    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $view_data = array(
            'can_create' => $this->_has_create_permission(),
            'can_edit' => $this->_has_edit_permission(),
            'can_delete' => $this->_has_delete_permission(),
            'can_download' => $this->_has_download_permission(),
            'document_types_dropdown' => $this->_get_document_types_dropdown(),
            'suppliers_dropdown' => $this->_get_suppliers_dropdown(),
            'employees_dropdown' => $this->_get_employees_dropdown(),
            'owner_types_dropdown' => array(
                '' => '-',
                'company' => 'Empresa',
                'employee' => 'Funcionario',
                'supplier' => 'Fornecedor'
            ),
            'status_dropdown' => array(
                '' => '-',
                'valid' => 'Valido',
                'expiring' => 'Vencendo',
                'expired' => 'Vencido',
                'pending' => 'Pendente',
                'archived' => 'Arquivado'
            ),
            'expiration_scope_dropdown' => array(
                '' => '-',
                'overdue' => 'Vencidos',
                'expiring_30' => 'Vencendo em 30 dias',
                'expiring_7' => 'Vencendo em 7 dias'
            )
        );

        return $this->template->rander('GED\\Views\\documents\\index', $view_data);
    }

    public function list_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $options = $this->_get_filters_from_request();
        $items = $this->Documents_model->get_details($options)->getResult();
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
            if (!$this->_has_edit_permission()) {
                app_redirect('forbidden');
            }
        } elseif (!$this->_has_create_permission()) {
            app_redirect('forbidden');
        }

        $document = $id ? $this->Documents_model->get_one_with_details($id) : null;
        if ($id && !$document) {
            show_404();
        }

        $view_data = array(
            'model_info' => $document ?: (object) array(
                'id' => 0,
                'title' => '',
                'document_type_id' => 0,
                'owner_type' => 'company',
                'owner_id' => 0,
                'employee_id' => 0,
                'supplier_id' => 0,
                'issue_date' => '',
                'expiration_date' => '',
                'notes' => '',
                'file_path' => '',
                'original_filename' => '',
                'status' => ged_setting('default_document_status', 'pending')
            ),
            'document_types_dropdown' => $this->_get_document_types_dropdown(),
            'suppliers_dropdown' => $this->_get_suppliers_dropdown(),
            'employees_dropdown' => $this->_get_employees_dropdown(),
            'owner_types_dropdown' => array(
                'company' => 'Empresa',
                'employee' => 'Funcionario',
                'supplier' => 'Fornecedor'
            ),
            'settings' => $this->_get_settings_map(),
        );

        return $this->template->view('GED\\Views\\documents\\modal_form', $view_data);
    }

    public function view($id = 0)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $document = $this->Documents_model->get_one_with_details((int) $id);
        if (!$document) {
            show_404();
        }

        $view_data = array(
            'model_info' => $document,
            'can_edit' => $this->_has_edit_permission(),
            'can_delete' => $this->_has_delete_permission(),
            'can_download' => $this->_has_download_permission(),
            'status_meta' => $this->_get_status_meta($document),
            'storage_info' => $this->_get_storage_info($document),
        );

        return $this->template->rander('GED\\Views\\documents\\view', $view_data);
    }

    public function save()
    {
        $id = (int) $this->request->getPost('id');
        if ($id) {
            if (!$this->_has_edit_permission()) {
                return $this->_json_permission_denied();
            }
        } elseif (!$this->_has_create_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'title' => 'required',
            'document_type_id' => 'required|numeric',
            'owner_type' => 'required'
        ));

        $document_type_id = (int) $this->request->getPost('document_type_id');
        $document_type = $this->Document_types_model->get_one($document_type_id);
        if (!$document_type || !$document_type->id) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Tipo de documento invalido.'));
        }

        $owner_type = trim((string) $this->request->getPost('owner_type'));
        if (!in_array($owner_type, array('company', 'employee', 'supplier'), true)) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Tipo de proprietario invalido.'));
        }

        $employee_id = (int) $this->request->getPost('employee_id');
        $supplier_id = (int) $this->request->getPost('supplier_id');
        $owner_id = 0;

        if ($owner_type === 'employee') {
            if (!$employee_id) {
                return $this->response->setJSON(array('success' => false, 'message' => 'Selecione um funcionario.'));
            }
            $owner_id = $employee_id;
        } elseif ($owner_type === 'supplier') {
            if (!$supplier_id) {
                return $this->response->setJSON(array('success' => false, 'message' => 'Selecione um fornecedor.'));
            }
            $owner_id = $supplier_id;
        }

        $issue_date = trim((string) $this->request->getPost('issue_date'));
        $expiration_date = trim((string) $this->request->getPost('expiration_date'));
        $notes = trim((string) $this->request->getPost('notes'));

        if ((int) $document_type->has_expiration === 1 && $expiration_date === '') {
            return $this->response->setJSON(array('success' => false, 'message' => 'Informe a data de vencimento para este tipo de documento.'));
        }

        $existing = $id ? $this->Documents_model->get_one_with_details($id) : null;
        $upload_result = $this->_handle_upload($existing ? $existing->file_path : '', $existing ? $existing->original_filename : '', $id);
        if (!$upload_result['success']) {
            return $this->response->setJSON(array('success' => false, 'message' => $upload_result['message']));
        }

        $status = $this->_resolve_status((int) $document_type->has_expiration, $expiration_date);
        if (!$expiration_date && (int) $document_type->has_expiration === 1) {
            $status = 'pending';
        }

        $data = array(
            'title' => trim((string) $this->request->getPost('title')),
            'document_type_id' => $document_type_id,
            'owner_type' => $owner_type,
            'owner_id' => $owner_id,
            'employee_id' => $owner_type === 'employee' ? $employee_id : null,
            'supplier_id' => $owner_type === 'supplier' ? $supplier_id : null,
            'issue_date' => $issue_date ?: null,
            'expiration_date' => $expiration_date ?: null,
            'status' => $status,
            'file_path' => $upload_result['file_path'],
            'original_filename' => $upload_result['original_filename'],
            'notes' => $notes ?: null,
            'updated_by' => $this->login_user->id,
            'updated_at' => get_my_local_time(),
        );

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->Documents_model->save_document($data, $id);
        if (!$save_id) {
            if ($upload_result['new_file_path'] && is_file($upload_result['new_file_path'])) {
                @unlink($upload_result['new_file_path']);
            }
            return $this->response->setJSON(array('success' => false, 'message' => 'Nao foi possivel salvar o documento.'));
        }

        if ($upload_result['old_file_path_to_delete'] && is_file($upload_result['old_file_path_to_delete'])) {
            @unlink($upload_result['old_file_path_to_delete']);
        }

        $row = $this->Documents_model->get_one_with_details($save_id);
        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $save_id,
            'message' => $id ? 'Documento atualizado com sucesso.' : 'Documento criado com sucesso.'
        ));
    }

    public function delete()
    {
        if (!$this->_has_delete_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => 'ID invalido.'));
        }

        $document = $this->Documents_model->get_one_with_details($id);
        if (!$document) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Documento nao encontrado.'));
        }

        $ok = $this->Documents_model->delete($id);
        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'message' => $ok ? 'Documento excluido com sucesso.' : 'Nao foi possivel excluir o documento.'
        ));
    }

    public function download($id = 0)
    {
        if (!$this->_has_download_permission()) {
            app_redirect('forbidden');
        }

        $document = $this->Documents_model->get_one_with_details((int) $id);
        if (!$document || !$document->file_path) {
            show_404();
        }

        $storage_info = $this->_get_storage_info($document);
        if (!is_file($storage_info['absolute_path'])) {
            show_404();
        }

        $download_name = $document->original_filename ?: basename($storage_info['absolute_path']);
        return $this->response->download($storage_info['absolute_path'], null)->setFileName($download_name);
    }

    private function _make_row($data)
    {
        $status_meta = $this->_get_status_meta($data);
        $expiration_badge = $this->_get_expiration_badge($data);

        $owner_label = 'Empresa';
        if ($data->owner_type === 'employee') {
            $owner_label = $data->employee_name ?: 'Funcionario';
        } elseif ($data->owner_type === 'supplier') {
            $owner_label = $data->supplier_name ?: 'Fornecedor';
        }

        $file_button = '-';
        if (!empty($data->file_path)) {
            if ($this->_has_download_permission()) {
                $file_button = anchor(
                    get_uri('ged/documents/download/' . $data->id),
                    "<i data-feather='download' class='icon-16'></i>",
                    array('class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Baixar arquivo')
                );
            } else {
                $file_button = "<span class='text-muted'>Arquivo</span>";
            }
        }

        $actions = '';
        $actions .= anchor(get_uri('ged/documents/view/' . $data->id), "<i data-feather='eye' class='icon-16'></i>", array(
            'class' => 'btn btn-sm btn-outline-secondary me-1',
            'title' => 'Visualizar'
        ));

        if ($this->_has_edit_permission()) {
            $actions .= modal_anchor(get_uri('ged/documents/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                'class' => 'edit btn btn-sm btn-outline-secondary me-1',
                'title' => 'Editar',
                'data-post-id' => $data->id
            ));
        }

        if ($this->_has_delete_permission()) {
            $actions .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => 'Excluir',
                'class' => 'btn btn-sm btn-outline-danger',
                'data-id' => $data->id,
                'data-action-url' => get_uri('ged/documents/delete'),
                'data-action' => 'delete-confirmation'
            ));
        }

        return array(
            esc($data->title),
            esc($data->document_type_name ?: '-'),
            esc($owner_label),
            esc($data->issue_date ? format_to_date($data->issue_date, false) : '-'),
            $this->_format_expiration_cell($data, $expiration_badge),
            $status_meta['html'],
            $file_button,
            $actions
        );
    }

    private function _format_expiration_cell($data, $badge_html)
    {
        if (!$data->expiration_date) {
            return '-';
        }

        return esc(format_to_date($data->expiration_date, false)) . '<div class="mt5">' . $badge_html . '</div>';
    }

    private function _get_status_meta($data)
    {
        $status = $this->_resolve_status((int) ($data->document_type_has_expiration ?? 0), (string) ($data->expiration_date ?? ''), (string) ($data->status ?? 'pending'));
        $html = get_document_status_label($status);
        return array(
            'status' => $status,
            'html' => $html,
        );
    }

    private function _get_expiration_badge($data)
    {
        if (empty($data->expiration_date) || (int) ($data->document_type_has_expiration ?? 0) !== 1) {
            return get_expiration_badge(null);
        }

        return get_expiration_badge($data->expiration_date);
    }

    private function _resolve_status($has_expiration, $expiration_date, $current_status = 'pending')
    {
        $current_status = trim((string) $current_status);
        if ($current_status === 'archived') {
            return 'archived';
        }

        if (!$has_expiration || !$expiration_date) {
            return $current_status === 'pending' ? 'pending' : 'valid';
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

    private function _get_filters_from_request()
    {
        return array(
            'document_type_id' => (int) $this->request->getPost('document_type_id'),
            'owner_type' => trim((string) $this->request->getPost('owner_type')),
            'employee_id' => (int) $this->request->getPost('employee_id'),
            'supplier_id' => (int) $this->request->getPost('supplier_id'),
            'status' => trim((string) $this->request->getPost('status')),
            'expiration_scope' => trim((string) $this->request->getPost('expiration_scope')),
            'expiration_start' => trim((string) $this->request->getPost('expiration_start')),
            'expiration_end' => trim((string) $this->request->getPost('expiration_end')),
            'search' => trim((string) $this->request->getPost('search')),
        );
    }

    private function _get_document_types_dropdown()
    {
        $dropdown = array('' => '-');
        $rows = $this->Document_types_model->get_details(array('is_active' => 1))->getResult();
        foreach ($rows as $row) {
            $dropdown[$row->id] = $row->name;
        }

        return $dropdown;
    }

    private function _get_suppliers_dropdown()
    {
        $dropdown = array('' => '-');
        $rows = $this->Suppliers_model->get_details(array('is_active' => 1))->getResult();
        foreach ($rows as $row) {
            $dropdown[$row->id] = $row->name;
        }

        return $dropdown;
    }

    private function _get_employees_dropdown()
    {
        $dropdown = array('' => '-');
        $rows = $this->Ged_users_model->get_all_where(array('deleted' => 0, 'status' => 'active', 'user_type' => 'staff'))->getResult();
        foreach ($rows as $row) {
            $dropdown[$row->id] = trim($row->first_name . ' ' . $row->last_name);
        }

        return $dropdown;
    }

    private function _get_settings_map()
    {
        return array(
            'alert_days' => $this->Ged_settings_model->get_value('alert_days', '30,15,7,0'),
            'enable_native_notifications' => $this->Ged_settings_model->get_value('enable_native_notifications', '1'),
            'notify_admins' => $this->Ged_settings_model->get_value('notify_admins', '1'),
            'notify_document_creator' => $this->Ged_settings_model->get_value('notify_document_creator', '1'),
            'upload_max_size_mb' => (int) $this->Ged_settings_model->get_value('upload_max_size_mb', '20'),
            'allowed_file_extensions' => $this->Ged_settings_model->get_value('allowed_file_extensions', 'pdf,jpg,jpeg,png,doc,docx'),
        );
    }

    private function _get_storage_info($document)
    {
        $base_path = getcwd() . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ged' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR;
        $stored_name = (string) ($document->file_path ?? '');

        return array(
            'base_path' => $base_path,
            'stored_name' => $stored_name,
            'absolute_path' => $stored_name ? $base_path . $stored_name : '',
        );
    }

    private function _handle_upload($current_file_path = '', $current_original_filename = '', $document_id = 0)
    {
        if (empty($_FILES) || empty($_FILES['document_file']) || empty($_FILES['document_file']['name'])) {
            return array(
                'success' => true,
                'file_path' => $current_file_path,
                'original_filename' => $current_original_filename,
                'new_file_path' => '',
                'old_file_path_to_delete' => '',
            );
        }

        $file = $_FILES['document_file'];
        $file_name = (string) $file['name'];
        $tmp_name = (string) $file['tmp_name'];
        $file_size = (int) $file['size'];

        if (!is_file($tmp_name)) {
            return array('success' => false, 'message' => 'Arquivo de upload invalido.');
        }

        $settings = $this->_get_settings_map();
        $max_size_mb = max(1, (int) $settings['upload_max_size_mb']);
        if ($file_size > ($max_size_mb * 1024 * 1024)) {
            return array('success' => false, 'message' => 'O arquivo excede o tamanho maximo de ' . $max_size_mb . ' MB.');
        }

        $allowed = array_filter(array_map('trim', explode(',', strtolower((string) $settings['allowed_file_extensions']))));
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!$extension || (!empty($allowed) && !in_array($extension, $allowed, true))) {
            return array('success' => false, 'message' => 'Tipo de arquivo nao permitido.');
        }

        if (in_array($extension, array('php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'inc'), true)) {
            return array('success' => false, 'message' => 'Tipo de arquivo nao permitido.');
        }

        $storage = $this->_get_storage_info((object) array('file_path' => ''));
        if (!is_dir($storage['base_path']) && !mkdir($storage['base_path'], 0755, true)) {
            return array('success' => false, 'message' => 'Nao foi possivel preparar a pasta de upload.');
        }

        $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '-', $file_name);
        if (strlen($safe_name) > 140) {
            $safe_name = substr($safe_name, -140);
        }

        $stored_name = 'ged_' . uniqid('doc', true) . '_' . $safe_name;
        $destination = $storage['base_path'] . $stored_name;

        if (!move_uploaded_file($tmp_name, $destination)) {
            if (!copy($tmp_name, $destination)) {
                return array('success' => false, 'message' => 'Nao foi possivel salvar o arquivo.');
            }
        }

        $old_file_to_delete = '';
        if ($current_file_path && $current_file_path !== $stored_name) {
            $old_file_to_delete = $storage['base_path'] . $current_file_path;
        }

        return array(
            'success' => true,
            'file_path' => $stored_name,
            'original_filename' => $file_name,
            'new_file_path' => $destination,
            'old_file_path_to_delete' => $old_file_to_delete,
        );
    }
}
