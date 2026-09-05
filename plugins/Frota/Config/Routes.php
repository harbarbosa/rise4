<?php

namespace Config;

$routes = Services::routes();
$frota = ['namespace' => 'Frota\\Controllers'];

$routes->get('frota', 'Frota::index', $frota);
$routes->get('frota/veiculos', 'Frota::veiculos', $frota);
$routes->get('frota/abastecimentos', 'Frota::abastecimentos', $frota);
$routes->get('frota/manutencoes', 'Frota::manutencoes', $frota);
$routes->get('frota/ocorrencias', 'Frota::ocorrencias', $frota);

$routes->get('frota/fipe/marcas', 'Fipe::marcas', $frota);
$routes->get('frota/fipe/modelos', 'Fipe::modelos', $frota);

$routes->post('frota/veiculos/list_data', 'Frota::veiculosListData', $frota);
$routes->post('frota/veiculos/modal_form', 'Frota::veiculoModalForm', $frota);
$routes->post('frota/veiculos/salvar', 'Frota::salvarVeiculo', $frota);

$routes->post('frota/abastecimentos/list_data', 'Frota::abastecimentosListData', $frota);
$routes->post('frota/abastecimentos/modal_form', 'Frota::abastecimentoModalForm', $frota);
$routes->post('frota/abastecimentos/salvar', 'Frota::salvarAbastecimento', $frota);

$routes->post('frota/manutencoes/list_data', 'Frota::manutencoesListData', $frota);
$routes->post('frota/manutencoes/modal_form', 'Frota::manutencaoModalForm', $frota);
$routes->post('frota/manutencoes/salvar', 'Frota::salvarManutencao', $frota);

$routes->post('frota/ocorrencias/list_data', 'Frota::ocorrenciasListData', $frota);
$routes->post('frota/ocorrencias/modal_form', 'Frota::ocorrenciaModalForm', $frota);
$routes->post('frota/ocorrencias/salvar', 'Frota::salvarOcorrencia', $frota);
$routes->post('frota/ocorrencias/upload_fotos', 'IssuePhotos::upload', $frota);
$routes->post('frota/ocorrencias/resolve_modal_form', 'Frota::resolverOcorrenciaModalForm', $frota);
$routes->post('frota/ocorrencias/(:num)/resolver', 'Frota::resolverOcorrencia/$1', $frota);

$api = ['namespace' => 'RestApi\\Controllers'];
$routes->group('api/frota', $api, function ($routes) {
    $routes->get('dashboard', 'FrotaController::dashboard');
    $routes->get('vehicles', 'FrotaController::vehicles');
    $routes->get('vehicles/(:num)', 'FrotaController::vehicle/$1');
    $routes->get('fuelings', 'FrotaController::fuelings');
    $routes->post('fuelings', 'FrotaController::createFueling');
    $routes->get('issues', 'FrotaController::issues');
    $routes->post('issues', 'FrotaController::createIssue');
    $routes->get('maintenances', 'FrotaController::maintenances');
});
