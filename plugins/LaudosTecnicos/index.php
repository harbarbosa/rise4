<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Laudos Técnicos
  Description: Gestão completa de laudos técnicos
  Version: 1.0.0
*/

// Carregar helpers
helper(['url', 'text', 'function']);

// Verificar se o plugin está ativado no autoload
$activated_plugins = @json_decode(file_get_contents(APPPATH . "Config/activated_plugins.json"), true);
if (!$activated_plugins || !in_array('LaudosTecnicos', $activated_plugins)) {
    // Adicionar ao arquivo de plugins ativados se não existir
    if (!$activated_plugins) $activated_plugins = [];
    if (!in_array('LaudosTecnicos', $activated_plugins)) {
        $activated_plugins[] = 'LaudosTecnicos';
        @file_put_contents(APPPATH . "Config/activated_plugins.json", json_encode($activated_plugins));
    }
}

// Registrar menu
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $sidebar_menu["laudos_tecnicos"] = array(
        "name" => "Laudos Técnicos",
        "url" => "laudos_tecnicos",
        "class" => "file-text"
    );
    return $sidebar_menu;
});