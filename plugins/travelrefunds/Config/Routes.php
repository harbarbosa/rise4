<?php

namespace Config;

$routes = Services::routes();

$travelrefunds_namespace = ['namespace' => 'travelrefunds\\Controllers'];

    $routes->group('travelrefunds', $travelrefunds_namespace, function ($routes) {
    $routes->get('', 'TravelRefunds::index');
    $routes->get('cities', 'Cities::index');
    $routes->get('trips', 'TravelRefunds::trips');
    $routes->get('trips/list_data', 'TravelRefunds::list_data');
    $routes->post('trips/list_data', 'TravelRefunds::list_data');
    $routes->get('trips/new', 'TravelRefunds::modalTripForm');
    $routes->post('trips/new', 'TravelRefunds::modalTripForm');
    $routes->get('trips/modal_form', 'TravelRefunds::modalTripForm');
    $routes->post('trips/modal_form', 'TravelRefunds::modalTripForm');
    $routes->get('trips/view/(:num)', 'TravelRefunds::viewTrip/$1');
    $routes->post('trips/save', 'TravelRefunds::saveTrip');
    $routes->post('trips/delete', 'TravelRefunds::deleteTrip');
    $routes->post('trips/delete/(:num)', 'TravelRefunds::deleteTrip/$1');
    $routes->post('trips/save-expense/(:num)', 'TravelRefunds::saveExpense/$1');
    $routes->post('trips/delete-expense/(:num)/(:num)', 'TravelRefunds::deleteExpense/$1/$2');

    $routes->get('reimbursements', 'TravelRefunds::reimbursements');
    $routes->get('reimbursements/list_data', 'TravelRefunds::reimbursementsListData');
    $routes->post('reimbursements/list_data', 'TravelRefunds::reimbursementsListData');
    $routes->get('reimbursements/modal_form', 'TravelRefunds::modalReimbursementForm');
    $routes->post('reimbursements/modal_form', 'TravelRefunds::modalReimbursementForm');
    $routes->post('reimbursements/save', 'TravelRefunds::saveReimbursement');
    $routes->post('reimbursements/delete/(:num)', 'TravelRefunds::deleteReimbursement/$1');

    $routes->get('approvals', 'TravelRefunds::approvals');
    $routes->get('approvals/view/(:num)', 'TravelRefunds::approvalView/$1');
    $routes->post('approvals/trip/approve/(:num)', 'TravelRefunds::approveTrip/$1');
    $routes->post('approvals/trip/reject/(:num)', 'TravelRefunds::rejectTrip/$1');
    $routes->post('approvals/expense/approve/(:num)/(:num)', 'TravelRefunds::approveExpense/$1/$2');
    $routes->post('approvals/expense/reject/(:num)/(:num)', 'TravelRefunds::rejectExpense/$1/$2');
    $routes->post('approvals/approve/(:num)', 'TravelRefunds::approve/$1');
    $routes->post('approvals/reject/(:num)', 'TravelRefunds::reject/$1');

    $routes->get('categories', 'TravelRefunds::categories');
    $routes->post('categories/save', 'TravelRefunds::saveCategory');
    $routes->post('categories/delete/(:num)', 'TravelRefunds::deleteCategory/$1');

    $routes->get('settings', 'TravelRefunds::settings');
    $routes->post('settings/save', 'TravelRefunds::saveSettings');

    $routes->get('reports', 'TravelRefunds::reports');
    $routes->get('reports/export/(:segment)', 'TravelRefunds::exportReport/$1');
    $routes->get('reports/export-xlsx/(:segment)', 'TravelRefunds::exportReportXlsx/$1');
});
