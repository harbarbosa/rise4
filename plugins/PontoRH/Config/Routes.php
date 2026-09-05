<?php
namespace Config;
use Config\Services;
$routes=Services::routes();
// PontoRH passa a ser somente uma camada de leitura/alerta sobre a Sólides.
$routes->get('pontorh','PontoRH_solides::index',['namespace'=>'PontoRH\\Controllers']);
$routes->get('pontorh/solides','PontoRH_solides::index',['namespace'=>'PontoRH\\Controllers']);
$routes->post('pontorh/solides/sincronizar','PontoRH_solides::sync',['namespace'=>'PontoRH\\Controllers']);
$routes->post('pontorh/solides/configuracoes','PontoRH_solides::save_settings',['namespace'=>'PontoRH\\Controllers']);
$routes->get('pontorh/mobile/status','PontoRH_solides::mobile_status',['namespace'=>'PontoRH\\Controllers']);
