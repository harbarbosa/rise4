<?php

namespace AssistenteIA;

use App\Controllers\Security_Controller;

class Plugin
{
    public static function register(): void
    {
        self::install();
        \app_hooks()->add_filter('app_filter_staff_left_menu', [self::class, 'menu']);
        \app_hooks()->add_action('app_hook_role_permissions_extension', [self::class, 'permissions']);
        \app_hooks()->add_filter('app_filter_role_permissions_save_data', [self::class, 'save_permissions']);
    }

    public static function install(): void
    {
        try {
            \service('migrations')->setNamespace('AssistenteIA')->latest();
            $seed_file = PLUGINPATH . 'AssistenteIA/Database/Seeds/AssistenteIASettingsSeeder.php';
            if (is_file($seed_file)) {
                require_once $seed_file;
                $database_config = new \Config\Database();
                $seeder = new \AssistenteIA\Database\Seeds\AssistenteIASettingsSeeder($database_config);
                $seeder->setSilent(true)->run();
            }
        } catch (\Throwable $e) {
            \log_message('error', '[AssistenteIA] Migration error: ' . $e->getMessage());
        }
    }

    public static function uninstall(): void
    {
        // Dados são preservados por segurança. A remoção deve ser manual e explícita.
    }

    public static function menu(array $menu): array
    {
        $ci = new Security_Controller(false);
        if (self::canAccess($ci->login_user ?? null)) {
            $menu['assistente_ia'] = [
                'name' => 'assistente_ia',
                'url' => 'assistente-ia',
                'class' => 'clipboard',
                'position' => 9,
                'sub_pages' => ['assistente-ia', 'assistente-ia/configuracoes'],
            ];
        }
        return $menu;
    }

    public static function canAccess($user): bool
    {
        return self::hasPermission($user, 'assistente_ia_access');
    }

    public static function canManageSettings($user): bool
    {
        return self::hasPermission($user, 'assistente_ia_manage_settings');
    }

    public static function canExecuteActions($user): bool
    {
        return self::hasPermission($user, 'assistente_ia_execute_actions');
    }

    private static function hasPermission($user, string $permission): bool
    {
        if (!$user || !empty($user->is_admin)) return true;
        return \get_array_value($user->permissions ?? [], $permission) == '1';
    }

    public static function permissions(): void
    {
        $request = \Config\Services::request();
        $roleId = (int)$request->getUri()->getSegment(3);
        $permissions = [];
        if ($roleId) {
            $role = \model('App\\Models\\Roles_model')->get_one($roleId);
            $permissions = $role && $role->permissions ? unserialize($role->permissions) : [];
        }
        if (!is_array($permissions)) $permissions = [];
        $viewPath = PLUGINPATH . 'AssistenteIA/Views/permissions/role_permissions.php';
        if (file_exists($viewPath)) include $viewPath;
    }

    public static function save_permissions(array $data): array
    {
        $request = \Config\Services::request();
        foreach (['assistente_ia_access', 'assistente_ia_view_history', 'assistente_ia_manage_settings', 'assistente_ia_execute_actions'] as $key) {
            $data[$key] = $request->getPost($key) ? '1' : '';
        }
        return $data;
    }
}
