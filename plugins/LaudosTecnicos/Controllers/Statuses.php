<?php

namespace LaudosTecnicos\Controllers;

class Statuses extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureStatusesAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\statuses\\index', array(
            'can_manage_statuses' => \LaudosTecnicos\Plugin::canManageStatuses($this->login_user),
        ));
    }

    public function list_data()
    {
        $this->ensureStatusesAccess();

        $rows = $this->statuses_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => $this->request->getPost('status'),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/statuses/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger ms-1 delete', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/statuses/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->name),
                esc($row->code),
                '<span class="badge" style="background:' . esc($row->color ?: '#6c757d') . ';">' . esc($row->color ?: '-') . '</span>',
                esc($row->icon ?: '-'),
                (int) $row->sort,
                $row->status_initial ? app_lang('yes') : app_lang('no'),
                $row->status_final ? app_lang('yes') : app_lang('no'),
                $row->status_cancellation ? app_lang('yes') : app_lang('no'),
                $row->is_active ? '<span class="badge bg-success">' . app_lang('yes') . '</span>' : '<span class="badge bg-secondary">' . app_lang('no') . '</span>',
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureStatusesAccess();

        $view_data['model_info'] = $id ? $this->statuses_model->get_one((int) $id) : (object) array(
            'id' => '',
            'name' => '',
            'code' => '',
            'color' => '#0d6efd',
            'icon' => 'circle',
            'sort' => 0,
            'status_initial' => 0,
            'status_final' => 0,
            'status_cancellation' => 0,
            'allow_edit' => 1,
            'allow_delete' => 0,
            'allow_issue' => 0,
            'require_comment' => 0,
            'is_active' => 1,
        );

        return $this->template->view('LaudosTecnicos\\Views\\statuses\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureStatusesAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'name' => trim((string) $this->request->getPost('name')),
            'code' => trim((string) $this->request->getPost('code')),
            'color' => trim((string) $this->request->getPost('color')),
            'icon' => trim((string) $this->request->getPost('icon')),
            'sort' => (int) $this->request->getPost('sort'),
            'status_initial' => $this->request->getPost('status_initial') ? 1 : 0,
            'status_final' => $this->request->getPost('status_final') ? 1 : 0,
            'status_cancellation' => $this->request->getPost('status_cancellation') ? 1 : 0,
            'allow_edit' => $this->request->getPost('allow_edit') ? 1 : 0,
            'allow_delete' => $this->request->getPost('allow_delete') ? 1 : 0,
            'allow_issue' => $this->request->getPost('allow_issue') ? 1 : 0,
            'require_comment' => $this->request->getPost('require_comment') ? 1 : 0,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        if ($data['name'] === '' || $data['code'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        if ($id) {
            $ok = $this->statuses_model->save_from_post($data, $id);
        } else {
            $data['created_by'] = (int) ($this->login_user->id ?? 0);
            $ok = $this->statuses_model->save_from_post($data, null);
        }

        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->logAudit('status', $id ?: 0, $id ? 'update' : 'create', 'Status saved', array(), $data);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureStatusesAccess();
        return $this->response->setJSON(array('success' => (bool) $this->statuses_model->toggle_status((int) $id)));
    }

    public function delete()
    {
        $this->ensureStatusesAccess();

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        if ($this->statuses_model->is_in_use($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('laudostecnicos_status_in_use')));
        }

        $ok = $this->statuses_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred')));
    }
}
