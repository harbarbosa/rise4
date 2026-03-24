<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('purchases_requests', 'Purchase_requests::index', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/list_data', 'Purchase_requests::list_data', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_requests/approvals', 'Purchase_requests::approvals', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/approvals_list_data', 'Purchase_requests::approvals_list_data', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_requests/request_form', 'Purchase_requests::request_form', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_requests/request_form/(:num)', 'Purchase_requests::request_form/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_requests/download_items_template', 'Purchase_requests::download_items_template', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/save', 'Purchase_requests::save', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_requests/view/(:num)', 'Purchase_requests::view/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/submit', 'Purchase_requests::submit', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/approve', 'Purchase_requests::approve', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/reject', 'Purchase_requests::reject', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/convert', 'Purchase_requests::convert', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/approve_requester', 'Purchase_requests::approve_requester', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/approve_financial', 'Purchase_requests::approve_financial', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/reject_approval', 'Purchase_requests::reject_approval', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/reopen', 'Purchase_requests::reopen', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/delete', 'Purchase_requests::delete', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/get_item_suggestion', 'Purchase_requests::get_item_suggestion', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/get_item_info_suggestion', 'Purchase_requests::get_item_info_suggestion', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/tasks/list_data/(:num)', 'Purchase_requests::tasks_list_data/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/reminders/list_data/(:num)/(:any)', 'Purchase_requests::reminders_list_data/$1/$2', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_requests/reminders/list_data/(:num)', 'Purchase_requests::reminders_list_data/$1', ['namespace' => 'Purchases\\Controllers']);

$routes->get('purchases_suppliers', 'Purchases_suppliers::index', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_suppliers/list_data', 'Purchases_suppliers::list_data', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_suppliers/modal_form', 'Purchases_suppliers::modal_form', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_suppliers/save', 'Purchases_suppliers::save', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_suppliers/delete', 'Purchases_suppliers::delete', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_transportadoras', 'Purchases_transportadoras::index', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_transportadoras/list_data', 'Purchases_transportadoras::list_data', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_transportadoras/modal_form', 'Purchases_transportadoras::modal_form', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_transportadoras/save', 'Purchases_transportadoras::save', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_transportadoras/delete', 'Purchases_transportadoras::delete', ['namespace' => 'Purchases\\Controllers']);

$routes->get('purchases_orders', 'Purchases_orders::index', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_orders/list_data', 'Purchases_orders::list_data', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_orders/view/(:num)', 'Purchases_orders::view/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_orders/update_status/(:num)', 'Purchases_orders::update_status/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_orders/print_view/(:num)', 'Purchases_orders::print_view/$1', ['namespace' => 'Purchases\\Controllers']);

$routes->post('purchases_goods_receipts/modal_form', 'Purchases_goods_receipts::modal_form', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_goods_receipts/save', 'Purchases_goods_receipts::save', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_goods_receipts/file_preview/(:num)', 'Purchases_goods_receipts::file_preview/$1', ['namespace' => 'Purchases\\Controllers']);

$routes->get('purchases_reports', 'Purchases_reports::index', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_reports/purchases_by_period', 'Purchases_reports::purchases_by_period', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_reports/open_overdue', 'Purchases_reports::open_overdue', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_reports/top_items', 'Purchases_reports::top_items', ['namespace' => 'Purchases\\Controllers']);

$routes->get('purchases_quotations/create_from_request/(:num)', 'Purchase_quotations::create_from_request/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_quotations/save_from_request', 'Purchase_quotations::save_from_request', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases_quotations/view/(:num)', 'Purchase_quotations::view/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_quotations/save_prices/(:num)', 'Purchase_quotations::save_prices/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_quotations/update_suppliers/(:num)', 'Purchase_quotations::update_suppliers/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_quotations/finalize/(:num)', 'Purchase_quotations::finalize/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_quotations/choose_winner/(:num)', 'Purchase_quotations::choose_winner/$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases_quotations/generate_po/(:num)', 'Purchase_quotations::generate_po/$1', ['namespace' => 'Purchases\\Controllers']);

$routes->get('purchases', 'Purchases::index', ['namespace' => 'Purchases\\Controllers']);
$routes->get('purchases/(:any)', 'Purchases::$1', ['namespace' => 'Purchases\\Controllers']);
$routes->post('purchases/(:any)', 'Purchases::$1', ['namespace' => 'Purchases\\Controllers']);
