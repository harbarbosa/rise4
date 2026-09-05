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
$routes->post('frota/veiculos/salvar', 'Vehicles::save', $frota);
$routes->post('frota/veiculos/(:num)/excluir', 'Vehicles::delete/$1', $frota);

$routes->post('frota/abastecimentos/list_data', 'Frota::abastecimentosListData', $frota);
$routes->post('frota/abastecimentos/modal_form', 'Frota::abastecimentoModalForm', $frota);
$routes->post('frota/abastecimentos/salvar', 'Frota::salvarAbastecimento', $frota);
$routes->post('frota/abastecimentos/(:num)/excluir', 'Records::deleteFueling/$1', $frota);

$routes->post('frota/manutencoes/list_data', 'Frota::manutencoesListData', $frota);
$routes->post('frota/manutencoes/modal_form', 'Frota::manutencaoModalForm', $frota);
$routes->get('frota/manutencoes/ocorrencias/veiculo/(:num)', 'MaintenanceWorkflow::issuesByVehicle/$1', $frota);
$routes->post('frota/manutencoes/salvar', 'MaintenanceWorkflow::save', $frota);
$routes->post('frota/manutencoes/(:num)/excluir', 'Records::deleteMaintenance/$1', $frota);

$routes->post('frota/ocorrencias/list_data', 'Records::issuesListData', $frota);
$routes->post('frota/ocorrencias/modal_form', 'Frota::ocorrenciaModalForm', $frota);
$routes->post('frota/ocorrencias/salvar', 'Frota::salvarOcorrencia', $frota);
$routes->post('frota/ocorrencias/fotos/upload', 'IssuePhotos::upload', $frota);
$routes->get('frota/ocorrencias/autor/(:num)', 'Records::issueReporter/$1', $frota);
$routes->post('frota/ocorrencias/(:num)/excluir', 'Records::deleteIssue/$1', $frota);
$routes->post('frota/ocorrencias/resolve_modal_form', 'Frota::resolverOcorrenciaModalForm', $frota);
$routes->post('frota/ocorrencias/(:num)/resolver', 'Frota::resolverOcorrencia/$1', $frota);
