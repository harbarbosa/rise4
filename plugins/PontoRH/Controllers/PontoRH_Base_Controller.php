<?php

namespace PontoRH\Controllers;

use App\Controllers\Security_Controller;
use PontoRH\Libraries\PontoRh_service;
use PontoRH\Models\PontoRh_adjustments_model;
use PontoRH\Models\PontoRh_audit_logs_model;
use PontoRH\Models\PontoRh_assignments_model;
use PontoRH\Models\PontoRh_location_assignments_model;
use PontoRH\Models\PontoRh_locations_model;
use PontoRH\Models\PontoRh_records_model;
use PontoRH\Models\PontoRh_treatment_cases_model;
use PontoRH\Models\PontoRh_treatment_history_model;
use PontoRH\Models\PontoRh_settings_model;
use PontoRH\Models\PontoRh_shifts_model;
use App\Models\Team_model;

abstract class PontoRH_Base_Controller extends Security_Controller
{
    protected PontoRh_service $service;
    protected PontoRh_records_model $records_model;
    protected PontoRh_shifts_model $shifts_model;
    protected PontoRh_assignments_model $assignments_model;
    protected PontoRh_location_assignments_model $location_assignments_model;
    protected PontoRh_locations_model $locations_model;
    protected PontoRh_adjustments_model $adjustments_model;
    protected PontoRh_audit_logs_model $audit_logs_model;
    protected PontoRh_treatment_cases_model $treatment_cases_model;
    protected PontoRh_treatment_history_model $treatment_history_model;
    protected PontoRh_settings_model $settings_model;
    protected Team_model $team_model;

    public function __construct()
    {
        parent::__construct();
        \PontoRH\Plugin::runMigrations();
        $this->service = new PontoRh_service();
        $this->records_model = model(PontoRh_records_model::class);
        $this->shifts_model = model(PontoRh_shifts_model::class);
        $this->assignments_model = model(PontoRh_assignments_model::class);
        $this->location_assignments_model = model(PontoRh_location_assignments_model::class);
        $this->locations_model = model(PontoRh_locations_model::class);
        $this->adjustments_model = model(PontoRh_adjustments_model::class);
        $this->audit_logs_model = model(PontoRh_audit_logs_model::class);
        $this->treatment_cases_model = model(PontoRh_treatment_cases_model::class);
        $this->treatment_history_model = model(PontoRh_treatment_history_model::class);
        $this->settings_model = model(PontoRh_settings_model::class);
        $this->team_model = model(Team_model::class);
    }

    protected function ensureAccess()
    {
        if (!\PontoRH\Plugin::canAccessModule($this->login_user)) {
            $this->auditDeniedAccess('module');
            app_redirect('forbidden');
        }
    }

    protected function ensureDashboardAccess()
    {
        $this->ensureAccess();
    }

    protected function ensureRecordsAccess()
    {
        if (!\PontoRH\Plugin::canViewRecords($this->login_user)) {
            $this->auditDeniedAccess('records');
            app_redirect('forbidden');
        }
    }

    protected function ensureRecordsWriteAccess()
    {
        if (!\PontoRH\Plugin::canManageRecords($this->login_user)) {
            $this->auditDeniedAccess('records_write');
            app_redirect('forbidden');
        }
    }

    protected function ensureAdjustmentsAccess()
    {
        if (!\PontoRH\Plugin::canViewAdjustments($this->login_user)) {
            $this->auditDeniedAccess('adjustments');
            app_redirect('forbidden');
        }
    }

    protected function ensureAdjustmentsWriteAccess()
    {
        if (
            !\PontoRH\Plugin::canRequestAdjustment($this->login_user)
            && !\PontoRH\Plugin::canManageAdjustments($this->login_user)
            && !\PontoRH\Plugin::canAdmin($this->login_user)
        ) {
            $this->auditDeniedAccess('adjustments_write');
            app_redirect('forbidden');
        }
    }

    protected function ensureSchedulesAccess()
    {
        if (!\PontoRH\Plugin::canManageSchedules($this->login_user)) {
            $this->auditDeniedAccess('schedules');
            app_redirect('forbidden');
        }
    }

    protected function ensureReportsAccess()
    {
        if (!\PontoRH\Plugin::canViewReports($this->login_user) && !\PontoRH\Plugin::canViewAllData($this->login_user) && !\PontoRH\Plugin::canAdmin($this->login_user)) {
            $this->auditDeniedAccess('reports');
            app_redirect('forbidden');
        }
    }

    protected function ensureSettingsAccess()
    {
        if (!\PontoRH\Plugin::canManageSettings($this->login_user) && !\PontoRH\Plugin::canAdmin($this->login_user)) {
            $this->auditDeniedAccess('settings');
            app_redirect('forbidden');
        }
    }

    protected function ensureTreatmentAccess()
    {
        if (
            !\PontoRH\Plugin::canViewTeam($this->login_user)
            && !\PontoRH\Plugin::canApproveAdjustment($this->login_user)
            && !\PontoRH\Plugin::canViewReports($this->login_user)
            && !\PontoRH\Plugin::canManageSettings($this->login_user)
            && !\PontoRH\Plugin::canAdmin($this->login_user)
        ) {
            $this->auditDeniedAccess('treatment');
            app_redirect('forbidden');
        }
    }

    protected function ensureTreatmentWriteAccess()
    {
        if (
            !\PontoRH\Plugin::canApproveAdjustment($this->login_user)
            && !\PontoRH\Plugin::canViewReports($this->login_user)
            && !\PontoRH\Plugin::canManageSettings($this->login_user)
            && !\PontoRH\Plugin::canAdmin($this->login_user)
        ) {
            $this->auditDeniedAccess('treatment_write');
            app_redirect('forbidden');
        }
    }

    protected function currentDataScope()
    {
        if (\PontoRH\Plugin::canViewAllData($this->login_user) || \PontoRH\Plugin::canAdmin($this->login_user)) {
            return 'all';
        }

        if (\PontoRH\Plugin::canViewTeam($this->login_user) || \PontoRH\Plugin::canApproveAdjustment($this->login_user) || \PontoRH\Plugin::canManageSchedules($this->login_user) || \PontoRH\Plugin::canViewReports($this->login_user)) {
            return 'team';
        }

        return 'own';
    }

    protected function accessibleTeamMemberIds($scope = null)
    {
        $scope = $scope ?: $this->currentDataScope();

        if ($scope === 'all') {
            return array();
        }

        if ($scope === 'own') {
            return array((int) $this->login_user->id);
        }

        $team_ids = trim((string) ($this->login_user->team_ids ?? ''));
        if (!$team_ids) {
            return array((int) $this->login_user->id);
        }

        $member_ids = array((int) $this->login_user->id);
        $team_rows = $this->team_model->get_members(explode(',', $team_ids))->getResult();
        foreach ($team_rows as $team_row) {
            if (empty($team_row->members)) {
                continue;
            }

            foreach (explode(',', (string) $team_row->members) as $member_id) {
                $member_id = (int) trim($member_id);
                if ($member_id > 0) {
                    $member_ids[] = $member_id;
                }
            }
        }

        $member_ids = array_values(array_unique(array_filter($member_ids)));
        return $member_ids ?: array((int) $this->login_user->id);
    }

    protected function teamMembersDropdown($include_blank = true, $scope = null)
    {
        $users_model = model('App\\Models\\Users_model');
        $scope = $scope ?: $this->currentDataScope();
        $allowed_member_ids = $this->accessibleTeamMemberIds($scope);

        $options = array();
        if ($scope === 'all') {
            $rows = $users_model->get_team_members_id_and_name()->getResult();
        } else {
            $rows = $users_model->get_team_members_id_and_name()->getResult();
            if ($allowed_member_ids) {
                $rows = array_values(array_filter($rows, function ($row) use ($allowed_member_ids) {
                    return in_array((int) $row->id, $allowed_member_ids, true);
                }));
            }
        }

        if ($include_blank) {
            $options[''] = '-';
        }

        foreach ($rows as $row) {
            $options[$row->id] = $row->user_name;
        }

        return $options;
    }

    protected function shiftsDropdown($include_blank = true)
    {
        $dropdown = array();
        if ($include_blank) {
            $dropdown[''] = '-';
        }

        foreach ($this->shifts_model->get_active_dropdown() as $id => $label) {
            if ($id === '') {
                continue;
            }
            $dropdown[$id] = $label;
        }

        return $dropdown;
    }

    protected function locationsDropdown($include_blank = true)
    {
        return $this->locations_model->get_active_dropdown($include_blank);
    }

    protected function assignedLocationsDropdown(int $team_member_id, ?string $date = null, $include_blank = true)
    {
        $dropdown = array();
        if ($include_blank) {
            $dropdown[''] = '-';
        }

        $assigned_ids = $this->location_assignments_model->get_location_ids_for_member($team_member_id, $date);
        if (!$assigned_ids) {
            return $this->locationsDropdown($include_blank);
        }

        $result = $this->locations_model->get_details(array('active' => 1));
        foreach ($result ? $result->getResult() : array() as $row) {
            if (!in_array((int) $row->id, $assigned_ids, true)) {
                continue;
            }
            $dropdown[$row->id] = $row->name;
        }

        return $dropdown;
    }

    protected function combineDateTime($date, $time)
    {
        return $this->service->normalizeDateTime($date, $time);
    }

    protected function calculateWorkedMinutes($check_in, $check_out, $break_minutes = 0)
    {
        $check_in_ts = strtotime((string) $check_in);
        $check_out_ts = strtotime((string) $check_out);

        if (!$check_in_ts || !$check_out_ts || $check_out_ts <= $check_in_ts) {
            return 0;
        }

        $minutes = (int) floor(($check_out_ts - $check_in_ts) / 60);
        $minutes -= max(0, (int) $break_minutes);

        return max(0, $minutes);
    }

    protected function normalizeDaysOfWeek($days)
    {
        if (is_string($days)) {
            $days = explode(',', $days);
        }

        $days = array_map('trim', (array) $days);
        $days = array_values(array_filter($days, static function ($value) {
            return $value !== '';
        }));

        return $days;
    }

    protected function logAudit($entity_type, $entity_id, $action, $description, $payload = array(), $team_member_id = null)
    {
        return $this->audit_logs_model->log_action(array(
            'team_member_id' => $team_member_id,
            'user_id' => (int) $this->login_user->id,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'action' => $action,
            'description' => $description,
            'payload_json' => pontorh_safe_json($payload),
            'ip_address' => $this->request->getIPAddress(),
            'source' => 'manual',
            'status' => 'logged',
            'created_by' => (int) $this->login_user->id,
            'created_at' => get_current_utc_time(),
        ));
    }

    protected function renderPluginView($relative_path, $data = array())
    {
        $relative_path = trim((string) $relative_path, "/\\");
        $view_path = rtrim(PLUGINPATH, '/\\') . DIRECTORY_SEPARATOR . 'PontoRH' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relative_path) . '.php';

        if (!is_file($view_path)) {
            throw new \RuntimeException('Invalid plugin view file: ' . $view_path);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $view_path;
        return ob_get_clean();
    }

    protected function auditDeniedAccess($section = 'module')
    {
        if (!$this->login_user || empty($this->login_user->id)) {
            return;
        }

        $this->logAudit(
            'pontorh_access',
            0,
            'invalid_attempt',
            'Denied access attempt to ' . $section,
            array(
                'section' => $section,
                'path' => $this->request->getUri()->getPath(),
                'method' => $this->request->getMethod(),
            ),
            (int) $this->login_user->id
        );
    }
}
