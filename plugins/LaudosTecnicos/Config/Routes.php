<?php

/**
 * Routes - Laudos Técnicos Plugin
 */

$routes->group('laudo_documents', ['namespace' => 'LaudosTecnicos\Controllers'], function($routes) {
    $routes->get('view/(:num)', 'Laudo_documents::view/$1');
    $routes->get('render_html/(:num)', 'Laudo_documents::render_html/$1');
    $routes->post('generate_pdf/(:num)', 'Laudo_documents::generate_pdf/$1');
    $routes->get('download_pdf/(:num)', 'Laudo_documents::download_pdf/$1');
    $routes->get('config/(:num)', 'Laudo_documents::config/$1');
    $routes->post('save_config', 'Laudo_documents::save_config');
    $routes->get('share/(:num)', 'Laudo_documents::share/$1');
    $routes->post('create_share', 'Laudo_documents::create_share');
    $routes->post('revoke_share/(:num)', 'Laudo_documents::revoke_share/$1');
    $routes->get('public_view/(:any)', 'Laudo_documents::public_view/$1');
    $routes->post('verify_password/(:any)', 'Laudo_documents::verify_password/$1');
    $routes->get('validate/(:any)', 'Laudo_documents::validate/$1');
});

$routes->group('laudo_review', ['namespace' => 'LaudosTecnicos\Controllers'], function($routes) {
    $routes->get('review/(:num)', 'Laudo_review::review/$1');
    $routes->post('add_comment', 'Laudo_review::add_comment');
    $routes->post('resolve_comment/(:num)', 'Laudo_review::resolve_comment/$1');
    $routes->post('add_pendency', 'Laudo_review::add_pendency');
    $routes->post('resolve_pendency/(:num)', 'Laudo_review::resolve_pendency/$1');
    $routes->post('finish_review/(:num)', 'Laudo_review::finish_review/$1');
    $routes->post('approve/(:num)', 'Laudo_review::approve/$1');
    $routes->post('reject_approval/(:num)', 'Laudo_review::reject_approval/$1');
    $routes->post('sign/(:num)', 'Laudo_review::sign/$1');
    $routes->get('get_signatures/(:num)', 'Laudo_review::get_signatures/$1');
    $routes->post('create_version/(:num)', 'Laudo_review::create_version/$1');
    $routes->post('publish_version/(:num)', 'Laudo_review::publish_version/$1');
    $routes->get('compare_versions/(:num)', 'Laudo_review::compare_versions/$1');
    $routes->get('view_version/(:num)', 'Laudo_review::view_version/$1');
    $routes->get('professionals', 'Laudo_review::professionals');
    $routes->get('professional_form/(:num)', 'Laudo_review::professional_form/$1');
    $routes->post('professional_form', 'Laudo_review::professional_form');
    $routes->post('professional_save', 'Laudo_review::professional_save');
});