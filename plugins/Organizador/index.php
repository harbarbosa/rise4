<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Organizador
  Description: Tarefas pessoais, kanban, calendario e notificacoes.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

require_once __DIR__ . '/Plugin.php';

\Organizador\Plugin::register();

register_installation_hook("Organizador", function () {
    require __DIR__ . '/install.php';
});

register_update_hook("Organizador", function () {
    require __DIR__ . '/install.php';
});
