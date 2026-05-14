<?php

namespace GED\Controllers;

class DocumentTypes extends GedBaseController
{
    private $Document_types_model;

    public function __construct()
    {
        parent::__construct();
        $this->Document_types_model = model('GED\\Models\\Ged_document_types_model');
    }

    public function index()
    {
        if (!$this->_has_manage_document_types_permission()) {
            app_redirect('forbidden');
        }

        $view_data = array(
            'can_manage' => $this->_has_manage_document_types_permission(),
            'can_create' => $this->_has_manage_document_types_permission(),
            'can_edit' => $this->_has_manage_document_types_permission(),
            'can_delete' => $this->_has_manage_document_types_permission(),
        );

        return $this->template->rander('GED\\Views\\document_types\\index', $view_data);
    }

    public function list_data()
    {
        if (!$this->_has_manage_document_types_permission()) {
            return $this->_json_permission_denied();
        }

        $items = $this->Document_types_model->get_details()->getResult();
        $result = array();
        foreach ($items as $item) {
            $result[] = $this->_make_row($item);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        if (!$this->_has_manage_document_types_permission()) {
            app_redirect('forbidden');
        }

        $id = (int) $id;
        if (!$id) {
            $id = (int) $this->request->getPost('id');
        }
        $view_data = array(
            'model_info' => $id ? $this->Document_types_model->get_one($id) : (object) array(
                'id' => 0,
                'name' => '',
                'description' => '',
                'has_expiration' => 0,
                'is_active' => 1,
            )
        );

        return $this->template->view('GED\\Views\\document_types\\modal_form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_manage_document_types_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'name' => 'required'
        ));

        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        $description = trim((string) $this->request->getPost('description'));
        $has_expiration = $this->request->getPost('has_expiration') ? 1 : 0;
        $is_active = $this->request->getPost('is_active') ? 1 : 0;

        if ($is_active && $this->Document_types_model->name_exists_active($name, $id)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Ja existe um tipo de documento ativo com esse nome.'
            ));
        }

        if (!$id && $this->Document_types_model->name_exists_active($name, 0)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Ja existe um tipo de documento ativo com esse nome.'
            ));
        }

        $data = array(
            'name' => $name,
            'description' => $description,
            'has_expiration' => $has_expiration,
            'is_active' => $is_active,
            'updated_at' => get_my_local_time(),
        );

        if (!$id) {
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->Document_types_model->save_type($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Nao foi possivel salvar o tipo de documento.'
            ));
        }

        $row = $this->Document_types_model->get_one($id ? $id : $save_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $id ? $id : $save_id,
            'message' => $id ? 'Tipo de documento atualizado com sucesso.' : 'Tipo de documento criado com sucesso.'
        ));
    }

    public function toggle_status($id = 0)
    {
        if (!$this->_has_manage_document_types_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int) $id;
        $model_info = $this->Document_types_model->get_one($id);
        if (!$model_info || !$model_info->id) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Tipo de documento nao encontrado.'
            ));
        }

        $new_status = (int) $model_info->is_active ? 0 : 1;
        if ($new_status === 1 && $this->Document_types_model->name_exists_active($model_info->name, $id)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Ja existe um tipo de documento ativo com esse nome.'
            ));
        }

        $ok = $this->Document_types_model->save_type(array(
            'is_active' => $new_status,
            'updated_at' => get_my_local_time(),
        ), $id);

        if (!$ok) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Nao foi possivel alterar o status do tipo de documento.'
            ));
        }

        $row = $this->Document_types_model->get_one($id);
        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $id,
            'message' => $new_status ? 'Tipo de documento ativado com sucesso.' : 'Tipo de documento inativado com sucesso.'
        ));
    }

    public function delete()
    {
        if (!$this->_has_manage_document_types_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'ID invalido.'
            ));
        }

        $model_info = $this->Document_types_model->get_one($id);
        if (!$model_info || !$model_info->id) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Tipo de documento nao encontrado.'
            ));
        }

        if ($this->Document_types_model->has_documents($id)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Nao e possivel excluir este tipo porque existem documentos vinculados.'
            ));
        }

        $ok = $this->Document_types_model->delete($id);
        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'message' => $ok ? 'Tipo de documento excluido com sucesso.' : 'Nao foi possivel excluir o tipo de documento.'
        ));
    }

    private function _make_row($data)
    {
        $status_badge = ((int) $data->is_active === 1)
            ? "<span class='badge bg-success'>Ativo</span>"
            : "<span class='badge bg-secondary'>Inativo</span>";

        $toggle_text = ((int) $data->is_active === 1) ? 'Inativar' : 'Ativar';
        $toggle_icon = ((int) $data->is_active === 1) ? 'pause-circle' : 'play-circle';
        $toggle_class = ((int) $data->is_active === 1) ? 'btn-outline-warning' : 'btn-outline-success';

        $actions = '';
        if ($this->_has_manage_document_types_permission()) {
            $actions .= modal_anchor(get_uri('ged/document_types/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                'title' => 'Editar',
                'class' => 'edit btn btn-sm btn-outline-secondary me-1',
                'data-post-id' => $data->id
            ));
            $actions .= js_anchor("<i data-feather='{$toggle_icon}' class='icon-16'></i>", array(
                'title' => $toggle_text,
                'class' => 'btn btn-sm ' . $toggle_class . ' me-1',
                'data-id' => $data->id,
                'data-action-url' => get_uri('ged/document_types/toggle_status/' . $data->id),
                'data-action' => 'delete-confirmation'
            ));
            $actions .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => 'Excluir',
                'class' => 'btn btn-sm btn-outline-danger',
                'data-id' => $data->id,
                'data-action-url' => get_uri('ged/document_types/delete'),
                'data-action' => 'delete-confirmation'
            ));
        }

        $description = $data->description ? $data->description : '-';
        if (strlen($description) > 100) {
            $description = substr($description, 0, 100) . '...';
        }

        return array(
            esc($data->name),
            esc($description),
            $data->has_expiration ? 'Sim' : 'Nao',
            $status_badge,
            $actions
        );
    }
}
