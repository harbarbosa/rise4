<?php

namespace LicitaIA;

use App\Controllers\Security_Controller;

class Plugin
{
    private static bool $schema_checked = false;

    public static function register()
    {
        self::registerMenus();
        self::registerPermissions();
        self::registerNotificationHooks();
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
            $migrations->setNamespace('LicitaIA');
            $migrations->latest();
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA] Migration hook error: ' . $e->getMessage());
        }
    }

    public static function canAccessModule($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_view',
            'licitaia_manage',
            'licitaia_keywords',
            'licitaia_sources',
            'licitaia_checklist',
            'licitaia_reports',
            'licitaia_settings',
            'licitaia_admin',
        ));
    }

    public static function canViewDashboard($login_user)
    {
        return self::canAccessModule($login_user);
    }

    public static function canViewOpportunities($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_view',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canManageOpportunities($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canViewKeywords($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_keywords',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canManageKeywords($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_keywords',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canViewSources($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_sources',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canManageSources($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_sources',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canViewChecklist($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_checklist',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canManageChecklist($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_checklist',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canViewReports($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_reports',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canManageSettings($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_settings',
            'licitaia_admin',
        ));
    }

    public static function canManageAiSettings($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_ai_settings',
            'licitaia_settings',
            'licitaia_admin',
        ));
    }

    public static function canRunSearch($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_sources',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canGenerateReport($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_generate_report',
            'licitaia_reports',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canDeleteRecords($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_delete_records',
            'licitaia_manage',
            'licitaia_admin',
        ));
    }

    public static function canUseAi($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'licitaia_ai',
            'licitaia_manage',
            'licitaia_admin',
        ));
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
                $submenu['licitaia_dashboard'] = array('name' => 'licitaia_dashboard', 'url' => 'licitaia', 'class' => 'home');
            }

            if (self::canViewOpportunities($login_user)) {
                $submenu['licitaia_opportunities'] = array('name' => 'licitaia_opportunities', 'url' => 'licitaia/opportunities', 'class' => 'file-text');
            }

            if (self::canViewKeywords($login_user)) {
                $submenu['licitaia_keywords'] = array('name' => 'licitaia_keywords', 'url' => 'licitaia/keywords', 'class' => 'hash');
            }

            if (self::canViewSources($login_user)) {
                $submenu['licitaia_sources'] = array('name' => 'licitaia_sources', 'url' => 'licitaia/sources', 'class' => 'globe');
                $submenu['licitaia_search_notices'] = array('name' => 'licitaia_search_notices', 'url' => 'licitaia/search', 'class' => 'search');
            }

            if (self::canViewChecklist($login_user)) {
                $submenu['licitaia_checklist'] = array('name' => 'licitaia_checklist', 'url' => 'licitaia/checklist', 'class' => 'check-square');
            }

            if (self::canViewReports($login_user)) {
                $submenu['licitaia_reports'] = array('name' => 'licitaia_reports', 'url' => 'licitaia/reports', 'class' => 'bar-chart-2');
            }

            if (self::canManageSettings($login_user) || self::canManageAiSettings($login_user)) {
                $submenu['licitaia_settings'] = array('name' => 'licitaia_settings', 'url' => 'licitaia/settings', 'class' => 'settings');
            }

            if (!$submenu) {
                return $sidebar_menu;
            }

            $sidebar_menu['licitaia'] = array(
                'name' => 'licitaia_menu',
                'url' => 'licitaia',
                'class' => 'shield',
                'position' => 8,
                'submenu' => $submenu,
                'sub_pages' => array(
                    'licitaia/index',
                    'licitaia/opportunities',
                    'licitaia/opportunities/view',
                    'licitaia/opportunities/modal_form',
                    'licitaia/opportunities/upload_document',
                    'licitaia/opportunities/download_document',
                    'licitaia/opportunities/create_task',
                    'licitaia/keywords',
                    'licitaia/keywords/modal_form',
                    'licitaia/sources',
                    'licitaia/sources/modal_form',
                    'licitaia/search',
                    'licitaia/search/import_selected',
                    'licitaia/search/run_cron',
                    'licitaia/settings',
                    'licitaia/checklist',
                    'licitaia/checklist/modal_form',
                    'licitaia/reports',
                    'licitaia/reports/technical_opinion',
                    'licitaia/reports/download',
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

                $view_path = PLUGINPATH . 'LicitaIA/Views/permissions/role_permissions.php';
                if (file_exists($view_path)) {
                    include $view_path;
                    if (!defined('LICITAIA_ROLE_PERMISSIONS_RENDERED')) {
                        define('LICITAIA_ROLE_PERMISSIONS_RENDERED', true);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[LicitaIA] Permissions hook error: ' . $e->getMessage());
            }
        });

        app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
            $request = \Config\Services::request();
            $keys = array(
                'licitaia_view',
                'licitaia_manage',
                'licitaia_keywords',
                'licitaia_sources',
                'licitaia_checklist',
                'licitaia_reports',
                'licitaia_settings',
                'licitaia_ai_settings',
                'licitaia_generate_report',
                'licitaia_delete_records',
                'licitaia_ai',
                'licitaia_admin',
            );

            foreach ($keys as $key) {
                $permissions[$key] = $request->getPost($key) ? '1' : '';
            }

            return $permissions;
        });
    }

    private static function registerNotificationHooks()
    {
        app_hooks()->add_filter('app_filter_notification_category_suggestion', function ($category_suggestions) {
            $has_licitaia = false;
            foreach ($category_suggestions as $suggestion) {
                if (get_array_value($suggestion, 'id') === 'licitaia') {
                    $has_licitaia = true;
                    break;
                }
            }

            if (!$has_licitaia) {
                $category_suggestions[] = array(
                    'id' => 'licitaia',
                    'text' => app_lang('licitaia_menu'),
                );
            }

            return $category_suggestions;
        });

        app_hooks()->add_filter('app_filter_notification_description', function ($notification_descriptions, $notification) {
            if (!$notification) {
                return $notification_descriptions;
            }

            $notification = is_array($notification) ? (object) $notification : $notification;
            if (empty($notification->event) || $notification->event !== 'licitaia_alert') {
                return $notification_descriptions;
            }

            $alert_type = trim((string) ($notification->plugin_alert_type ?? ''));
            $opportunity_id = (int) ($notification->plugin_opportunity_id ?? 0);
            $link_url = trim((string) ($notification->plugin_link_url ?? ''));
            $message = trim((string) ($notification->plugin_message ?? ''));

            $opportunity_title = '';
            if ($opportunity_id) {
                try {
                    $opportunity = model('LicitaIA\\Models\\Opportunity_model')->get_one($opportunity_id);
                    if ($opportunity && !empty($opportunity->title)) {
                        $opportunity_title = $opportunity->title;
                    }
                } catch (\Throwable $e) {
                    log_message('error', '[LicitaIA] Notification description hook error: ' . $e->getMessage());
                }
            }

            if ($alert_type) {
                $notification_descriptions[] = '<div><strong>' . app_lang('licitaia_alert_type') . ':</strong> ' . esc(app_lang('licitaia_alert_type_' . $alert_type)) . '</div>';
            }

            if ($opportunity_id) {
                $notification_descriptions[] = '<div><strong>' . app_lang('licitaia_opportunity') . ':</strong> #' . $opportunity_id . ($opportunity_title ? ' - ' . esc($opportunity_title) : '') . '</div>';
            }

            if ($message) {
                $notification_descriptions[] = $message;
            }

            if ($link_url) {
                $notification_descriptions[] = '<div>' . anchor($link_url, app_lang('licitaia_alert_view_opportunity'), array('target' => '_blank')) . '</div>';
            }

            return $notification_descriptions;
        });

        app_hooks()->add_filter('app_filter_notification_config', function ($events) {
            $events['licitaia_alert'] = array(
                'notify_to' => array('team_members', 'team'),
                'info' => function ($notification) {
                    $notification = is_array($notification) ? (object) $notification : $notification;
                    $opportunity_id = (int) ($notification->plugin_opportunity_id ?? 0);

                    return array(
                        'url' => $opportunity_id ? get_uri('licitaia/opportunities/view/' . $opportunity_id) : get_uri('licitaia'),
                    );
                },
            );

            return $events;
        });
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
