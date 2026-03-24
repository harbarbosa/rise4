<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('propostas', 'Proposals::index', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/list_data', 'Proposals::list_data', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/form', 'Proposals::form', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/form/(:num)', 'Proposals::form/$1', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/modal_form', 'Proposals::modal_form', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/modal_form/(:num)', 'Proposals::modal_form/$1', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/save', 'Proposals::save', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/delete', 'Proposals::delete', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/view/(:num)', 'Proposals::view/$1', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/settings', 'Proposals::settings', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/save_settings', 'Proposals::save_settings', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/sections/add', 'Proposals::add_section', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/sections/add', 'Proposals::add_section', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/sections/update', 'Proposals::update_section', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/sections/update', 'Proposals::update_section', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/sections/delete', 'Proposals::delete_section', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/sections/delete', 'Proposals::delete_section', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/items/add', 'Proposals::add_item', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/items/add', 'Proposals::add_item', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/items/update', 'Proposals::update_item', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/items/update', 'Proposals::update_item', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/items/delete', 'Proposals::delete_item', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/items/delete', 'Proposals::delete_item', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/items/create_quick', 'Proposals::create_item_quick', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/update_status', 'Proposals::update_status', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/products', 'Products::index', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/products_list_data', 'Products::list_data', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/products_modal_form', 'Products::modal_form', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/products_modal_form', 'Products::modal_form', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/products_save', 'Products::save', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/products_delete', 'Products::delete', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/reorder', 'Proposals::reorder', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/reorder', 'Proposals::reorder', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/items/search', 'Proposals::items_search', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/document/preview', 'Proposals::document_preview', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/document/save', 'Proposals::save_document', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/download_pdf/(:num)', 'Proposals::download_pdf/$1', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/items/visibility', 'Proposals::update_item_visibility', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/items/copy_from_memory', 'Proposals::copy_items_from_memory', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/dashboard_data', 'Proposals::dashboard_data', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/approve', 'Proposals::approve', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/duplicate', 'Proposals::duplicate', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/tasks/list_data/(:num)', 'Proposals::tasks_list_data/$1', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/reminders/list_data/(:num)/(:any)', 'Proposals::reminders_list_data/$1/$2', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/reminders/list_data/(:num)', 'Proposals::reminders_list_data/$1', ['namespace' => 'Proposals\\Controllers']);
$routes->get('company_data_settings', 'Company_data_settings::index', ['namespace' => 'Proposals\\Controllers']);
$routes->post('company_data_settings/save', 'Company_data_settings::save', ['namespace' => 'Proposals\\Controllers']);
$routes->get('propostas/(:any)', 'Proposals::$1', ['namespace' => 'Proposals\\Controllers']);
$routes->post('propostas/(:any)', 'Proposals::$1', ['namespace' => 'Proposals\\Controllers']);
