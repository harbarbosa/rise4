<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('pontorh', 'PontoRH::index', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/espelho', 'PontoRH::mirror', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/espelho/export_pdf', 'PontoRH::export_pdf', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/espelho/export_excel', 'PontoRH::export_excel', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/relatorios', 'PontoRH::reports', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/tratamento', 'PontoRH_treatment::index', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/tratamento/list_data', 'PontoRH_treatment::list_data', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/tratamento/detalhes', 'PontoRH_treatment::details', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/tratamento/detalhes/(:num)', 'PontoRH_treatment::details/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/tratamento/modal_form', 'PontoRH_treatment::modal_form', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/tratamento/modal_form/(:num)', 'PontoRH_treatment::modal_form/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/tratamento/save_manual', 'PontoRH_treatment::save_manual', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/tratamento/action', 'PontoRH_treatment::action', ['namespace' => 'PontoRH\Controllers']);

$routes->get('pontorh/registros', 'PontoRH_records::index', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/registros/list_data', 'PontoRH_records::list_data', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/registros/detalhes/(:num)', 'PontoRH_records::details/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/registros/view_modal', 'PontoRH_records::view_modal', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/registros/view_modal/(:num)', 'PontoRH_records::view_modal/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/registros/modal_form', 'PontoRH_records::modal_form', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/registros/modal_form/(:num)', 'PontoRH_records::modal_form/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/registros/save', 'PontoRH_records::save', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/registros/delete', 'PontoRH_records::delete', ['namespace' => 'PontoRH\Controllers']);

$routes->get('pontorh/jornadas', 'PontoRH_shifts::index', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/jornadas/list_data', 'PontoRH_shifts::list_data', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/jornadas/modal_form', 'PontoRH_shifts::modal_form', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/jornadas/modal_form/(:num)', 'PontoRH_shifts::modal_form/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/jornadas/save', 'PontoRH_shifts::save', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/jornadas/toggle_active', 'PontoRH_shifts::toggle_active', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/jornadas/delete', 'PontoRH_shifts::delete', ['namespace' => 'PontoRH\Controllers']);

$routes->get('pontorh/ajustes', 'PontoRH_adjustments::index', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/list_data', 'PontoRH_adjustments::list_data', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/ajustes/detalhes/(:num)', 'PontoRH_adjustments::details/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/view_modal', 'PontoRH_adjustments::view_modal', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/ajustes/view_modal/(:num)', 'PontoRH_adjustments::view_modal/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/modal_form', 'PontoRH_adjustments::modal_form', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/ajustes/modal_form/(:num)', 'PontoRH_adjustments::modal_form/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/save', 'PontoRH_adjustments::save', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/review', 'PontoRH_adjustments::review', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/delete', 'PontoRH_adjustments::delete', ['namespace' => 'PontoRH\Controllers']);

$routes->get('pontorh/configuracoes', 'PontoRH_settings::index', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/configuracoes/save', 'PontoRH_settings::save', ['namespace' => 'PontoRH\Controllers']);

$routes->get('pontorh/auditoria', 'PontoRH_audit_logs::index', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/auditoria/list_data', 'PontoRH_audit_logs::list_data', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/auditoria/detalhes/(:num)', 'PontoRH_audit_logs::details/$1', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/auditoria/view_modal', 'PontoRH_audit_logs::view_modal', ['namespace' => 'PontoRH\Controllers']);
$routes->get('pontorh/auditoria/view_modal/(:num)', 'PontoRH_audit_logs::view_modal/$1', ['namespace' => 'PontoRH\Controllers']);
