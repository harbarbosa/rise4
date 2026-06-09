<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Ponto RH
  Description: Modulo de controle de ponto integrado ao RiseCRM.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

require_once __DIR__ . '/Helpers/pontorh_helper.php';
require_once __DIR__ . '/Plugin.php';

$pontorh_language = get_setting('language') ?: 'english';
$pontorh_language_file = __DIR__ . '/Language/' . $pontorh_language . '/default_lang.php';
if (file_exists($pontorh_language_file)) {
    require_once $pontorh_language_file;
} elseif (file_exists(__DIR__ . '/Language/english/default_lang.php')) {
    require_once __DIR__ . '/Language/english/default_lang.php';
}

\PontoRH\Plugin::register();

if (file_exists(__DIR__ . '/Config/Routes.php')) {
    require_once __DIR__ . '/Config/Routes.php';
}

register_installation_hook('PontoRH', function () {
    require_once __DIR__ . '/install.php';
    \PontoRH\install\pontorh_install();
});

register_activation_hook('PontoRH', function () {
    \PontoRH\Plugin::runMigrations();
});

register_update_hook('PontoRH', function () {
    require_once __DIR__ . '/install.php';
    \PontoRH\install\pontorh_install();
});

register_deactivation_hook('PontoRH', function () {
    return true;
});

register_uninstallation_hook('PontoRH', function () {
    require_once __DIR__ . '/uninstall.php';
    \PontoRH\install\pontorh_uninstall();
});
