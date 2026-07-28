<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

// Rota principal
$routes->get('laudos_tecnicos', 'Laudos_tecnicos::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudos_tecnicos/index', 'Laudos_tecnicos::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudos_tecnicos/list_data', 'Laudos_tecnicos::list_data', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudos_tecnicos/form', 'Laudos_tecnicos::form', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudos_tecnicos/form/(:num)', 'Laudos_tecnicos::form/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudos_tecnicos/save', 'Laudos_tecnicos::save', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudos_tecnicos/delete', 'Laudos_tecnicos::delete', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudos_tecnicos/view/(:num)', 'Laudos_tecnicos::view/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Tipos de Laudo
$routes->get('laudos_templates', 'Laudos_templates::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudos_templates/list_data', 'Laudos_templates::list_data', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudos_templates/form', 'Laudos_templates::form', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudos_templates/form/(:num)', 'Laudos_templates::form/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudos_templates/save', 'Laudos_templates::save', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudos_templates/delete', 'Laudos_templates::delete', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Checklists técnicos
$routes->get('laudo_technical', 'Laudo_technical::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_technical/list_data', 'Laudo_technical::list_data', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_technical/form', 'Laudo_technical::form', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_technical/form/(:num)', 'Laudo_technical::form/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_technical/save', 'Laudo_technical::save', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_technical/delete', 'Laudo_technical::delete', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Inspeções
$routes->get('laudo_inspections', 'Laudo_inspections::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_inspections/list_data', 'Laudo_inspections::list_data', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_inspections/form', 'Laudo_inspections::form', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_inspections/form/(:num)', 'Laudo_inspections::form/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_inspections/save', 'Laudo_inspections::save', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_inspections/delete', 'Laudo_inspections::delete', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_inspections/checkin', 'Laudo_inspections::checkin', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_inspections/checkout', 'Laudo_inspections::checkout', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_inspections/upload_photo', 'Laudo_inspections::upload_photo', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Não Conformidades
$routes->get('laudo_nonconformities', 'Laudo_nonconformities::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_nonconformities/list_data', 'Laudo_nonconformities::list_data', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_nonconformities/form', 'Laudo_nonconformities::form', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_nonconformities/form/(:num)', 'Laudo_nonconformities::form/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_nonconformities/save', 'Laudo_nonconformities::save', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_nonconformities/delete', 'Laudo_nonconformities::delete', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_nonconformities/close/(:num)', 'Laudo_nonconformities::close/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Revisão e Aprovação
$routes->get('laudo_review', 'Laudo_review::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_review/review/(:num)', 'Laudo_review::review/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_review/approve/(:num)', 'Laudo_review::approve/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_review/reject/(:num)', 'Laudo_review::reject/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_review/sign/(:num)', 'Laudo_review::sign/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->get('laudo_review/professionals', 'Laudo_review::professionals', ['namespace' => 'LaudosTecnicos\\Controllers']);

// AI
$routes->get('laudo_ai', 'Laudo_ai::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_ai/generate', 'Laudo_ai::generate', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Dashboard
$routes->get('laudo_dashboard', 'Laudo_dashboard::index', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Relatórios
$routes->get('laudo_reports', 'Laudo_reports::index', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Biblioteca de Prompts
$routes->get('laudo_prompts_lib', 'Laudo_prompts_lib::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_prompts_lib/execute', 'Laudo_prompts_lib::execute', ['namespace' => 'LaudosTecnicos\\Controllers']);

// Automações
$routes->get('laudo_automations', 'Laudo_automations::index', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_automations/toggle/(:num)', 'Laudo_automations::toggle/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);
$routes->post('laudo_automations/run/(:any)', 'Laudo_automations::run/$1', ['namespace' => 'LaudosTecnicos\\Controllers']);

// API Routes
$routes->group('api/laudos', ['namespace' => 'LaudosTecnicos\\Controllers\\Api'], function($routes) {
    $routes->get('v1', 'Api::index');
    $routes->post('v1/auth/login', 'Api::login');
    $routes->get('v1/laudos', 'Api::get_laudos');
    $routes->get('v1/laudos/(:num)', 'Api::get_laudo/$1');
    $routes->get('v1/sync/changes', 'Api::get_changes');
    $routes->post('v1/sync/push', 'Api::push_changes');
});