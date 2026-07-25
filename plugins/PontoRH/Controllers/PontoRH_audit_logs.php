<?php

namespace PontoRH\Controllers;

class PontoRH_audit_logs extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureSettingsAccess();

        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, 'all');
        $view_data['action_dropdown'] = $this->auditActionsDropdown();
        $view_data['entity_dropdown'] = $this->auditEntitiesDropdown();
        $view_data['status_dropdown'] = array(
            '' => '-',
            'logged' => app_lang('pontorh_audit_status_logged'),
            'reviewed' => app_lang('pontorh_audit_status_reviewed'),
            'blocked' => app_lang('pontorh_audit_status_blocked'),
            'invalid' => app_lang('pontorh_audit_status_invalid'),
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

        $view_data['log'] = $this->normalizeAuditLog($log);
        return $this->template->rander('PontoRH\\Views\\audit_logs\\details', $view_data);
    }

    public function view_modal($id = 0)
    {
        $this->ensureSettingsAccess();

        $log = $this->audit_logs_model->get_details(array('id' => (int) $id))->getRow();
        if (!$log) {
            app_redirect('forbidden');
        }

        return $this->renderPluginView('audit_logs/details', array('log' => $this->normalizeAuditLog($log)));
    }

    private function _make_row($log)
    {
        $options = modal_anchor(get_uri('pontorh/auditoria/view_modal/' . (int) $log->id), "<i data-feather='eye' class='icon-14'></i>", array(
            'class' => 'action-icon',
            'title' => app_lang('view_details'),
            'data-modal-lg' => '1',
        ));

        return array(
            esc($this->formatAuditDateTime($log->created_at ?? '')),
            esc($log->team_member_name ?: '-'),
            esc($log->creator_name ?: '-'),
            esc($this->translateAuditValue('pontorh_audit_entity_' . strtolower((string) ($log->entity_type ?? '')), $log->entity_type ?? '-')),
            esc($this->translateAuditAction((string) ($log->action ?? ''))),
            esc($this->translateAuditDescription((string) ($log->description ?? '')) ?: '-'),
            esc($this->translateAuditSource((string) ($log->source ?? ''))),
            esc($this->translateAuditStatus((string) ($log->status ?? ''))),
            $options,
        );
    }

    private function auditActionsDropdown()
    {
        return array(
            '' => '-',
            'create' => app_lang('pontorh_audit_action_create'),
            'update' => app_lang('pontorh_audit_action_update'),
            'approve' => app_lang('pontorh_audit_action_approve'),
            'reject' => app_lang('pontorh_audit_action_reject'),
            'checkin' => app_lang('pontorh_audit_action_checkin'),
            'manual_mark_added' => app_lang('pontorh_audit_action_manual_mark_added'),
            'adjustment_requested' => app_lang('pontorh_audit_action_adjustment_requested'),
            'device_registered' => app_lang('pontorh_audit_action_device_registered'),
            'login_api' => app_lang('pontorh_audit_action_login_api'),
            'invalid_attempt' => app_lang('pontorh_audit_action_invalid_attempt'),
        );
    }

    private function auditEntitiesDropdown()
    {
        return array(
            '' => '-',
            'auth' => app_lang('pontorh_audit_entity_auth'),
            'checkin' => app_lang('pontorh_audit_entity_checkin'),
            'adjustment' => app_lang('pontorh_audit_entity_adjustment'),
            'device' => app_lang('pontorh_audit_entity_device'),
            'record' => app_lang('pontorh_audit_entity_record'),
            'pontorh_locations' => app_lang('pontorh_audit_entity_pontorh_locations'),
            'pontorh_settings' => app_lang('pontorh_audit_entity_pontorh_settings'),
            'pontorh_treatment' => app_lang('pontorh_audit_entity_treatment'),
            'pontorh_access' => app_lang('pontorh_audit_entity_access'),
        );
    }

    private function normalizeAuditLog($log)
    {
        if (!$log) {
            return $log;
        }

        $log->created_at_formatted = $this->formatAuditDateTime($log->created_at ?? '');
        $log->action_label = $this->translateAuditAction((string) ($log->action ?? ''));
        $log->status_label = $this->translateAuditStatus((string) ($log->status ?? ''));
        $log->source_label = $this->translateAuditSource((string) ($log->source ?? ''));
        $log->entity_type_label = $this->translateAuditValue('pontorh_audit_entity_' . strtolower((string) ($log->entity_type ?? '')), $log->entity_type ?? '-');
        $log->description = $this->translateAuditDescription((string) ($log->description ?? ''));
        return $log;
    }

    private function formatAuditDateTime($date_time): string
    {
        $date_time = trim((string) $date_time);
        if ($date_time === '' || !is_date_exists($date_time)) {
            return '-';
        }

        return format_to_datetime($date_time);
    }

    private function translateAuditAction(string $action): string
    {
        return $this->translateAuditValue('pontorh_audit_action_' . strtolower($action), $action ?: '-');
    }

    private function translateAuditStatus(string $status): string
    {
        return $this->translateAuditValue('pontorh_audit_status_' . strtolower($status), $status ?: '-');
    }

    private function translateAuditSource(string $source): string
    {
        return $this->translateAuditValue('pontorh_audit_source_' . strtolower($source), $source ?: '-');
    }

    private function translateAuditValue(string $key, string $fallback): string
    {
        $value = app_lang($key);
        if ($value === $key || $value === '') {
            return $fallback;
        }

        return $value;
    }
}
