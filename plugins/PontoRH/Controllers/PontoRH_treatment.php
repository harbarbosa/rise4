<?php

namespace PontoRH\Controllers;

class PontoRH_treatment extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureTreatmentAccess();

        $filters = $this->getTreatmentFilters();
        $scope = $this->currentDataScope();
        $member_ids = $this->accessibleTeamMemberIds($scope);

        $overview = $this->treatment_cases_model->get_dashboard_summary(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $member_ids,
            'team_member_id' => $filters['team_member_id'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ));

        $view_data['filters'] = $filters;
        $view_data['summary'] = $overview;
        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, $scope);
        $view_data['status_dropdown'] = pontorh_treatment_status_options();
        $view_data['pending_type_dropdown'] = pontorh_treatment_pending_type_options();
        $view_data['month_dropdown'] = pontorh_month_options();
        $view_data['year_dropdown'] = $this->getTreatmentYearOptions();
        $view_data['dashboard_period'] = $this->getTreatmentPeriodLabel($filters['date_from'], $filters['date_to']);

        return $this->template->rander('PontoRH\\Views\\treatment\\index', $view_data);
    }

    public function list_data()
    {
        $this->ensureTreatmentAccess();

        $filters = $this->getTreatmentFilters();
        $scope = $this->currentDataScope();
        $rows = $this->treatment_cases_model->sync_cases(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($scope),
            'team_member_id' => $filters['team_member_id'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ));

        $team_member_filter = (int) $this->request->getPost('team_member_id');
        $status_filter = trim((string) $this->request->getPost('status'));
        $pending_type_filter = trim((string) $this->request->getPost('pending_type'));
        $search = strtolower(trim((string) $this->request->getPost('search')));

        $data = array();
        foreach ($rows as $row) {
            if ($team_member_filter && (int) $row['team_member_id'] !== $team_member_filter) {
                continue;
            }
            if ($status_filter !== '' && (string) $row['status'] !== $status_filter) {
                continue;
            }
            if ($pending_type_filter !== '' && (string) $row['pending_type'] !== $pending_type_filter) {
                continue;
            }
            if ($search !== '') {
                $haystack = strtolower(implode(' ', array(
                    $row['team_member_name'] ?? '',
                    $row['project_name'] ?? '',
                    $row['work_date'] ?? '',
                    $row['status'] ?? '',
                    $row['pending_type'] ?? '',
                )));
                if (strpos($haystack, $search) === false) {
                    continue;
                }
            }

            $data[] = $this->makeRow($row);
        }

        echo json_encode(array('data' => $data));
    }

    public function details($id = 0)
    {
        $this->ensureTreatmentAccess();

        $id = (int) ($id ?: $this->request->getPost('id'));
        $case = $this->treatment_cases_model->get_one_with_details($id);
        if (!$case || empty($case->id)) {
            app_redirect('forbidden');
        }

        $case_records = $this->records_model->get_details(array(
            'team_member_id' => (int) $case->team_member_id,
            'date_from' => $case->work_date,
            'date_to' => $case->work_date,
        ))->getResult();

        $view_data['case'] = $case;
        $view_data['records'] = $case_records ?: array();
        $view_data['history'] = $this->treatment_history_model->get_details(array(
            'treatment_case_id' => (int) $case->id,
        ))->getResult();
        $view_data['diagnostics'] = $this->decodeJson($case->diagnostics_json ?? null);
        $view_data['classification'] = $this->decodeJson($case->classification_json ?? null);
        $view_data['final'] = $this->decodeJson($case->final_json ?? null);

        return $this->renderPluginView('treatment/details', $view_data);
    }

    public function modal_form($case_id = 0)
    {
        $this->ensureTreatmentWriteAccess();

        $case_id = (int) ($case_id ?: $this->request->getPost('id'));
        $case = $case_id ? $this->treatment_cases_model->get_one_with_details($case_id) : null;
        $view_data['case'] = $case;
        $view_data['punch_type_dropdown'] = array(
            '' => '-',
            'in' => app_lang('pontorh_punch_type_in'),
            'lunch_out' => app_lang('pontorh_punch_type_lunch_out'),
            'lunch_return' => app_lang('pontorh_punch_type_lunch_return'),
            'out' => app_lang('pontorh_punch_type_out'),
        );

        return $this->renderPluginView('treatment/modal_form', $view_data);
    }

    public function save_manual()
    {
        $this->ensureTreatmentWriteAccess();

        $this->validate_submitted_data(array(
            'team_member_id' => 'required',
            'work_date' => 'required',
            'punch_time' => 'required',
            'punch_type' => 'required',
            'justification' => 'required',
        ));

        $team_member_id = (int) $this->request->getPost('team_member_id');
        $work_date = $this->service->normalizeDate($this->request->getPost('work_date')) ?: date('Y-m-d');
        $punch_time = $this->combineDateTime($work_date, trim((string) $this->request->getPost('punch_time')));
        $punch_type = clean_data($this->request->getPost('punch_type'));
        $justification = clean_data($this->request->getPost('justification'));
        $notes = clean_data($this->request->getPost('notes'));

        $record = array(
            'team_member_id' => $team_member_id,
            'user_id' => (int) $this->login_user->id,
            'work_schedule_id' => null,
            'device_id' => null,
            'location_id' => null,
            'date' => $work_date,
            'punch_time' => $punch_time,
            'punch_type' => in_array($punch_type, array('in', 'lunch_out', 'lunch_return', 'out'), true) ? $punch_type : 'in',
            'latitude' => 0,
            'longitude' => 0,
            'ip_address' => $this->request->getIPAddress(),
            'source' => 'manual',
            'status' => 'adjusted',
            'hash' => hash('sha256', implode('|', array($team_member_id, $work_date, $punch_time, microtime(true)))),
            'work_date' => $work_date,
            'check_in' => in_array($punch_type, array('in', 'lunch_return'), true) ? $punch_time : null,
            'check_out' => in_array($punch_type, array('out', 'lunch_out'), true) ? $punch_time : null,
            'break_minutes' => 0,
            'minutes_worked' => 0,
            'notes' => $notes ?: $justification,
            'created_by' => (int) $this->login_user->id,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        $save = $record;
        $record_id = $this->records_model->ci_save($save);
        if (!$record_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $case = $this->treatment_cases_model->get_or_create_case($team_member_id, $work_date, array(
            'scope' => $this->currentDataScope(),
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($this->currentDataScope()),
        ));

        if ($case && !empty($case->id)) {
            $this->treatment_history_model->log_action(array(
                'treatment_case_id' => (int) $case->id,
                'team_member_id' => $team_member_id,
                'user_id' => (int) $this->login_user->id,
                'action' => 'manual_mark_added',
                'old_value_json' => null,
                'new_value_json' => pontorh_safe_json($record),
                'justification' => $justification,
                'ip_address' => $this->request->getIPAddress(),
                'source' => 'manual',
                'status' => 'logged',
                'created_by' => (int) $this->login_user->id,
                'created_at' => get_current_utc_time(),
            ));

            $this->logAudit('pontorh_treatment', (int) $case->id, 'manual_mark_added', 'Manual mark added in treatment', array('record' => $record), $team_member_id);
        }

        echo json_encode(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'id' => $record_id,
        ));
    }

    public function action()
    {
        $this->ensureTreatmentWriteAccess();

        $case_id = (int) $this->request->getPost('case_id');
        $action = trim((string) $this->request->getPost('action_type'));
        $justification = clean_data($this->request->getPost('justification'));
        $case = $this->treatment_cases_model->get_one_with_details($case_id);

        if (!$case || empty($case->id)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $before = $case;
        $status = (string) $case->status;
        $pending_type = (string) $case->pending_type;
        $closed_at = $case->closed_at ?? null;

        switch ($action) {
            case 'reprocess':
                $this->treatment_cases_model->sync_cases(array(
                    'scope' => 'all',
                    'current_user_id' => (int) $this->login_user->id,
                    'team_member_ids' => array((int) $case->team_member_id),
                    'team_member_id' => (int) $case->team_member_id,
                    'date_from' => $case->work_date,
                    'date_to' => $case->work_date,
                ));
                $status = 'pending';
                break;
            case 'request_justification':
                $status = 'awaiting_justification';
                $pending_type = 'awaiting_justification';
                break;
            case 'ignore_extra':
                $status = 'treated_manual';
                $pending_type = 'ignored';
                break;
            case 'correct_classification':
                $status = 'treated_manual';
                $pending_type = 'corrected';
                break;
            case 'approve_day':
                $status = 'treated_manual';
                break;
            case 'close_day':
                $status = 'closed';
                $closed_at = get_current_utc_time();
                break;
            case 'forward_rh':
                $status = 'adjustment_requested';
                $pending_type = 'adjustment_requested';
                break;
            default:
                echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
                return;
        }

        $save = array(
            'status' => $status,
            'pending_type' => $pending_type,
            'last_updated_by' => (int) $this->login_user->id,
            'last_updated_at' => get_current_utc_time(),
            'closed_at' => $closed_at,
            'updated_at' => get_current_utc_time(),
        );

        $this->treatment_cases_model->save_action($case_id, $save);
        $this->treatment_history_model->log_action(array(
            'treatment_case_id' => $case_id,
            'team_member_id' => (int) $case->team_member_id,
            'user_id' => (int) $this->login_user->id,
            'action' => $action,
            'old_value_json' => pontorh_safe_json($before),
            'new_value_json' => pontorh_safe_json(array_merge((array) $before, $save)),
            'justification' => $justification,
            'ip_address' => $this->request->getIPAddress(),
            'source' => 'manual',
            'status' => 'logged',
            'created_by' => (int) $this->login_user->id,
            'created_at' => get_current_utc_time(),
        ));

        $this->logAudit('pontorh_treatment', $case_id, $action, 'Treatment action executed', array('before' => $before, 'after' => $save, 'justification' => $justification), (int) $case->team_member_id);

        echo json_encode(array('success' => true, 'message' => app_lang('saved')));
    }

    private function makeRow(array $row)
    {
        $status = (string) ($row['status'] ?? 'pending');
        $pending_type = (string) ($row['pending_type'] ?? 'incomplete');
        $case_id = (int) ($row['id'] ?? 0);
        $details_url = get_uri('pontorh/tratamento/detalhes');
        $modal_url = get_uri('pontorh/tratamento/modal_form');

        $actions = modal_anchor($details_url, "<i data-feather='eye' class='icon-14'></i>", array(
            'class' => 'action-icon',
            'title' => app_lang('view_details'),
            'data-modal-lg' => '1',
            'data-post-id' => $case_id,
        ));

        $actions .= modal_anchor($modal_url, "<i data-feather='plus-circle' class='icon-14'></i>", array(
            'class' => 'action-icon',
            'title' => app_lang('pontorh_add_manual_mark'),
            'data-modal-lg' => '1',
            'data-post-id' => $case_id,
        ));

        return array(
            esc($row['team_member_name'] ?? $row['user_name'] ?? '-'),
            esc(format_to_date($row['work_date'] ?? '', false)),
            esc($row['project_name'] ?? '-'),
            (int) ($row['record_count'] ?? 0),
            '<span class="badge bg-secondary">' . esc(pontorh_treatment_status_label($status)) . '</span>',
            esc(pontorh_treatment_pending_type_label($pending_type)),
            !empty($row['last_updated_at']) && is_date_exists($row['last_updated_at']) ? format_to_datetime($row['last_updated_at'], false) : '-',
            $actions,
        );
    }

    private function getTreatmentFilters()
    {
        $now = new \DateTimeImmutable('now');
        $date_from = trim((string) ($this->request->getPost('date_from') ?: $this->request->getGet('date_from')));
        $date_to = trim((string) ($this->request->getPost('date_to') ?: $this->request->getGet('date_to')));
        $team_member_id = (int) ($this->request->getPost('team_member_id') ?: $this->request->getGet('team_member_id'));
        $status = trim((string) ($this->request->getPost('status') ?: $this->request->getGet('status')));
        $pending_type = trim((string) ($this->request->getPost('pending_type') ?: $this->request->getGet('pending_type')));

        if (!$date_from || !$date_to) {
            $date_from = $now->format('Y-m-01');
            $date_to = $now->format('Y-m-t');
        }

        $normalized_date_from = $this->service->normalizeDate($date_from);
        $normalized_date_to = $this->service->normalizeDate($date_to);

        return array(
            'team_member_id' => $team_member_id,
            'date_from' => $normalized_date_from ?: $now->format('Y-m-01'),
            'date_to' => $normalized_date_to ?: $now->format('Y-m-t'),
            'status' => $status,
            'pending_type' => $pending_type,
            'month' => (int) $now->format('n'),
            'year' => (int) $now->format('Y'),
        );
    }

    private function getTreatmentYearOptions()
    {
        $current_year = (int) date('Y');
        $years = array();
        for ($year = $current_year - 1; $year <= $current_year + 1; $year++) {
            $years[$year] = $year;
        }

        return $years;
    }

    private function getTreatmentPeriodLabel($date_from, $date_to)
    {
        return format_to_date($date_from, false) . ' - ' . format_to_date($date_to, false);
    }

    private function decodeJson($json)
    {
        if (!$json) {
            return array();
        }
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : array();
    }
}
