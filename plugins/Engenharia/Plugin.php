<?php

namespace Engenharia;

use App\Controllers\Security_Controller;

class Plugin
{
    private static bool $registered = false;
    private static bool $schema_checked = false;

    public static function register()
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        self::runMigrations();
        self::registerMenus();
        self::registerPermissions();
    }

    public static function runMigrations()
    {
        if (self::$schema_checked) {
            return;
        }

        self::$schema_checked = true;

        try {
            $migrations = service('migrations');
            $migrations->setNamespace('Engenharia');
            $migrations->latest();
        } catch (\Throwable $e) {
            log_message('error', '[Engenharia] Migration hook error: ' . $e->getMessage());
        }
    }

    public static function hasPermission($login_user, string $permission): bool
    {
        if (!$login_user) {
            return false;
        }

        if (!empty($login_user->is_admin)) {
            return true;
        }

        return get_array_value($login_user->permissions ?? array(), $permission) === '1';
    }

    public static function canAccessModule($login_user): bool
    {
        foreach (engenharia_permission_keys() as $permission) {
            if (self::hasPermission($login_user, $permission)) {
                return true;
            }
        }

        return false;
    }

    private static function registerMenus()
    {
        app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
            $login_user = (new Security_Controller(false))->login_user ?? null;

            if (!$login_user || $login_user->user_type !== 'staff' || !self::canAccessModule($login_user)) {
                return $sidebar_menu;
            }

            $submenu = array();

            if (self::hasPermission($login_user, 'engenharia_access')) {
                $submenu['engenharia_dashboard'] = array('name' => 'engenharia_dashboard', 'url' => 'engenharia', 'class' => 'home');
            }

            if (self::hasAny($login_user, array('engenharia_view_laudos', 'engenharia_create_laudos', 'engenharia_edit_laudos', 'engenharia_inspect_laudos', 'engenharia_review_laudos', 'engenharia_finalize_laudos'))) {
                $submenu['engenharia_laudos'] = array('name' => 'engenharia_laudos', 'url' => 'engenharia/laudos', 'class' => 'file-text');
            }

            if (self::hasPermission($login_user, 'engenharia_manage_checklists')) {
                $submenu['engenharia_checklists'] = array('name' => 'engenharia_checklists', 'url' => 'engenharia/checklists', 'class' => 'check-square');
            }

            if (self::hasPermission($login_user, 'engenharia_manage_settings')) {
                $submenu['engenharia_settings'] = array('name' => 'engenharia_settings', 'url' => 'engenharia/configuracoes', 'class' => 'settings');
            }

            if (!$submenu) {
                return $sidebar_menu;
            }

            $sidebar_menu['engenharia'] = array(
                'name' => 'engenharia_menu',
                'url' => 'engenharia',
                'class' => 'tool',
                'position' => 9,
                'submenu' => $submenu,
                'sub_pages' => array(
                    'engenharia',
                    'engenharia/index',
                    'engenharia/laudos',
                    'engenharia/laudos/list_data',
                    'engenharia/laudos/modal_form',
                    'engenharia/laudos/view',
                    'engenharia/checklists',
                    'engenharia/configuracoes',
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

                $view_path = PLUGINPATH . 'Engenharia/Views/permissions/role_permissions.php';
                if (file_exists($view_path)) {
                    include $view_path;
                }
            } catch (\Throwable $e) {
                log_message('error', '[Engenharia] Permissions hook error: ' . $e->getMessage());
            }
        });

        app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
            $request = \Config\Services::request();
            foreach (engenharia_permission_keys() as $key) {
                $permissions[$key] = $request->getPost($key) ? '1' : '';
            }

            return $permissions;
        });
    }

    private static function hasAny($login_user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::hasPermission($login_user, $permission)) {
                return true;
            }
        }

        return false;
    }
}
