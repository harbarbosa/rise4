<?php

namespace LaudosTecnicos;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudo_status_model;

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

            $permissions = $ci->login_user->permissions ?? array();
            
            $submenu = array(
                "laudos_dashboard" => array("name" => "laudos_dashboard", "url" => "laudos_tecnicos", "class" => "home"),
                "laudos_list" => array("name" => "laudos_list", "url" => "laudos_tecnicos/laudos", "class" => "file-text"),
            );
            
            // Adicionar categorias se tiver permissão
            if ($ci->login_user->is_admin || get_array_value($permissions, 'laudos_manage_categories') == '1') {
                $submenu["laudos_categories"] = array("name" => "laudos_categories_title", "url" => "laudos_tecnicos/categorias", "class" => "tag");
            }
            
            // Adicionar tipos
            if ($ci->login_user->is_admin || get_array_value($permissions, 'laudos_manage_types') == '1') {
                $submenu["laudos_types"] = array("name" => "laudos_types", "url" => "laudos_tecnicos/tipos", "class" => "list");
            }
            
            // Adicionar status
            if ($ci->login_user->is_admin || get_array_value($permissions, 'laudos_manage_status') == '1') {
                $submenu["laudos_status"] = array("name" => "laudos_status_title", "url" => "laudos_tecnicos/status", "class" => "activity");
            }
            
            // Adicionar transições
            if ($ci->login_user->is_admin || get_array_value($permissions, 'laudos_manage_transitions') == '1') {
                $submenu["laudos_transitions"] = array("name" => "laudos_transitions_title", "url" => "laudos_tecnicos/transicoes", "class" => "git-branch");
            }
            
            $submenu["laudos_templates"] = array("name" => "laudos_templates", "url" => "laudos_templates", "class" => "layout");
            $submenu["laudo_technical"] = array("name" => "laudos_technical", "url" => "laudo_technical", "class" => "check-square");
            $submenu["laudo_inspections"] = array("name" => "laudos_inspections", "url" => "laudo_inspections", "class" => "calendar");
            $submenu["laudo_nonconformities"] = array("name" => "laudos_nonconformities", "url" => "laudo_nonconformities", "class" => "alert-triangle");
            $submenu["laudos_inspections"] = array("name" => "laudos_inspections", "url" => "laudos_tecnicos/inspecoes", "class" => "clipboard");
            $submenu["laudos_settings"] = array("name" => "laudos_settings", "url" => "laudos_tecnicos/configuracoes", "class" => "settings");

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
            
            // Novas permissões
            $permissions['laudos_manage_categories'] = $request->getPost('laudos_manage_categories') ? '1' : '';
            $permissions['laudos_manage_status'] = $request->getPost('laudos_manage_status') ? '1' : '';
            $permissions['laudos_manage_transitions'] = $request->getPost('laudos_manage_transitions') ? '1' : '';
            $permissions['laudos_change_status'] = $request->getPost('laudos_change_status') ? '1' : '';
            
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
            || get_array_value($permissions, 'laudos_settings') == '1'
            || get_array_value($permissions, 'laudos_manage_categories') == '1'
            || get_array_value($permissions, 'laudos_manage_status') == '1'
            || get_array_value($permissions, 'laudos_manage_transitions') == '1';
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
     * Status dos laudos - do banco de dados
     */
    public static function statusList()
    {
        try {
            $model = model(Laudo_status_model::class);
            $status = $model->get_dropdown();
            return $status;
        } catch (\Throwable $e) {
            // Fallback se tabela não existir
            return array(
                'draft' => app_lang('laudos_status_draft'),
                'requested' => app_lang('laudos_status_requested'),
                'scheduled' => app_lang('laudos_status_scheduled'),
                'inspecting' => app_lang('laudos_status_inspecting'),
                'pending_review' => app_lang('laudos_status_pending_review'),
                'approved' => app_lang('laudos_status_approved'),
                'issued' => app_lang('laudos_status_issued'),
                'expired' => app_lang('laudos_status_expired'),
                'canceled' => app_lang('laudos_status_canceled'),
            );
        }
    }

    /**
     * Verificar se transição é válida
     */
    public static function canTransition($from_status_code, $to_status_code)
    {
        try {
            $model = model(Laudo_status_transitions_model::class);
            return $model->can_transition($from_status_code, $to_status_code);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Obter transições disponíveis para um status
     */
    public static function getTransitions($from_status_code)
    {
        try {
            $model = model(Laudo_status_transitions_model::class);
            return $model->get_transitions_from($from_status_code);
        } catch (\Throwable $e) {
            return array();
        }
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