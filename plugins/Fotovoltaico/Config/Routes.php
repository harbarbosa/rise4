<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('fotovoltaico', 'Fotovoltaico::index', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/projects', 'Fotovoltaico::index', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects_list_data', 'Fotovoltaico::list_data', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/projects_modal_form', 'Fotovoltaico::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects_modal_form', 'Fotovoltaico::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects_save', 'Fotovoltaico::save', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects_delete', 'Fotovoltaico::delete', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/projects_view/(:num)', 'Fotovoltaico::view/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects/(:num)/calculate', 'Fotovoltaico::calculate/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects/(:num)/regulatory_snapshot_save', 'Fotovoltaico::regulatory_snapshot_save/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects/(:num)/tariff_snapshot_save', 'Fotovoltaico::tariff_snapshot_save/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects/(:num)/irradiation_snapshot_save', 'Fotovoltaico::irradiation_snapshot_save/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/irradiation/fetch', 'Fotovoltaico::irradiation_fetch', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/projects/(:num)/proposal_generate', 'Fotovoltaico::proposal_generate/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/proposals/download/(:num)', 'Fotovoltaico::proposal_download/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/assistant', 'Fotovoltaico::assistant', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/assistant_generate', 'Fotovoltaico::assistant_generate', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/regulatory', 'Fv_regulatory::index', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/regulatory_list_data', 'Fv_regulatory::list_data', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/regulatory_modal_form', 'Fv_regulatory::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/regulatory_modal_form', 'Fv_regulatory::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/regulatory_save', 'Fv_regulatory::save', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/client_projects/(:num)', 'Fotovoltaico::client_projects/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/wizard/(:num)', 'Fotovoltaico::wizard/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/wizard/(:num)/(:num)', 'Fotovoltaico::wizard/$1/$2', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/wizard_modal/(:num)/(:num)', 'Fotovoltaico::wizard_modal/$1/$2', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/wizard_modal/(:num)/(:num)', 'Fotovoltaico::wizard_modal/$1/$2', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/products', 'Fv_products::index', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/products_list_data', 'Fv_products::list_data', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/products_modal_form', 'Fv_products::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/products_modal_form', 'Fv_products::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/products_save', 'Fv_products::save', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/products_delete', 'Fv_products::delete', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/products_toggle_active', 'Fv_products::toggle_active', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/products/view/(:num)', 'Fv_products::view/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/api/products', 'Fv_products::api_products', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/api/products/(:num)', 'Fv_products::api_product/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/products/import_modal', 'Fv_products::import_modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/products/import_preview', 'Fv_products::import_preview', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/products/import_process', 'Fv_products::import_process', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/kits', 'Fv_kits::index', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/kits_list_data', 'Fv_kits::list_data', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits_list_data', 'Fv_kits::list_data', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits_modal_form', 'Fv_kits::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/kits_modal_form', 'Fv_kits::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits_save', 'Fv_kits::save', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits_toggle_active', 'Fv_kits::toggle_active', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits_delete', 'Fv_kits::delete', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/kits/view/(:num)', 'Fv_kits::view/$1', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/kits/items/(:num)', 'Fv_kits::items/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits/items/add', 'Fv_kits::add_item', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits/items/update', 'Fv_kits::update_item', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits/items/delete', 'Fv_kits::delete_item', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits/items/reorder', 'Fv_kits::reorder_items', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/kits/validate_electrical', 'Fv_kits::validate_electrical', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/api/kits', 'Fv_kits::api_kits', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/api/kits/(:num)', 'Fv_kits::api_kit/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/api/kits/(:num)/totals', 'Fv_kits::api_kit_totals/$1', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/utilities', 'Utilities::index', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/utilities_list_data', 'Utilities::list_data', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/utilities_modal_form', 'Utilities::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/utilities_modal_form', 'Utilities::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/utilities_save', 'Utilities::save', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/utilities_delete', 'Utilities::delete', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/tariffs/(:num)', 'Tariffs::index/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/tariffs_list_data/(:num)', 'Tariffs::list_data/$1', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/tariffs_modal_form', 'Tariffs::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/tariffs_modal_form', 'Tariffs::modal_form', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/tariffs_save', 'Tariffs::save', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/tariffs_delete', 'Tariffs::delete', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/api/tariffs/(:num)', 'Tariffs::api_by_utility/$1', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/settings', 'Settings::index', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/settings_save', 'Settings::save', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/integrations/cec', 'Fv_integrations_cec::index', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/integrations/cec/save', 'Fv_integrations_cec::save', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/integrations/cec/test', 'Fv_integrations_cec::test', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->post('fotovoltaico/integrations/cec/run', 'Fv_integrations_cec::run', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/integrations/cec/logs', 'Fv_integrations_cec::logs', ['namespace' => 'Fotovoltaico\\Controllers']);
$routes->get('fotovoltaico/integrations/cec/log_view/(:num)', 'Fv_integrations_cec::log_view/$1', ['namespace' => 'Fotovoltaico\\Controllers']);

$routes->get('fotovoltaico/cron/cec_sync', 'Fv_cron::cec_sync', ['namespace' => 'Fotovoltaico\\Controllers']);
