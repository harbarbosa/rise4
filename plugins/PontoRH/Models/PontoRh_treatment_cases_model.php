<?php

namespace PontoRH\Models;

use App\Models\Team_model;
use App\Models\Users_model;

class PontoRh_treatment_cases_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_treatment_cases';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT c.*,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name,
                    CONCAT(TRIM(COALESCE(cu.first_name, '')), ' ', TRIM(COALESCE(cu.last_name, ''))) AS creator_name,
                    CONCAT(TRIM(COALESCE(lu.first_name, '')), ' ', TRIM(COALESCE(lu.last_name, ''))) AS last_updater_name
                FROM {$table} c
                LEFT JOIN {$users_table} u ON u.id = c.team_member_id
                LEFT JOIN {$users_table} cu ON cu.id = c.created_by
                LEFT JOIN {$users_table} lu ON lu.id = c.last_updated_by
                WHERE c.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND c.id = ' . $id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND c.team_member_id = ' . $team_member_id;
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $sql .= ' AND c.status = ' . $this->db->escape($status);
        }

        $pending_type = trim((string) get_array_value($options, 'pending_type'));
        if ($pending_type !== '') {
            $sql .= ' AND c.pending_type = ' . $this->db->escape($pending_type);
        }

        $date_from = trim((string) get_array_value($options, 'date_from'));
        if ($date_from !== '') {
            $sql .= ' AND c.work_date >= ' . $this->db->escape($date_from);
        }

        $date_to = trim((string) get_array_value($options, 'date_to'));
        if ($date_to !== '') {
            $sql .= ' AND c.work_date <= ' . $this->db->escape($date_to);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (u.first_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.last_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR c.project_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR c.pending_type LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= ' ORDER BY c.work_date DESC, c.last_updated_at DESC, c.id DESC';
        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id = 0)
    {
        $row = $this->get_details(array('id' => (int) $id))->getRow();
        return $row ?: null;
    }

    public function get_dashboard_summary($options = array())
    {
        $rows = $this->build_cases($options);

        $summary = array(
            'pending_total' => 0,
            'incomplete_days' => 0,
            'inconsistent_days' => 0,
            'adjustments_pending' => 0,
            'outside_area' => 0,
            'no_photo' => 0,
            'awaiting_justification' => 0,
        );

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $pending_type = (string) ($row['pending_type'] ?? '');

            if (!in_array($status, array('complete', 'closed'), true)) {
                $summary['pending_total']++;
            }
            if ($status === 'incomplete') {
                $summary['incomplete_days']++;
            }
            if ($status === 'inconsistent') {
                $summary['inconsistent_days']++;
            }
            if ($status === 'adjustment_requested') {
                $summary['adjustments_pending']++;
            }
            if ($status === 'outside_area') {
                $summary['outside_area']++;
            }
            if ($status === 'no_photo') {
                $summary['no_photo']++;
            }
            if ($pending_type === 'awaiting_justification' || $status === 'awaiting_justification') {
                $summary['awaiting_justification']++;
            }
        }

        return $summary;
    }

    public function build_cases($options = array())
    {
        $options = is_array($options) ? $options : array();
        $date_from = trim((string) get_array_value($options, 'date_from', date('Y-m-01')));
        $date_to = trim((string) get_array_value($options, 'date_to', date('Y-m-t')));
        if (!$date_from) {
            $date_from = date('Y-m-01');
        }
        if (!$date_to) {
            $date_to = date('Y-m-t');
        }

        $scope = get_array_value($options, 'scope', 'own');
        $current_user_id = (int) get_array_value($options, 'current_user_id');
        $team_member_ids = get_array_value($options, 'team_member_ids', array());
        $requested_member_id = (int) get_array_value($options, 'team_member_id');

        $users_model = model(Users_model::class);
        $shift_model = model('PontoRH\\Models\\PontoRh_shifts_model');
        $assignment_model = model('PontoRH\\Models\\PontoRh_assignments_model');
        $records_model = model('PontoRH\\Models\\PontoRh_records_model');

        $members = array();
        $member_names = array();
        $members_rows = $users_model->get_team_members_id_and_name()->getResult();
        foreach ($members_rows as $member) {
            $member_id = (int) $member->id;
            $member_names[$member_id] = (string) ($member->user_name ?? '');
            if ($scope === 'own' && $current_user_id && $member_id !== $current_user_id) {
                continue;
            }
            if ($scope === 'team' && is_array($team_member_ids) && $team_member_ids && !in_array($member_id, $team_member_ids, true)) {
                continue;
            }
            if ($requested_member_id && $member_id !== $requested_member_id) {
                continue;
            }
            $members[] = $member_id;
        }

        if (!$members && $current_user_id) {
            $members[] = $current_user_id;
        }

        $records_result = $records_model->get_details(array(
            'scope' => $scope,
            'current_user_id' => $current_user_id,
            'team_member_ids' => $team_member_ids,
            'team_member_id' => $requested_member_id,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ));
        $records = $records_result ? $records_result->getResult() : array();

        $grouped = array();
        foreach ($records as $record) {
            $grouped[$record->team_member_id][$record->date][] = $record;
        }

        $rows = array();
        $start_ts = strtotime($date_from);
        $end_ts = strtotime($date_to);
        if (!$start_ts || !$end_ts || $end_ts < $start_ts) {
            return array();
        }

        for ($member_index = 0, $members_count = count($members); $member_index < $members_count; $member_index++) {
            $member_id = (int) $members[$member_index];
            $schedule = $shift_model->get_active_schedule_for_member($member_id);
            $workdays_map = $this->getWorkdaysMap($assignment_model, $member_id, $schedule);

            for ($ts = $start_ts; $ts <= $end_ts; $ts = strtotime('+1 day', $ts)) {
                $work_date = date('Y-m-d', $ts);
                $day_records = get_array_value(get_array_value($grouped, $member_id, array()), $work_date, array());
                $day_records = is_array($day_records) ? $day_records : array();

                $analysis = $this->analyzeDay($work_date, $day_records, $schedule, $workdays_map);
                if (!$analysis['has_issue']) {
                    continue;
                }

                $existing = $this->get_one_where(array(
                    'team_member_id' => $member_id,
                    'work_date' => $work_date,
                    'deleted' => 0,
                ));

                $row = array(
                    'team_member_id' => $member_id,
                    'user_id' => $current_user_id ?: $member_id,
                    'work_date' => $work_date,
                    'project_name' => $analysis['project_name'],
                    'record_count' => $analysis['record_count'],
                    'status' => $existing->status ?? $analysis['status'],
                    'pending_type' => $existing->pending_type ?? $analysis['pending_type'],
                    'classification_json' => $existing->classification_json ?? pontorh_safe_json($analysis['classification']),
                    'final_json' => $existing->final_json ?? pontorh_safe_json($analysis['final']),
                    'diagnostics_json' => $existing->diagnostics_json ?? pontorh_safe_json($analysis['diagnostics']),
                    'last_updated_by' => !empty($existing->last_updated_by) ? (int) $existing->last_updated_by : ($current_user_id > 0 ? $current_user_id : null),
                    'last_updated_at' => $existing->last_updated_at ?? get_current_utc_time(),
                    'closed_at' => $existing->closed_at ?? null,
                    'created_by' => !empty($existing->created_by) ? (int) $existing->created_by : ($current_user_id > 0 ? $current_user_id : null),
                    'created_at' => $existing->created_at ?? get_current_utc_time(),
                    'updated_at' => get_current_utc_time(),
                    'deleted' => 0,
                    'hash' => hash('sha256', implode('|', array($member_id, $work_date, $analysis['status'], microtime(true)))),
                );

                if ($existing && !empty($existing->id)) {
                    $row['id'] = $existing->id;
                }

                $row = array_merge($row, $analysis);
                $row['team_member_name'] = get_array_value($member_names, $member_id, '');
                $row['updated_at'] = $row['last_updated_at'] ?? $row['updated_at'] ?? null;
                $rows[$member_id . '|' . $work_date] = $row;
            }
        }

        $rows = array_values($rows);

        usort($rows, static function ($a, $b) {
            if (($a['work_date'] ?? '') === ($b['work_date'] ?? '')) {
                return strcmp((string) ($b['record_count'] ?? 0), (string) ($a['record_count'] ?? 0));
            }
            return strcmp((string) ($b['work_date'] ?? ''), (string) ($a['work_date'] ?? ''));
        });

        return $rows;
    }

    public function sync_cases($options = array())
    {
        $rows = $this->build_cases($options);
        foreach ($rows as $index => $row) {
            $save = $row;
            $id = (int) get_array_value($row, 'id', 0);
            unset($save['has_issue'], $save['diagnostics'], $save['classification'], $save['final'], $save['project_name'], $save['record_count'], $save['status'], $save['pending_type']);
            $save['project_name'] = $row['project_name'] ?? null;
            $save['record_count'] = (int) ($row['record_count'] ?? 0);
            $save['status'] = (string) ($row['status'] ?? 'pending');
            $save['pending_type'] = (string) ($row['pending_type'] ?? 'incomplete');
            $save['classification_json'] = pontorh_safe_json($row['classification'] ?? array());
            $save['final_json'] = pontorh_safe_json($row['final'] ?? array());
            $save['diagnostics_json'] = pontorh_safe_json($row['diagnostics'] ?? array());
            $save['hash'] = $row['hash'] ?? hash('sha256', microtime(true));
            $save['last_updated_at'] = !empty($save['last_updated_at']) ? $save['last_updated_at'] : get_current_utc_time();
            $save['created_at'] = !empty($save['created_at']) ? $save['created_at'] : get_current_utc_time();
            $save['updated_at'] = !empty($save['updated_at']) ? $save['updated_at'] : get_current_utc_time();
            if ($id) {
                $this->ci_save($save, $id);
                $row['id'] = $id;
            } else {
                $inserted_id = $this->ci_save($save);
                if ($inserted_id) {
                    $row['id'] = $inserted_id;
                }
            }

            $rows[$index] = $row;
        }
        return $rows;
    }

    public function save_action(int $case_id, array $data)
    {
        $case = $this->get_one_where(array('id' => $case_id, 'deleted' => 0));
        if (!$case || !$case->id) {
            return false;
        }

        $payload = array(
            'status' => get_array_value($data, 'status', $case->status),
            'pending_type' => get_array_value($data, 'pending_type', $case->pending_type),
            'classification_json' => get_array_value($data, 'classification_json', $case->classification_json),
            'final_json' => get_array_value($data, 'final_json', $case->final_json),
            'diagnostics_json' => get_array_value($data, 'diagnostics_json', $case->diagnostics_json),
            'last_updated_by' => get_array_value($data, 'last_updated_by', $case->last_updated_by),
            'last_updated_at' => get_array_value($data, 'last_updated_at', get_current_utc_time()),
            'closed_at' => get_array_value($data, 'closed_at', $case->closed_at),
            'updated_at' => get_current_utc_time(),
        );

        return $this->ci_save($payload, $case_id);
    }

    public function get_or_create_case(int $team_member_id, string $work_date, array $options = array())
    {
        $existing = $this->get_one_where(array(
            'team_member_id' => $team_member_id,
            'work_date' => $work_date,
            'deleted' => 0,
        ));

        if ($existing && !empty($existing->id)) {
            return $existing;
        }

        $rows = $this->build_cases(array_merge($options, array(
            'team_member_id' => $team_member_id,
            'date_from' => $work_date,
            'date_to' => $work_date,
        )));

        $row = get_array_value($rows, 0);
        if (!$row) {
            return null;
        }

        $save = $row;
        unset($save['has_issue'], $save['diagnostics'], $save['classification'], $save['final']);
        $save['classification_json'] = pontorh_safe_json($row['classification'] ?? array());
        $save['final_json'] = pontorh_safe_json($row['final'] ?? array());
        $save['diagnostics_json'] = pontorh_safe_json($row['diagnostics'] ?? array());
        $id = $this->ci_save($save);
        return $id ? $this->get_one_with_details($id) : null;
    }

    private function analyzeDay(string $work_date, array $records, $schedule, array $workdays_map = array()): array
    {
        usort($records, static function ($a, $b) {
            return strcmp((string) $a->punch_time, (string) $b->punch_time);
        });

        $record_count = count($records);
        $punch_types = array();
        $diagnostics = array();
        $classification = array();
        $final = array();
        $project_name = '';
        $status = 'complete';
        $pending_type = '';
        $has_issue = false;

        foreach ($records as $record) {
            $punch_types[] = (string) ($record->punch_type ?? '');
            if (!$project_name) {
                $project_name = $this->extractProjectName($record);
            }
            if (($record->status ?? '') === 'outside_area') {
                $status = 'outside_area';
                $pending_type = 'outside_area';
                $has_issue = true;
            }
        }

        if ($record_count === 0) {
            if ($this->isWorkday($work_date, $workdays_map)) {
                $status = 'incomplete';
                $pending_type = 'no_entry';
                $diagnostics[] = 'Sem marcações no dia.';
                $has_issue = true;
            } else {
                return array(
                    'has_issue' => false,
                    'status' => 'complete',
                    'pending_type' => '',
                    'record_count' => 0,
                    'project_name' => $project_name ?: '-',
                    'classification' => array(),
                    'final' => array(),
                    'diagnostics' => array(),
                );
            }
        }

        if ($record_count > 0) {
            $expected = array('in', 'lunch_out', 'lunch_return', 'out');
            $missing = array();
            foreach ($expected as $expected_type) {
                if (!in_array($expected_type, $punch_types, true)) {
                    $missing[] = $expected_type;
                }
            }

            if ($record_count < 4) {
                $status = $status === 'outside_area' ? $status : 'incomplete';
                $pending_type = $pending_type ?: 'missing_punch';
                $diagnostics[] = 'Dia com menos de 4 marcações.';
                $has_issue = true;
            } elseif ($record_count > 4) {
                $status = $status === 'outside_area' ? $status : 'inconsistent';
                $pending_type = $pending_type ?: 'extra_marking';
                $diagnostics[] = 'Existem marcações extras no dia.';
                $has_issue = true;
            }

            if ($missing) {
                $status = $status === 'outside_area' ? $status : 'incomplete';
                $pending_type = $pending_type ?: 'invalid_sequence';
                $diagnostics[] = 'Sequência incompleta: ' . implode(', ', $missing);
                $has_issue = true;
            }

            if ($record_count >= 2 && $this->hasInvalidSequence($punch_types)) {
                $status = $status === 'outside_area' ? $status : 'inconsistent';
                $pending_type = $pending_type ?: 'sequence_invalid';
                $diagnostics[] = 'Sequência fora do padrão esperado.';
                $has_issue = true;
            }
        }

        foreach ($records as $record) {
            $classification[] = array(
                'id' => (int) $record->id,
                'time' => pontorh_extract_time($record->punch_time),
                'automatic_type' => (string) ($record->punch_type ?? ''),
                'corrected_type' => '',
                'effective_type' => (string) ($record->punch_type ?? ''),
                'latitude' => (string) ($record->latitude ?? '0'),
                'longitude' => (string) ($record->longitude ?? '0'),
                'address' => '',
                'source' => (string) ($record->source ?? ''),
                'device' => (string) ($record->device_name ?? ''),
                'selfie' => null,
                'status' => (string) ($record->status ?? ''),
            );
            $final[] = $record;
        }

        return array(
            'has_issue' => $has_issue,
            'status' => $status,
            'pending_type' => $pending_type,
            'record_count' => $record_count,
            'project_name' => $project_name ?: '-',
            'classification' => $classification,
            'final' => $final,
            'diagnostics' => $diagnostics,
        );
    }

    private function getWorkdaysMap($assignment_model, int $member_id, $schedule)
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

    private function isWorkday(string $date, array $workdays_map = array())
    {
        $weekday = (int) date('N', strtotime($date));
        if ($workdays_map) {
            return in_array($weekday, $workdays_map, true);
        }
        return $weekday >= 1 && $weekday <= 5;
    }

    private function hasInvalidSequence(array $punch_types)
    {
        $sequence = array('in', 'lunch_out', 'lunch_return', 'out');
        foreach ($punch_types as $index => $type) {
            if ($index > 3) {
                return true;
            }
            if ($type !== $sequence[$index]) {
                return true;
            }
        }

        return false;
    }

    private function extractProjectName($record)
    {
        if (!empty($record->project_name)) {
            return (string) $record->project_name;
        }

        if (!empty($record->notes)) {
            $decoded = json_decode((string) $record->notes, true);
            if (is_array($decoded)) {
                foreach (array('project_name', 'project_title', 'project') as $key) {
                    if (!empty($decoded[$key])) {
                        return (string) $decoded[$key];
                    }
                }
            }
        }

        return '-';
    }
}
