<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Laudos Tecnicos
  Description: Modulo para gestao de laudos tecnicos com base para inspecoes, templates, revisao e emissao.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

require_once __DIR__ . '/Helpers/laudostecnicos_helper.php';
require_once __DIR__ . '/Plugin.php';

$laudostecnicos_language = get_setting('language') ?: 'english';
$laudostecnicos_language = $laudostecnicos_language === 'portuguese_br' ? 'portuguese' : $laudostecnicos_language;
$laudostecnicos_language_file = __DIR__ . '/Language/' . $laudostecnicos_language . '/default_lang.php';
if (file_exists($laudostecnicos_language_file)) {
    require_once $laudostecnicos_language_file;
} elseif (file_exists(__DIR__ . '/Language/english/default_lang.php')) {
    require_once __DIR__ . '/Language/english/default_lang.php';
}

\LaudosTecnicos\Plugin::register();

if (file_exists(__DIR__ . '/Config/Routes.php')) {
    require_once __DIR__ . '/Config/Routes.php';
}

register_installation_hook('LaudosTecnicos', function () {
    require_once __DIR__ . '/install.php';
    \LaudosTecnicos\install\laudostecnicos_install();
});

register_activation_hook('LaudosTecnicos', function () {
    \LaudosTecnicos\Plugin::runMigrations();
});

register_update_hook('LaudosTecnicos', function () {
    require_once __DIR__ . '/install.php';
    \LaudosTecnicos\install\laudostecnicos_install();
    \LaudosTecnicos\Plugin::runMigrations();
});

register_deactivation_hook('LaudosTecnicos', function () {
    return true;
});

register_uninstallation_hook('LaudosTecnicos', function () {
    require_once __DIR__ . '/uninstall.php';
    \LaudosTecnicos\install\laudostecnicos_uninstall();
});
