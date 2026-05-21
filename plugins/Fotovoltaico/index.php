<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Fotovoltaico
  Description: Modulo fotovoltaico com dashboard, produtos, kits, propostas, tarifas e integracoes.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

require_once __DIR__ . '/Plugin.php';
require_once __DIR__ . '/Helpers/fotovoltaico_helper.php';

\Fotovoltaico\Plugin::register();

register_installation_hook('Fotovoltaico', function () {
    \Fotovoltaico\Plugin::runMigrations();
});

register_update_hook('Fotovoltaico', function () {
    \Fotovoltaico\Plugin::runMigrations();
});
