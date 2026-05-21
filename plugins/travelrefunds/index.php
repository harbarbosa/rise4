<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: TravelRefunds
  Description: Gestao de viagens de funcionarios e reembolsos de despesas para servicos externos.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Codex
*/

require_once __DIR__ . '/Helpers/travelrefunds_helper.php';
require_once __DIR__ . '/install.php';

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $ci = new \App\Controllers\Security_Controller(false);
    $login_user = $ci->login_user ?? null;

    if (!travelrefunds_can_access_module($login_user)) {
        return $sidebar_menu;
    }

    if (!isset($sidebar_menu['travelrefunds'])) {
        $sidebar_menu['travelrefunds'] = array(
            'name' => 'Viagens e Reembolsos',
            'url' => get_uri('travelrefunds'),
            'class' => 'map',
            'position' => 8,
            'is_custom_menu_item' => true,
            'submenu' => array(),
        );
    }

    $permissions = $login_user->permissions ?? array();
    $submenu = array();

    if ($login_user->is_admin || get_array_value($permissions, 'travelrefunds_view') == '1' || get_array_value($permissions, 'travelrefunds_create') == '1') {
        $submenu['travelrefunds_trips'] = array(
            'name' => 'Minhas Viagens',
            'url' => get_uri('travelrefunds/trips'),
            'class' => 'map-pin',
            'is_custom_menu_item' => true,
        );
    }

    if ($login_user->is_admin || get_array_value($permissions, 'travelrefunds_view') == '1' || get_array_value($permissions, 'travelrefunds_create') == '1') {
        $submenu['travelrefunds_reimbursements'] = array(
            'name' => 'Solicitacoes de Reembolso',
            'url' => get_uri('travelrefunds/reimbursements'),
            'class' => 'file-text',
            'is_custom_menu_item' => true,
        );
    }

    if ($login_user->is_admin || get_array_value($permissions, 'travelrefunds_approve') == '1') {
        $submenu['travelrefunds_approvals'] = array(
            'name' => 'Aprovacoes',
            'url' => get_uri('travelrefunds/approvals'),
            'class' => 'check-circle',
            'is_custom_menu_item' => true,
        );
    }

    if ($login_user->is_admin || get_array_value($permissions, 'travelrefunds_manage_settings') == '1') {
        $submenu['travelrefunds_categories'] = array(
            'name' => 'Categorias de Despesas',
            'url' => get_uri('travelrefunds/categories'),
            'class' => 'layers',
            'is_custom_menu_item' => true,
        );
        $submenu['travelrefunds_settings'] = array(
            'name' => 'Configuracoes',
            'url' => get_uri('travelrefunds/settings'),
            'class' => 'settings',
            'is_custom_menu_item' => true,
        );
    }

    $sidebar_menu['travelrefunds']['submenu'] = $submenu;

    return $sidebar_menu;
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = \Config\Services::request();
    $keys = array(
        'travelrefunds_view',
        'travelrefunds_create',
        'travelrefunds_edit',
        'travelrefunds_delete',
        'travelrefunds_approve',
        'travelrefunds_manage_settings',
    );

    foreach ($keys as $key) {
        $permissions[$key] = $request->getPost($key) ? '1' : '';
    }

    return $permissions;
});

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

        $view_path = PLUGINPATH . 'travelrefunds/Views/permissions/role_permissions.php';
        if (file_exists($view_path)) {
            include $view_path;
            if (!defined('TRAVELREFUNDS_ROLE_PERMISSIONS_RENDERED')) {
                define('TRAVELREFUNDS_ROLE_PERMISSIONS_RENDERED', true);
            }
        }
    } catch (\Throwable $e) {
        log_message('error', '[TravelRefunds] Permissions hook error: ' . $e->getMessage());
    }
});

register_installation_hook('travelrefunds', function () {
    require_once __DIR__ . '/install.php';
    travelrefunds_install();
});

register_update_hook('travelrefunds', function () {
    require_once __DIR__ . '/install.php';
    travelrefunds_install();
});

register_uninstallation_hook('travelrefunds', function () {
    require_once __DIR__ . '/uninstall.php';
    travelrefunds_uninstall();
});
