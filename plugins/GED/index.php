<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: GED
  Description: Gestao Eletronica de Documentos para Rise CRM.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

require_once __DIR__ . '/Plugin.php';
require_once __DIR__ . '/Libraries/GedExpirationService.php';
require_once __DIR__ . '/Libraries/GedNotificationService.php';
require_once __DIR__ . '/Helpers/ged_expiration_helper.php';
require_once __DIR__ . '/Helpers/ged_settings_helper.php';

\GED\Plugin::register();

register_installation_hook('GED', function () {
    \GED\Plugin::runInstall();
});

register_update_hook('GED', function () {
    \GED\Plugin::runUpdate();
});
