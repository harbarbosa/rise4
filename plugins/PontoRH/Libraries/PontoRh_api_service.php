<?php

namespace PontoRH\Libraries;

use App\Models\Team_model;
use App\Models\Users_model;

class PontoRh_api_service
{
    protected Users_model $usersModel;
    protected Team_model $teamModel;
    protected \PontoRH\Models\PontoRh_adjustments_model $adjustmentsModel;
    protected \PontoRH\Models\PontoRh_audit_logs_model $auditLogsModel;
    protected \PontoRH\Models\PontoRh_devices_model $devicesModel;
    protected \PontoRH\Models\PontoRh_locations_model $locationsModel;
    protected \PontoRH\Models\PontoRh_monthly_summaries_model $monthlySummariesModel;
    protected \PontoRH\Models\PontoRh_records_model $recordsModel;
    protected \PontoRH\Models\PontoRh_settings_model $settingsModel;
    protected \PontoRH\Models\PontoRh_shifts_model $shiftsModel;
    protected \PontoRH\Models\PontoRh_assignments_model $assignmentsModel;

    protected ?object $apiUser = null;
    protected ?object $user = null;
    protected array $permissions = array();
    protected array $context = array();

    public function __construct($api_user = null)
    {
        helper(array('general', 'date_time', 'notifications', 'pontorh'));

        $this->usersModel = model(Users_model::class);
        $this->teamModel = model(Team_model::class);
        $this->adjustmentsModel = model(\PontoRH\Models\PontoRh_adjustments_model::class);
        $this->auditLogsModel = model(\PontoRH\Models\PontoRh_audit_logs_model::class);
        $this->devicesModel = model(\PontoRH\Models\PontoRh_devices_model::class);
        $this->locationsModel = model(\PontoRH\Models\PontoRh_locations_model::class);
        $this->monthlySummariesModel = model(\PontoRH\Models\PontoRh_monthly_summaries_model::class);
        $this->recordsModel = model(\PontoRH\Models\PontoRh_records_model::class);
        $this->settingsModel = model(\PontoRH\Models\PontoRh_settings_model::class);
        $this->shiftsModel = model(\PontoRH\Models\PontoRh_shifts_model::class);
        $this->assignmentsModel = model(\PontoRH\Models\PontoRh_assignments_model::class);

        if ($api_user) {
            $this->setApiUser($api_user);
        }
    }

    protected function arrayValue($array, string $key, $default = null)
    {
        if (!is_array($array) && !is_object($array)) {
            return $default;
        }

        if (is_object($array)) {
            $array = (array) $array;
        }

        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    public function setApiUser($api_user): bool
    {
        $this->apiUser = is_object($api_user) ? $api_user : (object) $api_user;
        $context = $this->buildContextFromApiUser($this->apiUser);
        $this->context = $context;
        $this->user = $this->arrayValue($context, 'user');
        $this->permissions = $this->arrayValue($context, 'permissions', array());
        return !empty($this->user);
    }

    protected function punchTypeSequence(int $index): string
    {
        $sequence = array('in', 'lunch_out', 'lunch_return', 'out');
        return $sequence[max(0, $index) % 4];
    }

    protected function normalizePunchType(?string $type, int $existing_count = 0): string
    {
        $type = strtolower(trim((string) $type));
        $allowed = array('in', 'lunch_out', 'lunch_return', 'out');
        if (in_array($type, $allowed, true)) {
            return $type;
        }

        return $this->punchTypeSequence($existing_count);
    }

    protected function formatLocalTimeValue($date_time): string
    {
        $date_time = trim((string) $date_time);
        if ($date_time === '') {
            return '';
        }

        if (preg_match('/\b(\d{2}:\d{2})(?::\d{2})?\b/', $date_time, $matches)) {
            return $matches[1];
        }

        $timestamp = strtotime($date_time);
        if (!$timestamp) {
            return $date_time;
        }

        return date('H:i', $timestamp);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function canAccess(string $permission): bool
    {
        if (!empty($this->context['is_admin'])) {
            return true;
        }

        if ($permission === 'pontorh_admin') {
            return $this->arrayValue($this->permissions, 'pontorh_admin') == '1';
        }

        if ($permission === 'pontorh_view_own') {
            return $this->arrayValue($this->permissions, 'pontorh_view_own') == '1'
                || $this->arrayValue($this->permissions, 'pontorh_admin') == '1';
        }

        return $this->arrayValue($this->permissions, $permission) == '1'
            || $this->arrayValue($this->permissions, 'pontorh_admin') == '1';
    }

    public function permissionResponse(string $permission, string $message = 'Forbidden.')
    {
        if ($this->canAccess($permission)) {
            return array('ok' => true);
        }

        return array(
            'ok' => false,
            'code' => 403,
            'status' => false,
            'message' => $message,
        );
    }

    public function me(): array
    {
        $auth = $this->permissionResponse('pontorh_view_own');
        if (!$auth['ok']) {
            return $auth;
        }

        $user = $this->user;
        $schedule = $this->shiftsModel->get_active_schedule_for_member((int) $user->id);
        $status = $this->buildCurrentStatus((int) $user->id, $schedule);
        $last_record_result = $this->recordsModel->get_details(array(
            'team_member_id' => (int) $user->id,
        ));
        $last_record = $last_record_result ? $last_record_result->getRowArray() : array();

        return array(
            'ok' => true,
            'code' => 200,
            'status' => true,
            'resource' => 'pontorh_me',
            'data' => array(
                'id' => (int) $user->id,
                'team_member_id' => (int) $user->id,
                'name' => trim((string) ($user->first_name ?? '') . ' ' . (string) ($user->last_name ?? '')),
                'job_title' => (string) ($user->job_title ?? ''),
                'photo' => $user->image ?? null,
                'role' => (string) ($this->context['role_title'] ?? ''),
                'work_schedule' => $schedule ? $this->normalizeSchedule($schedule) : null,
                'current_status' => $status['status'],
                'last_record' => $last_record ?: null,
            ),
        );
    }

    public function status(): array
    {
        $auth = $this->permissionResponse('pontorh_view_own');
        if (!$auth['ok']) {
            return $auth;
        }

        $summary = $this->buildCurrentStatus((int) $this->user->id);
        return array(
            'ok' => true,
            'code' => 200,
            'status' => true,
            'resource' => 'pontorh_status',
            'data' => $summary,
        );
    }

    public function today(): array
    {
        $auth = $this->permissionResponse('pontorh_view_own');
        if (!$auth['ok']) {
            return $auth;
        }

        $today = date('Y-m-d');
        $rows_result = $this->recordsModel->get_details(array(
            'team_member_id' => (int) $this->user->id,
            'date_from' => $today,
            'date_to' => $today,
        ));
        $rows = $rows_result ? $rows_result->getResultArray() : array();

        $data = array();
        foreach ($rows as $row) {
            $data[] = array(
                'id' => (int) $row['id'],
                'type' => (string) $row['punch_type'],
                'time' => !empty($row['punch_time']) ? $this->formatLocalTimeValue($row['punch_time']) : null,
                'date' => (string) $row['date'],
                'latitude' => (string) ($row['latitude'] ?? '0'),
                'longitude' => (string) ($row['longitude'] ?? '0'),
                'status' => (string) ($row['status'] ?? ''),
                'source' => (string) ($row['source'] ?? ''),
            );
        }

        return array(
            'ok' => true,
            'code' => 200,
            'status' => true,
            'resource' => 'pontorh_today',
            'count' => count($data),
            'data' => $data,
        );
    }

    public function month(array $filters = array()): array
    {
        $auth = $this->permissionResponse('pontorh_view_own');
        if (!$auth['ok']) {
            return $auth;
        }

        $month = (int) $this->arrayValue($filters, 'month', (int) date('n'));
        $year = (int) $this->arrayValue($filters, 'year', (int) date('Y'));
        if ($month < 1 || $month > 12) {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'Invalid month.');
        }
        if ($year < 1970) {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'Invalid year.');
        }

        $report = $this->recordsModel->get_mirror_report(array(
            'team_member_id' => (int) $this->user->id,
            'month' => $month,
            'year' => $year,
            'scope' => 'own',
            'current_user_id' => (int) $this->user->id,
        ));

        $summary = $this->arrayValue($report, 'summary', array());
        $rows = $this->arrayValue($report, 'rows', array());
        $days_worked = (int) $this->arrayValue($summary, 'days_with_records', 0);
        $worked_minutes = (int) $this->arrayValue($summary, 'worked_minutes_total', 0);
        $extra_minutes = (int) $this->arrayValue($summary, 'extra_minutes_total', 0);
        $bank_minutes = (int) $this->arrayValue($summary, 'bank_minutes_end', 0);
        $absences = (int) $this->arrayValue($summary, 'absences_total', 0);
        $late_minutes = (int) $this->arrayValue($summary, 'lateness_total', 0);

        $this->refreshMonthlySummary((int) $this->user->id, $year, $month, $summary);

        return array(
            'ok' => true,
            'code' => 200,
            'status' => true,
            'resource' => 'pontorh_month',
            'month' => $month,
            'year' => $year,
            'summary' => array(
                'days_worked' => $days_worked,
                'worked_minutes' => $worked_minutes,
                'worked_hours' => $this->formatMinutesToTime($worked_minutes),
                'overtime_minutes' => $extra_minutes,
                'overtime_hours' => $this->formatMinutesToTime($extra_minutes),
                'bank_minutes' => $bank_minutes,
                'bank_hours' => $this->formatMinutesToTime($bank_minutes),
                'absences' => $absences,
                'late_minutes' => $late_minutes,
                'late_hours' => $this->formatMinutesToTime($late_minutes),
            ),
            'data' => $rows,
        );
    }

    public function history(array $filters = array()): array
    {
        $auth = $this->permissionResponse('pontorh_view_own');
        if (!$auth['ok']) {
            return $auth;
        }

        $start_date = trim((string) $this->arrayValue($filters, 'start_date', ''));
        $end_date = trim((string) $this->arrayValue($filters, 'end_date', ''));
        if ($start_date === '' || $end_date === '') {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'start_date and end_date are required.');
        }

        $start_ts = strtotime($start_date);
        $end_ts = strtotime($end_date);
        if (!$start_ts || !$end_ts) {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'Invalid date range.');
        }
        if ($end_ts < $start_ts) {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'end_date cannot be earlier than start_date.');
        }

        $rows = $this->recordsModel->get_details(array(
            'team_member_id' => (int) $this->user->id,
            'date_from' => date('Y-m-d', $start_ts),
            'date_to' => date('Y-m-d', $end_ts),
        ))->getResultArray();

        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->mapRecordRow($row);
        }

        return array(
            'ok' => true,
            'code' => 200,
            'status' => true,
            'resource' => 'pontorh_history',
            'count' => count($data),
            'data' => $data,
        );
    }

    public function checkin(array $payload): array
    {
        $auth = $this->permissionResponse('pontorh_create_record');
        if (!$auth['ok']) {
            return $auth;
        }

        $requested_type = strtolower(trim((string) $this->arrayValue($payload, 'type', '')));
        $latitude = trim((string) $this->arrayValue($payload, 'latitude', ''));
        $longitude = trim((string) $this->arrayValue($payload, 'longitude', ''));
        $device_id = trim((string) $this->arrayValue($payload, 'device_id', ''));
        $device_name = trim((string) $this->arrayValue($payload, 'device_name', ''));
        $battery_level = $this->arrayValue($payload, 'battery_level', null);

        $settings = $this->settingsModel->get_all_settings_with_defaults();
        $require_gps = $this->arrayValue($settings, 'require_gps') != '0';
        if ($require_gps && ($latitude === '' || $longitude === '')) {
            $this->auditEvent('invalid_attempt', 'GPS coordinates are required.', array('payload' => $payload), 'invalid', 'checkin');
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'GPS coordinates are required.');
        }

        $today = date('Y-m-d');
        $schedule = $this->shiftsModel->get_active_schedule_for_member((int) $this->user->id);
        if (!$schedule) {
            $this->auditEvent('invalid_attempt', 'No active schedule found.', array('payload' => $payload), 'invalid', 'checkin');
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'No active schedule found.');
        }

        $existing_count = (int) $this->recordsModel->get_details(array(
            'team_member_id' => (int) $this->user->id,
            'date_from' => $today,
            'date_to' => $today,
        ))->getNumRows();
        $expected_type = $this->punchTypeSequence($existing_count);
        $type = $expected_type;
        if ($requested_type !== '' && in_array($requested_type, array('entrada', 'saida_intervalo', 'retorno_intervalo', 'saida'), true)) {
            $type = $expected_type;
        }

        $device = null;
        if ($device_id !== '') {
            $device = $this->devicesModel->get_by_member_and_device((int) $this->user->id, $device_id);
        }

        $matched_location = $this->matchLocation($latitude, $longitude, $settings);
        $record_status = $matched_location ? 'pending' : 'outside_area';

        $now = get_my_local_time();
        $record_data = array(
            'team_member_id' => (int) $this->user->id,
            'user_id' => (int) $this->user->id,
            'work_schedule_id' => (int) ($schedule->id ?? 0),
            'device_id' => $device ? (int) $device->id : null,
            'location_id' => $matched_location ? (int) $matched_location->id : null,
            'date' => substr($now, 0, 10),
            'work_date' => substr($now, 0, 10),
            'punch_time' => $now,
            'punch_type' => $type,
            'check_in' => in_array($type, array('in', 'lunch_return'), true) ? $now : null,
            'check_out' => in_array($type, array('lunch_out', 'out'), true) ? $now : null,
            'latitude' => $latitude !== '' ? (float) $latitude : 0,
            'longitude' => $longitude !== '' ? (float) $longitude : 0,
            'ip_address' => $this->requestIpAddress(),
            'source' => 'mobile_app',
            'status' => $record_status,
            'hash' => hash('sha256', implode('|', array(
                (int) $this->user->id,
                $type,
                $now,
                $latitude,
                $longitude,
                $device_id,
                $this->requestIpAddress(),
                microtime(true),
            ))),
            'notes' => $this->buildRecordNotes($payload, $device_name, $battery_level),
            'created_by' => (int) $this->user->id,
            'created_at' => $now,
            'updated_at' => null,
            'deleted' => 0,
        );

        if (!$this->recordsModel->ci_save($record_data)) {
            $this->auditEvent('invalid_attempt', 'Could not save record.', array('payload' => $payload), 'invalid', 'checkin');
            return array('ok' => false, 'code' => 500, 'status' => false, 'message' => 'Could not save record.');
        }

        $record_id = (int) db_connect('default')->insertID();
        $saved = $this->recordsModel->get_one_with_details($record_id, array('scope' => 'own', 'current_user_id' => (int) $this->user->id));
        $this->auditEvent('checkin', 'Punch recorded through mobile app.', array(
            'record_id' => $record_id,
            'type' => $type,
            'device_id' => $device_id,
            'device_name' => $device_name,
            'battery_level' => $battery_level,
            'location_id' => $matched_location ? (int) $matched_location->id : null,
        ), 'logged', 'record', $record_id);

        $this->refreshMonthlySummary((int) $this->user->id, (int) date('Y'), (int) date('n'));

        return array(
            'ok' => true,
            'code' => 201,
            'status' => true,
            'message' => 'Punch recorded successfully.',
            'id' => $record_id,
            'data' => $saved ? $this->mapRecordObject($saved) : array(),
        );
    }

    public function adjustments(array $payload = array(), string $method = 'GET'): array
    {
        if ($method === 'POST') {
            return $this->createAdjustment($payload);
        }

        $auth = $this->permissionResponse('pontorh_request_adjustment');
        if (!$auth['ok']) {
            return $auth;
        }

        $rows = $this->adjustmentsModel->get_details(array(
            'team_member_id' => (int) $this->user->id,
        ))->getResultArray();

        $data = array();
        foreach ($rows as $row) {
            $type_map = array(
                'in' => 'entrada',
                'lunch_out' => 'saida_intervalo',
                'lunch_return' => 'retorno_intervalo',
                'out' => 'saida',
            );

            $data[] = array(
                'id' => (int) $row['id'],
                'date' => $row['request_date'] ?? null,
                'requested_time' => !empty($row['requested_time']) ? $this->formatLocalTimeValue($row['requested_time']) : null,
                'requested_type' => $this->arrayValue($type_map, (string) ($row['adjustment_type'] ?? ''), (string) ($row['adjustment_type'] ?? '')),
                'reason' => (string) ($row['reason'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'approver' => (string) ($row['reviewer_name'] ?? ''),
            );
        }

        return array(
            'ok' => true,
            'code' => 200,
            'status' => true,
            'resource' => 'pontorh_adjustments',
            'count' => count($data),
            'data' => $data,
        );
    }

    public function createAdjustment(array $payload): array
    {
        $auth = $this->permissionResponse('pontorh_request_adjustment');
        if (!$auth['ok']) {
            return $auth;
        }

        $record_date = trim((string) $this->arrayValue($payload, 'record_date', ''));
        $requested_time = trim((string) $this->arrayValue($payload, 'requested_time', ''));
        $requested_type = strtolower(trim((string) $this->arrayValue($payload, 'requested_type', '')));
        $reason = trim((string) $this->arrayValue($payload, 'reason', ''));

        if ($record_date === '' || $requested_time === '' || $requested_type === '' || $reason === '') {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'Required fields are missing.');
        }

        $allowed = array('entrada', 'saida_intervalo', 'retorno_intervalo', 'saida');
        if (!in_array($requested_type, $allowed, true)) {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'Invalid requested type.');
        }

        $adjustment_datetime = $this->normalizeDateTime($record_date, $requested_time);
        if (!$adjustment_datetime) {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'Invalid date or time.');
        }

        $data = array(
            'team_member_id' => (int) $this->user->id,
            'user_id' => (int) $this->user->id,
            'record_id' => null,
            'request_date' => date('Y-m-d', strtotime($record_date)),
            'requested_time' => $adjustment_datetime,
            'adjustment_type' => $this->arrayValue(array(
                'entrada' => 'in',
                'saida_intervalo' => 'lunch_out',
                'retorno_intervalo' => 'lunch_return',
                'saida' => 'out',
            ), $requested_type),
            'requested_minutes' => 0,
            'reason' => $reason,
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'ip_address' => $this->requestIpAddress(),
            'source' => 'mobile_app',
            'hash' => hash('sha256', implode('|', array((int) $this->user->id, $record_date, $requested_time, $requested_type, $reason, microtime(true)))),
            'created_by' => (int) $this->user->id,
            'created_at' => get_current_utc_time(),
            'updated_at' => null,
            'deleted' => 0,
        );

        if (!$this->adjustmentsModel->ci_save($data)) {
            $this->auditEvent('invalid_attempt', 'Could not save adjustment request.', array('payload' => $payload), 'invalid', 'adjustment');
            return array('ok' => false, 'code' => 500, 'status' => false, 'message' => 'Could not save adjustment request.');
        }

        $adjustment_id = (int) db_connect('default')->insertID();
        $adjustment = $this->adjustmentsModel->get_one_with_details($adjustment_id, array('scope' => 'own', 'current_user_id' => (int) $this->user->id));

        $this->auditEvent('adjustment_requested', 'Adjustment request created.', array(
            'adjustment_id' => $adjustment_id,
            'record_date' => $record_date,
            'requested_time' => $adjustment_datetime,
            'requested_type' => $requested_type,
            'reason' => $reason,
        ), 'logged', 'adjustment', $adjustment_id);

        log_notification('pontorh_adjustment_requested', array(
            'plugin_adjustment_id' => $adjustment_id,
            'plugin_requester_id' => (int) $this->user->id,
        ), (int) $this->user->id);

        return array(
            'ok' => true,
            'code' => 201,
            'status' => true,
            'message' => 'Adjustment request created successfully.',
            'id' => $adjustment_id,
            'data' => $adjustment ? $adjustment : array(),
        );
    }

    public function registerDevice(array $payload): array
    {
        $auth = $this->permissionResponse('pontorh_view_own');
        if (!$auth['ok']) {
            return $auth;
        }

        $device_id = trim((string) $this->arrayValue($payload, 'device_id', ''));
        $device_name = trim((string) $this->arrayValue($payload, 'device_name', ''));
        $platform = trim((string) $this->arrayValue($payload, 'platform', ''));
        $app_version = trim((string) $this->arrayValue($payload, 'app_version', ''));

        if ($device_id === '' || $device_name === '') {
            return array('ok' => false, 'code' => 422, 'status' => false, 'message' => 'device_id and device_name are required.');
        }

        $existing = $this->devicesModel->get_by_member_and_device((int) $this->user->id, $device_id);
        $now = get_current_utc_time();
        $data = array(
            'team_member_id' => (int) $this->user->id,
            'user_id' => (int) $this->user->id,
            'name' => $device_name,
            'serial_number' => $device_id,
            'token' => hash('sha256', implode('|', array((int) $this->user->id, $device_id, $now))),
            'ip_address' => $this->requestIpAddress(),
            'latitude' => 0,
            'longitude' => 0,
            'source' => 'mobile_app',
            'status' => 'active',
            'hash' => hash('sha256', implode('|', array((int) $this->user->id, $device_id, $device_name, $platform, $app_version, microtime(true)))),
            'last_seen_at' => $now,
            'active' => 1,
            'platform' => $platform,
            'app_version' => $app_version,
            'updated_at' => $now,
            'deleted' => 0,
        );

        $save_id = $existing && !empty($existing->id) ? (int) $existing->id : 0;
        if (!$save_id) {
            $data['created_by'] = (int) $this->user->id;
            $data['created_at'] = $now;
        }
        if (!$this->devicesModel->save_device($data, $save_id)) {
            $this->auditEvent('invalid_attempt', 'Could not save device.', array('payload' => $payload), 'invalid', 'device');
            return array('ok' => false, 'code' => 500, 'status' => false, 'message' => 'Could not save device.');
        }

        $device_record = $save_id ? $this->devicesModel->get_one_with_details($save_id) : $this->devicesModel->get_by_member_and_device((int) $this->user->id, $device_id);
        if (!$save_id) {
            $save_id = (int) db_connect('default')->insertID();
        }

        $this->auditEvent('device_registered', 'Device registered through mobile app.', array(
            'device_id' => $device_id,
            'device_name' => $device_name,
            'platform' => $platform,
            'app_version' => $app_version,
        ), 'logged', 'device', $save_id);

        return array(
            'ok' => true,
            'code' => 201,
            'status' => true,
            'message' => 'Device registered successfully.',
            'id' => $save_id,
            'data' => $device_record ? $device_record : array(),
        );
    }

    public function dashboard(): array
    {
        $auth = $this->permissionResponse('pontorh_view_own');
        if (!$auth['ok']) {
            return $auth;
        }

        $status = $this->buildCurrentStatus((int) $this->user->id);
        $pending_adjustments = (int) $this->adjustmentsModel->get_pending_count(array(
            'scope' => 'own',
            'current_user_id' => (int) $this->user->id,
        ));

        return array(
            'ok' => true,
            'code' => 200,
            'status' => true,
            'resource' => 'pontorh_dashboard',
            'data' => array(
                'status' => (string) $status['status'],
                'worked_hours' => (string) $status['worked_hours'],
                'remaining_hours' => (string) $status['remaining_hours'],
                'bank_hours' => (string) $status['bank_hours'],
                'pending_adjustments' => $pending_adjustments,
                'next_expected_action' => (string) $status['next_expected_action'],
                'last_record' => $status['last_record'],
            ),
        );
    }

    public function auditEvent(string $action, string $description, array $payload = array(), string $status = 'logged', string $entity_type = 'auth', $entity_id = null, ?int $user_id = null): bool
    {
        $team_member_id = $user_id ?: (int) $this->arrayValue($this->context, 'team_member_id', 0);
        $created_by = $user_id ?: (int) $this->arrayValue($this->context, 'user_id', 0);
        if (!$created_by) {
            $created_by = $this->resolveFallbackUserId();
        }

        return (bool) $this->auditLogsModel->log_action(array(
            'team_member_id' => $team_member_id ?: null,
            'user_id' => $user_id ?: (int) $this->arrayValue($this->context, 'user_id', 0),
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'action' => $action,
            'description' => $description,
            'payload_json' => !empty($payload) ? pontorh_safe_json($payload) : null,
            'ip_address' => $this->requestIpAddress(),
            'source' => 'restapi',
            'status' => $status,
            'created_by' => $created_by,
            'created_at' => get_current_utc_time(),
        ));
    }

    public function logAuthAttempt(string $action, string $description, array $payload = array(), string $status = 'invalid'): bool
    {
        return $this->auditEvent($action, $description, $payload, $status, 'auth', null, null);
    }

    protected function buildContextFromApiUser(object $api_user): array
    {
        $email = strtolower(trim((string) ($api_user->user ?? '')));
        if ($email === '') {
            return array();
        }

        $user = $this->usersModel->get_one_where(array(
            'email' => $email,
            'deleted' => 0,
            'status' => 'active',
            'disable_login' => 0,
            'user_type' => 'staff',
        ));

        if (empty($user->id)) {
            return array();
        }

        $access = $this->usersModel->get_access_info((int) $user->id);
        $permissions = array();
        if (!empty($access->permissions)) {
            $permissions = @unserialize($access->permissions);
            if (!is_array($permissions)) {
                $permissions = array();
            }
        }

        $team_member_ids = $this->resolveTeamMemberIds($access ? explode(',', (string) ($access->team_ids ?? '')) : array());
        $scope = 'own';
        if (!empty($user->is_admin) || $this->arrayValue($permissions, 'pontorh_admin') == '1') {
            $scope = 'all';
        } elseif ($this->arrayValue($permissions, 'pontorh_view_reports') == '1'
            || $this->arrayValue($permissions, 'pontorh_manage_settings') == '1') {
            $scope = 'all';
        } elseif ($this->arrayValue($permissions, 'pontorh_view_team') == '1'
            || $this->arrayValue($permissions, 'pontorh_approve_adjustment') == '1'
            || $this->arrayValue($permissions, 'pontorh_manage_schedules') == '1') {
            $scope = 'team';
        }

        return array(
            'api_user' => $api_user,
            'user' => $user,
            'user_id' => (int) $user->id,
            'team_member_id' => (int) $user->id,
            'permissions' => $permissions,
            'is_admin' => !empty($user->is_admin),
            'role_title' => (string) ($access->role_title ?? ''),
            'team_ids' => $access ? (string) ($access->team_ids ?? '') : '',
            'team_member_ids' => $team_member_ids,
            'scope' => $scope,
        );
    }

    protected function resolveTeamMemberIds(array $team_ids): array
    {
        $team_ids = array_values(array_filter(array_map('intval', $team_ids)));
        if (!$team_ids) {
            return array();
        }

        $rows = $this->teamModel->get_members($team_ids)->getResult();
        $member_ids = array();
        foreach ($rows as $row) {
            foreach (explode(',', (string) ($row->members ?? '')) as $member_id) {
                $member_id = (int) trim((string) $member_id);
                if ($member_id > 0) {
                    $member_ids[] = $member_id;
                }
            }
        }

        return array_values(array_unique(array_filter($member_ids)));
    }

    protected function resolveFallbackUserId(): int
    {
        $db = db_connect('default');
        $users_table = $db->prefixTable('users');

        $row = $db->query("SELECT id FROM {$users_table} WHERE deleted = 0 AND status = 'active' AND user_type = 'staff' ORDER BY is_admin DESC, id ASC LIMIT 1")->getRow();
        if (!empty($row->id)) {
            return (int) $row->id;
        }

        $row = $db->query("SELECT id FROM {$users_table} WHERE deleted = 0 ORDER BY id ASC LIMIT 1")->getRow();
        return !empty($row->id) ? (int) $row->id : 1;
    }

    protected function requestIpAddress(): string
    {
        $request = service('request');
        $ip = (string) $request->getIPAddress();
        return $ip !== '' ? $ip : '0.0.0.0';
    }

    protected function normalizeSchedule(object $schedule): array
    {
        return array(
            'id' => (int) ($schedule->id ?? 0),
            'name' => (string) ($schedule->name ?? ''),
            'schedule_type' => (string) ($schedule->schedule_type ?? ''),
            'start_time' => (string) ($schedule->start_time ?? ''),
            'end_time' => (string) ($schedule->end_time ?? ''),
            'break_minutes' => (int) ($schedule->break_minutes ?? 0),
            'tolerance_minutes' => (int) ($schedule->tolerance_minutes ?? 0),
            'extra_tolerance_minutes' => (int) ($schedule->extra_tolerance_minutes ?? 0),
            'bank_hours' => (float) ($schedule->bank_hours ?? 0),
            'active' => (int) ($schedule->active ?? 0),
        );
    }

    protected function buildCurrentStatus(int $team_member_id, ?object $schedule = null): array
    {
        $today = date('Y-m-d');
        if (!$schedule) {
            $schedule = $this->shiftsModel->get_active_schedule_for_member($team_member_id);
        }

        $rows_result = $this->recordsModel->get_details(array(
            'team_member_id' => $team_member_id,
            'date_from' => $today,
            'date_to' => $today,
        ));
        $rows = $rows_result ? $rows_result->getResult() : array();

        $summary = $this->summarizeDayRecords($today, $rows, $schedule, true);
        $summary['last_record'] = !empty($rows) ? $this->mapRecordObject(reset($rows)) : null;

        return $summary;
    }

    protected function summarizeDayRecords(string $date, array $records, ?object $schedule = null, bool $include_current_time = false): array
    {
        usort($records, static function ($a, $b) {
            return strcmp((string) $a->punch_time, (string) $b->punch_time);
        });

        $schedule_minutes = $this->scheduleMinutes($schedule);
        $scheduled_tolerance = (int) ($schedule->tolerance_minutes ?? 0);
        $scheduled_extra_tolerance = (int) ($schedule->extra_tolerance_minutes ?? 0);
        $expected_minutes = $schedule_minutes;
        $bank_enabled = (string) $this->settingsModel->get_setting('bank_hours_enabled', '1') !== '0';
        $bank_start = $bank_enabled ? (int) round(((float) ($schedule->bank_hours ?? 0)) * 60) : 0;

        $entries = array();
        $exits = array();
        $intervals_minutes = 0;
        $worked_minutes = 0;
        $open_time = null;
        $last_exit_time = null;
        $last_exit_type = '';
        $first_entry_time = null;

        foreach ($records as $record) {
            $record_time = strtotime((string) $record->punch_time);
            if (!$record_time) {
                continue;
            }

            if (in_array($record->punch_type, array('in', 'lunch_return'), true)) {
                $entries[] = $this->formatLocalTimeValue($record->punch_time);
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
                $exits[] = $this->formatLocalTimeValue($record->punch_time);
                if ($open_time) {
                    $worked_minutes += (int) floor(($record_time - $open_time) / 60);
                    $open_time = null;
                }
                $last_exit_time = $record_time;
                $last_exit_type = $record->punch_type;
            }
        }

        if ($include_current_time && $open_time) {
            $worked_minutes += max(0, (int) floor((time() - $open_time) / 60));
        }

        $late_minutes = 0;
        if ($schedule && $first_entry_time && !empty($schedule->start_time)) {
            $entry_minutes = (int) floor(($first_entry_time - strtotime($date . ' 00:00:00')) / 60);
            $scheduled_start = $this->timeToMinutes((string) $schedule->start_time);
            $late_minutes = max(0, $entry_minutes - $scheduled_start - $scheduled_tolerance);
        }

        $extra_minutes = max(0, $worked_minutes - $expected_minutes - $scheduled_extra_tolerance);
        $bank_minutes = $bank_start + ($worked_minutes - $expected_minutes);
        $status = 'pendente';
        $next_expected_action = 'entrada';

        if (!empty($records)) {
            $last_type = (string) ($records[array_key_last($records)]->punch_type ?? '');
            if ($last_type === 'in') {
                $status = 'em_trabalho';
                $next_expected_action = 'saida_intervalo';
            } elseif ($last_type === 'lunch_out') {
                $status = 'em_intervalo';
                $next_expected_action = 'retorno_intervalo';
            } elseif ($last_type === 'lunch_return') {
                $status = 'em_trabalho';
                $next_expected_action = 'saida';
            } elseif ($last_type === 'out') {
                $status = 'finalizado';
                $next_expected_action = 'entrada';
            }
        }

        return array(
            'status' => $status,
            'entry_recorded_at' => !empty($entries) ? $entries[0] : null,
            'interval_started_at' => $this->findLatestPunchTime($records, 'lunch_out'),
            'interval_finished_at' => $this->findLatestPunchTime($records, 'lunch_return'),
            'exit_recorded_at' => $this->findLatestPunchTime($records, 'out'),
            'worked_minutes' => $worked_minutes,
            'worked_hours' => $this->formatMinutesToTime($worked_minutes),
            'remaining_minutes' => max(0, $expected_minutes - $worked_minutes),
            'remaining_hours' => $this->formatMinutesToTime(max(0, $expected_minutes - $worked_minutes)),
            'bank_minutes' => $bank_minutes,
            'bank_hours' => $this->formatMinutesToTime($bank_minutes),
            'extra_minutes' => $extra_minutes,
            'extra_hours' => $this->formatMinutesToTime($extra_minutes),
            'late_minutes' => $late_minutes,
            'late_hours' => $this->formatMinutesToTime($late_minutes),
            'entries' => $entries,
            'exits' => $exits,
            'next_expected_action' => $next_expected_action,
        );
    }

    protected function scheduleMinutes(?object $schedule): int
    {
        if (!$schedule || empty($schedule->start_time) || empty($schedule->end_time)) {
            return 0;
        }

        $start = $this->timeToMinutes((string) $schedule->start_time);
        $end = $this->timeToMinutes((string) $schedule->end_time);
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

    protected function timeToMinutes(string $time): ?int
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        }

        $timestamp = strtotime($time);
        if (!$timestamp) {
            return null;
        }

        return ((int) date('H', $timestamp) * 60) + (int) date('i', $timestamp);
    }

    protected function findLatestPunchTime(array $records, string $type): ?string
    {
        for ($i = count($records) - 1; $i >= 0; $i--) {
            $record = $records[$i];
            if (($record->punch_type ?? '') === $type) {
                return $this->formatLocalTimeValue($record->punch_time);
            }
        }

        return null;
    }

    protected function formatMinutesToTime($minutes): string
    {
        $minutes = (int) round((float) $minutes);
        $sign = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);
        $hours = floor($minutes / 60);
        $remaining = $minutes % 60;
        return $sign . str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $remaining, 2, '0', STR_PAD_LEFT);
    }

    protected function mapRecordRow(array $row): array
    {
        $type_map = array(
            'in' => 'entrada',
            'lunch_out' => 'saida_intervalo',
            'lunch_return' => 'retorno_intervalo',
            'out' => 'saida',
        );

        return array(
            'id' => (int) ($row['id'] ?? 0),
            'type' => $this->arrayValue($type_map, (string) ($row['punch_type'] ?? ''), (string) ($row['punch_type'] ?? '')),
            'time' => !empty($row['punch_time']) ? $this->formatLocalTimeValue($row['punch_time']) : null,
            'date' => (string) ($row['date'] ?? ''),
            'location' => (string) ($row['location_name'] ?? ''),
            'source' => (string) ($row['source'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'latitude' => (string) ($row['latitude'] ?? '0'),
            'longitude' => (string) ($row['longitude'] ?? '0'),
            'team_member_name' => (string) ($row['team_member_name'] ?? ''),
        );
    }

    protected function mapRecordObject(object $row): array
    {
        return $this->mapRecordRow((array) $row);
    }

    protected function normalizeScheduleType(array $records): string
    {
        return !empty($records) ? 'em_trabalho' : 'pendente';
    }

    protected function buildRecordNotes(array $payload, string $device_name, $battery_level): ?string
    {
        $notes = array();
        if ($device_name !== '') {
            $notes['device_name'] = $device_name;
        }
        if ($battery_level !== null && $battery_level !== '') {
            $notes['battery_level'] = (int) $battery_level;
        }
        $extra = $this->arrayValue($payload, 'notes');
        if ($extra) {
            $notes['payload_notes'] = $extra;
        }

        return $notes ? pontorh_safe_json($notes) : null;
    }

    protected function isSequenceValid(int $team_member_id, string $type): bool
    {
        $today = date('Y-m-d');
        $records = $this->recordsModel->get_details(array(
            'team_member_id' => $team_member_id,
            'date_from' => $today,
            'date_to' => $today,
        ))->getResult();

        if (!$records) {
            return $type === 'entrada';
        }

        $last = reset($records);
        $last_type = (string) ($last->punch_type ?? '');

        if ($last_type === 'in') {
            return in_array($type, array('saida_intervalo', 'saida'), true);
        }

        if ($last_type === 'lunch_out') {
            return $type === 'retorno_intervalo';
        }

        if ($last_type === 'lunch_return') {
            return $type === 'saida';
        }

        if ($last_type === 'out') {
            return false;
        }

        return true;
    }

    protected function matchLocation(string $latitude, string $longitude, array $settings)
    {
        if ($latitude === '' || $longitude === '') {
            return null;
        }

        $allowed_radius = (int) $this->arrayValue($settings, 'allowed_radius_meters', 0);
        if ($allowed_radius <= 0) {
            return null;
        }

        $locations_result = $this->locationsModel->get_details(array('active' => 1));
        $locations = $locations_result ? $locations_result->getResult() : array();
        foreach ($locations as $location) {
            $radius = (int) ($location->radius_meters ?? $allowed_radius);
            $distance = $this->distanceMeters((float) $latitude, (float) $longitude, (float) ($location->latitude ?? 0), (float) ($location->longitude ?? 0));
            if ($distance <= $radius) {
                return $location;
            }
        }

        return null;
    }

    protected function distanceMeters($lat1, $lng1, $lat2, $lng2)
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

    protected function refreshMonthlySummary(int $team_member_id, int $year, int $month, array $summary = array()): void
    {
        $period_start = sprintf('%04d-%02d-01', $year, $month);
        $period_end = date('Y-m-t', strtotime($period_start));
        $records = $this->recordsModel->get_details(array(
            'team_member_id' => $team_member_id,
            'date_from' => $period_start,
            'date_to' => $period_end,
        ))->getResult();

        $grouped = array();
        foreach ($records as $record) {
            $grouped[$record->date][] = $record;
        }

        $schedule = $this->shiftsModel->get_active_schedule_for_member($team_member_id);
        $days_in_month = (int) date('t', strtotime($period_start));

        $worked_minutes = 0;
        $extra_minutes = 0;
        $absence_minutes = 0;
        $late_minutes = 0;
        $adjustment_minutes = 0;
        $expected_minutes = 0;

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $day_records = $this->arrayValue($grouped, $date, array());
            $day_summary = $this->summarizeDayRecords($date, $day_records, $schedule, false);
            $worked_minutes += (int) $this->arrayValue($day_summary, 'worked_minutes', 0);
            $extra_minutes += (int) $this->arrayValue($day_summary, 'extra_minutes', 0);
            $late_minutes += (int) $this->arrayValue($day_summary, 'late_minutes', 0);
            if ($this->isWorkdayForMember($date, $team_member_id, $schedule)) {
                $expected_minutes += $this->scheduleMinutes($schedule);
            }
            if (!$day_records && $this->isWorkdayForMember($date, $team_member_id, $schedule)) {
                $absence_minutes += $this->scheduleMinutes($schedule);
            }
        }

        $payload = array(
            'team_member_id' => $team_member_id,
            'user_id' => $team_member_id,
            'date' => $period_start,
            'summary_year' => $year,
            'summary_month' => $month,
            'expected_minutes' => $expected_minutes,
            'worked_minutes' => $worked_minutes,
            'overtime_minutes' => $extra_minutes,
            'absence_minutes' => $absence_minutes,
            'late_minutes' => $late_minutes,
            'adjustment_minutes' => $adjustment_minutes,
            'status' => 'calculated',
            'hash' => hash('sha256', implode('|', array($team_member_id, $year, $month, $worked_minutes, $extra_minutes, microtime(true)))),
            'created_by' => $team_member_id,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        $this->monthlySummariesModel->upsert_summary($payload);
    }

    protected function isWorkdayForMember(string $date, int $team_member_id, ?object $schedule = null): bool
    {
        $weekday = (int) date('N', strtotime($date));
        if ($schedule && !empty($schedule->days_of_week)) {
            $days = array_values(array_filter(array_map('intval', explode(',', (string) $schedule->days_of_week))));
            if ($days) {
                return in_array($weekday, $days, true);
            }
        }

        $days = $this->assignmentsModel->get_member_workdays($team_member_id, (int) ($schedule->id ?? 0));
        if ($days) {
            return in_array($weekday, $days, true);
        }

        return $weekday >= 1 && $weekday <= 5;
    }
}
