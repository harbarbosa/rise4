<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Gestão de Frota
  Description: Controle de veículos, abastecimentos, manutenções e ocorrências da frota.
  Version: 1.0.1
  Requires at least: 3.9.0
  Author: Alfa HP
*/

require_once __DIR__ . '/Helpers/frota_helper.php';
require_once __DIR__ . '/install.php';

$frota_language = get_setting('language') ?: 'english';
$frota_language_file = __DIR__ . '/Language/' . $frota_language . '/default_lang.php';
if (file_exists($frota_language_file)) {
    require_once $frota_language_file;
} elseif (file_exists(__DIR__ . '/Language/portuguese/default_lang.php')) {
    require_once __DIR__ . '/Language/portuguese/default_lang.php';
}

$frota_t = function ($key, $fallback) use (&$lang) {
    return isset($lang[$key]) && $lang[$key] !== '' ? $lang[$key] : $fallback;
};

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) use ($frota_t) {
    $ci = new \App\Controllers\Security_Controller(false);
    $user = $ci->login_user ?? null;
    if (!frota_can_access($user)) {
        return $sidebar_menu;
    }

    $sidebar_menu['frota'] = [
        'name' => $frota_t('frota', 'Gestão de Frota'),
        'url' => get_uri('frota'),
        'class' => 'truck',
        'position' => 9,
        'is_custom_menu_item' => true,
        'submenu' => [
            'frota_dashboard' => ['name' => $frota_t('frota_dashboard', 'Visão geral'), 'url' => get_uri('frota'), 'class' => 'bar-chart-2'],
            'frota_veiculos' => ['name' => $frota_t('frota_vehicles', 'Veículos'), 'url' => get_uri('frota/veiculos'), 'class' => 'truck'],
            'frota_abastecimentos' => ['name' => $frota_t('frota_fuelings', 'Abastecimentos'), 'url' => get_uri('frota/abastecimentos'), 'class' => 'droplet'],
            'frota_manutencoes' => ['name' => $frota_t('frota_maintenances', 'Manutenções'), 'url' => get_uri('frota/manutencoes'), 'class' => 'tool'],
            'frota_ocorrencias' => ['name' => $frota_t('frota_issues', 'Ocorrências'), 'url' => get_uri('frota/ocorrencias'), 'class' => 'alert-triangle'],
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
