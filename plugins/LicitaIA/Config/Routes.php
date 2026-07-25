<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('licitaia', 'Licitaia::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/dashboard', 'Licitaia::index', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/opportunities', 'Opportunities::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/opportunities/list_data', 'Opportunities::list_data', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/list_data', 'Opportunities::list_data', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/opportunities/view/(:num)', 'Opportunities::view/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/opportunities/modal_form/(:num)', 'Opportunities::modal_form/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/opportunities/modal_form', 'Opportunities::modal_form', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/modal_form', 'Opportunities::modal_form', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/save', 'Opportunities::save', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/delete', 'Opportunities::delete', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/upload_document/(:num)', 'Opportunities::upload_document/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/delete_document/(:num)', 'Opportunities::delete_document/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/opportunities/download_document/(:num)', 'Opportunities::download_document/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/update_status', 'Opportunities::update_status', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/create_task/(:num)/(:segment)', 'Opportunities::create_task/$1/$2', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/create_checklist', 'Opportunities::create_checklist', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/analyze_ai', 'Opportunities::analyze_ai', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/opportunities/generate_report', 'Opportunities::generate_report', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/ai-analysis', 'Ai_analysis::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/ai_analysis/analyze/(:num)', 'Ai_analysis::analyze/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/ai_analysis/reanalyze/(:num)', 'Ai_analysis::reanalyze/$1', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/keywords', 'Keywords::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/keywords/list_data', 'Keywords::list_data', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/keywords/modal_form', 'Keywords::modal_form', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/keywords/modal_form/(:num)', 'Keywords::modal_form/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/keywords/modal_form', 'Keywords::modal_form', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/keywords/save', 'Keywords::save', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/keywords/toggle_status', 'Keywords::toggle_status', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/keywords/delete', 'Keywords::delete', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/sources', 'Sources::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/sources/list_data', 'Sources::list_data', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/sources/modal_form', 'Sources::modal_form', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/sources/modal_form/(:num)', 'Sources::modal_form/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/sources/modal_form', 'Sources::modal_form', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/sources/save', 'Sources::save', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/sources/toggle_status', 'Sources::toggle_status', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/sources/test/(:num)', 'Sources::test/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/sources/search_now/(:num)', 'Sources::search_now/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/sources/delete', 'Sources::delete', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/search', 'Search::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/search', 'Search::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/search/list_data', 'Search::list_data', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/search/list_data', 'Search::list_data', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/search/import_selected', 'Search::import_selected', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/search/run_cron', 'Search::run_cron', ['namespace' => 'LicitaIA\Controllers']);
$routes->cli('licitaia/search/run_cron', 'Search::run_cron', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/settings', 'Settings::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/settings/save', 'Settings::save', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/checklist', 'Checklist::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/checklist/list_data', 'Checklist::list_data', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/checklist/modal_form/(:num)', 'Checklist::modal_form/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/checklist/modal_form', 'Checklist::modal_form', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/checklist/modal_form', 'Checklist::modal_form', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/checklist/save', 'Checklist::save', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/checklist/delete', 'Checklist::delete', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/checklist/create_for_opportunity/(:num)', 'Checklist::create_for_opportunity/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/checklist/update_opportunity_item/(:num)', 'Checklist::update_opportunity_item/$1', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/reports', 'Reports::index', ['namespace' => 'LicitaIA\Controllers']);
$routes->post('licitaia/reports/list_data', 'Reports::list_data', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/reports/technical_opinion/(:num)', 'Reports::technical_opinion/$1', ['namespace' => 'LicitaIA\Controllers']);
$routes->get('licitaia/reports/download/(:num)', 'Reports::download/$1', ['namespace' => 'LicitaIA\Controllers']);

$routes->get('licitaia/alerts/run_cron', 'Alerts::run_cron', ['namespace' => 'LicitaIA\Controllers']);
$routes->cli('licitaia/alerts/run_cron', 'Alerts::run_cron', ['namespace' => 'LicitaIA\Controllers']);
