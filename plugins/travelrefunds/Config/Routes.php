<?php

namespace Config;

$routes = Services::routes();

$travelrefunds_namespace = ['namespace' => 'travelrefunds\\Controllers'];

$routes->group('travelrefunds', $travelrefunds_namespace, function ($routes) {
    $routes->get('', 'TravelRefunds::index');
    $routes->get('trips', 'TravelRefunds::trips');
    $routes->get('trips/new', 'TravelRefunds::viewTrip');
    $routes->get('trips/view/(:num)', 'TravelRefunds::viewTrip/$1');
    $routes->post('trips/save', 'TravelRefunds::saveTrip');
    $routes->post('trips/delete/(:num)', 'TravelRefunds::deleteTrip/$1');
    $routes->post('trips/save-expense/(:num)', 'TravelRefunds::saveExpense/$1');
    $routes->post('trips/delete-expense/(:num)/(:num)', 'TravelRefunds::deleteExpense/$1/$2');

    $routes->get('reimbursements', 'TravelRefunds::reimbursements');
    $routes->post('reimbursements/save', 'TravelRefunds::saveReimbursement');
    $routes->post('reimbursements/delete/(:num)', 'TravelRefunds::deleteReimbursement/$1');

    $routes->get('approvals', 'TravelRefunds::approvals');
    $routes->post('approvals/approve/(:num)', 'TravelRefunds::approve/$1');
    $routes->post('approvals/reject/(:num)', 'TravelRefunds::reject/$1');

    $routes->get('categories', 'TravelRefunds::categories');
    $routes->post('categories/save', 'TravelRefunds::saveCategory');
    $routes->post('categories/delete/(:num)', 'TravelRefunds::deleteCategory/$1');

    $routes->get('settings', 'TravelRefunds::settings');
    $routes->post('settings/save', 'TravelRefunds::saveSettings');
});
