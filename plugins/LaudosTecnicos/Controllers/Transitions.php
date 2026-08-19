<?php

namespace LaudosTecnicos\Controllers;

class Transitions extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureTransitionsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\transitions\\index', array(
            'can_manage_transitions' => \LaudosTecnicos\Plugin::canManageTransitions($this->login_user),
            'statuses_dropdown' => $this->statuses_model->get_dropdown(true),
        ));
    }

    public function list_data()
    {
        $this->ensureTransitionsAccess();

        $rows = $this->transitions_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => $this->request->getPost('status'),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/transitions/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger ms-1 delete', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/transitions/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->from_status_name ?: $row->from_status_code),
                esc($row->to_status_name ?: $row->to_status_code),
                $row->require_comment ? app_lang('yes') : app_lang('no'),
                $row->send_notification ? app_lang('yes') : app_lang('no'),
                $row->auto_create_task ? app_lang('yes') : app_lang('no'),
                $row->is_active ? '<span class="badge bg-success">' . app_lang('yes') . '</span>' : '<span class="badge bg-secondary">' . app_lang('no') . '</span>',
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureTransitionsAccess();

        $view_data['model_info'] = $id ? $this->transitions_model->get_one((int) $id) : (object) array(
            'id' => '',
            'from_status_code' => '',
            'to_status_code' => '',
            'allowed_roles_json' => '[]',
            'required_permissions_json' => '[]',
            'require_comment' => 0,
            'required_validations_json' => '[]',
            'send_notification' => 1,
            'auto_create_task' => 0,
            'task_title' => '',
            'task_description' => '',
            'sort' => 0,
            'is_active' => 1,
        );
        $view_data['statuses_dropdown'] = $this->statuses_model->get_dropdown(true);

        return $this->template->view('LaudosTecnicos\\Views\\transitions\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureTransitionsAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'from_status_code' => trim((string) $this->request->getPost('from_status_code')),
            'to_status_code' => trim((string) $this->request->getPost('to_status_code')),
            'allowed_roles_json' => trim((string) $this->request->getPost('allowed_roles_json')) ?: '[]',
            'required_permissions_json' => trim((string) $this->request->getPost('required_permissions_json')) ?: '[]',
            'require_comment' => $this->request->getPost('require_comment') ? 1 : 0,
            'required_validations_json' => trim((string) $this->request->getPost('required_validations_json')) ?: '[]',
            'send_notification' => $this->request->getPost('send_notification') ? 1 : 0,
            'auto_create_task' => $this->request->getPost('auto_create_task') ? 1 : 0,
            'task_title' => trim((string) $this->request->getPost('task_title')),
            'task_description' => trim((string) $this->request->getPost('task_description')),
            'sort' => (int) $this->request->getPost('sort'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        if ($data['from_status_code'] === '' || $data['to_status_code'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        if (!$this->statuses_model->get_status_by_code($data['from_status_code']) || !$this->statuses_model->get_status_by_code($data['to_status_code'])) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        if ($id) {
            $ok = $this->transitions_model->save_from_post($data, $id);
        } else {
            $data['created_by'] = (int) ($this->login_user->id ?? 0);
            $ok = $this->transitions_model->save_from_post($data, null);
        }

        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->logAudit('transition', $id ?: 0, $id ? 'update' : 'create', 'Transition saved', array(), $data);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureTransitionsAccess();
        return $this->response->setJSON(array('success' => (bool) $this->transitions_model->toggle_status((int) $id)));
    }

    public function delete()
    {
        $this->ensureTransitionsAccess();

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        $ok = $this->transitions_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred')));
    }
}
