<?php

namespace LaudosTecnicos\Controllers;

class Types extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureTypesAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\types\\index', array(
            'can_manage_types' => \LaudosTecnicos\Plugin::canManageTypes($this->login_user),
            'categories_dropdown' => $this->categories_model->get_active_dropdown(true),
        ));
    }

    public function list_data()
    {
        $this->ensureTypesAccess();

        $rows = $this->types_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => $this->request->getPost('status'),
            'category_id' => (int) $this->request->getPost('category_id'),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/tipos/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger ms-1 delete', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/tipos/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->name),
                esc($row->code),
                esc($row->category_name ?: '-'),
                esc($row->prefix ?: '-'),
                (int) $row->validity_days,
                $row->is_active ? '<span class="badge bg-success">' . app_lang('yes') . '</span>' : '<span class="badge bg-secondary">' . app_lang('no') . '</span>',
                (int) $row->sort,
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureTypesAccess();

        $view_data['model_info'] = $id ? $this->types_model->get_one((int) $id) : (object) array(
            'id' => '',
            'name' => '',
            'code' => '',
            'category_id' => '',
            'description' => '',
            'prefix' => '',
            'default_template_id' => '',
            'validity_days' => 365,
            'require_technical_responsible' => 1,
            'require_review' => 1,
            'require_approval' => 1,
            'require_signature' => 1,
            'require_inspection' => 1,
            'require_calibrated_equipment' => 0,
            'allow_mobile' => 1,
            'is_active' => 1,
            'sort' => 0,
        );
        $view_data['categories_dropdown'] = $this->categories_model->get_active_dropdown(true);

        return $this->template->view('LaudosTecnicos\\Views\\types\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureTypesAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'name' => trim((string) $this->request->getPost('name')),
            'code' => trim((string) $this->request->getPost('code')),
            'category_id' => (int) $this->request->getPost('category_id'),
            'description' => trim((string) $this->request->getPost('description')),
            'prefix' => trim((string) $this->request->getPost('prefix')),
            'default_template_id' => (int) $this->request->getPost('default_template_id') ?: null,
            'validity_days' => (int) $this->request->getPost('validity_days'),
            'require_technical_responsible' => $this->request->getPost('require_technical_responsible') ? 1 : 0,
            'require_review' => $this->request->getPost('require_review') ? 1 : 0,
            'require_approval' => $this->request->getPost('require_approval') ? 1 : 0,
            'require_signature' => $this->request->getPost('require_signature') ? 1 : 0,
            'require_inspection' => $this->request->getPost('require_inspection') ? 1 : 0,
            'require_calibrated_equipment' => $this->request->getPost('require_calibrated_equipment') ? 1 : 0,
            'allow_mobile' => $this->request->getPost('allow_mobile') ? 1 : 0,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'sort' => (int) $this->request->getPost('sort'),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        if ($data['name'] === '' || $data['code'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        if ($id) {
            $ok = $this->types_model->save_from_post($data, $id);
        } else {
            $data['created_by'] = (int) ($this->login_user->id ?? 0);
            $ok = $this->types_model->save_from_post($data, null);
        }

        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->logAudit('type', $id ?: 0, $id ? 'update' : 'create', 'Type saved', array(), $data);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureTypesAccess();
        return $this->response->setJSON(array('success' => (bool) $this->types_model->toggle_status((int) $id)));
    }

    public function delete()
    {
        $this->ensureTypesAccess();

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        if ($this->types_model->is_in_use($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('laudostecnicos_type_in_use')));
        }

        $ok = $this->types_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred')));
    }
}
