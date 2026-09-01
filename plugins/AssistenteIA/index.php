<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/Helpers/assistente_ia_helper.php';
require_once __DIR__ . '/Plugin.php';

AssistenteIA\Plugin::register();

$assistente_ia_language = get_setting('language') ?: 'english';
$assistente_ia_language = str_replace('_br', '', strtolower($assistente_ia_language));
$assistente_ia_language_file = __DIR__ . '/Language/' . $assistente_ia_language . '/default_lang.php';
if (file_exists($assistente_ia_language_file)) {
    require_once $assistente_ia_language_file;
} elseif (file_exists(__DIR__ . '/Language/english/default_lang.php')) {
    require_once __DIR__ . '/Language/english/default_lang.php';
}

if (file_exists(__DIR__ . '/Config/Routes.php')) {
    require_once __DIR__ . '/Config/Routes.php';
}

register_deactivation_hook('AssistenteIA', static function () { return true; });
register_activation_hook('AssistenteIA', static function () { \AssistenteIA\Plugin::install(); });
register_uninstallation_hook('AssistenteIA', static function () { return true; });
