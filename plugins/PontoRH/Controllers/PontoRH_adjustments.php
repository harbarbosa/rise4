<?php

namespace PontoRH\Controllers;

class PontoRH_adjustments extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureAdjustmentsAccess();

        $scope = $this->currentDataScope();
        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, $scope);
        $view_data['adjustment_type_dropdown'] = pontorh_adjustment_type_options();
        $view_data['status_dropdown'] = pontorh_adjustment_status_options();
        $view_data['can_request'] = \PontoRH\Plugin::canRequestAdjustment($this->login_user) || \PontoRH\Plugin::canAdmin($this->login_user);
        $view_data['can_approve'] = \PontoRH\Plugin::canApproveAdjustment($this->login_user) || \PontoRH\Plugin::canAdmin($this->login_user);
        $view_data['can_manage'] = \PontoRH\Plugin::canManageAdjustments($this->login_user) || \PontoRH\Plugin::canAdmin($this->login_user);
        $view_data['current_scope'] = $scope;

        return $this->template->rander('PontoRH\\Views\\adjustments\\index', $view_data);
    }

    public function list_data()
    {
        $this->ensureAdjustmentsAccess();

        $scope = $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);
        $requested_member_id = (int) $this->request->getPost('team_member_id');
        if ($scope !== 'all' && $requested_member_id && !in_array($requested_member_id, $allowed_member_ids, true)) {
            $requested_member_id = 0;
        }

        $rows = array();
        foreach ($this->adjustments_model->get_details(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $allowed_member_ids,
            'team_member_id' => $requested_member_id,
            'status' => clean_data($this->request->getPost('status')),
            'adjustment_type' => clean_data($this->request->getPost('adjustment_type')),
            'date_from' => clean_data($this->request->getPost('date_from')),
            'date_to' => clean_data($this->request->getPost('date_to')),
            'search' => clean_data($this->request->getPost('search')),
        ))->getResult() as $adjustment) {
            $rows[] = $this->_make_row($adjustment);
        }

        echo json_encode(array('data' => $rows));
    }

    public function details($id = 0)
    {
        $this->ensureAdjustmentsAccess();

        $adjustment = $this->getAccessibleAdjustment((int) $id);
        if (!$adjustment) {
            app_redirect('forbidden');
        }

        $view_data['adjustment'] = $adjustment;
        $view_data['can_approve'] = \PontoRH\Plugin::canApproveAdjustment($this->login_user) || \PontoRH\Plugin::canAdmin($this->login_user);

        return $this->template->rander('PontoRH\\Views\\adjustments\\details', $view_data);
    }

    public function view_modal($id = 0)
    {
        $this->ensureAdjustmentsAccess();

        $adjustment = $this->getAccessibleAdjustment($id ? (int) $id : (int) $this->request->getPost('id'));
        if (!$adjustment) {
            app_redirect('forbidden');
        }

        $view_data['adjustment'] = $adjustment;
        $view_data['can_approve'] = \PontoRH\Plugin::canApproveAdjustment($this->login_user) || \PontoRH\Plugin::canAdmin($this->login_user);

        return $this->renderPluginView('adjustments/modal_view', $view_data);
    }

    public function modal_form($id = 0)
    {
        $this->ensureAdjustmentsWriteAccess();

        $scope = $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);
        $id = $id ? (int) $id : (int) $this->request->getPost('id');

        if ($id) {
            $view_data['model_info'] = $this->adjustments_model->get_one_with_details($id, array(
                'scope' => $scope,
                'current_user_id' => (int) $this->login_user->id,
                'team_member_ids' => $allowed_member_ids,
            ));
            if (!$view_data['model_info']) {
                app_redirect('forbidden');
            }

            if ($view_data['model_info']->status !== 'pending' && !\PontoRH\Plugin::canAdmin($this->login_user) && !\PontoRH\Plugin::canApproveAdjustment($this->login_user)) {
                app_redirect('forbidden');
            }
        } else {
            $view_data['model_info'] = (object) array(
                'id' => 0,
                'team_member_id' => '',
                'request_date' => get_my_local_time('Y-m-d'),
                'requested_time' => get_my_local_time('H:i'),
                'adjustment_type' => 'in',
                'reason' => '',
                'status' => 'pending',
            );
        }

        $view_data['model_info']->team_member_id = $view_data['model_info']->team_member_id ?? $this->login_user->id;
        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, $scope);
        $view_data['adjustment_type_dropdown'] = pontorh_adjustment_type_options();

        return $this->renderPluginView('adjustments/modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureAdjustmentsWriteAccess();

        $scope = $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);
        $this->validate_submitted_data(array(
            'team_member_id' => 'required',
            'request_date' => 'required',
            'requested_time' => 'required',
            'adjustment_type' => 'required',
            'reason' => 'required',
        ));

        $id = (int) $this->request->getPost('id');
        $before = null;
        if ($id) {
            $before = $this->getAccessibleAdjustment($id);
            if (!$before) {
                app_redirect('forbidden');
            }
            if ($before->status !== 'pending' && !\PontoRH\Plugin::canAdmin($this->login_user) && !\PontoRH\Plugin::canApproveAdjustment($this->login_user)) {
                app_redirect('forbidden');
            }
        }

        $requested_team_member_id = (int) $this->request->getPost('team_member_id');
        if ($scope === 'own') {
            $requested_team_member_id = (int) $this->login_user->id;
        } else if ($scope === 'team' && $allowed_member_ids && !in_array($requested_team_member_id, $allowed_member_ids, true)) {
            app_redirect('forbidden');
        } else if ($scope !== 'all' && !$requested_team_member_id) {
            $requested_team_member_id = (int) $this->login_user->id;
        }

        $request_date = $this->service->normalizeDate($this->request->getPost('request_date')) ?: get_my_local_time('Y-m-d');
        $requested_time = trim((string) $this->request->getPost('requested_time'));
        $requested_datetime = $this->combineDateTime($request_date, $requested_time);
        $status = 'pending';

        $data = array(
            'team_member_id' => $requested_team_member_id,
            'user_id' => (int) $this->login_user->id,
            'request_date' => $request_date,
            'requested_time' => $requested_datetime,
            'adjustment_type' => clean_data($this->request->getPost('adjustment_type')) ?: 'in',
            'reason' => clean_data($this->request->getPost('reason')),
            'status' => $status,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'ip_address' => $this->request->getIPAddress(),
            'source' => 'manual',
            'hash' => hash('sha256', implode('|', array(
                $requested_team_member_id,
                (int) $this->login_user->id,
                $request_date,
                $requested_datetime,
                $status,
                microtime(true),
            ))),
            'deleted' => 0,
        );

        if (!$id) {
            $data['created_by'] = (int) $this->login_user->id;
            $data['created_at'] = get_current_utc_time();
        }
        $data['updated_at'] = get_current_utc_time();

        try {
            $save_id = $this->adjustments_model->ci_save($data, $id);
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Adjustment save failed: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }
        if (!$save_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $adjustment = $this->adjustments_model->get_one_with_details($save_id, array(
            'scope' => 'all',
            'current_user_id' => (int) $this->login_user->id,
        ));
        if (!$adjustment) {
            $adjustment = (object) array_merge($data, array('id' => $save_id));
        }

        try {
            $this->logAudit(
                'pontorh_adjustment_requests',
                $save_id,
                $id ? 'update' : 'create',
                $id ? app_lang('pontorh_record_updated') : app_lang('pontorh_adjustment_request'),
                array('before' => $before, 'after' => $adjustment),
                $requested_team_member_id
            );
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Adjustment audit log failed: ' . $e->getMessage());
        }

        if (!$id) {
            try {
                log_notification('pontorh_adjustment_requested', array(
                    'plugin_adjustment_id' => $save_id,
                    'plugin_requester_id' => $requested_team_member_id,
                ), (int) $this->login_user->id);
            } catch (\Throwable $e) {
                log_message('error', '[PontoRH] Adjustment notification failed: ' . $e->getMessage());
            }
        }

        echo json_encode(array(
            'success' => true,
            'data' => $this->_make_row($adjustment),
            'id' => $save_id,
            'message' => app_lang('record_saved'),
        ));
    }

    public function review()
    {
        if (!\PontoRH\Plugin::canApproveAdjustment($this->login_user) && !\PontoRH\Plugin::canAdmin($this->login_user)) {
            app_redirect('forbidden');
        }

        $this->validate_submitted_data(array(
            'id' => 'required',
            'decision' => 'required',
        ));

        $id = (int) $this->request->getPost('id');
        $decision = clean_data($this->request->getPost('decision'));
        if (!in_array($decision, array('approved', 'rejected'), true)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $adjustment = $this->adjustments_model->get_one_with_details($id, array('scope' => 'all', 'current_user_id' => (int) $this->login_user->id));
        if (!$adjustment || $adjustment->status !== 'pending') {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $before = $adjustment;
        $now = get_current_utc_time();
        try {
            $update_data = array(
                'status' => $decision,
                'reviewed_by' => (int) $this->login_user->id,
                'reviewed_at' => $now,
                'updated_at' => $now,
            );
            $success = $this->adjustments_model->ci_save($update_data, $id);
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Adjustment review failed: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        if (!$success) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $adjustment = $this->adjustments_model->get_one_with_details($id, array('scope' => 'all', 'current_user_id' => (int) $this->login_user->id));
        $this->logAudit(
            'pontorh_adjustment_requests',
            $id,
            $decision,
            $decision === 'approved' ? app_lang('pontorh_adjustment_approve') : app_lang('pontorh_adjustment_reject'),
            array('before' => $before, 'after' => $adjustment),
            (int) $adjustment->team_member_id
        );

        log_notification('pontorh_adjustment_reviewed', array(
            'plugin_adjustment_id' => $id,
            'plugin_requester_id' => (int) $adjustment->team_member_id,
            'plugin_decision' => $decision,
        ), (int) $this->login_user->id);

        echo json_encode(array(
            'success' => true,
            'message' => $decision === 'approved' ? app_lang('pontorh_adjustment_approve') : app_lang('pontorh_adjustment_reject'),
        ));
    }

    public function delete()
    {
        if (!\PontoRH\Plugin::canAdmin($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $adjustment = $this->adjustments_model->get_one_with_details($id, array('scope' => 'all', 'current_user_id' => (int) $this->login_user->id));
        if (!$adjustment || !$adjustment->id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $delete_data = array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        );
        $success = $this->adjustments_model->ci_save($delete_data, $id);

        if ($success) {
            $this->logAudit(
                'pontorh_adjustment_requests',
                $id,
                'delete',
                app_lang('record_deleted'),
                array('before' => $adjustment, 'after' => null),
                (int) $adjustment->team_member_id
            );
        }

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    private function _make_row($adjustment)
    {
        if (!$adjustment) {
            return array();
        }

        $status_class = 'bg-warning text-dark';
        if ($adjustment->status === 'approved') {
            $status_class = 'bg-success';
        } elseif ($adjustment->status === 'rejected') {
            $status_class = 'bg-danger';
        }

        $can_edit_pending = (
            $adjustment->status === 'pending'
            && (
                (int) $adjustment->team_member_id === (int) $this->login_user->id
                || \PontoRH\Plugin::canApproveAdjustment($this->login_user)
                || \PontoRH\Plugin::canAdmin($this->login_user)
            )
        );

        $actions = modal_anchor(get_uri('pontorh/ajustes/view_modal'), "<i data-feather='eye' class='icon-14'></i>", array(
            'class' => 'action-icon',
            'title' => app_lang('view_details'),
            'data-post-id' => $adjustment->id,
            'data-modal-lg' => '1',
        ));

        if ($can_edit_pending) {
            $actions .= modal_anchor(get_uri('pontorh/ajustes/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array(
                'class' => 'action-icon',
                'title' => app_lang('edit'),
                'data-post-id' => $adjustment->id,
                'data-modal-lg' => '1',
            ));
        }

        if (\PontoRH\Plugin::canApproveAdjustment($this->login_user) || \PontoRH\Plugin::canAdmin($this->login_user)) {
            $actions .= modal_anchor(get_uri('pontorh/ajustes/view_modal'), "<i data-feather='check-circle' class='icon-14'></i>", array(
                'class' => 'action-icon text-success',
                'title' => app_lang('pontorh_adjustment_review'),
                'data-post-id' => $adjustment->id,
                'data-modal-lg' => '1',
            ));
        }

        if (\PontoRH\Plugin::canAdmin($this->login_user)) {
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array(
                'class' => 'action-icon text-danger',
                'title' => app_lang('delete'),
                'data-id' => $adjustment->id,
                'data-action-url' => get_uri('pontorh/ajustes/delete'),
                'data-action' => 'delete-confirmation',
            ));
        }

        return array(
            esc($adjustment->team_member_name ?: '-'),
            esc($adjustment->adjustment_date ?: '-'),
            esc($adjustment->adjustment_time ? pontorh_extract_time($adjustment->adjustment_time) : '-'),
            esc(pontorh_adjustment_type_label($adjustment->adjustment_type ?? '')),
            esc($adjustment->reason ?: '-'),
            '<span class="badge ' . $status_class . '">' . esc(pontorh_adjustment_status_label($adjustment->status ?? '')) . '</span>',
            $actions,
        );
    }

    private function getAccessibleAdjustment($id)
    {
        if (!$id) {
            return null;
        }

        $scope = $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);
        return $this->adjustments_model->get_one_with_details($id, array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $allowed_member_ids,
        ));
    }
}
