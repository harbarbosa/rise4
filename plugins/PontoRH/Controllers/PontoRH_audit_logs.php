<?php

namespace PontoRH\Controllers;

class PontoRH_audit_logs extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureSettingsAccess();

        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, 'all');
        $view_data['action_dropdown'] = $this->auditActionsDropdown();
        $view_data['status_dropdown'] = array(
            '' => '-',
            'logged' => 'Logged',
            'reviewed' => 'Reviewed',
            'blocked' => 'Blocked',
            'invalid' => 'Invalid',
        );

        return $this->template->rander('PontoRH\\Views\\audit_logs\\index', $view_data);
    }

    public function list_data()
    {
        $this->ensureSettingsAccess();

        $rows = array();
        foreach ($this->audit_logs_model->get_details(array(
            'team_member_id' => (int) $this->request->getPost('team_member_id'),
            'action' => clean_data($this->request->getPost('action')),
            'entity_type' => clean_data($this->request->getPost('entity_type')),
            'status' => clean_data($this->request->getPost('status')),
            'date_from' => clean_data($this->request->getPost('date_from')),
            'date_to' => clean_data($this->request->getPost('date_to')),
            'search' => clean_data($this->request->getPost('search')),
        ))->getResult() as $log) {
            $rows[] = $this->_make_row($log);
        }

        echo json_encode(array('data' => $rows));
    }

    public function details($id = 0)
    {
        $this->ensureSettingsAccess();

        $log = $this->audit_logs_model->get_details(array('id' => (int) $id))->getRow();
        if (!$log) {
            app_redirect('forbidden');
        }

        $view_data['log'] = $log;
        return $this->template->rander('PontoRH\\Views\\audit_logs\\details', $view_data);
    }

    public function view_modal($id = 0)
    {
        $this->ensureSettingsAccess();

        $log = $this->audit_logs_model->get_details(array('id' => (int) $id))->getRow();
        if (!$log) {
            app_redirect('forbidden');
        }

        return $this->renderPluginView('audit_logs/details', array('log' => $log));
    }

    private function _make_row($log)
    {
        $options = modal_anchor(get_uri('pontorh/auditoria/view_modal/' . (int) $log->id), "<i data-feather='eye' class='icon-14'></i>", array(
            'class' => 'action-icon',
            'title' => app_lang('view_details'),
            'data-modal-lg' => '1',
        ));

        return array(
            esc($log->created_at),
            esc($log->team_member_name ?: '-'),
            esc($log->creator_name ?: '-'),
            esc($log->entity_type),
            esc($log->action),
            esc($log->description ?: '-'),
            esc($log->source ?: '-'),
            esc($log->status ?: '-'),
            $options,
        );
    }

    private function auditActionsDropdown()
    {
        return array(
            '' => '-',
            'create' => app_lang('create'),
            'update' => app_lang('edit'),
            'approve' => app_lang('approve'),
            'reject' => app_lang('reject'),
            'login_api' => 'Login API',
            'invalid_attempt' => 'Invalid attempt',
        );
    }
}
