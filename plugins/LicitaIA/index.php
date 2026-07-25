<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: LicitaIA
  Description: Gestao de oportunidades de licitacoes publicas com analise por IA.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

require_once __DIR__ . '/Plugin.php';

$licitaia_language = get_setting('language') ?: 'english';
$licitaia_language_file = __DIR__ . '/Language/' . $licitaia_language . '/default_lang.php';
if (file_exists($licitaia_language_file)) {
    require_once $licitaia_language_file;
} elseif (file_exists(__DIR__ . '/Language/english/default_lang.php')) {
    require_once __DIR__ . '/Language/english/default_lang.php';
}

\LicitaIA\Plugin::register();

if (file_exists(__DIR__ . '/Config/Routes.php')) {
    require_once __DIR__ . '/Config/Routes.php';
}

register_installation_hook('LicitaIA', function () {
    require_once __DIR__ . '/install.php';
    \LicitaIA\install\licitaia_install();
});

register_activation_hook('LicitaIA', function () {
    \LicitaIA\Plugin::runMigrations();
});

register_update_hook('LicitaIA', function () {
    require_once __DIR__ . '/install.php';
    \LicitaIA\install\licitaia_install();
    \LicitaIA\Plugin::runMigrations();
});

register_deactivation_hook('LicitaIA', function () {
    return true;
});
