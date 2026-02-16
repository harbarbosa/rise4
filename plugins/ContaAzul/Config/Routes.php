<?php

namespace Config;

$routes = Services::routes();

$routes->get('contaazul', 'ContaAzulSettings::index', ['namespace' => 'ContaAzul\Controllers']);
$routes->get('contaazul/authorize', 'ContaAzulSettings::authorize', ['namespace' => 'ContaAzul\Controllers']);
$routes->get('contaazul/callback', 'ContaAzulSettings::callback', ['namespace' => 'ContaAzul\Controllers']);
$routes->post('contaazul/save', 'ContaAzulSettings::save', ['namespace' => 'ContaAzul\Controllers']);
$routes->post('contaazul/import-clients', 'ContaAzulSettings::import_clients', ['namespace' => 'ContaAzul\Controllers']);
$routes->post('contaazul/import-items', 'ContaAzulSettings::import_items', ['namespace' => 'ContaAzul\Controllers']);
$routes->post('contaazul/import-general', 'ContaAzulSettings::import_general', ['namespace' => 'ContaAzul\Controllers']);
$routes
->post('contaazul/import-cost-centers', 'ContaAzulSettings::import_cost_centers', ['namespace' => 'ContaAzul\\Controllers']);
$routes->post('contaazul/import-cost-center-transactions', 'ContaAzulSettings::import_cost_center_transactions', ['namespace' => 'ContaAzul\\Controllers']);
$routes->get('contaazul/cost-centers-preview', 'ContaAzulSettings::cost_centers_preview', ['namespace' => 'ContaAzul\Controllers']);
$routes->get('contaazul/cron-import', 'ContaAzulSettings::cron_import', ['namespace' => 'ContaAzul\Controllers']);

