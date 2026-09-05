<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Gestão de Frota
  Description: Controle de veículos, abastecimentos, manutenções e ocorrências da frota.
  Version: 1.1.1
  Requires at least: 3.9.0
  Author: Alfa HP
*/

require_once __DIR__ . '/Helpers/frota_helper.php';
require_once __DIR__ . '/install.php';

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $ci = new \App\Controllers\Security_Controller(false);
    $user = $ci->login_user ?? null;
    if (!frota_can_access($user)) {
        return $sidebar_menu;
    }

    $sidebar_menu['frota'] = [
        'name' => 'frota',
        'url' => 'frota',
        'class' => 'truck',
        'position' => 9,
        'submenu' => [
            'frota_dashboard' => ['name' => 'frota_dashboard', 'url' => 'frota', 'class' => 'bar-chart-2'],
            'frota_veiculos' => ['name' => 'frota_vehicles', 'url' => 'frota/veiculos', 'class' => 'truck'],
            'frota_abastecimentos' => ['name' => 'frota_fuelings', 'url' => 'frota/abastecimentos', 'class' => 'droplet'],
            'frota_manutencoes' => ['name' => 'frota_maintenances', 'url' => 'frota/manutencoes', 'class' => 'tool'],
            'frota_ocorrencias' => ['name' => 'frota_issues', 'url' => 'frota/ocorrencias', 'class' => 'alert-triangle'],
        ],
        'sub_pages' => [
            'frota/index',
            'frota/veiculos',
            'frota/abastecimentos',
            'frota/manutencoes',
            'frota/ocorrencias',
        ],
    ];
    return $sidebar_menu;
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = \Config\Services::request();
    foreach (['frota_view','frota_manage','frota_fueling','frota_issue','frota_maintenance'] as $key) {
        $permissions[$key] = $request->getPost($key) ? '1' : '';
    }
    return $permissions;
});

register_installation_hook('Frota', function () { frota_install(); });
register_update_hook('Frota', function () { frota_install(); });

if (file_exists(__DIR__ . '/Config/Routes.php')) {
    require_once __DIR__ . '/Config/Routes.php';
}
