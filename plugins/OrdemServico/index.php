<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: OrdemServico
  Description: GestÃ£o de Ordens de ServiÃ§o (cadastro, execuÃ§Ã£o e encerramento) integrada a clientes, projetos, tarefas, contratos e equipe tÃ©cnica.
  Version: 0.2.0
  Requires at least: 3.9.0
  Author: Codex
 */

use App\Controllers\Security_Controller;

// Menu lateral
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $ci = new Security_Controller(false);
    if (!isset($ci->login_user) || $ci->login_user->user_type !== "staff") {
        return $sidebar_menu;
    }

    $permissions = $ci->login_user->permissions ?? array();
    $has_access = $ci->login_user->is_admin || get_array_value($permissions, 'ordemservico_manage') == '1';
    if (!$has_access) {
        return $sidebar_menu;
    }

    if (!is_array($sidebar_menu)) { $sidebar_menu = array(); }
    $sidebar_menu['ordemservico'] = array(
        'name' => 'os_menu_title',
        'url'  => 'ordemservico',
        'class'=> 'tool',
        'position' => 6,
    );
    if (!isset($sidebar_menu["cadastro"])) {
        $sidebar_menu["cadastro"] = array(
            "name" => "Cadastro",
            "url" => "#",
            "class" => "book",
            "position" => 7,
            "submenu" => array()
        );
    }
    if (!isset($sidebar_menu["cadastro"]["submenu"]) || !is_array($sidebar_menu["cadastro"]["submenu"])) {
        $sidebar_menu["cadastro"]["submenu"] = array();
    }
    if (!isset($sidebar_menu["cadastro"]["submenu"]["cadastro_os_services"])) {
        $sidebar_menu["cadastro"]["submenu"]["cadastro_os_services"] = array(
            "name" => "Serviços",
            "url" => "ordemservico/services",
            "class" => "tool"
        );
    }
    return $sidebar_menu;
});

// Carrega JS do plugin nas pÃ¡ginas do mÃ³dulo
app_hooks()->add_action('app_hook_head_extension', function (){
    // Avoid injecting assets on AJAX requests to keep JSON responses clean
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if ($is_ajax) { return; }
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/ordemservico') !== false) {
        echo '<script src="' . base_url('plugins/OrdemServico/assets/js/ordemservico.js') . '"></script>';
    }
});

app_hooks()->add_action('app_hook_role_permissions_extension', function () {
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

        $view_path = PLUGINPATH . 'OrdemServico/Views/permissions/role_permissions.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            log_message('error', '[OrdemServico] role_permissions view not found at ' . $view_path);
        }
    } catch (\Throwable $e) {
        log_message('error', '[OrdemServico] Permissions hook error: ' . $e->getMessage());
    }
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = \Config\Services::request();
    $permissions['ordemservico_manage'] = $request->getPost('ordemservico_manage') ? '1' : '';
    return $permissions;
});

// InstalaÃ§Ã£o (executa Migrations/install.sql)
register_installation_hook('OrdemServico', function ($item_purchase_code) {
    $db = db_connect('default');
    $dbprefix = get_db_prefix();
    $sql_file = PLUGINPATH . 'OrdemServico/Migrations/install.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        $sql = str_replace('{{DB_PREFIX}}', $dbprefix, $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement) { $db->query($statement); }
        }
    }
});

// Adiciona entrada no menu de Configurações (sidebar padrão do RISE)
app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu["plugins"][] = array("name" => "ordemservico_settings", "url" => "ordemservico/settings");
    return $settings_menu;
});

// Acrescenta submenu de ConfiguraÃ§Ãµes (Tipos e Motivos)
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    if (!is_array($sidebar_menu)) { $sidebar_menu = array(); }
    if (isset($sidebar_menu['ordemservico']) && is_array($sidebar_menu['ordemservico'])) {
        $sidebar_menu['ordemservico']['submenu'] = array(
            'os_list'     => array('name' => 'os_menu_list',     'url' => 'ordemservico',          'class' => 'list'),
            'os_services' => array('name' => 'Serviços',          'url' => 'ordemservico/services', 'class' => 'tool'),
            'os_settings' => array('name' => 'os_menu_settings', 'url' => 'ordemservico/settings', 'class' => 'settings')
        );
    }
    return $sidebar_menu;
});

// Ajuste final do menu Cadastro
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    if (!is_array($sidebar_menu)) { return $sidebar_menu; }
    if (isset($sidebar_menu['ordemservico']['submenu']['os_services'])) {
        unset($sidebar_menu['ordemservico']['submenu']['os_services']);
    }
    if (isset($sidebar_menu["cadastro"])) {
        $sidebar_menu["cadastro"]["name"] = "cadastro";
    }
    return $sidebar_menu;
});
