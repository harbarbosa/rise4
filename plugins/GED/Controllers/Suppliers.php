<?php

namespace GED\Controllers;

class Suppliers extends GedBaseController
{
    private $Suppliers_model;

    public function __construct()
    {
        parent::__construct();
        $this->Suppliers_model = model('GED\\Models\\Ged_suppliers_model');
    }

    public function index()
    {
        if (!$this->_has_manage_suppliers_permission()) {
            app_redirect('forbidden');
        }

        return $this->template->rander('GED\\Views\\suppliers\\index', array(
            'can_manage' => $this->_has_manage_suppliers_permission(),
            'can_create' => $this->_has_manage_suppliers_permission(),
            'can_edit' => $this->_has_manage_suppliers_permission(),
            'can_delete' => $this->_has_manage_suppliers_permission()
        ));
    }

    public function list_data()
    {
        if (!$this->_has_manage_suppliers_permission()) {
            return $this->_json_permission_denied();
        }

        $items = $this->Suppliers_model->get_details()->getResult();
        $result = array();
        foreach ($items as $item) {
            $result[] = $this->_make_row($item);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        if (!$this->_has_manage_suppliers_permission()) {
            app_redirect('forbidden');
        }

        $id = (int) $id;
        if (!$id) {
            $id = (int) $this->request->getPost('id');
        }
        $view_data = array(
            'model_info' => $id ? $this->Suppliers_model->get_one($id) : (object) array(
                'id' => 0,
                'name' => '',
                'portal_url' => '',
                'contact_name' => '',
                'contact_email' => '',
                'contact_phone' => '',
                'notes' => '',
                'is_active' => 1
            )
        );

        return $this->template->view('GED\\Views\\suppliers\\modal_form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_manage_suppliers_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'name' => 'required'
        ));

        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        $portal_url = trim((string) $this->request->getPost('portal_url'));
        $contact_email = trim((string) $this->request->getPost('contact_email'));
        $is_active = $this->request->getPost('is_active') ? 1 : 0;

        if ($portal_url !== '' && !filter_var($portal_url, FILTER_VALIDATE_URL)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Informe uma URL valida para o portal.'
            ));
        }

        if ($contact_email !== '' && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Informe um e-mail valido.'
            ));
        }

        $data = array(
            'name' => $name,
            'portal_url' => $portal_url,
            'contact_name' => trim((string) $this->request->getPost('contact_name')),
            'contact_email' => $contact_email,
            'contact_phone' => trim((string) $this->request->getPost('contact_phone')),
            'notes' => trim((string) $this->request->getPost('notes')),
            'is_active' => $is_active,
            'updated_at' => get_my_local_time()
        );

        if (!$id) {
            $data['created_at'] = get_my_local_time();
        }

        if ($this->Suppliers_model->name_exists_active($name, $id)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Ja existe um fornecedor/portal ativo com esse nome.'
            ));
        }

        $save_id = $this->Suppliers_model->save_supplier($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Nao foi possivel salvar o fornecedor/portal.'
            ));
        }

        $new_id = $id ? $id : $save_id;
        $row = $this->Suppliers_model->get_one($new_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $new_id,
            'message' => $id ? 'Fornecedor/portal atualizado com sucesso.' : 'Fornecedor/portal criado com sucesso.'
        ));
    }

    public function delete()
    {
        if (!$this->_has_manage_suppliers_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'ID invalido.'
            ));
        }

        $model_info = $this->Suppliers_model->get_one($id);
        if (!$model_info || !$model_info->id) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Fornecedor/portal nao encontrado.'
            ));
        }

        if ($this->Suppliers_model->has_documents_or_submissions($id)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Nao e possivel excluir este fornecedor/portal porque existem documentos ou envios vinculados.'
            ));
        }

        $ok = $this->Suppliers_model->delete($id);
        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'message' => $ok ? 'Fornecedor/portal excluido com sucesso.' : 'Nao foi possivel excluir o fornecedor/portal.'
        ));
    }

    private function _make_row($data)
    {
        $status_badge = ((int) $data->is_active === 1)
            ? "<span class='badge bg-success'>Ativo</span>"
            : "<span class='badge bg-secondary'>Inativo</span>";

        $portal_url = trim((string) $data->portal_url);
        $portal_link = '-';
        if ($portal_url !== '') {
            $portal_link = anchor($portal_url, "<i data-feather='external-link' class='icon-16'></i>", array(
                'title' => app_lang('open_in_new_tab'),
                'class' => 'btn btn-sm btn-outline-secondary',
                'target' => '_blank',
                'rel' => 'noopener noreferrer'
            ));
        }

        $actions = '';
        if ($this->_has_manage_suppliers_permission()) {
            $actions .= modal_anchor(get_uri('ged/suppliers/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                'title' => 'Editar',
                'class' => 'edit btn btn-sm btn-outline-secondary me-1',
                'data-post-id' => $data->id
            ));
            $actions .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => 'Excluir',
                'class' => 'btn btn-sm btn-outline-danger',
                'data-id' => $data->id,
                'data-action-url' => get_uri('ged/suppliers/delete'),
                'data-action' => 'delete-confirmation'
            ));
        }

        return array(
            esc($data->name),
            $portal_link,
            esc($data->contact_name ? $data->contact_name : '-'),
            esc($data->contact_email ? $data->contact_email : '-'),
            esc($data->contact_phone ? $data->contact_phone : '-'),
            esc($data->notes ? (strlen($data->notes) > 80 ? substr($data->notes, 0, 80) . '...' : $data->notes) : '-'),
            $status_badge,
            $actions
        );
    }
}
