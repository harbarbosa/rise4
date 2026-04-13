<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('organizador', 'Organizador::index', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/tasks', 'Organizador::tasks', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/tasks/view', 'Organizador::view', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/view', 'Organizador::view', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/tasks/view/(:num)', 'Organizador::view/$1', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/kanban', 'Organizador::kanban', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/calendar', 'Organizador::calendar', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/list_data', 'Organizador::list_data', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/modal_form', 'Organizador::modal_form', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/tasks/modal_form/(:num)', 'Organizador::modal_form/$1', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/save', 'Organizador::save', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/delete', 'Organizador::delete', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/duplicate', 'Organizador::duplicate', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/complete', 'Organizador::complete', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/toggle_favorite', 'Organizador::toggle_favorite', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/save_comment', 'Organizador::save_comment', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/delete_comment', 'Organizador::delete_comment', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/tasks/download_comment_files/(:num)', 'Organizador::download_comment_files/$1', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/save_reminder', 'Organizador::save_reminder', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/delete_reminder', 'Organizador::delete_reminder', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/update_reminder_status', 'Organizador::update_reminder_status', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/kanban_data', 'Organizador::kanban_data', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tasks/update_status', 'Organizador::update_status', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/tasks/calendar_data', 'Organizador::calendar_data', ['namespace' => 'Organizador\Controllers']);

$routes->get('organizador/categories', 'Organizador_categories::index', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/categories/list_data', 'Organizador_categories::list_data', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/categories/modal_form', 'Organizador_categories::modal_form', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/categories/modal_form/(:num)', 'Organizador_categories::modal_form/$1', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/categories/save', 'Organizador_categories::save', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/categories/delete', 'Organizador_categories::delete', ['namespace' => 'Organizador\Controllers']);

$routes->get('organizador/tags', 'Organizador_tags::index', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tags/list_data', 'Organizador_tags::list_data', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tags/modal_form', 'Organizador_tags::modal_form', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/tags/modal_form/(:num)', 'Organizador_tags::modal_form/$1', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tags/save', 'Organizador_tags::save', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/tags/delete', 'Organizador_tags::delete', ['namespace' => 'Organizador\Controllers']);

$routes->get('organizador/phases', 'Organizador_phases::index', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/phases/list_data', 'Organizador_phases::list_data', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/phases/modal_form', 'Organizador_phases::modal_form', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/phases/modal_form/(:num)', 'Organizador_phases::modal_form/$1', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/phases/save', 'Organizador_phases::save', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/phases/delete', 'Organizador_phases::delete', ['namespace' => 'Organizador\Controllers']);

$routes->get('organizador/settings', 'Organizador_settings::index', ['namespace' => 'Organizador\Controllers']);
$routes->post('organizador/settings/save', 'Organizador_settings::save', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador/settings/regenerate_public_api_token', 'Organizador_settings::regenerate_public_api_token', ['namespace' => 'Organizador\Controllers']);

$routes->get('organizador-api', 'Organizador_api::index', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador-api/health', 'Organizador_api::health', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador-api/v1/dashboard', 'Organizador_api::dashboard', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador-api/v1/tasks', 'Organizador_api::tasks', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador-api/v1/tasks/(:num)', 'Organizador_api::task/$1', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador-api/v1/calendar', 'Organizador_api::calendar', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador-api/v1/categories', 'Organizador_api::categories', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador-api/v1/tags', 'Organizador_api::tags', ['namespace' => 'Organizador\Controllers']);
$routes->get('organizador-api/v1/phases', 'Organizador_api::phases', ['namespace' => 'Organizador\Controllers']);
