<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Fotovoltaico
  Description: Gerador de propostas para sistemas fotovoltaicos.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

use App\Controllers\Security_Controller;

/**
 * Hooks do plugin Fotovoltaico.
 */
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $ci = new Security_Controller(false);
    if (!isset($ci->login_user) || $ci->login_user->user_type !== 'staff') {
        return $sidebar_menu;
    }

    if (!isset($sidebar_menu['fotovoltaico'])) {
        $sidebar_menu['fotovoltaico'] = array(
            'name' => 'fotovoltaico_menu',
            'url' => 'fotovoltaico/projects',
            'class' => 'sun',
            'position' => 8,
            'submenu' => array()
        );
    }

    $sidebar_menu['fotovoltaico']['submenu'] = array(
        'fotovoltaico_projects' => array('name' => 'fv_projects', 'url' => 'fotovoltaico/projects', 'class' => 'folder'),
        'fotovoltaico_products' => array('name' => 'fv_products', 'url' => 'fotovoltaico/products', 'class' => 'package'),
        'fotovoltaico_kits' => array('name' => 'fv_kits', 'url' => 'fotovoltaico/kits', 'class' => 'grid'),
        'fotovoltaico_utilities' => array('name' => 'fv_utilities_tariffs', 'url' => 'fotovoltaico/utilities', 'class' => 'zap'),
        'fotovoltaico_settings' => array('name' => 'fv_settings', 'url' => 'fotovoltaico/settings', 'class' => 'settings'),
        'fotovoltaico_integrations' => array('name' => 'fv_integrations_cec', 'url' => 'fotovoltaico/integrations/cec', 'class' => 'link'),
        'fotovoltaico_regulatory' => array('name' => 'fv_regulatory_profiles', 'url' => 'fotovoltaico/regulatory', 'class' => 'shield')
    );

    return $sidebar_menu;
});

/**
 * Adiciona aba de propostas FV dentro do cliente.
 */
app_hooks()->add_filter('app_filter_client_details_ajax_tab', function ($hook_tabs, $client_id = 0) {
    $hook_tabs[] = array(
        'title' => app_lang('fv_client_tab'),
        'url' => get_uri('fotovoltaico/client_projects/' . $client_id),
        'target' => 'client-fv-propostas'
    );

    return $hook_tabs;
});

/**
 * Permissões do plugin Fotovoltaico.
 */
app_hooks()->add_action('app_hook_role_permissions_extension', function ($hook_data = null) {
    try {
        $request = \Config\Services::request();
        $role_id = (int)$request->getUri()->getSegment(3);
        $permissions = array();
        if ($role_id) {
            $Roles_model = model('App\\Models\\Roles_model');
            $role = $Roles_model->get_one($role_id);
            $permissions = $role && $role->permissions ? unserialize($role->permissions) : array();
        }
        if (!is_array($permissions)) {
            $permissions = array();
        }

        $view_path = PLUGINPATH . 'Fotovoltaico/Views/permissions/role_permissions.php';
        if (file_exists($view_path)) {
            include $view_path;
        }
    } catch (\Throwable $e) {
        log_message('error', '[Fotovoltaico] Permissions hook error: ' . $e->getMessage());
    }
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = \Config\Services::request();
    $permissions['fv_products_manage'] = $request->getPost('fv_products_manage') ? '1' : '';
    return $permissions;
});

/**
 * Instalação e atualização do plugin.
 */
register_installation_hook('Fotovoltaico', function () {
    return require __DIR__ . '/install.php';
});

register_update_hook('Fotovoltaico', function () {
    $result = require __DIR__ . '/install.php';
    $template = new \App\Libraries\Template(false);
    echo $template->view('Fotovoltaico\\Views\\update_result', array('result' => $result));
});
