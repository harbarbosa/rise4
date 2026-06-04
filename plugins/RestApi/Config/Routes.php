<?php

namespace Config;

$routes = Services::routes();

$rest_api_namespace = ['namespace' => 'RestApi\Controllers'];

$routes->get('api_settings', 'Api_settings_Controller::index', $rest_api_namespace);

//for loading datatable
$routes->post('restapi/table', 'Api_settings_Controller::table', $rest_api_namespace);

//for show modal
$routes->post('restapi/modal/?(:num)?', 'Api_settings_Controller::modal_form/$1', $rest_api_namespace);

//for Add/Edit Api Users
$routes->post('restapi/manage/', 'Api_settings_Controller::save', $rest_api_namespace);

//for delete Api Users
$routes->post('restapi/remove/(:num)', 'Api_settings_Controller::delete_user/$1', $rest_api_namespace);

//mobile auth
$routes->post('api/auth/login', 'AuthController::login', $rest_api_namespace);
$routes->post('api/auth/logout', 'AuthController::logout', $rest_api_namespace);

//For all kind of api get request
$routes->group('api', $rest_api_namespace, function ($routes) {
	$routes->add('client_groups', 'UtilitiesController::getClientGroups');
	$routes->add('project_labels', 'UtilitiesController::getProejctLabels');
	$routes->add('invoice_labels', 'UtilitiesController::getInvoiceLabels');
	$routes->add('ticket_labels', 'UtilitiesController::getTicketLabels');
	$routes->add('invoice_tax', 'UtilitiesController::getInvoiceTaxes');
	$routes->add('contact_by_clientid/(:num)', 'UtilitiesController::getContactByClientid/$1');
	$routes->add('ticket_type', 'UtilitiesController::getTicketType');
	$routes->add('staff_owner', 'UtilitiesController::getStaffOwner');
	$routes->add('project_members', 'UtilitiesController::getProjectMembers');
	$routes->get('team_members', 'TeamMembersController::index');
	$routes->get('team_members/(:num)', 'TeamMembersController::show/$1');
	$routes->match(['GET', 'POST'], 'projectanalizer/tasks', 'ProjectAnalizerController::tasks');
	$routes->match(['GET', 'POST'], 'projectanalizer/tasks/(:num)', 'ProjectAnalizerController::tasks/$1');
	$routes->match(['GET', 'POST'], 'projectanalizer/tasks/(:num)/(:num)', 'ProjectAnalizerController::task/$1/$2');
	$routes->get('projectanalizer/timesheets/(:num)', 'ProjectAnalizerTimesheetsController::listByProject/$1');
	$routes->get('projectanalizer/timesheets/(:num)/(:num)', 'ProjectAnalizerTimesheetsController::fetchOne/$1/$2');
	$routes->post('projectanalizer/timesheets/(:num)', 'ProjectAnalizerTimesheetsController::store/$1');
	$routes->put('projectanalizer/timesheets/(:num)/(:num)', 'ProjectAnalizerTimesheetsController::modify/$1/$2');
	$routes->patch('projectanalizer/timesheets/(:num)/(:num)', 'ProjectAnalizerTimesheetsController::modify/$1/$2');
	$routes->delete('projectanalizer/timesheets/(:num)/(:num)', 'ProjectAnalizerTimesheetsController::remove/$1/$2');
	$routes->match(['GET', 'POST'], 'projectanalizer/team-activities', 'ProjectAnalizerController::teamActivities');
	$routes->match(['GET', 'POST'], 'projectanalizer/timelogs', 'ProjectAnalizerController::timelogs');
	$routes->get('projectanalizer/timelogs/(:num)/photos', 'ProjectAnalizerController::timelogPhotos/$1');
	$routes->match(['GET', 'POST'], 'projectanalizer/execution-schedules', 'ProjectAnalizerController::executionSchedules');
	$routes->match(['GET', 'POST'], 'projectanalizer/execution-schedules/(:num)', 'ProjectAnalizerController::executionSchedules/$1');
	$routes->delete('projectanalizer/execution-schedules/(:num)', 'ProjectAnalizerController::deleteExecutionSchedule/$1');
	$routes->get('projectanalizer/endpoints', 'ProjectAnalizerController::endpoints');
	$routes->get('proposals', 'ProposalsController::index');
	$routes->post('proposals', 'ProposalsController::store');
	$routes->get('proposals/(:num)', 'ProposalsController::show/$1');
	$routes->post('proposals/(:num)', 'ProposalsController::store/$1');
	$routes->delete('proposals/(:num)', 'ProposalsController::delete/$1');
	$routes->match(['GET', 'POST'], 'proposals/(:num)/sections', 'ProposalsController::sections/$1');
	$routes->post('proposals/(:num)/sections/save', 'ProposalsController::saveSection/$1');
	$routes->delete('proposals/sections/(:num)', 'ProposalsController::deleteSection/$1');
	$routes->match(['GET', 'POST'], 'proposals/(:num)/items', 'ProposalsController::items/$1');
	$routes->post('proposals/(:num)/items/save', 'ProposalsController::saveItem/$1');
	$routes->delete('proposals/items/(:num)', 'ProposalsController::deleteItem/$1');
	$routes->get('proposals/(:num)/dashboard', 'ProposalsController::dashboard/$1');
	$routes->get('proposals/(:num)/tasks', 'ProposalsController::tasks/$1');
	$routes->get('proposals/(:num)/reminders', 'ProposalsController::reminders/$1');
	$routes->post('proposals/(:num)/approve', 'ProposalsController::approve/$1');
	$routes->post('proposals/(:num)/duplicate', 'ProposalsController::duplicate/$1');
	$routes->get('proposals/products', 'ProposalsController::products');
	$routes->post('proposals/products', 'ProposalsController::saveProduct');
	$routes->post('proposals/products/(:num)', 'ProposalsController::saveProduct/$1');
	$routes->delete('proposals/products/(:num)', 'ProposalsController::deleteProduct/$1');
	$routes->get('proposals/settings', 'ProposalsController::settings');
	$routes->post('proposals/settings', 'ProposalsController::saveSettings');
	$routes->get('travelrefunds/dashboard', 'TravelRefundsController::dashboard');
	$routes->match(['GET', 'POST'], 'travelrefunds/trips', 'TravelRefundsController::trips');
	$routes->match(['GET', 'POST'], 'travelrefunds/trips/(:num)', 'TravelRefundsController::trips/$1');
	$routes->post('travelrefunds/trips/save', 'TravelRefundsController::saveTrip');
	$routes->post('travelrefunds/trips/save/(:num)', 'TravelRefundsController::saveTrip/$1');
	$routes->delete('travelrefunds/trips/(:num)', 'TravelRefundsController::deleteTrip/$1');
	$routes->match(['GET', 'POST'], 'travelrefunds/trips/(:num)/expenses', 'TravelRefundsController::expenses/$1');
	$routes->match(['GET', 'POST'], 'travelrefunds/trips/(:num)/expenses/(:num)', 'TravelRefundsController::expenses/$1/$2');
	$routes->post('travelrefunds/trips/(:num)/expenses/save', 'TravelRefundsController::saveExpense/$1');
	$routes->post('travelrefunds/trips/(:num)/expenses/save/(:num)', 'TravelRefundsController::saveExpense/$1/$2');
	$routes->delete('travelrefunds/trips/(:num)/expenses/(:num)', 'TravelRefundsController::deleteExpense/$1/$2');
	$routes->match(['GET', 'POST'], 'travelrefunds/reimbursements', 'TravelRefundsController::reimbursements');
	$routes->match(['GET', 'POST'], 'travelrefunds/reimbursements/(:num)', 'TravelRefundsController::reimbursements/$1');
	$routes->post('travelrefunds/reimbursements/save', 'TravelRefundsController::saveReimbursement');
	$routes->post('travelrefunds/reimbursements/save/(:num)', 'TravelRefundsController::saveReimbursement/$1');
	$routes->delete('travelrefunds/reimbursements/(:num)', 'TravelRefundsController::deleteReimbursement/$1');
	$routes->match(['GET', 'POST'], 'travelrefunds/approvals', 'TravelRefundsController::approvals');
	$routes->match(['GET', 'POST'], 'travelrefunds/approvals/(:num)', 'TravelRefundsController::approvals/$1');
	$routes->post('travelrefunds/approvals/trip/approve/(:num)', 'TravelRefundsController::approveTrip/$1');
	$routes->post('travelrefunds/approvals/trip/reject/(:num)', 'TravelRefundsController::rejectTrip/$1');
	$routes->post('travelrefunds/approvals/expense/approve/(:num)/(:num)', 'TravelRefundsController::approveExpense/$1/$2');
	$routes->post('travelrefunds/approvals/expense/reject/(:num)/(:num)', 'TravelRefundsController::rejectExpense/$1/$2');
	$routes->match(['GET', 'POST'], 'travelrefunds/categories', 'TravelRefundsController::categories');
	$routes->match(['GET', 'POST'], 'travelrefunds/categories/(:num)', 'TravelRefundsController::categories/$1');
	$routes->post('travelrefunds/categories/save', 'TravelRefundsController::saveCategory');
	$routes->post('travelrefunds/categories/save/(:num)', 'TravelRefundsController::saveCategory/$1');
	$routes->delete('travelrefunds/categories/(:num)', 'TravelRefundsController::deleteCategory/$1');
	$routes->get('travelrefunds/settings', 'TravelRefundsController::settings');
	$routes->post('travelrefunds/settings', 'TravelRefundsController::saveSettings');
	$routes->get('travelrefunds/reports', 'TravelRefundsController::reports');
	$routes->get('travelrefunds/reports/export/(:segment)', 'TravelRefundsController::exportReport/$1');
	$routes->get('travelrefunds/reports/export-xlsx/(:segment)', 'TravelRefundsController::exportReportXlsx/$1');
	$routes->match(['GET', 'POST'], 'ged/documents', 'GedController::documents');
	$routes->match(['GET', 'POST'], 'ged/documents/(:num)', 'GedController::documents/$1');
	$routes->post('ged/documents/save', 'GedController::saveDocument');
	$routes->post('ged/documents/save/(:num)', 'GedController::saveDocument/$1');
	$routes->delete('ged/documents/(:num)', 'GedController::deleteDocument/$1');
	$routes->match(['GET', 'POST'], 'ged/document_types', 'GedController::documentTypes');
	$routes->match(['GET', 'POST'], 'ged/document_types/(:num)', 'GedController::documentTypes/$1');
	$routes->post('ged/document_types/save', 'GedController::saveDocumentType');
	$routes->post('ged/document_types/save/(:num)', 'GedController::saveDocumentType/$1');
	$routes->post('ged/document_types/toggle_status/(:num)', 'GedController::toggleDocumentTypeStatus/$1');
	$routes->delete('ged/document_types/(:num)', 'GedController::deleteDocumentType/$1');
	$routes->match(['GET', 'POST'], 'ged/suppliers', 'GedController::suppliers');
	$routes->match(['GET', 'POST'], 'ged/suppliers/(:num)', 'GedController::suppliers/$1');
	$routes->post('ged/suppliers/save', 'GedController::saveSupplier');
	$routes->post('ged/suppliers/save/(:num)', 'GedController::saveSupplier/$1');
	$routes->delete('ged/suppliers/(:num)', 'GedController::deleteSupplier/$1');
	$routes->match(['GET', 'POST'], 'ged/submissions', 'GedController::submissions');
	$routes->match(['GET', 'POST'], 'ged/submissions/(:num)', 'GedController::submissions/$1');
	$routes->post('ged/submissions/save', 'GedController::saveSubmission');
	$routes->post('ged/submissions/save/(:num)', 'GedController::saveSubmission/$1');
	$routes->delete('ged/submissions/(:num)', 'GedController::deleteSubmission/$1');
	$routes->get('ged/settings', 'GedController::settings');
	$routes->post('ged/settings', 'GedController::saveSettings');
	$routes->get('ged/reports', 'GedController::reports');
	$routes->post('ged/reports', 'GedController::reports');
	$routes->get('ged/notifications/run', 'GedController::notificationsRun');
	$routes->post('ged/notifications/run', 'GedController::notificationsRun');
	$routes->match(['GET', 'POST'], 'organizador/tasks', 'OrganizadorController::tasks');
	$routes->match(['GET', 'POST'], 'organizador/tasks/(:num)', 'OrganizadorController::tasks/$1');
	$routes->post('organizador/tasks/save', 'OrganizadorController::saveTask');
	$routes->post('organizador/tasks/save/(:num)', 'OrganizadorController::saveTask/$1');
	$routes->delete('organizador/tasks/(:num)', 'OrganizadorController::deleteTask/$1');
	$routes->post('organizador/tasks/(:num)/duplicate', 'OrganizadorController::duplicateTask/$1');
	$routes->post('organizador/tasks/(:num)/complete', 'OrganizadorController::complete/$1');
	$routes->post('organizador/tasks/(:num)/favorite', 'OrganizadorController::toggleFavorite/$1');
	$routes->post('organizador/tasks/(:num)/status', 'OrganizadorController::updateStatus/$1');
	$routes->match(['GET', 'POST'], 'organizador/tasks/(:num)/comments', 'OrganizadorController::comments/$1');
	$routes->post('organizador/tasks/(:num)/comments/save', 'OrganizadorController::saveComment/$1');
	$routes->delete('organizador/tasks/comments/(:num)', 'OrganizadorController::deleteComment/$1');
	$routes->match(['GET', 'POST'], 'organizador/tasks/(:num)/reminders', 'OrganizadorController::reminders/$1');
	$routes->post('organizador/tasks/(:num)/reminders/save', 'OrganizadorController::saveReminder/$1');
	$routes->post('organizador/tasks/reminders/status/(:num)', 'OrganizadorController::updateReminderStatus/$1');
	$routes->delete('organizador/tasks/reminders/(:num)', 'OrganizadorController::deleteReminder/$1');
	$routes->get('organizador/kanban', 'OrganizadorController::kanban');
	$routes->get('organizador/kanban_data', 'OrganizadorController::kanbanData');
	$routes->get('organizador/calendar', 'OrganizadorController::calendar');
	$routes->get('organizador/calendar_data', 'OrganizadorController::calendarData');
	$routes->match(['GET', 'POST'], 'organizador/categories', 'OrganizadorController::categories');
	$routes->match(['GET', 'POST'], 'organizador/categories/(:num)', 'OrganizadorController::categories/$1');
	$routes->post('organizador/categories/save', 'OrganizadorController::saveCategory');
	$routes->post('organizador/categories/save/(:num)', 'OrganizadorController::saveCategory/$1');
	$routes->delete('organizador/categories/(:num)', 'OrganizadorController::deleteCategory/$1');
	$routes->match(['GET', 'POST'], 'organizador/tags', 'OrganizadorController::tags');
	$routes->match(['GET', 'POST'], 'organizador/tags/(:num)', 'OrganizadorController::tags/$1');
	$routes->post('organizador/tags/save', 'OrganizadorController::saveTag');
	$routes->post('organizador/tags/save/(:num)', 'OrganizadorController::saveTag/$1');
	$routes->delete('organizador/tags/(:num)', 'OrganizadorController::deleteTag/$1');
	$routes->match(['GET', 'POST'], 'organizador/phases', 'OrganizadorController::phases');
	$routes->match(['GET', 'POST'], 'organizador/phases/(:num)', 'OrganizadorController::phases/$1');
	$routes->post('organizador/phases/save', 'OrganizadorController::savePhase');
	$routes->post('organizador/phases/save/(:num)', 'OrganizadorController::savePhase/$1');
	$routes->delete('organizador/phases/(:num)', 'OrganizadorController::deletePhase/$1');
	$routes->get('organizador/settings', 'OrganizadorController::settings');
	$routes->post('organizador/settings', 'OrganizadorController::saveSettings');
	$routes->match(['GET', 'POST'], 'organizador/dashboard', 'OrganizadorController::dashboard');
	$routes->get('contaazul/endpoints', 'ContaAzulController::endpoints');
	$routes->get('contaazul/query/(:segment)', 'ContaAzulController::query/$1');
});

$routes->group('api', $rest_api_namespace, function ($routes) {
	$routes->get('leads', 'LeadsController::index'); //get
	$routes->get('leads/(:segment)', 'LeadsController::show/$1'); //get by id
	$routes->get('leads/search/(:segment)', 'LeadsController::search/$1'); //get search
	$routes->post('leads', 'LeadsController::create');
	$routes->put('leads/(:segment)', 'LeadsController::update/$1'); //update
	$routes->patch('leads/(:segment)', 'LeadsController::update/$1'); //update
	$routes->delete('leads/(:segment)', 'LeadsController::delete/$1'); //delete

	$routes->get('clients', 'ClientsController::index'); //get
	$routes->get('clients/(:segment)', 'ClientsController::show/$1'); //get by id
	$routes->get('clients/search/(:segment)', 'ClientsController::search/$1'); //get search
	$routes->post('clients', 'ClientsController::create');
	$routes->put('clients/(:segment)', 'ClientsController::update/$1'); //update
	$routes->patch('clients/(:segment)', 'ClientsController::update/$1'); //update
	$routes->delete('clients/(:segment)', 'ClientsController::delete/$1'); //delete

	$routes->get('projects', 'ProjectsController::index'); //get
	$routes->get('projects/(:segment)', 'ProjectsController::show/$1'); //get by id
	$routes->get('projects/search/(:segment)', 'ProjectsController::search/$1'); //get search
	$routes->post('projects', 'ProjectsController::create');
	$routes->put('projects/(:segment)', 'ProjectsController::update/$1'); //update
	$routes->patch('projects/(:segment)', 'ProjectsController::update/$1'); //update
	$routes->delete('projects/(:segment)', 'ProjectsController::delete/$1'); //delete

	$routes->get('tickets', 'TicketsController::index'); //get
	$routes->get('tickets/(:segment)', 'TicketsController::show/$1'); //get by id
	$routes->get('tickets/search/(:segment)', 'TicketsController::search/$1'); //get search
	$routes->post('tickets', 'TicketsController::create');
	$routes->put('tickets/(:segment)', 'TicketsController::update/$1'); //update
	$routes->patch('tickets/(:segment)', 'TicketsController::update/$1'); //update
	$routes->delete('tickets/(:segment)', 'TicketsController::delete/$1'); //delete

	$routes->get('invoices', 'InvoicesController::index'); //get
	$routes->get('invoices/(:segment)', 'InvoicesController::show/$1'); //get by id
	$routes->get('invoices/search/(:segment)', 'InvoicesController::search/$1'); //get search
	$routes->post('invoices', 'InvoicesController::create');
	$routes->put('invoices/(:segment)', 'InvoicesController::update/$1'); //update
	$routes->patch('invoices/(:segment)', 'InvoicesController::update/$1'); //update
	$routes->delete('invoices/(:segment)', 'InvoicesController::delete/$1'); //delete
});

$routes->get('api/resources', 'ResourcesController::resources', $rest_api_namespace);
$routes->get('api/resources/plugins', 'ResourcesController::pluginResources', $rest_api_namespace);
$routes->get('api/resources/plugins/(:segment)', 'ResourcesController::describePlugin/$1', $rest_api_namespace);
$routes->get('api/resources/(:segment)', 'ResourcesController::describe/$1', $rest_api_namespace);
$routes->get('api/(:segment)', 'ResourcesController::listResource/$1', $rest_api_namespace);
$routes->get('api/(:segment)/(:segment)', 'ResourcesController::showResource/$1/$2', $rest_api_namespace);
$routes->post('api/(:segment)', 'ResourcesController::createResource/$1', $rest_api_namespace);
$routes->put('api/(:segment)/(:segment)', 'ResourcesController::updateResource/$1/$2', $rest_api_namespace);
$routes->patch('api/(:segment)/(:segment)', 'ResourcesController::updateResource/$1/$2', $rest_api_namespace);
$routes->delete('api/(:segment)/(:segment)', 'ResourcesController::deleteResource/$1/$2', $rest_api_namespace);

//Override 404 and give response in JSON format
$routes->set404Override(function ($a) {
	header('Content-Type: application/json');
	echo json_encode([
				"status"  => false,
				"code"    => 404,
				"message" => "Route not found",
			], JSON_PRETTY_PRINT);
	die();
});
