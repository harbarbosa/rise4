<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Engenharia
  Description: Estrutura inicial para gestao de laudos de engenharia.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

require_once __DIR__ . '/Helpers/engenharia_helper.php';
require_once __DIR__ . '/Plugin.php';

$engenharia_language = get_setting('language') ?: 'portuguese';
$engenharia_language = str_replace('_br', '', strtolower($engenharia_language));
$engenharia_language_file = __DIR__ . '/Language/' . $engenharia_language . '/default_lang.php';
if (file_exists($engenharia_language_file)) {
    require_once $engenharia_language_file;
} else {
    require_once __DIR__ . '/Language/portuguese/default_lang.php';
}

\Engenharia\Plugin::register();

if (file_exists(__DIR__ . '/Config/Routes.php')) {
    require_once __DIR__ . '/Config/Routes.php';
}

register_installation_hook('Engenharia', function () {
    require_once __DIR__ . '/install.php';
    \Engenharia\install\engenharia_install();
});

register_activation_hook('Engenharia', function () {
    \Engenharia\Plugin::runMigrations();
});

register_update_hook('Engenharia', function () {
    require_once __DIR__ . '/install.php';
    \Engenharia\install\engenharia_install();
    \Engenharia\Plugin::runMigrations();
});

register_deactivation_hook('Engenharia', function () {
    // Desativar nao remove dados nem executa rollback.
    return true;
});

register_uninstallation_hook('Engenharia', function () {
    require_once __DIR__ . '/uninstall.php';
    return \Engenharia\install\engenharia_uninstall();
});
