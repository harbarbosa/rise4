<?php

namespace Config;

$routes = Services::routes();
$options = array('namespace' => 'AssistenteIA\\Controllers');

$routes->get('assistente-ia', 'Assistente::index', $options);
$routes->post('assistente-ia/chat', 'Assistente::chat', $options);
$routes->get('assistente-ia/conversas', 'Assistente::conversations', $options);
$routes->get('assistente-ia/conversas/(:num)', 'Assistente::conversation/$1', $options);
$routes->post('assistente-ia/conversas/(:num)/delete', 'Assistente::deleteConversation/$1', $options);
$routes->get('assistente-ia/configuracoes', 'Settings::index', $options);
$routes->post('assistente-ia/configuracoes/save', 'Settings::save', $options);
