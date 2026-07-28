<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Laudos Técnicos
  Description: Gestão completa de laudos técnicos
  Version: 1.0.0
  Requires at least: 3.9.0
  Author: RISE CRM
*/

// Carregar helpers necessários
helper(['url', 'text', 'function']);

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    helper(['url', 'text', 'function']);
    
    // Usar app_lang para nome do menu
    $menu_name = app_lang('laudos_tecnicos');
    if (empty($menu_name) || $menu_name === 'laudos_tecnicos') {
        $menu_name = 'Laudos Técnicos';
    }
    
    $laudos_menu = array(
        "name" => $menu_name,
        "url" => "laudos_tecnicos",
        "class" => "file-text"
    );
    
    // Adicionar como primeiro item se não existir
    if (!isset($sidebar_menu["laudos_tecnicos"])) {
        $sidebar_menu = array("laudos_tecnicos" => $laudos_menu) + $sidebar_menu;
    }

    return $sidebar_menu;
});