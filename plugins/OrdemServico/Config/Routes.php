<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

// Rotas do plugin Ordem de Serviço
$routes->get('ordemservico', 'OrdemServico::index', ['namespace' => 'OrdemServico\\Controllers']);
$routes->get('ordemservico/view/(:num)', 'OrdemServico::view/$1', ['namespace' => 'OrdemServico\\Controllers']);
$routes->get('ordemservico/(:any)', 'OrdemServico::$1', ['namespace' => 'OrdemServico\\Controllers']);
$routes->post('ordemservico/(:any)', 'OrdemServico::$1', ['namespace' => 'OrdemServico\\Controllers']);
$routes->post('ordemservico/close', 'OrdemServico::close', ['namespace' => 'OrdemServico\\Controllers']);
$routes->post('ordemservico/comment_save', 'OrdemServico::comment_save', ['namespace' => 'OrdemServico\\Controllers']);
$routes->get('ordemservico/egestor/os', 'OrdemServico::egestor_os_list', ['namespace' => 'OrdemServico\\Controllers']);
$routes->get('ordemservico/egestor/os/(:num)', 'OrdemServico::egestor_os_show/$1', ['namespace' => 'OrdemServico\\Controllers']);
$routes->post('ordemservico/egestor/sync/(:num)', 'OrdemServico::egestor_sync_os/$1', ['namespace' => 'OrdemServico\\Controllers']);
$routes->post('ordemservico/egestor/sync', 'OrdemServico::egestor_sync_os', ['namespace' => 'OrdemServico\\Controllers']);
$routes->post('ordemservico/egestor/settings_save', 'OrdemServico::egestor_settings_save', ['namespace' => 'OrdemServico\\Controllers']);
