<?php

namespace GED;

use App\Controllers\Security_Controller;

class Plugin
{
    private static $schema_checked = false;

    public static function register()
    {
        self::runMigrations();
        self::ensureNotificationSettings();
        self::registerMenus();
        self::registerPermissions();
        self::registerNotificationHooks();
    }

    public static function runInstall()
    {
        self::runMigrations();
        self::ensureNotificationSettings();
    }

    public static function runUpdate()
    {
        self::runMigrations();
        self::ensureNotificationSettings();
    }

    public static function canAccessModule($login_user)
    {
        if (!$login_user) {
            return false;
        }

        if (!empty($login_user->is_admin)) {
            return true;
        }

        $permissions = $login_user->permissions ?? array();

        return get_array_value($permissions, 'ged_access') == '1'
            || get_array_value($permissions, 'ged_view_documents') == '1'
            || get_array_value($permissions, 'ged_create_documents') == '1'
            || get_array_value($permissions, 'ged_edit_documents') == '1'
            || get_array_value($permissions, 'ged_delete_documents') == '1'
            || get_array_value($permissions, 'ged_download_documents') == '1'
            || get_array_value($permissions, 'ged_manage_document_types') == '1'
            || get_array_value($permissions, 'ged_view_reports') == '1'
            || get_array_value($permissions, 'ged_manage_settings') == '1'
            || get_array_value($permissions, 'ged_manage_notifications') == '1';
    }

    public static function canManageSettings($login_user)
    {
        return $login_user && (!empty($login_user->is_admin) || get_array_value($login_user->permissions ?? array(), 'ged_manage_settings') == '1');
    }

    public static function documentUrl($document_id)
    {
        $document_id = (int) $document_id;
        return $document_id ? get_uri('ged/documents/view/' . $document_id) : get_uri('ged/documents');
    }

    public static function runMigrations()
    {
        if (self::$schema_checked) {
            return;
        }

        self::$schema_checked = true;

        try {
            $migration_map = array(
                '2026-05-12-000001_CreateGedDocumentTypes.php' => 'GED\\Database\\Migrations\\CreateGedDocumentTypes',
                '2026-05-12-000002_CreateGedSuppliers.php' => 'GED\\Database\\Migrations\\CreateGedSuppliers',
                '2026-05-12-000003_CreateGedDocuments.php' => 'GED\\Database\\Migrations\\CreateGedDocuments',
                '2026-05-12-000004_CreateGedDocumentSubmissions.php' => 'GED\\Database\\Migrations\\CreateGedDocumentSubmissions',
                '2026-05-12-000005_CreateGedNotificationLogs.php' => 'GED\\Database\\Migrations\\CreateGedNotificationLogs',
                '2026-05-12-000006_CreateGedSettings.php' => 'GED\\Database\\Migrations\\CreateGedSettings',
                '2026-05-12-000007_AddGedNotificationColumns.php' => 'GED\\Database\\Migrations\\AddGedNotificationColumns',
            );

            foreach ($migration_map as $file => $class) {
                require_once __DIR__ . '/Database/Migrations/' . $file;
                if (class_exists($class)) {
                    (new $class())->up();
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[GED] Migration hook error: ' . $e->getMessage());
        }
    }

    private static function registerMenus()
    {
        app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
            $ci = new Security_Controller(false);
            $login_user = $ci->login_user ?? null;

            if (!self::canAccessModule($login_user)) {
                return $sidebar_menu;
            }

            if (!isset($sidebar_menu['ged'])) {
                $sidebar_menu['ged'] = array(
                    'name' => 'ged',
                    'url' => 'ged',
                    'class' => 'file-text',
                    'position' => 7,
                );
            }

            $permissions = $login_user->permissions ?? array();
            $submenu = array();

            if ($login_user->is_admin || get_array_value($permissions, 'ged_access') == '1' || get_array_value($permissions, 'ged_view_documents') == '1') {
                $submenu['ged_dashboard'] = array('name' => 'ged_dashboard', 'url' => 'ged', 'class' => 'home');
            }

            if ($login_user->is_admin || get_array_value($permissions, 'ged_view_documents') == '1' || get_array_value($permissions, 'ged_create_documents') == '1' || get_array_value($permissions, 'ged_edit_documents') == '1') {
                $submenu['ged_documents'] = array('name' => 'ged_documents', 'url' => 'ged/documents', 'class' => 'file-text');
            }

            if ($login_user->is_admin || get_array_value($permissions, 'ged_manage_document_types') == '1') {
                $submenu['ged_document_types'] = array('name' => 'ged_document_types', 'url' => 'ged/document_types', 'class' => 'tag');
            }

            if ($login_user->is_admin || get_array_value($permissions, 'ged_view_reports') == '1') {
                $submenu['ged_reports'] = array('name' => 'ged_reports', 'url' => 'ged/reports', 'class' => 'bar-chart-2');
            }

            if ($login_user->is_admin || get_array_value($permissions, 'ged_manage_settings') == '1' || get_array_value($permissions, 'ged_manage_notifications') == '1') {
                $submenu['ged_settings'] = array('name' => 'ged_settings', 'url' => 'ged/settings', 'class' => 'settings');
            }

            $sidebar_menu['ged']['submenu'] = $submenu;

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

                $view_path = PLUGINPATH . 'GED/Views/permissions/role_permissions.php';
                if (file_exists($view_path)) {
                    include $view_path;
                    if (!defined('GED_ROLE_PERMISSIONS_RENDERED')) {
                        define('GED_ROLE_PERMISSIONS_RENDERED', true);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[GED] Permissions hook error: ' . $e->getMessage());
            }
        });

        app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
            $request = \Config\Services::request();
            $keys = array(
                'ged_access',
                'ged_view_documents',
                'ged_create_documents',
                'ged_edit_documents',
                'ged_delete_documents',
                'ged_download_documents',
                'ged_manage_document_types',
                'ged_view_reports',
                'ged_manage_settings',
                'ged_manage_notifications',
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
            $has_ged = false;
            foreach ($category_suggestions as $suggestion) {
                if (get_array_value($suggestion, 'id') === 'ged') {
                    $has_ged = true;
                    break;
                }
            }

            if (!$has_ged) {
                $category_suggestions[] = array(
                    'id' => 'ged',
                    'text' => app_lang('ged'),
                );
            }

            return $category_suggestions;
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

            $sort = 1;
            foreach (array_keys(self::getNotificationDefinitions()) as $event) {
                $payload = array(
                    'event' => $event,
                    'category' => 'ged',
                    'enable_email' => 1,
                    'enable_web' => 1,
                    'enable_slack' => 0,
                    'notify_to_team' => '',
                    'notify_to_team_members' => '',
                    'notify_to_terms' => '',
                    'sort' => $sort++,
                    'deleted' => 0,
                );

                $exists = $db->table($table)->where('event', $event)->where('deleted', 0)->get()->getRow();
                if ($exists) {
                    $db->table($table)->where('id', $exists->id)->update($payload);
                    continue;
                }

                $db->table($table)->insert($payload);
            }
        } catch (\Throwable $e) {
            log_message('error', '[GED] Notification settings seed error: ' . $e->getMessage());
        }
    }

    private static function getNotificationDefinitions()
    {
        return array(
            'ged_document_due_30' => array('type' => 'document'),
            'ged_document_due_15' => array('type' => 'document'),
            'ged_document_due_7' => array('type' => 'document'),
            'ged_document_due_today' => array('type' => 'document'),
            'ged_document_overdue' => array('type' => 'document'),
        );
    }
}
