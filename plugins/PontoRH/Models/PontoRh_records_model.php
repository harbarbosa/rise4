<?php

namespace PontoRH\Models;

class PontoRh_records_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_records';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    protected function localTimestamp($date_time): ?int
    {
        $date_time = trim((string) $date_time);
        if ($date_time === '') {
            return null;
        }

        try {
            if (function_exists('convert_date_utc_to_local') && is_date_exists($date_time)) {
                $date_time = convert_date_utc_to_local($date_time);
            }

            $timezone = new \DateTimeZone(get_setting('timezone') ?: (date_default_timezone_get() ?: 'UTC'));
            $date = new \DateTime($date_time, $timezone);
            return $date->getTimestamp();
        } catch (\Throwable $e) {
            $timestamp = strtotime($date_time);
            return $timestamp ?: null;
        }
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $records_table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');
        $shifts_table = $this->db->prefixTable('pontorh_work_schedules');
        $locations_table = $this->db->prefixTable('pontorh_locations');
        $creator_users_table = $this->db->prefixTable('users');

        $include_photo = (bool) get_array_value($options, 'include_photo');
        $photo_select = $include_photo ? 'r.photo AS photo' : 'NULL AS photo';

        $sql = "SELECT r.id,
                    r.team_member_id,
                    r.user_id,
                    r.work_schedule_id,
                    r.device_id,
                    r.location_id,
                    r.date,
                    r.punch_time,
                    r.punch_type,
                    r.latitude,
                    r.longitude,
                    r.ip_address,
                    r.source,
                    r.status,
                    r.hash,
                    r.work_date,
                    r.check_in,
                    r.check_out,
                    r.break_minutes,
                    r.minutes_worked,
                    r.shift_id,
                    r.notes,
                    {$photo_select},
                    r.created_by,
                    r.created_at,
                    r.updated_at,
                    r.deleted,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name,
                    s.name AS shift_name,
                    l.name AS location_name,
                    l.latitude AS location_latitude,
                    l.longitude AS location_longitude,
                    l.radius_meters AS location_radius_meters,
                    CONCAT(TRIM(COALESCE(cu.first_name, '')), ' ', TRIM(COALESCE(cu.last_name, ''))) AS creator_name
                FROM {$records_table} r
                LEFT JOIN {$users_table} u ON u.id = r.team_member_id
                LEFT JOIN {$shifts_table} s ON s.id = r.work_schedule_id
                LEFT JOIN {$locations_table} l ON l.id = r.location_id
                LEFT JOIN {$creator_users_table} cu ON cu.id = r.created_by
                WHERE r.deleted = 0";

        $sql .= $this->getScopeWhere($options, 'r');

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND r.id = ' . $id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND r.team_member_id = ' . $team_member_id;
        }

        $shift_id = (int) get_array_value($options, 'shift_id');
        if ($shift_id) {
            $sql .= ' AND r.work_schedule_id = ' . $shift_id;
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $sql .= ' AND r.status = ' . $this->db->escape($status);
        }

        $punch_type = trim((string) get_array_value($options, 'punch_type'));
        if ($punch_type !== '') {
            $sql .= ' AND r.punch_type = ' . $this->db->escape($punch_type);
        }

        $date_from = trim((string) get_array_value($options, 'date_from'));
        if ($date_from !== '') {
            $sql .= ' AND r.date >= ' . $this->db->escape($date_from);
        }

        $date_to = trim((string) get_array_value($options, 'date_to'));
        if ($date_to !== '') {
            $sql .= ' AND r.date <= ' . $this->db->escape($date_to);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (r.notes LIKE '%{$search}%' ESCAPE '!'"
                . " OR s.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR l.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.first_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.last_name LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= ' ORDER BY r.date DESC, r.punch_time DESC, r.id DESC';

        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id = 0, $options = array())
    {
        $options['id'] = $id;
        $options['include_photo'] = true;
        $row = $this->get_details($options)->getRow();
        return $row ?: null;
    }

    public function get_dashboard_summary($options = array())
    {
        if (!$this->hasTable()) {
            return (object) array(
                'today_records' => 0,
                'open_today' => 0,
                'active_shifts' => 0,
                'pending_adjustments' => 0,
                'records_last_30_days' => 0,
            );
        }

        $records_table = $this->db->prefixTable($this->table);
        $adjustments_table = $this->db->prefixTable('pontorh_adjustment_requests');
        $shifts_table = $this->db->prefixTable('pontorh_work_schedules');
        $scope_where = $this->getScopeWhere($options, 'r');

        $sql = "SELECT
                    SUM(CASE WHEN r.work_date = CURDATE() THEN 1 ELSE 0 END) AS today_records,
                    SUM(CASE WHEN r.work_date = CURDATE() AND r.check_out IS NULL THEN 1 ELSE 0 END) AS open_today,
                    (SELECT COUNT(*) FROM {$shifts_table} s WHERE s.deleted = 0 AND s.active = 1) AS active_shifts,
                    (SELECT COUNT(*) FROM {$adjustments_table} a WHERE a.deleted = 0" . $this->getScopeWhere($options, 'a', true) . " AND a.status = 'pending') AS pending_adjustments,
                    SUM(CASE WHEN r.work_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS records_last_30_days
                FROM {$records_table} r
                WHERE r.deleted = 0" . $scope_where;

        $row = $this->db->query($sql)->getRow();
        return $row ?: (object) array(
            'today_records' => 0,
            'open_today' => 0,
            'active_shifts' => 0,
            'pending_adjustments' => 0,
            'records_last_30_days' => 0,
        );
    }

    public function get_recent_records($limit = 5, $options = array())
    {
        $limit = max(1, (int) $limit);
        if (!$this->hasTable()) {
            return array();
        }

        $result = $this->db->query($this->get_details_sql($options) . ' LIMIT ' . $limit);
        return $result ? $result->getResult() : array();
    }

    public function get_timesheet_rows($options = array())
    {
        $rows = array();
        foreach ($this->get_details($options)->getResult() as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function get_dashboard_overview($options = array())
    {
        if (!$this->hasTable()) {
            return array(
                'summary' => array(
                    'present_today' => 0,
                    'absent_today' => 0,
                    'late_today' => 0,
                    'extra_minutes_month' => 0,
                    'pending_adjustments' => 0,
                    'inconsistent_records' => 0,
                    'bank_minutes_end' => 0,
                    'members_visible' => 0,
                ),
                'charts' => array(
                    'labels' => array(),
                    'frequency' => array(),
                    'extra_hours' => array(),
                    'bank_hours' => array(),
                ),
                'period_start' => get_my_local_time('Y-m-01'),
                'period_end' => get_my_local_time('Y-m-t'),
                'month' => (int) get_my_local_time('n'),
                'year' => (int) get_my_local_time('Y'),
            );
        }

        $today = new \DateTimeImmutable('today');
        $month = (int) get_array_value($options, 'month');
        $year = (int) get_array_value($options, 'year');

        if ($month < 1 || $month > 12) {
            $month = (int) $today->format('n');
        }
        if ($year < 1970) {
            $year = (int) $today->format('Y');
        }

        $period_start = sprintf('%04d-%02d-01', $year, $month);
        $period_end = date('Y-m-t', strtotime($period_start));
        $today_date = $today->format('Y-m-d');
        $scope = get_array_value($options, 'scope');
        $current_user_id = (int) get_array_value($options, 'current_user_id');
        $team_member_ids = get_array_value($options, 'team_member_ids');

        $users_model = model('App\\Models\\Users_model');
        $all_members = $users_model->get_team_members_id_and_name()->getResult();
        $visible_members = array();
        foreach ($all_members as $member) {
            $member_id = (int) $member->id;
            if ($scope === 'own' && $member_id !== $current_user_id) {
                continue;
            }

            if ($scope === 'team' && is_array($team_member_ids) && $team_member_ids && !in_array($member_id, $team_member_ids, true)) {
                continue;
            }

            $visible_members[] = $member;
        }

        if (!$visible_members && $scope === 'own' && $current_user_id) {
            $current_member = $users_model->get_one($current_user_id);
            if ($current_member && !$current_member->deleted) {
                $visible_members[] = (object) array(
                    'id' => $current_member->id,
                    'user_name' => trim((string) ($current_member->first_name . ' ' . $current_member->last_name)),
                );
            }
        }

        $member_ids = array();
        foreach ($visible_members as $member) {
            $member_ids[] = (int) $member->id;
        }

        $record_rows_result = $this->get_details(array(
            'scope' => $scope,
            'current_user_id' => $current_user_id,
            'team_member_ids' => $team_member_ids,
            'date_from' => $period_start,
            'date_to' => $period_end,
        ));
        $record_rows = $record_rows_result ? $record_rows_result->getResult() : array();

        $grouped_records = array();
        foreach ($record_rows as $record) {
            $grouped_records[$record->team_member_id][$record->date][] = $record;
        }

        $schedule_model = model('PontoRH\\Models\\PontoRh_shifts_model');
        $assignment_model = model('PontoRH\\Models\\PontoRh_assignments_model');
        $day_labels = array();
        $frequency_series = array();
        $extra_series = array();
        $bank_series = array();
        $working_bank_series = array();
        $days_in_month = (int) date('t', strtotime($period_start));
        for ($day = 1; $day <= $days_in_month; $day++) {
            $day_labels[] = sprintf('%02d', $day);
            $frequency_series[$day] = 0;
            $extra_series[$day] = 0;
            $bank_series[$day] = 0;
        }

        $present_today = 0;
        $absent_today = 0;
        $late_today = 0;
        $extra_minutes_month = 0;
        $inconsistent_records = 0;
        $bank_running_totals = array();

        foreach ($visible_members as $member) {
            $member_id = (int) $member->id;
            $schedule = $schedule_model->get_active_schedule_for_member($member_id);
            $scheduled_minutes = $this->_get_schedule_minutes($schedule);
            $scheduled_start_minutes = $this->_time_to_minutes($schedule && $schedule->start_time ? $schedule->start_time : null);
            $scheduled_tolerance = (int) ($schedule->tolerance_minutes ?? 0);
            $scheduled_extra_tolerance = (int) ($schedule->extra_tolerance_minutes ?? 0);
            $bank_enabled = $this->_is_bank_hours_enabled();
            $running_bank = $bank_enabled ? (int) round(((float) ($schedule->bank_hours ?? 0)) * 60) : 0;
            $workdays_map = $this->_get_member_workdays_map($assignment_model, $member_id, $schedule);

            for ($day = 1; $day <= $days_in_month; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $day_records = get_array_value(get_array_value($grouped_records, $member_id, array()), $date, array());
                $is_workday = $this->_is_workday_for_member($date, $workdays_map);
                $day_row = $this->_build_mirror_day_row($date, $day_records, $schedule, $scheduled_minutes, $scheduled_start_minutes, $scheduled_tolerance, $scheduled_extra_tolerance, $running_bank, $is_workday);
                $running_bank = (int) $day_row['bank_minutes'];

                if ($day_row['has_records']) {
                    $frequency_series[$day] += 1;
                }

                $extra_series[$day] += (int) $day_row['extra_minutes'];
                $bank_series[$day] += (int) $day_row['bank_minutes'];
                $extra_minutes_month += (int) $day_row['extra_minutes'];

                if ($date === $today_date) {
                    if ($day_row['has_records'] && (int) $day_row['entries_count'] > 0) {
                        $present_today++;
                    }
                    if (!$day_row['has_records'] && $is_workday) {
                        $absent_today++;
                    }
                    if ((int) $day_row['lateness_minutes'] > 0) {
                        $late_today++;
                    }
                }

                if ($day_row['has_records'] && $this->_is_inconsistent_day($day_row)) {
                    $inconsistent_records++;
                }
            }

            $bank_running_totals[] = $running_bank;
        }

        $summary = array(
            'present_today' => $present_today,
            'absent_today' => $absent_today,
            'late_today' => $late_today,
            'extra_minutes_month' => $extra_minutes_month,
            'pending_adjustments' => (int) model('PontoRH\\Models\\PontoRh_adjustments_model')->get_pending_count(array(
                'scope' => $scope,
                'current_user_id' => $current_user_id,
                'team_member_ids' => $team_member_ids,
            )),
            'inconsistent_records' => $inconsistent_records,
            'bank_minutes_end' => array_sum($bank_running_totals),
            'members_visible' => count($member_ids),
        );

        return array(
            'summary' => $summary,
            'charts' => array(
                'labels' => $day_labels,
                'frequency' => array_values($frequency_series),
                'extra_hours' => array_values($extra_series),
                'bank_hours' => array_values($bank_series),
            ),
            'period_start' => $period_start,
            'period_end' => $period_end,
            'month' => $month,
            'year' => $year,
        );
    }

    public function get_report_overview($options = array())
    {
        if (!$this->hasTable()) {
            return array(
                'summary' => array(
                    'worked_minutes_total' => 0,
                    'extra_minutes_total' => 0,
                    'bank_minutes_end' => 0,
                    'absences_total' => 0,
                    'out_of_area_total' => 0,
                    'late_total' => 0,
                ),
                'charts' => array(
                    'labels' => array(),
                    'worked_hours' => array(),
                    'extra_hours' => array(),
                    'bank_hours' => array(),
                    'absences' => array(),
                    'outside_area' => array(),
                ),
                'period_start' => get_my_local_time('Y-m-01'),
                'period_end' => get_my_local_time('Y-m-t'),
                'month' => (int) get_my_local_time('n'),
                'year' => (int) get_my_local_time('Y'),
            );
        }

        $today = new \DateTimeImmutable('today');
        $month = (int) get_array_value($options, 'month');
        $year = (int) get_array_value($options, 'year');

        if ($month < 1 || $month > 12) {
            $month = (int) $today->format('n');
        }
        if ($year < 1970) {
            $year = (int) $today->format('Y');
        }

        $period_start = sprintf('%04d-%02d-01', $year, $month);
        $period_end = date('Y-m-t', strtotime($period_start));
        $today_date = $today->format('Y-m-d');
        $scope = get_array_value($options, 'scope');
        $current_user_id = (int) get_array_value($options, 'current_user_id');
        $team_member_ids = get_array_value($options, 'team_member_ids');
        $team_member_id = (int) get_array_value($options, 'team_member_id');

        $filters = array(
            'scope' => $scope,
            'current_user_id' => $current_user_id,
            'team_member_ids' => $team_member_ids,
            'team_member_id' => $team_member_id,
            'date_from' => $period_start,
            'date_to' => $period_end,
        );

        $records_result = $this->get_details($filters);
        $records = $records_result ? $records_result->getResult() : array();
        $rows_by_member_date = array();
        foreach ($records as $record) {
            $rows_by_member_date[(int) $record->team_member_id][$record->date][] = $record;
        }

        $schedule_model = model('PontoRH\\Models\\PontoRh_shifts_model');
        $assignment_model = model('PontoRH\\Models\\PontoRh_assignments_model');
        $labels = array();
        $worked_series = array();
        $extra_series = array();
        $bank_series = array();
        $absence_series = array();
        $outside_area_series = array();
        $days_in_month = (int) date('t', strtotime($period_start));

        $summary = array(
            'worked_minutes_total' => 0,
            'extra_minutes_total' => 0,
            'bank_minutes_end' => 0,
            'absences_total' => 0,
            'out_of_area_total' => 0,
            'late_total' => 0,
        );

        $running_bank = 0;
        $bank_enabled = $this->_is_bank_hours_enabled();
        $active_member_ids = array();
        if ($team_member_id) {
            $active_member_ids[] = $team_member_id;
        } elseif (is_array($team_member_ids) && $team_member_ids) {
            $active_member_ids = array_values(array_filter(array_map('intval', $team_member_ids)));
        }

        for ($day = 1; $day <= $days_in_month; $day++) {
            $labels[] = sprintf('%02d', $day);
            $worked_series[$day] = 0;
            $extra_series[$day] = 0;
            $bank_series[$day] = 0;
            $absence_series[$day] = 0;
            $outside_area_series[$day] = 0;
        }

        $members = array();
        if ($active_member_ids) {
            foreach ($active_member_ids as $member_id) {
                $members[] = $member_id;
            }
        } else {
            $users_model = model('App\\Models\\Users_model');
            foreach ($users_model->get_team_members_id_and_name()->getResult() as $row) {
                $members[] = (int) $row->id;
            }
        }

        foreach ($members as $member_id) {
            $schedule = $schedule_model->get_active_schedule_for_member($member_id);
            $scheduled_minutes = $this->_get_schedule_minutes($schedule);
            $scheduled_start_minutes = $this->_time_to_minutes($schedule && $schedule->start_time ? $schedule->start_time : null);
            $scheduled_tolerance = (int) ($schedule->tolerance_minutes ?? 0);
            $scheduled_extra_tolerance = (int) ($schedule->extra_tolerance_minutes ?? 0);
            $running_bank = $bank_enabled ? (int) round(((float) ($schedule->bank_hours ?? 0)) * 60) : 0;
            $workdays_map = $this->_get_member_workdays_map($assignment_model, $member_id, $schedule);

            for ($day = 1; $day <= $days_in_month; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $day_records = get_array_value(get_array_value($rows_by_member_date, $member_id, array()), $date, array());
                if (!is_array($day_records)) {
                    $day_records = array();
                }

                $is_workday = $this->_is_workday_for_member($date, $workdays_map);
                $day_row = $this->_build_mirror_day_row($date, $day_records, $schedule, $scheduled_minutes, $scheduled_start_minutes, $scheduled_tolerance, $scheduled_extra_tolerance, $running_bank, $is_workday);
                $running_bank = (int) $day_row['bank_minutes'];

                $worked_series[$day] += (int) $day_row['worked_minutes'];
                $extra_series[$day] += (int) $day_row['extra_minutes'];
                $bank_series[$day] += $bank_enabled ? (int) $day_row['bank_minutes'] : 0;
                $absence_series[$day] += (int) $day_row['absences'];
                $summary['worked_minutes_total'] += (int) $day_row['worked_minutes'];
                $summary['extra_minutes_total'] += (int) $day_row['extra_minutes'];
                $summary['absences_total'] += (int) $day_row['absences'];
                $summary['late_total'] += (int) $day_row['lateness_minutes'];

                foreach ($day_records as $record) {
                    if ($this->_is_record_out_of_area($record)) {
                        $outside_area_series[$day]++;
                        $summary['out_of_area_total']++;
                    }
                }

                if ($date === $today_date && $day_row['has_records']) {
                    $summary['bank_minutes_end'] = $running_bank;
                }
            }
        }

        if (!$summary['bank_minutes_end']) {
            $summary['bank_minutes_end'] = array_sum($bank_series);
        }

        return array(
            'summary' => $summary,
            'charts' => array(
                'labels' => $labels,
                'worked_hours' => array_values($worked_series),
                'extra_hours' => array_values($extra_series),
                'bank_hours' => array_values($bank_series),
                'absences' => array_values($absence_series),
                'outside_area' => array_values($outside_area_series),
            ),
            'period_start' => $period_start,
            'period_end' => $period_end,
            'month' => $month,
            'year' => $year,
        );
    }

    public function get_mirror_report($options = array())
    {
        if (!$this->hasTable()) {
            return array(
                'rows' => array(),
                'summary' => array(
                    'total_days' => 0,
                    'days_with_records' => 0,
                    'entries_total' => 0,
                    'exits_total' => 0,
                    'intervals_total' => 0,
                    'worked_minutes_total' => 0,
                    'extra_minutes_total' => 0,
                    'bank_minutes_end' => 0,
                    'absences_total' => 0,
                    'lateness_total' => 0,
                ),
                'period_start' => get_my_local_time('Y-m-01'),
                'period_end' => get_my_local_time('Y-m-t'),
                'month' => (int) get_my_local_time('n'),
                'year' => (int) get_my_local_time('Y'),
                'schedule' => null,
            );
        }

        $today = new \DateTimeImmutable('now');
        $month = (int) get_array_value($options, 'month');
        $year = (int) get_array_value($options, 'year');
        if ($month < 1 || $month > 12) {
            $month = (int) $today->format('n');
        }
        if ($year < 1970) {
            $year = (int) $today->format('Y');
        }

        $period_start = sprintf('%04d-%02d-01', $year, $month);
        $period_end = date('Y-m-t', strtotime($period_start));
        $team_member_id = (int) get_array_value($options, 'team_member_id');
        $scope = get_array_value($options, 'scope');
        $current_user_id = (int) get_array_value($options, 'current_user_id');
        $team_member_ids = get_array_value($options, 'team_member_ids');

        $report_records_result = $this->get_details(array(
            'scope' => $scope,
            'current_user_id' => $current_user_id,
            'team_member_ids' => $team_member_ids,
            'team_member_id' => $team_member_id,
            'date_from' => $period_start,
            'date_to' => $period_end,
        ));
        $report_records = $report_records_result ? $report_records_result->getResult() : array();

        $grouped = array();
        foreach ($report_records as $record) {
            $grouped[$record->team_member_id][$record->date][] = $record;
        }

        $schedule_model = model('PontoRH\\Models\\PontoRh_shifts_model');
        $schedule = $schedule_model->get_active_schedule_for_member($team_member_id);
        $scheduled_minutes = $this->_get_schedule_minutes($schedule);
        $scheduled_start_minutes = $this->_time_to_minutes($schedule && $schedule->start_time ? $schedule->start_time : null);
        $scheduled_tolerance = (int) ($schedule->tolerance_minutes ?? 0);
        $scheduled_extra_tolerance = (int) ($schedule->extra_tolerance_minutes ?? 0);
        $bank_enabled = $this->_is_bank_hours_enabled();
        $running_bank = $bank_enabled ? (int) round(((float) ($schedule->bank_hours ?? 0)) * 60) : 0;

        $rows = array();
        $summary = array(
            'total_days' => 0,
            'days_with_records' => 0,
            'entries_total' => 0,
            'exits_total' => 0,
            'intervals_total' => 0,
            'worked_minutes_total' => 0,
            'extra_minutes_total' => 0,
            'bank_minutes_end' => $running_bank,
            'absences_total' => 0,
            'lateness_total' => 0,
        );

        $period_start_dt = new \DateTimeImmutable($period_start);
        $period_end_dt = new \DateTimeImmutable($period_end);
        $days = ((int) $period_end_dt->format('d'));
        for ($day = 1; $day <= $days; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $day_records = get_array_value(get_array_value($grouped, $team_member_id, array()), $date, array());
            if (!is_array($day_records)) {
                $day_records = array();
            }
            $day_row = $this->_build_mirror_day_row($date, $day_records, $schedule, $scheduled_minutes, $scheduled_start_minutes, $scheduled_tolerance, $scheduled_extra_tolerance, $running_bank);
            $running_bank = (int) $day_row['bank_minutes'];
            $rows[] = $day_row;

            $summary['total_days']++;
            if ($day_row['has_records']) {
                $summary['days_with_records']++;
            }
            $summary['entries_total'] += (int) $day_row['entries_count'];
            $summary['exits_total'] += (int) $day_row['exits_count'];
            $summary['intervals_total'] += (int) $day_row['intervals_minutes'];
            $summary['worked_minutes_total'] += (int) $day_row['worked_minutes'];
            $summary['extra_minutes_total'] += (int) $day_row['extra_minutes'];
            $summary['absences_total'] += (int) $day_row['absences'];
            $summary['lateness_total'] += (int) $day_row['lateness_minutes'];
        }
        $summary['bank_minutes_end'] = $running_bank;

        return array(
            'rows' => $rows,
            'summary' => $summary,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'month' => $month,
            'year' => $year,
            'schedule' => $schedule,
        );
    }

    private function _build_mirror_day_row($date, $records, $schedule, $scheduled_minutes, $scheduled_start_minutes, $scheduled_tolerance, $scheduled_extra_tolerance, $running_bank, $is_workday = null)
    {
        $records = is_array($records) ? $records : array();
        usort($records, function ($a, $b) {
            return ($this->localTimestamp($a->punch_time ?? '') ?? 0) <=> ($this->localTimestamp($b->punch_time ?? '') ?? 0);
        });

        $entries = array();
        $exits = array();
        $intervals_minutes = 0;
        $worked_minutes = 0;
        $late_minutes = 0;
        $open_time = null;
        $last_exit_time = null;
        $last_exit_type = '';
        $first_entry_time = null;

        foreach ($records as $record) {
            $record_time = $this->localTimestamp($record->punch_time ?? '');
            if (!$record_time) {
                continue;
            }

            if (in_array($record->punch_type, array('in', 'lunch_return'), true)) {
                $entries[] = pontorh_extract_time($record->punch_time);
                if ($first_entry_time === null) {
                    $first_entry_time = $record_time;
                }

                if ($last_exit_time && $last_exit_type === 'lunch_out') {
                    $intervals_minutes += (int) floor(($record_time - $last_exit_time) / 60);
                }

                $open_time = $record_time;
                $last_exit_time = null;
                $last_exit_type = '';
                continue;
            }

            if (in_array($record->punch_type, array('out', 'lunch_out'), true)) {
                $exits[] = pontorh_extract_time($record->punch_time);
                if ($open_time) {
                    $worked_minutes += (int) floor(($record_time - $open_time) / 60);
                    $open_time = null;
                }
                $last_exit_time = $record_time;
                $last_exit_type = $record->punch_type;
            }
        }

        if ($schedule && $first_entry_time && $scheduled_start_minutes) {
            $day_start = $this->localTimestamp($date . ' 00:00:00') ?: strtotime($date . ' 00:00:00');
            $entry_minutes = (int) floor(($first_entry_time - $day_start) / 60);
            $late_minutes = max(0, $entry_minutes - $scheduled_start_minutes - $scheduled_tolerance);
        }

        $expected_minutes = $scheduled_minutes;
        if (!$expected_minutes && $schedule && $schedule->start_time && $schedule->end_time) {
            $expected_minutes = $this->_get_schedule_minutes($schedule);
        }

        $extra_minutes = max(0, $worked_minutes - $expected_minutes - $scheduled_extra_tolerance);
        $bank_delta = $worked_minutes - $expected_minutes;
        $bank_minutes = $running_bank + $bank_delta;
        $absence = 0;
        if ($is_workday === null) {
            $is_workday = $this->_is_workday($date);
        }

        if (!$records && $is_workday) {
            $absence = 1;
            $bank_minutes = $running_bank - $expected_minutes;
        }

        return array(
            'date' => $date,
            'weekday' => strtolower(date('l', strtotime($date))),
            'punch_count' => count($records),
            'entries' => implode(', ', $entries),
            'exits' => implode(', ', $exits),
            'entries_count' => count($entries),
            'exits_count' => count($exits),
            'intervals_minutes' => $intervals_minutes,
            'worked_minutes' => $worked_minutes,
            'extra_minutes' => $extra_minutes,
            'bank_minutes' => $bank_minutes,
            'absences' => $absence,
            'lateness_minutes' => $late_minutes,
            'has_records' => !empty($records),
            'expected_minutes' => $expected_minutes,
            'schedule_name' => $schedule && $schedule->name ? $schedule->name : '',
        );
    }

    private function _is_workday($date)
    {
        $weekday = (int) date('N', strtotime($date));
        return $weekday >= 1 && $weekday <= 5;
    }

    private function _is_workday_for_member($date, $workdays_map = array())
    {
        $weekday = (int) date('N', strtotime($date));
        if (is_array($workdays_map) && !empty($workdays_map)) {
            return in_array($weekday, $workdays_map, true);
        }

        return $this->_is_workday($date);
    }

    private function _is_bank_hours_enabled()
    {
        $settings_model = model('PontoRH\\Models\\PontoRh_settings_model');
        return (string) $settings_model->get_setting('bank_hours_enabled', '1') !== '0';
    }

    private function _is_record_out_of_area($record)
    {
        $record_lat = (float) ($record->latitude ?? 0);
        $record_lng = (float) ($record->longitude ?? 0);
        $location_lat = (float) ($record->location_latitude ?? 0);
        $location_lng = (float) ($record->location_longitude ?? 0);
        $radius = (int) ($record->location_radius_meters ?? 0);

        if (!$record->location_id || !$radius) {
            return false;
        }

        if (!$record_lat || !$record_lng || !$location_lat || !$location_lng) {
            return true;
        }

        return $this->_distanceMeters($record_lat, $record_lng, $location_lat, $location_lng) > $radius;
    }

    private function _distanceMeters($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad((float) $lat1);
        $lonFrom = deg2rad((float) $lng1);
        $latTo = deg2rad((float) $lat2);
        $lonTo = deg2rad((float) $lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(min(1, sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2))));
        return $angle * $earthRadius;
    }

    private function _get_member_workdays_map($assignment_model, $member_id, $schedule)
    {
        $days = array();

        if ($schedule && !empty($schedule->days_of_week)) {
            foreach (explode(',', (string) $schedule->days_of_week) as $day) {
                $day = (int) trim($day);
                if ($day >= 1 && $day <= 7) {
                    $days[] = $day;
                }
            }
        }

        if (!$days) {
            $assignments = $assignment_model->get_details(array('team_member_id' => $member_id))->getResult();
            foreach ($assignments as $assignment) {
                $day_of_week = (int) $assignment->day_of_week;
                if ($day_of_week >= 1 && $day_of_week <= 7) {
                    $days[] = $day_of_week;
                }
            }
        }

        return array_values(array_unique($days));
    }

    private function _is_inconsistent_day($day_row)
    {
        $punch_count = (int) get_array_value($day_row, 'punch_count');
        if ($punch_count <= 0) {
            return false;
        }

        return $punch_count !== 4;
    }

    private function _get_schedule_minutes($schedule)
    {
        if (!$schedule || !$schedule->start_time || !$schedule->end_time) {
            return 0;
        }

        $start = $this->_time_to_minutes($schedule->start_time);
        $end = $this->_time_to_minutes($schedule->end_time);
        if ($start === null || $end === null) {
            return 0;
        }

        $minutes = $end - $start;
        if ($minutes < 0) {
            $minutes += 24 * 60;
        }

        $minutes -= (int) ($schedule->break_minutes ?? 0);
        return max(0, $minutes);
    }

    private function _time_to_minutes($time)
    {
        if (!$time) {
            return null;
        }

        $timestamp = strtotime((string) $time);
        if (!$timestamp) {
            return null;
        }

        return ((int) date('H', $timestamp) * 60) + (int) date('i', $timestamp);
    }

    private function get_details_sql($options = array())
    {
        $records_table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');
        $shifts_table = $this->db->prefixTable('pontorh_work_schedules');
        $locations_table = $this->db->prefixTable('pontorh_locations');
        $creator_users_table = $this->db->prefixTable('users');

        $include_photo = (bool) get_array_value($options, 'include_photo');
        $photo_select = $include_photo ? 'r.photo AS photo' : 'NULL AS photo';

        return "SELECT r.id,
                    r.team_member_id,
                    r.user_id,
                    r.work_schedule_id,
                    r.device_id,
                    r.location_id,
                    r.date,
                    r.punch_time,
                    r.punch_type,
                    r.latitude,
                    r.longitude,
                    r.ip_address,
                    r.source,
                    r.status,
                    r.hash,
                    r.work_date,
                    r.check_in,
                    r.check_out,
                    r.break_minutes,
                    r.minutes_worked,
                    r.shift_id,
                    r.notes,
                    {$photo_select},
                    r.created_by,
                    r.created_at,
                    r.updated_at,
                    r.deleted,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name,
                    s.name AS shift_name,
                    l.name AS location_name,
                    CONCAT(TRIM(COALESCE(cu.first_name, '')), ' ', TRIM(COALESCE(cu.last_name, ''))) AS creator_name
                FROM {$records_table} r
                LEFT JOIN {$users_table} u ON u.id = r.team_member_id
                LEFT JOIN {$shifts_table} s ON s.id = r.work_schedule_id
                LEFT JOIN {$locations_table} l ON l.id = r.location_id
                LEFT JOIN {$creator_users_table} cu ON cu.id = r.created_by
                WHERE r.deleted = 0
                " . $this->getScopeWhere($options, 'r') . "
                ORDER BY r.date DESC, r.punch_time DESC, r.id DESC";
    }

    private function getScopeWhere($options = array(), $alias = 'r', $allow_empty_scope = false)
    {
        $scope = get_array_value($options, 'scope');
        $current_user_id = (int) get_array_value($options, 'current_user_id');
        $team_member_ids = get_array_value($options, 'team_member_ids');
        $team_member_id = (int) get_array_value($options, 'team_member_id');

        if (!$scope && $allow_empty_scope) {
            return '';
        }

        if ($scope === 'all' || !$scope) {
            return '';
        }

        if ($scope === 'own') {
            return $current_user_id ? ' AND ' . $alias . '.team_member_id = ' . $current_user_id : '';
        }

        if ($scope === 'team') {
            if (is_array($team_member_ids)) {
                $team_member_ids = array_values(array_filter(array_map('intval', $team_member_ids)));
            } else {
                $team_member_ids = array();
            }

            if ($team_member_id) {
                if ($team_member_ids && in_array($team_member_id, $team_member_ids, true)) {
                    return ' AND ' . $alias . '.team_member_id = ' . $team_member_id;
                }
                return ' AND ' . $alias . '.team_member_id = 0';
            }

            if ($team_member_ids) {
                return ' AND ' . $alias . '.team_member_id IN (' . implode(',', $team_member_ids) . ')';
            }

            return $current_user_id ? ' AND ' . $alias . '.team_member_id = ' . $current_user_id : '';
        }

        return '';
    }
}
