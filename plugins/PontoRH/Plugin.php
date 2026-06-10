<?php

namespace PontoRH;

use App\Controllers\Security_Controller;

class Plugin
{
    private static bool $schema_checked = false;

    public static function register()
    {
        self::registerMenus();
        self::registerPermissions();
        self::registerNotificationHooks();
        self::ensureNotificationSettings();
        self::runMigrations();
    }

    public static function runMigrations()
    {
        if (self::$schema_checked) {
            return;
        }

        self::$schema_checked = true;

        try {
            $migrations = service('migrations');
            $migrations->setNamespace('PontoRH');
            $migrations->latest();
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Migration hook error: ' . $e->getMessage());
        }
    }

    public static function canAccessModule($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'pontorh_view_own',
            'pontorh_create_record',
            'pontorh_request_adjustment',
            'pontorh_view_team',
            'pontorh_approve_adjustment',
            'pontorh_manage_schedules',
            'pontorh_view_reports',
            'pontorh_manage_settings',
            'pontorh_admin',
        ));
    }

    public static function canAdmin($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_admin'));
    }

    public static function canViewOwn($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_view_own'));
    }

    public static function canCreateRecord($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_create_record'));
    }

    public static function canRequestAdjustment($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_request_adjustment'));
    }

    public static function canViewTeam($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_view_team'));
    }

    public static function canViewShifts($login_user)
    {
        return self::canManageSchedules($login_user) || self::canAdmin($login_user);
    }

    public static function canManageShifts($login_user)
    {
        return self::canManageSchedules($login_user) || self::canAdmin($login_user);
    }

    public static function canApproveAdjustment($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_approve_adjustment'));
    }

    public static function canManageSchedules($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_manage_schedules'));
    }

    public static function canViewReports($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_view_reports'));
    }

    public static function canManageSettings($login_user)
    {
        return self::hasAnyPermission($login_user, array('pontorh_manage_settings'));
    }

    public static function canManageLocations($login_user)
    {
        return self::canManageSchedules($login_user)
            || self::canManageSettings($login_user)
            || self::canAdmin($login_user);
    }

    public static function canViewAllData($login_user)
    {
        if (self::canAdmin($login_user)) {
            return true;
        }

        $permissions = $login_user->permissions ?? array();
        return get_array_value($permissions, 'pontorh_view_team') == '1'
            && get_array_value($permissions, 'pontorh_view_reports') == '1'
            && get_array_value($permissions, 'pontorh_manage_schedules') == '1'
            && get_array_value($permissions, 'pontorh_approve_adjustment') == '1'
            && get_array_value($permissions, 'pontorh_manage_settings') == '1';
    }

    public static function canViewDashboard($login_user)
    {
        return self::canAccessModule($login_user);
    }

    public static function canViewRecords($login_user)
    {
        return self::canViewOwn($login_user)
            || self::canCreateRecord($login_user)
            || self::canViewTeam($login_user)
            || self::canViewAllData($login_user);
    }

    public static function canManageRecords($login_user)
    {
        return self::canCreateRecord($login_user) || self::canViewAllData($login_user) || self::canAdmin($login_user);
    }

    public static function canViewAdjustments($login_user)
    {
        return self::canRequestAdjustment($login_user) || self::canApproveAdjustment($login_user) || self::canViewAllData($login_user);
    }

    public static function canManageAdjustments($login_user)
    {
        return self::canApproveAdjustment($login_user) || self::canAdmin($login_user);
    }

    public static function canViewMirror($login_user)
    {
        return self::canViewOwn($login_user)
            || self::canViewTeam($login_user)
            || self::canViewAllData($login_user)
            || self::canAdmin($login_user);
    }

    public static function canViewTeamScope($login_user)
    {
        return self::canViewTeam($login_user) || self::canViewAllData($login_user);
    }

    public static function canManageSchedulesView($login_user)
    {
        return self::canManageSchedules($login_user) || self::canAdmin($login_user);
    }

    public static function canManageSchedulesOnly($login_user)
    {
        return self::canManageSchedules($login_user) || self::canAdmin($login_user);
    }

    public static function canViewReportsScope($login_user)
    {
        return self::canViewReports($login_user) || self::canViewAllData($login_user) || self::canAdmin($login_user);
    }

    public static function canManageSettingsScope($login_user)
    {
        return self::canManageSettings($login_user) || self::canAdmin($login_user);
    }

    public static function canViewAuditLogs($login_user)
    {
        return self::canManageSettingsScope($login_user);
    }

    public static function canManageRecordsScope($login_user)
    {
        return self::canCreateRecord($login_user) || self::canAdmin($login_user);
    }

    public static function canApproveAdjustmentScope($login_user)
    {
        return self::canApproveAdjustment($login_user) || self::canAdmin($login_user);
    }

    public static function canRequestAdjustmentScope($login_user)
    {
        return self::canRequestAdjustment($login_user) || self::canAdmin($login_user);
    }

    public static function canManageSchedulesAccess($login_user)
    {
        return self::canManageSchedules($login_user) || self::canAdmin($login_user);
    }

    public static function canViewTeamAndAbove($login_user)
    {
        return self::canViewTeam($login_user) || self::canViewAllData($login_user);
    }

    public static function canUseModule($login_user)
    {
        return self::canAccessModule($login_user);
    }

    public static function canViewReportsOnly($login_user)
    {
        return self::canViewReports($login_user) || self::canViewAllData($login_user);
    }

    public static function canManageSettingsOnly($login_user)
    {
        return self::canManageSettings($login_user) || self::canAdmin($login_user);
    }

    public static function canManageSchedulesOnlyAccess($login_user)
    {
        return self::canManageSchedules($login_user) || self::canAdmin($login_user);
    }

    public static function canViewDashboardOnly($login_user)
    {
        return self::canAccessModule($login_user);
    }

    public static function canViewAllRecords($login_user)
    {
        return self::canViewAllData($login_user);
    }

    public static function canViewOwnRecords($login_user)
    {
        return self::canViewOwn($login_user) || self::canViewTeam($login_user) || self::canViewAllData($login_user);
    }

    public static function canManageSchedulesItems($login_user)
    {
        return self::canManageSchedules($login_user) || self::canAdmin($login_user);
    }

    public static function canViewReportsItems($login_user)
    {
        return self::canViewReports($login_user) || self::canViewAllData($login_user);
    }

    public static function canManageSettingsItems($login_user)
    {
        return self::canManageSettings($login_user) || self::canAdmin($login_user);
    }

    public static function canCreateRecordScope($login_user)
    {
        return self::canCreateRecord($login_user) || self::canAdmin($login_user);
    }

    public static function canRequestAdjustmentItems($login_user)
    {
        return self::canRequestAdjustment($login_user) || self::canAdmin($login_user);
    }

    public static function canApproveAdjustmentItems($login_user)
    {
        return self::canApproveAdjustment($login_user) || self::canAdmin($login_user);
    }

    public static function canViewTeamItems($login_user)
    {
        return self::canViewTeam($login_user) || self::canViewAllData($login_user);
    }

    public static function canViewOwnItems($login_user)
    {
        return self::canViewOwn($login_user) || self::canViewTeam($login_user) || self::canViewAllData($login_user);
    }

    public static function canAdminAccess($login_user)
    {
        return self::canAdmin($login_user);
    }

    public static function canManageModuleSettings($login_user)
    {
        return self::canManageSettings($login_user) || self::canAdmin($login_user);
    }

    public static function canViewModuleReports($login_user)
    {
        return self::canViewReports($login_user) || self::canViewAllData($login_user);
    }

    public static function canManageModuleSchedules($login_user)
    {
        return self::canManageSchedules($login_user) || self::canAdmin($login_user);
    }

    public static function canViewModuleTeam($login_user)
    {
        return self::canViewTeam($login_user) || self::canViewAllData($login_user);
    }

    public static function canViewModuleOwn($login_user)
    {
        return self::canViewOwn($login_user) || self::canViewTeam($login_user) || self::canViewAllData($login_user);
    }

    private static function registerMenus()
    {
        app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
            $ci = new Security_Controller(false);
            $login_user = $ci->login_user ?? null;

            if (!$login_user || $login_user->user_type !== 'staff' || !self::canAccessModule($login_user)) {
                return $sidebar_menu;
            }

            $submenu = array();

            if (self::canViewDashboard($login_user)) {
                $submenu['pontorh_dashboard'] = array('name' => 'pontorh_dashboard', 'url' => 'pontorh', 'class' => 'home');
            }

            if (self::canViewRecords($login_user)) {
                $submenu['pontorh_records'] = array('name' => 'pontorh_records', 'url' => 'pontorh/registros', 'class' => 'list');
            }

            if (self::canViewShifts($login_user)) {
                $submenu['pontorh_shifts'] = array('name' => 'pontorh_shifts', 'url' => 'pontorh/jornadas', 'class' => 'calendar');
            }

            if (self::canViewAdjustments($login_user)) {
                $submenu['pontorh_adjustments'] = array('name' => 'pontorh_adjustments', 'url' => 'pontorh/ajustes', 'class' => 'sliders');
            }

            if (self::canViewMirror($login_user)) {
                $submenu['pontorh_mirror'] = array('name' => 'pontorh_mirror', 'url' => 'pontorh/espelho', 'class' => 'copy');
            }

            if (self::canManageLocations($login_user)) {
                $submenu['pontorh_locations'] = array('name' => 'pontorh_locations', 'url' => 'pontorh/locais', 'class' => 'map-pin');
            }

            if (self::canViewReportsScope($login_user)) {
                $submenu['pontorh_reports'] = array('name' => 'pontorh_reports', 'url' => 'pontorh/relatorios', 'class' => 'bar-chart-2');
            }

            if (self::canViewReportsScope($login_user) || self::canManageSettingsScope($login_user) || self::canAdmin($login_user)) {
                $submenu['pontorh_treatment'] = array('name' => 'pontorh_treatment', 'url' => 'pontorh/tratamento', 'class' => 'filter');
            }

            if (self::canManageSettingsScope($login_user)) {
                $submenu['pontorh_settings'] = array('name' => 'pontorh_settings', 'url' => 'pontorh/configuracoes', 'class' => 'settings');
                $submenu['pontorh_audit_logs'] = array('name' => 'pontorh_audit_logs', 'url' => 'pontorh/auditoria', 'class' => 'file-text');
            }

            if (!$submenu) {
                return $sidebar_menu;
            }

            $sidebar_menu['pontorh'] = array(
                'name' => 'pontorh_menu',
                'url' => 'pontorh',
                'class' => 'clock',
                'position' => 8,
                'submenu' => $submenu,
                'sub_pages' => array(
                    'pontorh/index',
                    'pontorh/registros',
                    'pontorh/jornadas',
                    'pontorh/ajustes',
                    'pontorh/espelho',
                    'pontorh/locais',
                    'pontorh/locais/detalhes',
                    'pontorh/locais/modal_form',
                    'pontorh/locais/view_modal',
                    'pontorh/locais/assignment_modal',
                    'pontorh/relatorios',
                    'pontorh/tratamento',
                    'pontorh/configuracoes',
                    'pontorh/auditoria',
                ),
            );

            return $sidebar_menu;
        });
    }

    private static function registerPermissions()
    {
        app_hooks()->add_action('app_hook_role_permissions_extension', function () {
            try {
                $request = \Config\Services::request();
                $role_id = (int) $request->getUri()->getSegment(3);
                $permissions = array();

                if ($role_id) {
                    $roles_model = model('App\\Models\\Roles_model');
                    $role = $roles_model->get_one($role_id);
                    $permissions = $role && $role->permissions ? unserialize($role->permissions) : array();
                }

                if (!is_array($permissions)) {
                    $permissions = array();
                }

                $view_path = PLUGINPATH . 'PontoRH/Views/permissions/role_permissions.php';
                if (file_exists($view_path)) {
                    include $view_path;
                    if (!defined('PONTORH_ROLE_PERMISSIONS_RENDERED')) {
                        define('PONTORH_ROLE_PERMISSIONS_RENDERED', true);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[PontoRH] Permissions hook error: ' . $e->getMessage());
            }
        });

        app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
            $request = \Config\Services::request();
            $keys = array(
                'pontorh_view_own',
                'pontorh_create_record',
                'pontorh_request_adjustment',
                'pontorh_view_team',
                'pontorh_approve_adjustment',
                'pontorh_manage_schedules',
                'pontorh_view_reports',
                'pontorh_manage_settings',
                'pontorh_admin',
            );

            foreach ($keys as $key) {
                $permissions[$key] = $request->getPost($key) ? '1' : '';
            }

            return $permissions;
        });
    }

    private static function registerNotificationHooks()
    {
        app_hooks()->add_filter('app_filter_notification_config', function ($events) {
            $adjustment_link = function ($options) {
                $adjustment_id = 0;
                if (is_object($options) && isset($options->plugin_adjustment_id)) {
                    $adjustment_id = (int) $options->plugin_adjustment_id;
                } elseif (is_array($options) && isset($options['plugin_adjustment_id'])) {
                    $adjustment_id = (int) $options['plugin_adjustment_id'];
                }

                $url = $adjustment_id ? get_uri('pontorh/ajustes/detalhes/' . $adjustment_id) : get_uri('pontorh/ajustes');
                return array('url' => $url, 'app_modal_url' => $adjustment_id ? get_uri('pontorh/ajustes/view_modal/' . $adjustment_id) : get_uri('pontorh/ajustes'));
            };

            $events['pontorh_adjustment_requested'] = array(
                'notify_to' => array('team_members', 'team'),
                'info' => $adjustment_link,
            );

            $events['pontorh_adjustment_reviewed'] = array(
                'notify_to' => array('team_members', 'team'),
                'info' => $adjustment_link,
            );

            return $events;
        });

        app_hooks()->add_filter('app_filter_create_notification_where_query', function ($where_queries, $hook_data) {
            $event = get_array_value($hook_data, 'event');
            if (!in_array($event, array('pontorh_adjustment_requested', 'pontorh_adjustment_reviewed'), true)) {
                return $where_queries;
            }

            $options = get_array_value($hook_data, 'options');
            $users_model = model('App\\Models\\Users_model');
            $users_table = db_connect('default')->prefixTable('users');
            $target_ids = array();

            if ($event === 'pontorh_adjustment_requested') {
                foreach ($users_model->get_all_where(array('deleted' => 0, 'status' => 'active', 'user_type' => 'staff'))->getResult() as $user) {
                    if (!empty($user->is_admin)) {
                        $target_ids[] = (int) $user->id;
                        continue;
                    }

                    $permissions = @unserialize($user->permissions);
                    if (!is_array($permissions)) {
                        $permissions = array();
                    }

                    if (get_array_value($permissions, 'pontorh_approve_adjustment') == '1') {
                        $target_ids[] = (int) $user->id;
                    }
                }
            } else {
                $requester_id = (int) get_array_value($options, 'plugin_requester_id');
                if ($requester_id) {
                    $target_ids[] = $requester_id;
                }
            }

            $target_ids = array_values(array_unique(array_filter(array_map('intval', $target_ids))));
            if (!$target_ids) {
                return $where_queries;
            }

            $where_queries[] = ' OR ' . $users_table . '.id IN (' . implode(',', $target_ids) . ')';
            return $where_queries;
        });

        app_hooks()->add_filter('app_filter_notification_description', function ($descriptions, $notification) {
            if (!$notification || !in_array($notification->event, array('pontorh_adjustment_requested', 'pontorh_adjustment_reviewed'), true)) {
                return $descriptions;
            }

            $adjustment_id = (int) ($notification->plugin_adjustment_id ?? 0);
            if (!$adjustment_id) {
                return $descriptions;
            }

            $adjustment = model('PontoRH\\Models\\PontoRh_adjustments_model')->get_one_with_details($adjustment_id, array('scope' => 'all'));
            if (!$adjustment) {
                return $descriptions;
            }

            $descriptions[] = '<div><strong>' . app_lang('pontorh_employee') . ':</strong> ' . esc($adjustment->team_member_name ?: '-') . '</div>';
            $descriptions[] = '<div><strong>' . app_lang('pontorh_work_date') . ':</strong> ' . esc($adjustment->adjustment_date ?: '-') . '</div>';
            $descriptions[] = '<div><strong>' . app_lang('pontorh_check_in') . ':</strong> ' . esc($adjustment->adjustment_time ? pontorh_extract_time($adjustment->adjustment_time) : '-') . '</div>';
            $descriptions[] = '<div><strong>' . app_lang('pontorh_type') . ':</strong> ' . esc(pontorh_adjustment_type_label($adjustment->adjustment_type ?? '')) . '</div>';
            $descriptions[] = '<div><strong>' . app_lang('pontorh_status') . ':</strong> ' . esc(pontorh_adjustment_status_label($adjustment->status ?? '')) . '</div>';

            return $descriptions;
        });
    }

    private static function ensureNotificationSettings()
    {
        try {
            $db = db_connect('default');
            $table = $db->prefixTable('notification_settings');
            if (!$db->tableExists($table)) {
                return;
            }

            $events = array(
                'pontorh_adjustment_requested' => 980,
                'pontorh_adjustment_reviewed' => 981,
            );

            foreach ($events as $event => $sort) {
                $exists = $db->table($table)->where('event', $event)->where('deleted', 0)->get()->getRow();
                if ($exists) {
                    continue;
                }

                $db->table($table)->insert(array(
                    'event' => $event,
                    'category' => 'pontorh',
                    'enable_email' => 1,
                    'enable_web' => 1,
                    'enable_slack' => 0,
                    'notify_to_team' => '',
                    'notify_to_team_members' => '',
                    'notify_to_terms' => '',
                    'sort' => $sort,
                    'deleted' => 0,
                ));
            }
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Notification settings hook error: ' . $e->getMessage());
        }
    }

    private static function hasAnyPermission($login_user, $keys)
    {
        if (!$login_user) {
            return false;
        }

        if (!empty($login_user->is_admin)) {
            return true;
        }

        $permissions = $login_user->permissions ?? array();
        foreach ((array) $keys as $key) {
            if (get_array_value($permissions, $key) == '1') {
                return true;
            }
        }

        return false;
    }
}
