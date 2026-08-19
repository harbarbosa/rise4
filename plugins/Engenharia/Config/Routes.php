<?php

namespace Config;

use Config\Services;

$routes = Services::routes();

$routes->get('engenharia', 'Engenharia::index', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/index', 'Engenharia::index', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/laudos', 'Engenharia::laudos', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/checklists', 'Engenharia::checklists', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/modelos', 'Engenharia::modelos', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/configuracoes', 'Engenharia::configuracoes', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/list_data', 'Engenharia::list_data', ['namespace' => 'Engenharia\\Controllers']);
$routes->match(['GET', 'POST'], 'engenharia/laudos/modal_form/(:num)', 'Engenharia::modal_form/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->match(['GET', 'POST'], 'engenharia/laudos/modal_form', 'Engenharia::modal_form', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/save', 'Engenharia::save', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/laudos/view/(:num)', 'Engenharia::view/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/delete', 'Engenharia::delete', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/get_contacts', 'Engenharia::get_contacts', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/get_projects', 'Engenharia::get_projects', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/get_checklists', 'Engenharia::get_checklists', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/laudos/inspection/(:num)', 'Engenharia::inspection/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/inspection/save_response', 'Engenharia::save_response', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/inspection/start', 'Engenharia::start_inspection', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/inspection/save_area', 'Engenharia::save_area', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/inspection/save_photo', 'Engenharia::save_photo', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/inspection/save_measurement', 'Engenharia::save_measurement', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/inspection/save_nonconformity', 'Engenharia::save_nonconformity', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/inspection/delete_photo', 'Engenharia::delete_photo', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/instruments/save', 'Engenharia::save_instrument', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/laudos/report/preview/(:num)', 'Engenharia::report_preview/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/laudos/report/final/(:num)', 'Engenharia::report_final/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/laudos/report/versions/(:num)', 'Engenharia::report_versions/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/report/settings', 'Engenharia::save_report_settings', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/inspection/finish', 'Engenharia::finish_inspection', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/laudos/change_status', 'Engenharia::change_status', ['namespace' => 'Engenharia\\Controllers']);
$routes->match(['GET', 'POST'], 'engenharia/checklists/modal_form', 'Engenharia::checklist_modal_form', ['namespace' => 'Engenharia\\Controllers']);
$routes->match(['GET', 'POST'], 'engenharia/checklists/modal_form/(:num)', 'Engenharia::checklist_modal_form/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/checklists/save', 'Engenharia::checklist_save', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/checklists/manage/(:num)', 'Engenharia::checklist_manage/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->match(['GET', 'POST'], 'engenharia/checklists/group_modal_form/(:num)', 'Engenharia::group_modal_form/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->match(['GET', 'POST'], 'engenharia/checklists/group_modal_form/(:num)/(:num)', 'Engenharia::group_modal_form/$1/$2', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/checklists/group_save', 'Engenharia::group_save', ['namespace' => 'Engenharia\\Controllers']);
$routes->match(['GET', 'POST'], 'engenharia/checklists/item_modal_form/(:num)', 'Engenharia::item_modal_form/$1', ['namespace' => 'Engenharia\\Controllers']);
$routes->match(['GET', 'POST'], 'engenharia/checklists/item_modal_form/(:num)/(:num)', 'Engenharia::item_modal_form/$1/$2', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/checklists/item_save', 'Engenharia::item_save', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/checklists/version', 'Engenharia::checklist_version', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/checklists/duplicate', 'Engenharia::checklist_duplicate', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/checklists/toggle', 'Engenharia::checklist_toggle', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/checklists/delete', 'Engenharia::checklist_delete', ['namespace' => 'Engenharia\\Controllers']);
$routes->post('engenharia/checklists/sort', 'Engenharia::checklist_sort', ['namespace' => 'Engenharia\\Controllers']);
$routes->get('engenharia/checklists/preview/(:num)', 'Engenharia::checklist_preview/$1', ['namespace' => 'Engenharia\\Controllers']);
