<?php

namespace PontoRH\Controllers;

class PontoRH_records extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureRecordsAccess();

        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true);
        $view_data['locations_dropdown'] = $this->locationsDropdown();
        $view_data['punch_type_dropdown'] = pontorh_punch_type_options();
        $view_data['status_dropdown'] = pontorh_status_options();
        $view_data['can_manage'] = \PontoRH\Plugin::canManageRecords($this->login_user);
        $view_data['current_scope'] = $this->currentDataScope();

        return $this->template->rander('PontoRH\\Views\\records\\index', $view_data);
    }

    public function list_data()
    {
        $this->ensureRecordsAccess();

        $scope = $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);
        $requested_member_id = (int) $this->request->getPost('team_member_id');
        if ($scope !== 'all' && $requested_member_id && !in_array($requested_member_id, $allowed_member_ids, true)) {
            $requested_member_id = 0;
        }

        $options = array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_id' => $requested_member_id,
            'team_member_ids' => $allowed_member_ids,
            'punch_type' => clean_data($this->request->getPost('punch_type')),
            'status' => clean_data($this->request->getPost('status')),
            'date_from' => $this->service->normalizeDate($this->request->getPost('date_from')),
            'date_to' => $this->service->normalizeDate($this->request->getPost('date_to')),
            'search' => clean_data($this->request->getPost('search')),
        );

        $rows = array();
        foreach ($this->records_model->get_details($options)->getResult() as $record) {
            $rows[] = $this->_make_row($record);
        }

        echo json_encode(array('data' => $rows));
    }

    private function _make_row($record)
    {
        $team_member_name = esc($record->team_member_name ?: '-');
        $record_date = $record->date ?: $record->work_date ?: '';
        $work_date = $record_date && is_date_exists($record_date) ? esc(format_to_date($record_date, false)) : esc($record_date ?: '-');
        $punch_time = $record->punch_time ? esc(pontorh_extract_time($record->punch_time)) : '-';
        $punch_type = esc(pontorh_punch_type_label($record->punch_type ?? ''));
        $location_name = esc($record->location_name ?: '-');
        $source = esc($record->source ?: '-');
        $status = '<span class="badge bg-secondary">' . esc(app_lang('pontorh_status_' . $record->status)) . '</span>';

        $actions = modal_anchor(get_uri('pontorh/registros/view_modal'), "<i data-feather='eye' class='icon-14'></i>", array(
            'class' => 'action-icon',
            'title' => app_lang('view_details'),
            'data-post-id' => $record->id,
            'data-modal-lg' => '1',
        ));

        if (\PontoRH\Plugin::canManageRecords($this->login_user)) {
            $actions .= modal_anchor(get_uri('pontorh/registros/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array(
                'class' => 'action-icon',
                'title' => app_lang('edit'),
                'data-post-id' => $record->id,
                'data-modal-lg' => '1',
            ));
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array(
                'class' => 'action-icon text-danger',
                'title' => app_lang('delete'),
                'data-id' => $record->id,
                'data-action-url' => get_uri('pontorh/registros/delete'),
                'data-action' => 'delete-confirmation',
            ));
        }

        return array(
            $team_member_name,
            $work_date,
            $punch_time,
            $punch_type,
            $location_name,
            $source,
            $status,
            $actions,
        );
    }

    public function details($id = 0)
    {
        $this->ensureRecordsAccess();

        $record = $this->getAccessibleRecord((int) $id);
        if (!$record) {
            app_redirect('forbidden');
        }

        $view_data['record'] = $record;
        $view_data['can_manage'] = \PontoRH\Plugin::canManageRecords($this->login_user);

        return $this->template->rander('PontoRH\\Views\\records\\details', $view_data);
    }

    public function view_modal($id = 0)
    {
        $this->ensureRecordsAccess();

        $record = $this->getAccessibleRecord($id ? (int) $id : (int) $this->request->getPost('id'));
        if (!$record) {
            app_redirect('forbidden');
        }

        $view_data['record'] = $record;

        return $this->renderPluginView('records/modal_view', $view_data);
    }

    public function modal_form($id = 0)
    {
        $this->ensureRecordsWriteAccess();

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        $scope = $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);

        if ($id) {
            $view_data['model_info'] = $this->records_model->get_one_with_details($id, array(
                'scope' => $scope,
                'current_user_id' => (int) $this->login_user->id,
                'team_member_ids' => $allowed_member_ids,
            ));
            if (!$view_data['model_info']) {
                app_redirect('forbidden');
            }
        } else {
            $view_data['model_info'] = (object) array(
                'id' => 0,
                'team_member_id' => '',
                'location_id' => '',
                'date' => date('Y-m-d'),
                'punch_time' => get_my_local_time('H:i'),
                'punch_type' => '',
                'source' => 'manual',
                'status' => 'pending',
                'latitude' => 0,
                'longitude' => 0,
                'notes' => '',
            );
        }

        $view_data['model_info']->team_member_id = $view_data['model_info']->team_member_id ?? $this->login_user->id;
        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, $scope);
        $view_data['locations_dropdown'] = $this->assignedLocationsDropdown((int) ($view_data['model_info']->team_member_id ?? $this->login_user->id), $view_data['model_info']->date ?? date('Y-m-d'));
        $view_data['punch_type_dropdown'] = pontorh_punch_type_options();
        $view_data['status_dropdown'] = pontorh_status_options();

        return $this->renderPluginView('records/modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureRecordsWriteAccess();

        $scope = $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);
        $this->validate_submitted_data(array(
            'team_member_id' => 'required',
            'date' => 'required',
            'punch_time' => 'required',
        ));

        $id = (int) $this->request->getPost('id');
        $before = null;
        if ($id) {
            $before = $this->getAccessibleRecord($id);
            if (!$before) {
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

        $record_date = $this->service->normalizeDate($this->request->getPost('date')) ?: date('Y-m-d');
        $punch_time_value = trim((string) $this->request->getPost('punch_time'));
        $punch_time = $this->combineDateTime($record_date, $punch_time_value);
        $requested_punch_type = clean_data($this->request->getPost('punch_type'));
        $existing_count = (int) $this->records_model->get_details(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $allowed_member_ids,
            'team_member_id' => $requested_team_member_id,
            'date_from' => $record_date,
            'date_to' => $record_date,
        ))->getNumRows();
        $punch_type = in_array($requested_punch_type, array('in', 'lunch_out', 'lunch_return', 'out'), true)
            ? $requested_punch_type
            : pontorh_infer_punch_type_from_index($existing_count);
        $location_id = (int) $this->request->getPost('location_id');
        $source = clean_data($this->request->getPost('source')) ?: 'manual';
        $status = clean_data($this->request->getPost('status')) ?: 'pending';
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $notes = clean_data($this->request->getPost('notes'));

        $data = array(
            'team_member_id' => $requested_team_member_id,
            'user_id' => (int) $this->login_user->id,
            'work_schedule_id' => (int) $this->request->getPost('shift_id') ?: null,
            'device_id' => null,
            'location_id' => $location_id ?: null,
            'date' => $record_date,
            'punch_time' => $punch_time,
            'punch_type' => $punch_type,
            'latitude' => $latitude !== '' ? (float) $latitude : 0,
            'longitude' => $longitude !== '' ? (float) $longitude : 0,
            'ip_address' => $this->request->getIPAddress(),
            'source' => $source,
            'status' => $status,
            'hash' => hash('sha256', implode('|', array(
                $requested_team_member_id,
                (int) $this->login_user->id,
                $record_date,
                $punch_time,
                $punch_type,
                $source,
                microtime(true),
            ))),
            'work_date' => $record_date,
            'check_in' => in_array($punch_type, array('in', 'lunch_return'), true) ? $punch_time : null,
            'check_out' => in_array($punch_type, array('out', 'lunch_out'), true) ? $punch_time : null,
            'break_minutes' => 0,
            'minutes_worked' => 0,
            'notes' => $notes,
            'deleted' => 0,
        );

        if (!$id) {
            $data['created_by'] = (int) $this->login_user->id;
            $data['created_at'] = get_current_utc_time();
        }
        $data['updated_at'] = get_current_utc_time();

        try {
            $save_id = $this->records_model->ci_save($data, $id);
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Record save failed: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }
        if ($save_id) {
            $record = $this->records_model->get_one_with_details($save_id, array(
                'scope' => 'all',
                'current_user_id' => (int) $this->login_user->id,
            ));
            $this->logAudit(
                'pontorh_records',
                $save_id,
                $id ? 'update' : 'create',
                $id ? app_lang('pontorh_record_updated') : app_lang('pontorh_record_created'),
                array('before' => $before, 'after' => $record),
                $requested_team_member_id
            );

            echo json_encode(array(
                'success' => true,
                'data' => $this->_make_row($record),
                'id' => $save_id,
                'message' => app_lang('record_saved'),
            ));
            return;
        }

        echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function delete()
    {
        if (!\PontoRH\Plugin::canAdmin($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $record = $this->records_model->get_one_with_details($id, array(
            'scope' => 'all',
            'current_user_id' => (int) $this->login_user->id,
        ));
        if (!$record || !$record->id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $delete_data = array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        );
        $success = $this->records_model->ci_save($delete_data, $id);

        if ($success) {
            $this->logAudit(
                'pontorh_records',
                $id,
                'delete',
                app_lang('pontorh_record_deleted'),
                array('before' => $record, 'after' => null),
                (int) $record->team_member_id
            );
        }

        echo json_encode(array(
            'success' => (bool) $success,
            'message' => $success ? app_lang('pontorh_record_deleted') : app_lang('error_occurred'),
        ));
    }

    private function getAccessibleRecord($id)
    {
        if (!$id) {
            return null;
        }

        $scope = $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);

        return $this->records_model->get_one_with_details($id, array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $allowed_member_ids,
        ));
    }
}
