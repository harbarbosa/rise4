<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('ged', 'Ged::index', ['namespace' => 'GED\\Controllers']);

$routes->get('ged/documents', 'Documents::index', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/documents/list_data', 'Documents::list_data', ['namespace' => 'GED\\Controllers']);
$routes->get('ged/documents/modal_form', 'Documents::modal_form', ['namespace' => 'GED\\Controllers']);
$routes->get('ged/documents/modal_form/(:num)', 'Documents::modal_form/$1', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/documents/modal_form', 'Documents::modal_form', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/documents/modal_form/(:num)', 'Documents::modal_form/$1', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/documents/save', 'Documents::save', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/documents/delete', 'Documents::delete', ['namespace' => 'GED\\Controllers']);
$routes->get('ged/documents/view/(:num)', 'Documents::view/$1', ['namespace' => 'GED\\Controllers']);
$routes->get('ged/documents/download/(:num)', 'Documents::download/$1', ['namespace' => 'GED\\Controllers']);

$routes->get('ged/document_types', 'DocumentTypes::index', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/document_types/list_data', 'DocumentTypes::list_data', ['namespace' => 'GED\\Controllers']);
$routes->get('ged/document_types/modal_form', 'DocumentTypes::modal_form', ['namespace' => 'GED\\Controllers']);
$routes->get('ged/document_types/modal_form/(:num)', 'DocumentTypes::modal_form/$1', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/document_types/modal_form', 'DocumentTypes::modal_form', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/document_types/modal_form/(:num)', 'DocumentTypes::modal_form/$1', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/document_types/save', 'DocumentTypes::save', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/document_types/toggle_status/(:num)', 'DocumentTypes::toggle_status/$1', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/document_types/delete', 'DocumentTypes::delete', ['namespace' => 'GED\\Controllers']);

$routes->get('ged/reports', 'Reports::index', ['namespace' => 'GED\\Controllers']);

$routes->get('ged/settings', 'Settings::index', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/settings/save', 'Settings::save', ['namespace' => 'GED\\Controllers']);

$routes->get('ged/notifications/run', 'GedNotifications::run', ['namespace' => 'GED\\Controllers']);
$routes->post('ged/notifications/run', 'GedNotifications::run', ['namespace' => 'GED\\Controllers']);
$routes->get('ged/notifications/run/(:any)', 'GedNotifications::run/$1', ['namespace' => 'GED\\Controllers']);
