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

