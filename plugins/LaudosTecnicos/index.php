<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Laudos Técnicos
  Description: Gestão completa de laudos técnicos - solicitação, inspeção, elaboração, revisão, aprovação, emissão e controle de versões.
  Version: 1.0.0
  Requires at least: 3.9.0
  Author: RISE CRM
*/

// Carregar helpers necessários
helper(['url', 'text', 'function']);

use App\Controllers\Security_Controller;

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    // Carregar helpers necessários
    helper(['url', 'text', 'function']);

    $ci = new Security_Controller(false);
    if (!isset($ci->login_user) || $ci->login_user->user_type !== 'staff') {
        return $sidebar_menu;
    }

    $laudos_menu = array(
        "laudos_tecnicos" => array(
            "name" => "laudos_tecnicos",
            "url" => "laudos_tecnicos",
            "class" => "file-text"
        ),
        "laudos_templates" => array(
            "name" => "laudos_templates",
            "url" => "laudos_templates",
            "class" => "layout"
        ),
        "laudo_technical" => array(
            "name" => "laudos_technical",
            "url" => "laudo_technical",
            "class" => "check-square"
        ),
        "laudo_inspections" => array(
            "name" => "laudos_inspections",
            "url" => "laudo_inspections",
            "class" => "calendar"
        ),
        "laudo_nonconformities" => array(
            "name" => "laudos_nonconformities",
            "url" => "laudo_nonconformities",
            "class" => "alert-triangle"
        ),
        "laudo_review" => array(
            "name" => "laudos_review",
            "url" => "laudo_review/professionals",
            "class" => "check-circle"
        ),
        "laudo_documents" => array(
            "name" => "laudos_documents",
            "url" => "laudos_tecnicos",
            "class" => "file-text"
        ),
        "laudo_ai" => array(
            "name" => "laudos_ai",
            "url" => "laudo_ai",
            "class" => "cpu"
        ),
        "laudo_dashboard" => array(
            "name" => "laudos_dashboard",
            "url" => "laudo_dashboard",
            "class" => "grid"
        ),
        "laudo_reports" => array(
            "name" => "laudos_reports",
            "url" => "laudo_reports",
            "class" => "bar-chart-2"
        ),
        "laudo_prompts_lib" => array(
            "name" => "laudos_prompts",
            "url" => "laudo_prompts_lib",
            "class" => "message-square"
        ),
        "laudo_automations" => array(
            "name" => "laudos_automations",
            "url" => "laudo_automations",
            "class" => "clock"
        )
    );

    // Inserir menu
    $position = array_search("projects", array_keys($sidebar_menu));
    if ($position !== false) {
        $sidebar_menu = array_slice($sidebar_menu, 0, $position + 1, true) +
            array("laudos_tecnicos" => $laudos_menu) +
            array_slice($sidebar_menu, $position + 1, null, true);
    } else {
        $sidebar_menu["laudos_tecnicos"] = $laudos_menu;
    }

    return $sidebar_menu;
});

// Registrar permissões
app_hooks()->add_filter('app_filter_permission_fields', function ($fields) {
    $fields[] = array(
        "name" => "laudos_tecnicos",
        "label" => "Laudos Técnicos",
        "sub_permissions" => array(
            array("name" => "laudos_view", "label" => "Visualizar"),
            array("name" => "laudos_edit", "label" => "Editar"),
            array("name" => "laudos_delete", "label" => "Excluir"),
            array("name" => "laudos_approve", "label" => "Aprovar")
        )
    );
    return $fields;
});

// Configurações do plugin
app_hooks()->add_filter('laudos_settings', function ($settings) {
    $settings = array(
        array("name" => "laudos_enable_notifications", "label" => "Ativar notificações", "type" => "checkbox", "default" => "1"),
        array("name" => "laudos_default_validity_months", "label" => "Validade padrão (meses)", "type" => "number", "default" => "12"),
        array("name" => "laudos_require_approval", "label" => "Exigir aprovação", "type" => "checkbox", "default" => "1"),
        array("name" => "laudos_auto_create_nc", "label" => "Criar NC automaticamente", "type" => "checkbox", "default" => "1")
    );
    return $settings;
});