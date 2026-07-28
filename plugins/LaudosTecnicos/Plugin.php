<?php

namespace LaudosTecnicos;

use App\Controllers\Security_Controller;

class Plugin
{
    const VERSION = '1.0.0';
    
    public static function register()
    {
        self::registerMenus();
        self::registerPermissions();
        self::registerSettingsMenu();
    }

    /**
     * Registrar menus no sidebar
     */
    private static function registerMenus()
    {
        app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
            helper(['url', 'text', 'function']);
            
            $ci = new Security_Controller(false);
            if (!isset($ci->login_user) || $ci->login_user->user_type !== 'staff') {
                return $sidebar_menu;
            }

            if (!self::canAccessModule($ci->login_user)) {
                return $sidebar_menu;
            }

            if (!isset($sidebar_menu["laudos_tecnicos"])) {
                $sidebar_menu["laudos_tecnicos"] = array(
                    "name" => "laudos_tecnicos_menu",
                    "url" => "laudos_tecnicos",
                    "class" => "file-text",
                    "position" => 15,
                );
            }

            $submenu = array(
                "laudos_dashboard" => array("name" => "laudos_dashboard", "url" => "laudos_tecnicos", "class" => "home"),
                "laudos_list" => array("name" => "laudos_list", "url" => "laudos_tecnicos/laudos", "class" => "file-text"),
                "laudos_types" => array("name" => "laudos_types", "url" => "laudos_tecnicos/tipos", "class" => "list"),
                "laudos_templates" => array("name" => "laudos_templates", "url" => "laudos_tecnicos/templates", "class" => "layout"),
                "laudos_inspections" => array("name" => "laudos_inspections", "url" => "laudos_tecnicos/inspecoes", "class" => "clipboard"),
                "laudos_settings" => array("name" => "laudos_settings", "url" => "laudos_tecnicos/configuracoes", "class" => "settings"),
            );

            $sidebar_menu["laudos_tecnicos"] = array_merge($sidebar_menu["laudos_tecnicos"], $submenu);

            return $sidebar_menu;
        });
    }

    /**
     * Registrar permissões do módulo
     */
    private static function registerPermissions()
    {
        app_hooks()->add_action('app_hook_role_permissions_extension', function () {
            try {
                helper(['url', 'text', 'function']);
                
                $request = \Config\Services::request();
                $role_id = (int) $request->getUri()->getSegment(3);
                $permissions = array();
                if ($role_id) {
                    $roles_model = model('App\Models\Roles_model');
                    $role = $roles_model->get_one($role_id);
                    $permissions = $role && $role->permissions ? unserialize($role->permissions) : array();
                }
                if (!is_array($permissions)) {
                    $permissions = array();
                }

                $view_path = PLUGINPATH . 'LaudosTecnicos/Views/permissions/role_permissions.php';
                if (file_exists($view_path)) {
                    include $view_path;
                    if (!defined('LAUDOS_TECNICOS_ROLE_PERMISSIONS_RENDERED')) {
                        define('LAUDOS_TECNICOS_ROLE_PERMISSIONS_RENDERED', true);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[LaudosTecnicos] Permissions hook error: ' . $e->getMessage());
            }
        });

        app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
            $request = \Config\Services::request();
            $permissions['laudos_view'] = $request->getPost('laudos_view') ? '1' : '';
            $permissions['laudos_create'] = $request->getPost('laudos_create') ? '1' : '';
            $permissions['laudos_edit'] = $request->getPost('laudos_edit') ? '1' : '';
            $permissions['laudos_delete_draft'] = $request->getPost('laudos_delete_draft') ? '1' : '';
            $permissions['laudos_manage_types'] = $request->getPost('laudos_manage_types') ? '1' : '';
            $permissions['laudos_manage_templates'] = $request->getPost('laudos_manage_templates') ? '1' : '';
            $permissions['laudos_settings'] = $request->getPost('laudos_settings') ? '1' : '';
            return $permissions;
        });
    }

    /**
     * Registrar menu de configurações
     */
    private static function registerSettingsMenu()
    {
        app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
            helper(['url', 'text', 'function']);
            
            $ci = new Security_Controller(false);
            $login_user = $ci->login_user ?? null;
            if (!$login_user) {
                return $settings_menu;
            }

            $can_manage_settings = $login_user->is_admin 
                || get_array_value($login_user->permissions ?? array(), 'laudos_settings') == '1';
            if (!$can_manage_settings) {
                return $settings_menu;
            }

            $settings_menu["plugins"][] = array(
                "name" => "laudos_tecnicos_settings",
                "url" => "laudos_tecnicos/configuracoes"
            );

            return $settings_menu;
        });
    }

    /**
     * Verificar se o usuário pode acessar o módulo
     */
    public static function canAccessModule($login_user)
    {
        if ($login_user->is_admin) {
            return true;
        }

        $permissions = $login_user->permissions ?? array();
        return get_array_value($permissions, 'laudos_view') == '1'
            || get_array_value($permissions, 'laudos_create') == '1'
            || get_array_value($permissions, 'laudos_edit') == '1'
            || get_array_value($permissions, 'laudos_manage_types') == '1'
            || get_array_value($permissions, 'laudos_manage_templates') == '1'
            || get_array_value($permissions, 'laudos_settings') == '1';
    }

    /**
     * Verificar se o usuário pode gerenciar configurações
     */
    public static function canManageSettings($login_user)
    {
        if ($login_user->is_admin) {
            return true;
        }
        return get_array_value($login_user->permissions ?? array(), 'laudos_settings') == '1';
    }

    /**
     * Verificar se o usuário pode criar laudos
     */
    public static function canCreate($login_user)
    {
        if ($login_user->is_admin) {
            return true;
        }
        return get_array_value($login_user->permissions ?? array(), 'laudos_create') == '1';
    }

    /**
     * Verificar se o usuário pode editar laudos
     */
    public static function canEdit($login_user)
    {
        if ($login_user->is_admin) {
            return true;
        }
        return get_array_value($login_user->permissions ?? array(), 'laudos_edit') == '1';
    }

    /**
     * Status dos laudos
     */
    public static function statusList()
    {
        return array(
            'draft' => app_lang('laudos_status_draft'),
            'in_progress' => app_lang('laudos_status_in_progress'),
            'pending_review' => app_lang('laudos_status_pending_review'),
            'approved' => app_lang('laudos_status_approved'),
            'issued' => app_lang('laudos_status_issued'),
            'expired' => app_lang('laudos_status_expired'),
            'canceled' => app_lang('laudos_status_canceled'),
        );
    }

    /**
     * Obter configurações do módulo
     */
    public static function getSettings()
    {
        $model = model('LaudosTecnicos\Models\Laudos_settings_model');
        return $model->get_settings();
    }
}