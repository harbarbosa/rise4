<?php

namespace PontoRH\Controllers;

class PontoRH_shifts extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureSchedulesAccess();

        $view_data['can_manage'] = \PontoRH\Plugin::canManageShifts($this->login_user);
        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, 'all');
        $view_data['schedule_type_dropdown'] = pontorh_schedule_type_options();

        return $this->template->rander('PontoRH\\Views\\shifts\\index', $view_data);
    }

    public function list_data()
    {
        $this->ensureSchedulesAccess();

        $rows = array();
        foreach ($this->shifts_model->get_details(array(
            'search' => clean_data($this->request->getPost('search')),
            'team_member_id' => (int) $this->request->getPost('team_member_id'),
            'schedule_type' => clean_data($this->request->getPost('schedule_type')),
            'active' => clean_data($this->request->getPost('active')),
        ))->getResult() as $shift) {
            $rows[] = $this->_make_row($shift);
        }

        echo json_encode(array('data' => $rows));
    }

    private function _make_row($shift)
    {
        $status = $shift->active ? '<span class="badge bg-success">' . app_lang('active') . '</span>' : '<span class="badge bg-secondary">' . app_lang('inactive') . '</span>';
        $actions = '';

        if (\PontoRH\Plugin::canManageShifts($this->login_user)) {
            $actions .= modal_anchor(get_uri('pontorh/jornadas/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array(
                'class' => 'action-icon',
                'title' => app_lang('edit'),
                'data-post-id' => $shift->id,
                'data-modal-lg' => '1',
            ));
            $actions .= js_anchor("<i data-feather='power' class='icon-14'></i>", array(
                'class' => 'action-icon text-success',
                'title' => $shift->active ? app_lang('deactivate') : app_lang('activate'),
                'data-id' => $shift->id,
                'data-action-url' => get_uri('pontorh/jornadas/toggle_active'),
                'data-action' => 'toggle-confirmation',
            ));
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array(
                'class' => 'action-icon text-danger',
                'title' => app_lang('delete'),
                'data-id' => $shift->id,
                'data-action-url' => get_uri('pontorh/jornadas/delete'),
                'data-action' => 'delete-confirmation',
            ));
        }

        return array(
            esc($shift->name),
            esc($shift->team_members_name ?: $shift->team_member_name ?: '-'),
            esc(pontorh_schedule_type_label($shift->schedule_type ?? '')),
            esc($shift->start_time ?: '-'),
            esc($shift->end_time ?: '-'),
            pontorh_format_minutes($shift->break_minutes),
            (int) ($shift->tolerance_minutes ?? 0),
            (int) ($shift->extra_tolerance_minutes ?? 0),
            esc($shift->bank_hours !== null ? number_format((float) $shift->bank_hours, 2, ',', '.') : '0,00'),
            $status,
            $actions,
        );
    }

    public function modal_form($id = 0)
    {
        $this->ensureSchedulesAccess();

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id) {
            $view_data['model_info'] = $this->shifts_model->get_one_with_details($id);
            if (!$view_data['model_info']) {
                app_redirect('forbidden');
            }
            $view_data['selected_team_member_ids'] = $this->shifts_model->get_member_ids($id);
        } else {
            $view_data['model_info'] = (object) array(
                'id' => 0,
                'name' => '',
                'description' => '',
                'schedule_type' => 'comercial',
                'start_time' => '',
                'end_time' => '',
                'break_minutes' => 0,
                'tolerance_minutes' => 0,
                'extra_tolerance_minutes' => 0,
                'weekly_hours' => '',
                'bank_hours' => 0,
                'active' => 1,
            );
            $view_data['selected_team_member_ids'] = array();
        }

        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, 'all');
        $view_data['schedule_type_dropdown'] = pontorh_schedule_type_options();

        return $this->renderPluginView('shifts/modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureSchedulesAccess();
        $this->validate_submitted_data(array(
            'name' => 'required',
            'schedule_type' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ));

        $id = (int) $this->request->getPost('id');
        $team_member_ids = $this->request->getPost('team_member_ids');
        if (!is_array($team_member_ids)) {
            $team_member_ids = array($team_member_ids);
        }
        $team_member_ids = array_values(array_unique(array_filter(array_map('intval', $team_member_ids))));
        if (!$team_member_ids) {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $team_members_result = model('App\\Models\\Users_model')->get_team_members(implode(',', $team_member_ids));
        $team_members = $team_members_result ? $team_members_result->getResult() : array();
        if (count($team_members) !== count($team_member_ids)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $data = array(
            'name' => clean_data($this->request->getPost('name')),
            'description' => clean_data($this->request->getPost('description')),
            'team_member_id' => $team_member_ids[0] ?? null,
            'schedule_type' => clean_data($this->request->getPost('schedule_type')) ?: 'comercial',
            'start_time' => clean_data($this->request->getPost('start_time')) ?: null,
            'end_time' => clean_data($this->request->getPost('end_time')) ?: null,
            'break_minutes' => (int) $this->request->getPost('break_minutes'),
            'tolerance_minutes' => (int) $this->request->getPost('tolerance_minutes'),
            'extra_tolerance_minutes' => (int) $this->request->getPost('extra_tolerance_minutes'),
            'weekly_hours' => $this->request->getPost('weekly_hours') !== '' ? (float) $this->request->getPost('weekly_hours') : null,
            'bank_hours' => $this->request->getPost('bank_hours') !== '' ? (float) $this->request->getPost('bank_hours') : 0,
            'active' => $this->request->getPost('active') ? 1 : 0,
            'deleted' => 0,
        );

        $before = null;
        if ($id) {
            $before = $this->shifts_model->get_one_with_details($id);
            if (!$before || !$before->id) {
                app_redirect('forbidden');
            }
        }

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_current_utc_time();
        }
        $data['updated_at'] = get_current_utc_time();

        try {
            $save_id = $this->shifts_model->ci_save($data, $id);
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Shift save failed: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }
        if (!$save_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $shift = $this->shifts_model->get_one_with_details($save_id);
        $this->shifts_model->sync_members($save_id, $team_member_ids, (int) $this->login_user->id);
        $shift = $this->shifts_model->get_one_with_details($save_id);
        $member_names = array();
        foreach ($team_members as $team_member) {
            $member_names[] = trim((string) ($team_member->first_name ?? '') . ' ' . (string) ($team_member->last_name ?? ''));
        }
        $shift->team_members_name = implode(', ', array_filter($member_names));
        $this->logAudit(
            'pontorh_work_schedules',
            $save_id,
            $id ? 'update' : 'create',
            $id ? app_lang('pontorh_record_updated') : app_lang('pontorh_record_created'),
            array('before' => $before, 'after' => $shift),
            $team_member_id
        );

        echo json_encode(array(
            'success' => true,
            'data' => $this->_make_row($shift),
            'id' => $save_id,
            'message' => app_lang('record_saved'),
        ));
    }

    public function toggle_active()
    {
        $this->ensureSchedulesAccess();

        $id = (int) $this->request->getPost('id');
        $shift = $this->shifts_model->get_one_with_details($id);
        if (!$shift || !$shift->id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $new_active = $shift->active ? 0 : 1;
        $update_data = array(
            'active' => $new_active,
            'updated_at' => get_current_utc_time(),
        );
        $success = $this->shifts_model->ci_save($update_data, $id);

        if ($success) {
            $this->logAudit(
                'pontorh_work_schedules',
                $id,
                'toggle_active',
                $new_active ? app_lang('active') : app_lang('inactive'),
                array('before' => $shift, 'after' => array('active' => $new_active)),
                (int) $shift->team_member_id
            );
        }

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function delete()
    {
        $this->ensureSchedulesAccess();

        $id = (int) $this->request->getPost('id');
        $shift = $this->shifts_model->get_one_with_details($id);
        if (!$shift || !$shift->id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $delete_data = array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        );
        $success = $this->shifts_model->ci_save($delete_data, $id);

        if ($success) {
            $this->logAudit(
                'pontorh_work_schedules',
                $id,
                'delete',
                app_lang('record_deleted'),
                array('before' => $shift, 'after' => null),
                (int) $shift->team_member_id
            );
        }

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }
}
