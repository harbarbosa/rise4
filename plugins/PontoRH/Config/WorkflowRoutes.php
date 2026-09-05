<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

// These routes intentionally override the generic write routes registered in Routes.php.
$routes->post('pontorh/registros/save', 'PontoRH_records_guarded::save', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/registros/delete', 'PontoRH_records_guarded::delete', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/tratamento/save_manual', 'PontoRH_treatment_guarded::save_manual', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/tratamento/record_action', 'PontoRH_treatment_guarded::record_action', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/tratamento/action', 'PontoRH_treatment_guarded::action', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/save', 'PontoRH_adjustments_guarded::save', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/delete', 'PontoRH_adjustments_guarded::delete', ['namespace' => 'PontoRH\Controllers']);
$routes->post('pontorh/ajustes/review', 'PontoRH_adjustment_workflow::review', ['namespace' => 'PontoRH\Controllers']);
