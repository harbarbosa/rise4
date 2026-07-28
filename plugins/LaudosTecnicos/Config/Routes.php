<?php

/**
 * Routes - Laudos Técnicos Plugin
 */

// API Routes
$routes->group('api/laudos', ['namespace' => 'LaudosTecnicos\Controllers\Api'], function($routes) {
    $routes->get('v1', 'Api::index');
    $routes->get('v1/swagger', 'Swagger::index');
    $routes->get('swagger-ui', 'Swagger::ui');
    
    // Auth
    $routes->post('v1/auth/login', 'Api::login');
    $routes->post('v1/auth/refresh', 'Api::refresh_token');
    $routes->post('v1/auth/logout', 'Api::logout');
    
    // Laudos
    $routes->get('v1/laudos', 'Api::get_laudos');
    $routes->get('v1/laudos/(:num)', 'Api::get_laudo/$1');
    
    // Inspeções
    $routes->get('v1/inspections', 'Api::get_inspections');
    $routes->get('v1/inspections/(:num)', 'Api::get_inspection/$1');
    $routes->post('v1/inspections/(:num)/checkin', 'Api::checkin/$1');
    
    // Checklists
    $routes->get('v1/checklists/(:num)', 'Api::get_checklists/$1');
    $routes->post('v1/checklists/(:num)/answers', 'Api::submit_answers/$1');
    
    // Fotografias
    $routes->post('v1/photos/upload', 'Api::upload_photo');
    
    // Não Conformidades
    $routes->post('v1/nonconformities', 'Api::create_nc');
    
    // Sincronização
    $routes->get('v1/sync/changes', 'Api::get_changes');
    $routes->post('v1/sync/push', 'Api::push_changes');
    
    // Versões
    $routes->get('v1/versions/(:num)', 'Api::get_versions/$1');
});

// Admin Routes
$routes->group('laudo_ai', ['namespace' => 'LaudosTecnicos\Controllers'], function($routes) {
    $routes->get('/', 'Laudo_ai::index');
    $routes->post('save_config', 'Laudo_ai::save_config');
    $routes->post('generate', 'Laudo_ai::generate');
    $routes->post('test', 'Laudo_ai::test');
});

// Laudo Review Routes
$routes->group('laudo_review', ['namespace' => 'LaudosTecnicos\Controllers'], function($routes) {
    $routes->get('review/(:num)', 'Laudo_review::review/$1');
    $routes->post('add_comment', 'Laudo_review::add_comment');
    $routes->post('resolve_comment/(:num)', 'Laudo_review::resolve_comment/$1');
    $routes->post('add_pendency', 'Laudo_review::add_pendency');
    $routes->post('resolve_pendency/(:num)', 'Laudo_review::resolve_pendency/$1');
    $routes->post('finish_review/(:num)', 'Laudo_review::finish_review/$1');
    $routes->post('approve/(:num)', 'Laudo_review::approve/$1');
    $routes->post('reject_approval/(:num)', 'Laudo_review::reject_approval/$1');
    $routes->post('sign/(:num)', 'Laudo_review::sign/$1');
    $routes->get('get_signatures/(:num)', 'Laudo_review::get_signatures/$1');
    $routes->post('create_version/(:num)', 'Laudo_review::create_version/$1');
    $routes->post('publish_version/(:num)', 'Laudo_review::publish_version/$1');
    $routes->get('compare_versions/(:num)', 'Laudo_review::compare_versions/$1');
    $routes->get('view_version/(:num)', 'Laudo_review::view_version/$1');
    $routes->get('professionals', 'Laudo_review::professionals');
    $routes->get('professional_form/(:num)', 'Laudo_review::professional_form/$1');
    $routes->post('professional_form', 'Laudo_review::professional_form');
    $routes->post('professional_save', 'Laudo_review::professional_save');
});

// Dashboard
$routes->group('laudo_dashboard', ['namespace' => 'LaudosTecnicos\Controllers'], function($routes) {
    $routes->get('/', 'Laudo_dashboard::index');
});

// Relatórios
$routes->group('laudo_reports', ['namespace' => 'LaudosTecnicos\Controllers'], function($routes) {
    $routes->get('/', 'Laudo_reports::index');
    $routes->get('run/(:any)', 'Laudo_reports::run/$1');
    $routes->get('export/(:any)/(:any)', 'Laudo_reports::export/$1/$2');
});

// Biblioteca de Prompts
$routes->group('laudo_prompts_lib', ['namespace' => 'LaudosTecnicos\Controllers'], function($routes) {
    $routes->get('/', 'Laudo_prompts_lib::index');
    $routes->get('use_prompt/(:any)', 'Laudo_prompts_lib::use_prompt/$1');
    $routes->post('execute', 'Laudo_prompts_lib::execute');
});

// Automações
$routes->group('laudo_automations', ['namespace' => 'LaudosTecnicos\Controllers'], function($routes) {
    $routes->get('/', 'Laudo_automations::index');
    $routes->post('run/(:any)', 'Laudo_automations::run/$1');
    $routes->post('toggle/(:num)', 'Laudo_automations::toggle/$1');
});