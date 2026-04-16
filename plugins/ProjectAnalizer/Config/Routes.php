<?php
namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('projectanalizer', 'ProjectAnalizer::index', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/execution_schedule/(:num)', 'ProjectAnalizer::execution_schedule/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolution_project/(:num)', 'Tasks::evolution_project/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolucao/(:num)', 'Projectanalizer_projects::evolucao/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/revenues_expenses/(:num)', 'Projectanalizer_projects::revenues_expenses/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolucao/reschedule_modal_form/(:num)', 'Projectanalizer_projects::reschedule_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/reschedule_modal_form/(:num)', 'Projectanalizer_projects::reschedule_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/generate_baseline/(:num)', 'Projectanalizer_projects::generate_baseline/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/reschedule_project/(:num)', 'Projectanalizer_projects::reschedule_project/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolucao/cost_modal_form/(:num)', 'Projectanalizer_projects::cost_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/cost_modal_form/(:num)', 'Projectanalizer_projects::cost_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/save_task_cost/(:num)', 'Projectanalizer_projects::save_task_cost/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/delete_task_cost/(:num)', 'Projectanalizer_projects::delete_task_cost/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolucao/realized_modal_form/(:num)', 'Projectanalizer_projects::realized_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/realized_modal_form/(:num)', 'Projectanalizer_projects::realized_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolucao/revenue_planned_modal_form/(:num)', 'Projectanalizer_projects::revenue_planned_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/revenue_planned_modal_form/(:num)', 'Projectanalizer_projects::revenue_planned_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolucao/revenue_realized_modal_form/(:num)', 'Projectanalizer_projects::revenue_realized_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/revenue_realized_modal_form/(:num)', 'Projectanalizer_projects::revenue_realized_modal_form/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/save_realized/(:num)', 'Projectanalizer_projects::save_realized/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/evolucao/delete_realized/(:num)', 'Projectanalizer_projects::delete_realized/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/revenue/list', 'Projectanalizer_projects::revenue_list', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/revenue/save_planned', 'Projectanalizer_projects::save_revenue_planned', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/revenue/delete_planned', 'Projectanalizer_projects::delete_revenue_planned', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/revenue/save_realized', 'Projectanalizer_projects::save_revenue_realized', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/revenue/delete_realized', 'Projectanalizer_projects::delete_revenue_realized', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/revenue/summary', 'Projectanalizer_projects::revenue_summary', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/cashflow/summary', 'Projectanalizer_projects::cashflow_summary', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/cron-snapshots', 'Projectanalizer_projects::cron_snapshots', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolucao/export/(:num)', 'Projectanalizer_projects::export_report/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/evolucao/export_costs_csv/(:num)', 'Projectanalizer_projects::export_costs_csv/$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/cost_centers/sync', 'ProjectAnalizer::sync_cost_centers', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer/(:any)', 'ProjectAnalizer::$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer/(:any)', 'ProjectAnalizer::$1', ['namespace' => 'ProjectAnalizer\Controllers']);

$routes->get('projectanalizer_settings', 'ProjectAnalizer_settings::index', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->get('projectanalizer_settings/(:any)', 'ProjectAnalizer_settings::$1', ['namespace' => 'ProjectAnalizer\Controllers']);
$routes->post('projectanalizer_settings/(:any)', 'ProjectAnalizer_settings::$1', ['namespace' => 'ProjectAnalizer\Controllers']);

