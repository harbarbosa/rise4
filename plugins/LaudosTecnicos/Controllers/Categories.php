<?php

namespace LaudosTecnicos\Controllers;

class Categories extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureCategoriesAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\categories\\index', array(
            'can_manage_categories' => \LaudosTecnicos\Plugin::canManageCategories($this->login_user),
        ));
    }

    public function list_data()
    {
        $this->ensureCategoriesAccess();

        $search = trim((string) $this->request->getPost('search'));
        $status = $this->request->getPost('status');
        $rows = $this->categories_model->get_details(array(
            'search' => $search,
            'status' => $status,
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/categorias/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger ms-1 delete', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/categorias/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->name),
                esc($row->code),
                esc($row->description ?: '-'),
                '<span class="badge" style="background:' . esc($row->color ?: '#6c757d') . ';">' . esc($row->color ?: '-') . '</span>',
                esc($row->icon ?: '-'),
                (int) $row->sort,
                $row->is_active ? '<span class="badge bg-success">' . app_lang('yes') . '</span>' : '<span class="badge bg-secondary">' . app_lang('no') . '</span>',
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureCategoriesAccess();

        $view_data['model_info'] = $id ? $this->categories_model->get_one((int) $id) : (object) array(
            'id' => '',
            'name' => '',
            'code' => '',
            'description' => '',
            'color' => '#0d6efd',
            'icon' => 'layers',
            'sort' => 0,
            'is_active' => 1,
        );

        return $this->template->view('LaudosTecnicos\\Views\\categories\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureCategoriesAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'name' => trim((string) $this->request->getPost('name')),
            'code' => trim((string) $this->request->getPost('code')),
            'description' => trim((string) $this->request->getPost('description')),
            'color' => trim((string) $this->request->getPost('color')),
            'icon' => trim((string) $this->request->getPost('icon')),
            'sort' => (int) $this->request->getPost('sort'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        if ($data['name'] === '' || $data['code'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        if ($id) {
            $data['updated_at'] = get_current_utc_time();
            $ok = $this->categories_model->save_from_post($data, $id);
        } else {
            $data['created_by'] = (int) ($this->login_user->id ?? 0);
            $ok = $this->categories_model->save_from_post($data, null);
        }

        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->logAudit('category', $id ?: 0, $id ? 'update' : 'create', 'Category saved', array(), $data);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureCategoriesAccess();

        $ok = $this->categories_model->toggle_status((int) $id);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function delete()
    {
        $this->ensureCategoriesAccess();

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        if ($this->categories_model->is_in_use($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('laudostecnicos_category_in_use')));
        }

        $ok = $this->categories_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred')));
    }
}
