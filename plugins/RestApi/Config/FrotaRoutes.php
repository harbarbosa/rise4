<?php

namespace Config;

$routes = Services::routes();
$rest_api_namespace = ['namespace' => 'RestApi\\Controllers'];

$routes->group('api/frota', $rest_api_namespace, function ($routes) {
    $routes->get('endpoints', 'FrotaController::endpoints');
    $routes->get('dashboard', 'FrotaController::dashboard');

    $routes->get('vehicles', 'FrotaController::vehicles');
    $routes->post('vehicles', 'FrotaController::createVehicle');
    $routes->get('vehicles/(:num)', 'FrotaController::vehicle/$1');
    $routes->put('vehicles/(:num)', 'FrotaController::updateVehicle/$1');
    $routes->patch('vehicles/(:num)', 'FrotaController::updateVehicle/$1');
    $routes->delete('vehicles/(:num)', 'FrotaController::deleteVehicle/$1');
    $routes->get('vehicles/(:num)/issues/open', 'FrotaController::openIssuesByVehicle/$1');

    $routes->get('fuelings', 'FrotaController::fuelings');
    $routes->post('fuelings', 'FrotaController::createFueling');
    $routes->get('fuelings/(:num)', 'FrotaController::fueling/$1');
    $routes->put('fuelings/(:num)', 'FrotaController::updateFueling/$1');
    $routes->patch('fuelings/(:num)', 'FrotaController::updateFueling/$1');
    $routes->delete('fuelings/(:num)', 'FrotaController::deleteFueling/$1');

    $routes->get('issues', 'FrotaController::issues');
    $routes->post('issues', 'FrotaController::createIssue');
    $routes->get('issues/(:num)', 'FrotaController::issue/$1');
    $routes->put('issues/(:num)', 'FrotaController::updateIssue/$1');
    $routes->patch('issues/(:num)', 'FrotaController::updateIssue/$1');
    $routes->delete('issues/(:num)', 'FrotaController::deleteIssue/$1');
    $routes->post('issues/(:num)/resolve', 'FrotaController::resolveIssue/$1');
    $routes->post('issues/(:num)/photos', 'FrotaController::uploadIssuePhotos/$1');

    $routes->get('maintenances', 'FrotaController::maintenances');
    $routes->post('maintenances', 'FrotaController::createMaintenance');
    $routes->get('maintenances/(:num)', 'FrotaController::maintenance/$1');
    $routes->put('maintenances/(:num)', 'FrotaController::updateMaintenance/$1');
    $routes->patch('maintenances/(:num)', 'FrotaController::updateMaintenance/$1');
    $routes->delete('maintenances/(:num)', 'FrotaController::deleteMaintenance/$1');
});
