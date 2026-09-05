<?php

namespace PontoRH\Libraries;

class PontoRh_period_service
{
    protected $records;
    protected $shifts;
    protected $assignments;
    protected $summaries;

    public function __construct()
    {
        $this->records = model('PontoRH\\Models\\PontoRh_records_model');
        $this->shifts = model('PontoRH\\Models\\PontoRh_shifts_model');
        $this->assignments = model('PontoRH\\Models\\PontoRh_assignments_model');
        $this->summaries = model('PontoRH\\Models\\PontoRh_monthly_summaries_model');
    }

    public function isClosed(int $team_member_id, string $date): bool
    {
        $ts = strtotime($date);
        if (!$team_member_id || !$ts) {
            return false;
        }
        $row = $this->summaries->get_by_member_month($team_member_id, (int) date('Y', $ts), (int) date('n', $ts));
        return $row && (string) ($row->status ?? '') === 'closed';
    }

    public function calculate(int $team_member_id, int $year, int $month, int $user_id): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $month_end = date('Y-m-t', strtotime($start));
        $today = get_my_local_time('Y-m-d');
        $effective_end = $month_end < $today ? $month_end : $today;
        $has_started = $start <= $today;

        $records = $has_started ? $this->records->get_details(array('team_member_id' => $team_member_id, 'date_from' => $start, 'date_to' => $effective_end))->getResult() : array();
        $grouped = array();
        foreach ($records as $record) {
            $grouped[(string) $record->date][] = $record;
        }

        $schedule = $this->shifts->get_active_schedule_for_member($team_member_id);
        $expected = 0;
        $worked = 0;
        $late = 0;
        $absence = 0;
        $overtime = 0;

        if ($has_started) {
            for ($ts = strtotime($start); $ts <= strtotime($effective_end); $ts = strtotime('+1 day', $ts)) {
                $date = date('Y-m-d', $ts);
                if (!$this->isWorkday($date, $team_member_id, $schedule)) {
                    continue;
                }
                $daily_expected = $this->scheduleMinutes($schedule);
                $expected += $daily_expected;
                $day_records = $grouped[$date] ?? array();
                if (!$day_records) {
                    $absence += $daily_expected;
                    continue;
                }
                $summary = $this->summarizeDay($date, $day_records, $schedule);
                $worked += $summary['worked'];
                $late += $summary['late'];
                $overtime += max(0, $summary['worked'] - $daily_expected - (int) ($schedule->extra_tolerance_minutes ?? 0));
            }
        }

        $existing = $this->summaries->get_by_member_month($team_member_id, $year, $month);
        $status = $existing && (string) ($existing->status ?? '') === 'closed' ? 'closed' : 'calculated';
        $payload = array(
            'team_member_id' => $team_member_id,
            'user_id' => $team_member_id,
            'date' => $start,
            'summary_year' => $year,
            'summary_month' => $month,
            'expected_minutes' => $expected,
            'worked_minutes' => $worked,
            'overtime_minutes' => $overtime,
            'absence_minutes' => $absence,
            'late_minutes' => $late,
            'adjustment_minutes' => 0,
            'status' => $status,
            'hash' => hash('sha256', implode('|', array($team_member_id, $year, $month, $expected, $worked, $overtime, $absence, $late))),
            'created_by' => $existing->created_by ?? $user_id,
            'created_at' => $existing->created_at ?? get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );
        $this->summaries->upsert_summary($payload);
        $payload['balance_minutes'] = $worked - $expected;
        return $payload;
    }

    public function setStatus(int $team_member_id, int $year, int $month, string $status, int $user_id): bool
    {
        $this->calculate($team_member_id, $year, $month, $user_id);
        $existing = $this->summaries->get_by_member_month($team_member_id, $year, $month);
        if (!$existing || empty($existing->id)) {
            return false;
        }
        return (bool) $this->summaries->ci_save(array('status' => $status, 'updated_at' => get_current_utc_time()), (int) $existing->id);
    }

    protected function summarizeDay(string $date, array $records, $schedule): array
    {
        usort($records, static function ($a, $b) { return strcmp((string) $a->punch_time, (string) $b->punch_time); });
        $worked = 0;
        $open = null;
        $first = null;
        foreach ($records as $record) {
            $ts = $this->localTimestamp($record->punch_time ?? null);
            if (!$ts) {
                continue;
            }
            $type = (string) ($record->punch_type ?? '');
            if (in_array($type, array('in', 'lunch_return'), true)) {
                if ($first === null) {
                    $first = $ts;
                }
                $open = $ts;
            } elseif (in_array($type, array('lunch_out', 'out'), true) && $open) {
                $worked += max(0, (int) floor(($ts - $open) / 60));
                $open = null;
            }
        }
        $late = 0;
        if ($first && $schedule && !empty($schedule->start_time)) {
            $day_start = strtotime($date . ' 00:00:00');
            $scheduled = $this->timeToMinutes((string) $schedule->start_time);
            $late = max(0, (int) floor(($first - $day_start) / 60) - $scheduled - (int) ($schedule->tolerance_minutes ?? 0));
        }
        return array('worked' => $worked, 'late' => $late);
    }

    protected function scheduleMinutes($schedule): int
    {
        if (!$schedule || empty($schedule->start_time) || empty($schedule->end_time)) {
            return 0;
        }
        $start = $this->timeToMinutes((string) $schedule->start_time);
        $end = $this->timeToMinutes((string) $schedule->end_time);
        $minutes = $end - $start;
        if ($minutes < 0) {
            $minutes += 1440;
        }
        return max(0, $minutes - (int) ($schedule->break_minutes ?? 0));
    }

    protected function isWorkday(string $date, int $team_member_id, $schedule): bool
    {
        $weekday = (int) date('N', strtotime($date));
        if ($schedule && !empty($schedule->days_of_week)) {
            $days = array_values(array_filter(array_map('intval', explode(',', (string) $schedule->days_of_week))));
            if ($days) {
                return in_array($weekday, $days, true);
            }
        }
        $days = $this->assignments->get_member_workdays($team_member_id, (int) ($schedule->id ?? 0));
        return $days ? in_array($weekday, $days, true) : ($weekday >= 1 && $weekday <= 5);
    }

    protected function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }

    protected function localTimestamp($value)
    {
        if (!$value) {
            return null;
        }
        if (function_exists('convert_date_utc_to_local') && is_date_exists($value)) {
            $value = convert_date_utc_to_local($value);
        }
        $ts = strtotime((string) $value);
        return $ts ?: null;
    }
}
