<?php

namespace travelrefunds\Controllers;

use App\Controllers\Security_Controller;
use App\Models\Clients_model;
use App\Models\Projects_model;
use App\Models\Users_model;
use travelrefunds\Models\TravelRefundsApprovals_model;
use travelrefunds\Models\TravelRefundsCategories_model;
use travelrefunds\Models\TravelRefundsReimbursements_model;
use travelrefunds\Models\TravelRefundsSettings_model;
use travelrefunds\Models\TravelRefundsTrips_model;

class TravelRefunds extends Security_Controller
{
    protected TravelRefundsTrips_model $tripsModel;
    protected TravelRefundsReimbursements_model $reimbursementsModel;
    protected TravelRefundsCategories_model $categoriesModel;
    protected TravelRefundsApprovals_model $approvalsModel;
    protected TravelRefundsSettings_model $settingsModel;
    protected Users_model $usersModel;
    protected Projects_model $projectsModel;
    protected Clients_model $clientsModel;

    public function __construct()
    {
        parent::__construct();
        helper('travelrefunds');
        $this->tripsModel = model('travelrefunds\\Models\\TravelRefundsTrips_model');
        $this->reimbursementsModel = model('travelrefunds\\Models\\TravelRefundsReimbursements_model');
        $this->categoriesModel = model('travelrefunds\\Models\\TravelRefundsCategories_model');
        $this->approvalsModel = model('travelrefunds\\Models\\TravelRefundsApprovals_model');
        $this->settingsModel = model('travelrefunds\\Models\\TravelRefundsSettings_model');
        $this->usersModel = model('App\\Models\\Users_model');
        $this->projectsModel = model('App\\Models\\Projects_model');
        $this->clientsModel = model('App\\Models\\Clients_model');
    }

    protected function requirePermission(string $permission)
    {
        if ($this->login_user && $this->login_user->is_admin) {
            return true;
        }

        $allowed = get_array_value($this->login_user->permissions ?? array(), $permission) == '1';
        if (!$allowed) {
            show_404();
        }

        return true;
    }

    protected function getResponsibleEmployeesDropdown(): array
    {
        $dropdown = array('' => '-');
        $db = db_connect('default');
        $users = $db->table($db->prefixTable('users') . ' u')
            ->select('u.id, u.first_name, u.last_name')
            ->where('u.deleted', 0)
            ->where('u.user_type', 'staff')
            ->where('u.status', 'active')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResult();

        foreach ($users as $user) {
            $dropdown[$user->id] = trim($user->first_name . ' ' . $user->last_name);
        }

        return $dropdown;
    }

    protected function getActiveProjectsDropdown(): array
    {
        $dropdown = array('' => '-');
        $project_list = $this->projectsModel->get_details()->getResult();

        foreach ($project_list as $project) {
            $status_text = strtolower(trim((string) ($project->status_key_name ?: $project->status_title ?: '')));
            if (in_array($status_text, array('completed', 'cancelled', 'canceled', 'finalizado', 'finalizada', 'concluido', 'concluida'), true)) {
                continue;
            }

            $dropdown[$project->id] = $project->title;
        }

        return $dropdown;
    }

    public function index()
    {
        $this->requirePermission('travelrefunds_view');
        $filters = $this->getDashboardFilters();
        $trips = $this->tripsModel->get_details($filters)->getResult();
        $expenses = $this->reimbursementsModel->get_details($filters)->getResult();
        $approved_month_total = 0;
        $open_total = 0;
        $pending_trips = 0;
        $rejected_trips = 0;
        $spend_by_category = array();

        foreach ($trips as $trip) {
            if ($trip->status === 'submitted') {
                $pending_trips++;
                $open_total += (float) $trip->total_amount;
            } else if ($trip->status === 'rejected') {
                $rejected_trips++;
                $open_total += (float) $trip->total_amount;
            } else if ($trip->status === 'draft') {
                $open_total += (float) $trip->total_amount;
            }

            if ($trip->status === 'approved' && $trip->approved_at && date('Y-m', strtotime($trip->approved_at)) === date('Y-m')) {
                $approved_month_total += (float) $trip->approved_amount;
            }
        }

        $summary = array(
            'trips_total' => count($trips),
            'reimbursements_total' => count($expenses),
            'pending_total' => $pending_trips,
            'approved_total' => $approved_month_total,
            'spent_total' => 0,
            'open_total' => $open_total,
            'rejected_total' => $rejected_trips,
        );

        foreach ($expenses as $item) {
            $summary['spent_total'] += (float) $item->amount;
            $category = $item->category_name ?: $item->category_title ?: 'Sem categoria';
            if (!isset($spend_by_category[$category])) {
                $spend_by_category[$category] = 0;
            }
            $spend_by_category[$category] += (float) $item->amount;
        }

        arsort($spend_by_category);

        return $this->template->rander('travelrefunds\\Views\\dashboard\\index', array(
            'summary' => $summary,
            'filters' => $this->normalizeFilterValues($filters),
            'spend_by_category' => $spend_by_category,
            'recent_trips' => array_slice($trips, 0, 5),
            'recent_reimbursements' => array_slice($expenses, 0, 5),
            'users' => $this->getFilterUsers(),
            'projects' => $this->projectsModel->get_details()->getResult(),
            'clients' => $this->clientsModel->get_all_where(array('deleted' => 0), 1000000, 0, 'company_name', 'id, company_name')->getResult(),
            'categories' => $this->categoriesModel->get_details()->getResult(),
            'status_options' => array('draft', 'submitted', 'approved', 'rejected', 'closed'),
        ));
    }

    public function trips()
    {
        $this->requirePermission('travelrefunds_view');

        return $this->template->rander('travelrefunds\\Views\\trips\\index', array(
            'can_edit' => $this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_edit') == '1',
            'can_delete' => $this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_delete') == '1',
            'can_create' => $this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_create') == '1',
        ));
    }

    public function list_data()
    {
        $this->requirePermission('travelrefunds_view');

        $trips = $this->tripsModel->get_details(array(
            'employee_id' => $this->login_user->is_admin ? 0 : $this->login_user->id,
        ))->getResult();
        $traveler_names = $this->buildTravelerNameMap();
        $result = array();

        foreach ($trips as $trip) {
            $result[] = $this->makeTripRow($trip, $traveler_names);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modalTripForm($id = 0)
    {
        $id = (int) $id;
        if (!$id) {
            $id = (int) $this->request->getPost('id');
        }

        if ($id) {
            $this->requirePermission('travelrefunds_edit');
        } else {
            $this->requirePermission('travelrefunds_create');
        }

        $trip = $id ? $this->tripsModel->get_one($id) : null;
        if ($id && (!$trip || !$trip->id)) {
            show_404();
        }

        $default_trip = (object) array(
            'id' => 0,
            'title' => '',
            'employee_id' => $this->login_user->id,
            'project_id' => 0,
            'client_id' => 0,
            'destination' => '',
            'start_date' => '',
            'end_date' => '',
            'purpose' => '',
            'notes' => '',
            'status' => 'draft',
            'total_amount' => 0,
            'approved_amount' => 0,
            'traveler_ids' => '',
        );

        $project_dropdown = array('' => '-');
        $client_dropdown = array('' => '-');
        $active_clients = array();
        $project_list = $this->projectsModel->get_details()->getResult();

        foreach ($project_list as $project) {
            $status_text = strtolower(trim((string) ($project->status_key_name ?: $project->status_title ?: '')));
            if ((int) ($trip->project_id ?? 0) !== (int) $project->id && in_array($status_text, array('completed', 'cancelled', 'canceled', 'finalizado', 'finalizada', 'concluido', 'concluida'), true)) {
                continue;
            }

            $project_dropdown[$project->id] = $project->title;
            if (!empty($project->client_id)) {
                $active_clients[(int) $project->client_id] = true;
            }
        }

        foreach ($this->clientsModel->get_all_where(array('deleted' => 0), 1000000, 0, 'company_name', 'id, company_name')->getResult() as $client) {
            if ((int) ($trip->client_id ?? 0) !== (int) $client->id && !isset($active_clients[(int) $client->id])) {
                continue;
            }

            $client_dropdown[$client->id] = $client->company_name;
        }

        $travelers_dropdown = array();
        $travelers = db_connect('default')->table(db_connect('default')->prefixTable('users') . ' u')
            ->select('u.id, u.first_name, u.last_name')
            ->where('u.deleted', 0)
            ->where('u.user_type', 'staff')
            ->where('u.status', 'active')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResult();
        foreach ($travelers as $traveler) {
            $travelers_dropdown[$traveler->id] = trim($traveler->first_name . ' ' . $traveler->last_name);
        }

        return $this->template->view('travelrefunds\\Views\\trips\\modal_form', array(
            'model_info' => $trip ?: $default_trip,
            'project_dropdown' => $project_dropdown,
            'client_dropdown' => $client_dropdown,
            'responsible_employee_dropdown' => $this->getResponsibleEmployeesDropdown(),
            'travelers_dropdown' => $travelers_dropdown,
            'selected_traveler_ids' => $this->parseSelectedIds(($trip->traveler_ids ?? '')),
            'can_edit_trip' => !$trip || in_array($trip->status, array('draft', 'rejected'), true),
        ));
    }

    public function viewTrip($id = 0)
    {
        if ($id) {
            $this->requirePermission('travelrefunds_view');
        } else {
            $this->requirePermission('travelrefunds_create');
        }

        $trip = $id ? $this->tripsModel->get_one((int) $id) : null;
        if ($id && !$trip->id) {
            show_404();
        }

        $expense_edit_id = (int) $this->request->getGet('expense_edit_id');
        $expense_edit = $expense_edit_id ? $this->reimbursementsModel->get_one($expense_edit_id) : null;
        if ($expense_edit && $expense_edit->trip_id != $id) {
            $expense_edit = null;
        }

        $db = db_connect('default');
        $users = $db->table($db->prefixTable('users') . ' u')
            ->select('u.id, u.first_name, u.last_name')
            ->where('u.deleted', 0)
            ->where('u.user_type', 'staff')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResult();
        $projects = $this->projectsModel->get_details()->getResult();
        $clients = $this->clientsModel->get_all_where(array('deleted' => 0), 1000000, 0, 'company_name', 'id, company_name')->getResult();
        $categories = $this->categoriesModel->get_details(array('active' => 1))->getResult();
        $expenses = $id ? $this->reimbursementsModel->get_details(array('trip_id' => $id))->getResult() : array();

        $summary = $this->buildExpenseSummary($expenses);
        $trip_summary = $this->buildTripSummary($trip, $expenses);
        $can_edit_expenses = !$trip || in_array($trip->status, array('draft', 'rejected'), true);

        return $this->template->rander('travelrefunds\\Views\\trips\\view', array(
            'trip' => $trip,
            'trip_edit' => $trip,
            'expense_edit' => $expense_edit,
            'projects' => $projects,
            'clients' => $clients,
            'categories' => $categories,
            'responsible_employee_dropdown' => $this->getResponsibleEmployeesDropdown(),
            'expenses' => $expenses,
            'expense_summary' => $summary,
            'trip_summary' => $trip_summary,
            'can_edit_expenses' => $can_edit_expenses,
            'can_edit_trip' => !$trip || in_array($trip->status, array('draft', 'rejected'), true),
            'status_options' => array('draft', 'submitted', 'approved', 'rejected', 'closed'),
            'payment_methods' => array('Dinheiro', 'Cartao', 'PIX', 'Transferencia', 'Boleto', 'Outro'),
        ));
    }

    public function saveTrip()
    {
        $this->requirePermission($this->request->getPost('id') ? 'travelrefunds_edit' : 'travelrefunds_create');

        $id = (int) $this->request->getPost('id');
        $existing = $id ? $this->tripsModel->get_one($id) : null;
        $save_action = $this->request->getPost('save_action') ?: 'draft';
        $data = array(
            'title' => trim((string) $this->request->getPost('title')),
            'employee_id' => (int) $this->request->getPost('employee_id'),
            'project_id' => (int) $this->request->getPost('project_id'),
            'client_id' => (int) $this->request->getPost('client_id'),
            'destination' => trim((string) $this->request->getPost('destination')),
            'purpose' => trim((string) $this->request->getPost('purpose')),
            'start_date' => $this->request->getPost('start_date') ?: $this->request->getPost('departure_date'),
            'end_date' => $this->request->getPost('end_date') ?: $this->request->getPost('return_date'),
            'traveler_ids' => implode(',', array_filter(array_map('intval', (array) $this->request->getPost('traveler_ids')))),
            'status' => $save_action === 'submit' ? 'submitted' : ($existing && $existing->status ? $existing->status : 'draft'),
            'total_amount' => (float) unformat_currency($this->request->getPost('total_amount') ?: $this->request->getPost('estimated_amount')),
            'approved_amount' => (float) unformat_currency($this->request->getPost('approved_amount') ?: $this->request->getPost('actual_amount')),
            'notes' => trim((string) $this->request->getPost('notes')),
            'departure_date' => $this->request->getPost('departure_date'),
            'return_date' => $this->request->getPost('return_date'),
            'estimated_amount' => (float) unformat_currency($this->request->getPost('total_amount') ?: $this->request->getPost('estimated_amount')),
            'actual_amount' => (float) unformat_currency($this->request->getPost('actual_amount')),
        );

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            if (!$data['employee_id']) {
                $data['employee_id'] = $this->login_user->id;
            }
        }

        if (!$data['title']) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(array(
                    'success' => false,
                    'message' => 'Titulo da viagem e obrigatorio.'
                ));
            }

            $this->session->setFlashdata('error_message', 'Titulo da viagem e obrigatorio.');
            return redirect()->back();
        }

        $result = $this->tripsModel->ci_save($data, $id ?: null);
        $success_message = $result ? 'Registro salvo com sucesso.' : 'Nao foi possivel salvar.';

        if ($this->request->isAJAX()) {
            if ($result) {
                return $this->response->setJSON(array(
                    'success' => true,
                    'id' => $result,
                    'message' => $success_message,
                    'redirect' => get_uri('travelrefunds/trips/view/' . $result)
                ));
            }

            return $this->response->setJSON(array(
                'success' => false,
                'message' => $success_message
            ));
        }

        $this->session->setFlashdata('success_message', $success_message);
        if ($result) {
            return redirect()->to(get_uri('travelrefunds/trips/view/' . $result));
        }

        return redirect()->back();
    }

    public function deleteTrip($id = 0)
    {
        $this->requirePermission('travelrefunds_delete');
        $id = (int) ($id ?: $this->request->getPost('id'));

        if (!$id) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'ID invalido.'
            ));
        }

        $ok = $this->tripsModel->delete($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(array(
                'success' => $ok ? true : false,
                'message' => $ok ? 'Registro excluido.' : 'Nao foi possivel excluir o registro.'
            ));
        }

        $this->session->setFlashdata($ok ? 'success_message' : 'error_message', $ok ? 'Registro excluido.' : 'Nao foi possivel excluir o registro.');
        return redirect()->to(get_uri('travelrefunds/trips'));
    }

    public function reimbursements()
    {
        $this->requirePermission('travelrefunds_view');
        return $this->template->rander('travelrefunds\\Views\\reimbursements\\index', array(
            'can_create' => $this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_create') == '1',
            'can_edit' => $this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_edit') == '1',
            'can_delete' => $this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_delete') == '1',
        ));
    }

    public function reimbursementsListData()
    {
        $this->requirePermission('travelrefunds_view');

        $reimbursements = $this->reimbursementsModel->get_details(array(
            'employee_id' => $this->login_user->is_admin ? 0 : $this->login_user->id,
        ))->getResult();

        $rows = array();
        foreach ($reimbursements as $item) {
            $rows[] = $this->makeReimbursementRow($item);
        }

        return $this->response->setJSON(array('data' => $rows));
    }

    public function modalReimbursementForm($id = 0)
    {
        $id = (int) $id;
        if (!$id) {
            $id = (int) $this->request->getPost('id');
        }

        if ($id) {
            $this->requirePermission('travelrefunds_edit');
        } else {
            $this->requirePermission('travelrefunds_create');
        }

        $reimbursement = $id ? $this->reimbursementsModel->get_one($id) : null;
        if ($id && (!$reimbursement || !$reimbursement->id)) {
            show_404();
        }

        $db = db_connect('default');
        $users = $db->table($db->prefixTable('users') . ' u')
            ->select('u.id, u.first_name, u.last_name')
            ->where('u.deleted', 0)
            ->where('u.user_type', 'staff')
            ->where('u.status', 'active')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResult();

        $trips = $this->tripsModel->get_details()->getResult();
        $categories = $this->categoriesModel->get_details()->getResult();
        $trip_project_map = array();
        foreach ($trips as $trip_item) {
            $trip_project_map[$trip_item->id] = isset($trip_item->project_id) ? $trip_item->project_id : 0;
        }

        $selected_trip_id = (int) ($reimbursement->trip_id ?? 0);
        $selected_project_id = (int) ($reimbursement->project_id ?? 0);
        if (!$selected_project_id && $selected_trip_id && !empty($trip_project_map[$selected_trip_id])) {
            $selected_project_id = (int) $trip_project_map[$selected_trip_id];
        }

        $attachment_files = '';
        $attachment_id_value = (int) ($reimbursement->attachment_id ?? 0);
        if ($attachment_id_value) {
            $attachment_model = model('App\\Models\\General_files_model');
            $attachment_file_info = $attachment_model->get_one($attachment_id_value);
            if ($attachment_file_info && !empty($attachment_file_info->id)) {
                $attachment_files = serialize(array(make_array_of_file($attachment_file_info)));
            }
        }

        return $this->template->view('travelrefunds\\Views\\reimbursements\\modal_form', array(
            'model_info' => $reimbursement ?: (object) array(
                'id' => 0,
                'trip_id' => 0,
                'employee_id' => $this->login_user->id,
                'category_id' => 0,
                'expense_date' => get_my_local_time('Y-m-d'),
                'amount' => 0,
                'status' => 'pending',
                'payment_method' => '',
                'description' => '',
                'notes' => '',
                'vendor' => '',
                'receipt_number' => '',
                'receipt_file' => '',
                'has_invoice' => 0,
                'project_id' => 0,
            ),
            'selected_trip_id' => $selected_trip_id,
            'selected_project_id' => $selected_project_id,
            'selected_attachment_files' => $attachment_files,
            'projects_dropdown' => $this->getActiveProjectsDropdown(),
            'trips' => $trips,
            'trip_project_map' => $trip_project_map,
            'users' => $users,
            'categories' => $categories,
            'status_options' => array('pending', 'approved', 'rejected', 'paid'),
            'payment_methods' => array('Dinheiro', 'Cartao', 'PIX', 'Transferencia', 'Boleto', 'Outro'),
            'can_edit' => true,
        ));
    }

    public function saveReimbursement()
    {
        $this->requirePermission($this->request->getPost('id') ? 'travelrefunds_edit' : 'travelrefunds_create');

        $id = (int) $this->request->getPost('id');
        $existing = $id ? $this->reimbursementsModel->get_one($id) : null;
        $attachment_id = (int) $this->request->getPost('attachment_id') ?: (int) ($existing->attachment_id ?? 0);
        $receipt_file = trim((string) $this->request->getPost('receipt_file')) ?: trim((string) ($existing->receipt_file ?? ''));
        $trip_id = (int) $this->request->getPost('trip_id');
        $trip = $trip_id ? $this->tripsModel->get_one($trip_id) : null;
        $db = db_connect('default');
        $expense_fields = $db->getFieldNames($db->prefixTable('travelrefunds_expenses'));
        $has_project_id = in_array('project_id', $expense_fields, true);
        $uploaded_files = move_files_from_temp_dir_to_permanent_dir('files/travelrefunds/reimbursements/', 'travelrefunds');
        if ($uploaded_files) {
            $uploaded_list = @unserialize($uploaded_files);
            if (is_array($uploaded_list) && get_array_value($uploaded_list, 0)) {
                $uploaded_file = get_array_value($uploaded_list, 0);
                $uploaded_file_id = (int) get_array_value($uploaded_file, 'file_id');
                if ($uploaded_file_id) {
                    $attachment_id = $uploaded_file_id;
                } else if (!$receipt_file) {
                    $receipt_file = trim((string) get_array_value($uploaded_file, 'file_name'));
                }
            }
        }

        $data = array(
            'trip_id' => $trip_id,
            'employee_id' => (int) $this->request->getPost('employee_id'),
            'category_id' => (int) $this->request->getPost('category_id'),
            'expense_date' => $this->request->getPost('expense_date'),
            'amount' => (float) unformat_currency($this->request->getPost('amount')),
            'description' => trim((string) $this->request->getPost('description')),
            'payment_method' => trim((string) $this->request->getPost('payment_method')),
            'has_invoice' => $this->request->getPost('has_invoice') ? 1 : (($this->request->getPost('receipt_number') || $this->request->getPost('receipt_file')) ? 1 : 0),
            'invoice_number' => trim((string) $this->request->getPost('invoice_number')) ?: trim((string) $this->request->getPost('receipt_number')),
            'supplier_name' => trim((string) $this->request->getPost('supplier_name')) ?: trim((string) $this->request->getPost('vendor')),
            'attachment_id' => $attachment_id ?: null,
            'receipt_file' => $receipt_file ?: null,
            'status' => trim((string) $this->request->getPost('status')) ?: 'pending',
            'rejection_reason' => trim((string) $this->request->getPost('rejection_reason')),
            'notes' => trim((string) $this->request->getPost('notes')),
            'vendor' => trim((string) $this->request->getPost('vendor')),
            'receipt_number' => trim((string) $this->request->getPost('receipt_number')),
            'receipt_file' => trim((string) $this->request->getPost('receipt_file')),
        );

        if ($has_project_id) {
            $data['project_id'] = (int) $this->request->getPost('project_id') ?: (int) ($trip->project_id ?? 0);
        }

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
        }

        if (!$data['amount']) {
            $this->session->setFlashdata('error_message', 'Valor e obrigatorio.');
            return redirect()->back();
        }

        $has_receipt = $this->hasExpenseReceiptEvidence($data);
        if (!$this->isExpenseReceiptAllowed((int) $data['category_id'], $has_receipt)) {
            $message = 'Esta categoria exige comprovante.';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(array('success' => false, 'message' => $message));
            }

            $this->session->setFlashdata('error_message', $message);
            return redirect()->to(get_uri('travelrefunds/reimbursements'));
        }

        $result = $this->reimbursementsModel->ci_save($data, $id ?: null);
        $success_message = $result ? 'Registro salvo com sucesso.' : 'Nao foi possivel salvar.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(array(
                'success' => (bool) $result,
                'message' => $success_message,
                'redirect' => get_uri('travelrefunds/reimbursements')
            ));
        }

        $this->session->setFlashdata($result ? 'success_message' : 'error_message', $success_message);
        return redirect()->to(get_uri('travelrefunds/reimbursements'));
    }

    public function saveExpense($trip_id)
    {
        $this->requirePermission($this->request->getPost('id') ? 'travelrefunds_edit' : 'travelrefunds_create');

        $trip = $this->tripsModel->get_one((int) $trip_id);
        if (!$trip || !$trip->id) {
            show_404();
        }

        if (!in_array($trip->status, array('draft', 'rejected'), true)) {
            $this->session->setFlashdata('error_message', 'Despesas so podem ser alteradas quando a viagem estiver em rascunho ou rejeitada.');
            return redirect()->to(get_uri('travelrefunds/trips/view/' . $trip_id));
        }

        $id = (int) $this->request->getPost('id');
        $attachment_id = (int) $this->request->getPost('attachment_id');
        $receipt_file = trim((string) $this->request->getPost('receipt_file'));
        $uploaded_files = move_files_from_temp_dir_to_permanent_dir('files/travelrefunds/' . $trip_id . '/', 'travelrefunds');
        if ($uploaded_files) {
            $uploaded_list = @unserialize($uploaded_files);
            if (is_array($uploaded_list) && get_array_value($uploaded_list, 0)) {
                $uploaded_file = get_array_value($uploaded_list, 0);
                $uploaded_file_id = (int) get_array_value($uploaded_file, 'file_id');
                if ($uploaded_file_id) {
                    $attachment_id = $uploaded_file_id;
                } else if (!$receipt_file) {
                    $receipt_file = trim((string) get_array_value($uploaded_file, 'file_name'));
                }
            }
        }

        $data = array(
            'trip_id' => (int) $trip_id,
            'employee_id' => (int) ($trip->employee_id ?: $this->login_user->id),
            'category_id' => (int) $this->request->getPost('category_id'),
            'expense_date' => $this->request->getPost('expense_date'),
            'description' => trim((string) $this->request->getPost('description')),
            'amount' => (float) $this->request->getPost('amount'),
            'payment_method' => trim((string) $this->request->getPost('payment_method')),
            'has_invoice' => $this->request->getPost('has_invoice') ? 1 : (($this->request->getPost('invoice_number')) ? 1 : 0),
            'invoice_number' => trim((string) $this->request->getPost('invoice_number')),
            'supplier_name' => trim((string) $this->request->getPost('supplier_name')),
            'attachment_id' => $attachment_id ?: null,
            'receipt_file' => $receipt_file ?: null,
            'status' => trim((string) $this->request->getPost('status')) ?: 'pending',
            'rejection_reason' => trim((string) $this->request->getPost('rejection_reason')),
            'notes' => trim((string) $this->request->getPost('notes')),
        );

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
        }

        if (!$data['category_id'] || !$data['description'] || !$data['amount']) {
            $this->session->setFlashdata('error_message', 'Categoria, descricao e valor sao obrigatorios.');
            return redirect()->to(get_uri('travelrefunds/trips/view/' . $trip_id));
        }

        $has_receipt = $this->hasExpenseReceiptEvidence($data);
        if (!$this->isExpenseReceiptAllowed((int) $data['category_id'], $has_receipt)) {
            $this->session->setFlashdata('error_message', 'Esta categoria exige comprovante.');
            return redirect()->to(get_uri('travelrefunds/trips/view/' . $trip_id));
        }

        $result = $this->reimbursementsModel->ci_save($data, $id ?: null);
        if ($result) {
            $this->recalculateTripTotals($trip_id);
            $this->session->setFlashdata('success_message', 'Despesa salva com sucesso.');
        } else {
            $this->session->setFlashdata('error_message', 'Nao foi possivel salvar a despesa.');
        }

        return redirect()->to(get_uri('travelrefunds/trips/view/' . $trip_id));
    }

    public function deleteExpense($trip_id, $expense_id)
    {
        $this->requirePermission('travelrefunds_delete');

        $trip = $this->tripsModel->get_one((int) $trip_id);
        if (!$trip || !$trip->id) {
            show_404();
        }

        if (!in_array($trip->status, array('draft', 'rejected'), true)) {
            $this->session->setFlashdata('error_message', 'Despesas so podem ser alteradas quando a viagem estiver em rascunho ou rejeitada.');
            return redirect()->to(get_uri('travelrefunds/trips/view/' . $trip_id));
        }

        $this->reimbursementsModel->delete((int) $expense_id);
        $this->recalculateTripTotals($trip_id);
        $this->session->setFlashdata('success_message', 'Despesa excluida.');
        return redirect()->to(get_uri('travelrefunds/trips/view/' . $trip_id));
    }

    public function deleteReimbursement($id)
    {
        $this->requirePermission('travelrefunds_delete');
        $this->reimbursementsModel->delete((int) $id);
        $this->session->setFlashdata('success_message', 'Registro excluido.');
        return redirect()->to(get_uri('travelrefunds/reimbursements'));
    }

    public function approvals()
    {
        $this->requirePermission('travelrefunds_approve');
        $submitted_trips = $this->tripsModel->get_details(array('status' => 'submitted'))->getResult();
        $pending_trips = array();

        foreach ($submitted_trips as $trip) {
            $expenses = $this->reimbursementsModel->get_details(array('trip_id' => $trip->id))->getResult();
            $trip->approval_summary = $this->buildTripSummary($trip, $expenses);
            $pending_trips[] = $trip;
        }

        $logs = $this->approvalsModel->get_details()->getResult();

        return $this->template->rander('travelrefunds\\Views\\approvals\\index', array(
            'pending_trips' => $pending_trips,
            'logs' => $logs,
        ));
    }

    public function approvalView($id = 0)
    {
        $this->requirePermission('travelrefunds_approve');

        $trip = $id ? $this->tripsModel->get_details(array('id' => (int) $id))->getRow() : null;
        if (!$trip || !$trip->id) {
            show_404();
        }

        if (empty($trip->employee_name) && !empty($trip->employee_id)) {
            $employee = $this->usersModel->get_one((int) $trip->employee_id);
            if ($employee && $employee->id) {
                $trip->employee_name = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
            }
        }

        $expenses = $this->reimbursementsModel->get_details(array('trip_id' => $trip->id))->getResult();
        foreach ($expenses as $expense) {
            $expense->attachment_url = $this->buildExpenseAttachmentUrl($expense->attachment_id, $expense->receipt_file ?? '');
            $expense->category_label = $expense->category_name ?: $expense->category_title ?: '-';
        }

        return $this->template->rander('travelrefunds\\Views\\approvals\\view', array(
            'trip' => $trip,
            'expenses' => $expenses,
            'trip_summary' => $this->buildTripSummary($trip, $expenses),
            'expense_summary' => $this->buildExpenseSummary($expenses),
            'can_decide_trip' => $trip->status === 'submitted',
            'status_options' => array('draft', 'submitted', 'approved', 'rejected', 'closed'),
            'special_approval_limit' => (float) $this->settingsModel->get_setting('travelrefunds_special_approval_limit', '0'),
        ));
    }

    public function approveTrip($id)
    {
        $this->requirePermission('travelrefunds_approve');
        $this->processTripDecision((int) $id, 'approved');
        return redirect()->to(get_uri('travelrefunds/approvals'));
    }

    public function rejectTrip($id)
    {
        $this->requirePermission('travelrefunds_approve');
        $this->processTripDecision((int) $id, 'rejected');
        return redirect()->to(get_uri('travelrefunds/approvals'));
    }

    public function approveExpense($trip_id, $expense_id)
    {
        $this->requirePermission('travelrefunds_approve');
        $this->processExpenseDecision((int) $trip_id, (int) $expense_id, 'approved');
        return redirect()->to(get_uri('travelrefunds/approvals/view/' . (int) $trip_id));
    }

    public function rejectExpense($trip_id, $expense_id)
    {
        $this->requirePermission('travelrefunds_approve');
        $this->processExpenseDecision((int) $trip_id, (int) $expense_id, 'rejected');
        return redirect()->to(get_uri('travelrefunds/approvals/view/' . (int) $trip_id));
    }

    public function approve($id)
    {
        $this->requirePermission('travelrefunds_approve');
        $expense = $this->reimbursementsModel->get_one((int) $id);
        if ($expense && $expense->id) {
            $this->processExpenseDecision((int) $expense->trip_id, (int) $expense->id, 'approved');
        }
        return redirect()->to(get_uri('travelrefunds/approvals'));
    }

    public function reject($id)
    {
        $this->requirePermission('travelrefunds_approve');
        $expense = $this->reimbursementsModel->get_one((int) $id);
        if ($expense && $expense->id) {
            $this->processExpenseDecision((int) $expense->trip_id, (int) $expense->id, 'rejected');
        }
        return redirect()->to(get_uri('travelrefunds/approvals'));
    }

    public function categories()
    {
        $this->requirePermission('travelrefunds_manage_settings');
        $edit_id = (int) $this->request->getGet('edit_id');
        $edit_row = $edit_id ? $this->categoriesModel->get_one($edit_id) : null;
        $categories = $this->categoriesModel->get_details()->getResult();

        return $this->template->rander('travelrefunds\\Views\\categories\\index', array(
            'category_edit' => $edit_row,
            'categories' => $categories,
        ));
    }

    public function saveCategory()
    {
        $this->requirePermission('travelrefunds_manage_settings');

        $id = (int) $this->request->getPost('id');
        $data = array(
            'name' => trim((string) $this->request->getPost('name')) ?: trim((string) $this->request->getPost('title')),
            'title' => trim((string) $this->request->getPost('title')) ?: trim((string) $this->request->getPost('name')),
            'description' => trim((string) $this->request->getPost('description')),
            'requires_invoice' => $this->request->getPost('requires_invoice') ? 1 : 0,
            'active' => $this->request->getPost('active') ? 1 : ($this->request->getPost('is_active') ? 1 : 0),
            'is_active' => $this->request->getPost('is_active') ? 1 : ($this->request->getPost('active') ? 1 : 0),
            'sort_order' => (int) ($this->request->getPost('sort_order') ?: $this->request->getPost('sort')),
            'sort' => (int) $this->request->getPost('sort'),
        );

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
        }

        if (!$data['title']) {
            $this->session->setFlashdata('error_message', 'Titulo e obrigatorio.');
            return redirect()->back();
        }

        $result = $this->categoriesModel->ci_save($data, $id ?: null);
        $this->session->setFlashdata('success_message', $result ? 'Registro salvo com sucesso.' : 'Nao foi possivel salvar.');
        return redirect()->to(get_uri('travelrefunds/categories'));
    }

    public function deleteCategory($id)
    {
        $this->requirePermission('travelrefunds_manage_settings');
        $this->categoriesModel->delete((int) $id);
        $this->session->setFlashdata('success_message', 'Registro excluido.');
        return redirect()->to(get_uri('travelrefunds/categories'));
    }

    public function settings()
    {
        $this->requirePermission('travelrefunds_manage_settings');

        return $this->template->rander('travelrefunds\\Views\\settings\\index', array(
            'settings' => $this->loadSettings(),
            'users' => $this->getFilterUsers(),
            'selected_approver_ids' => $this->parseSelectedIds($this->settingsModel->get_setting('travelrefunds_default_approver_ids', '')),
        ));
    }

    public function saveSettings()
    {
        $this->requirePermission('travelrefunds_manage_settings');

        $settings = array(
            'travelrefunds_enabled' => $this->request->getPost('travelrefunds_enabled') ? '1' : '0',
            'travelrefunds_default_currency_symbol' => trim((string) $this->request->getPost('travelrefunds_default_currency_symbol')),
            'travelrefunds_allow_public_receipts' => $this->request->getPost('travelrefunds_allow_public_receipts') ? '1' : '0',
            'travelrefunds_allow_expenses_without_receipt' => $this->request->getPost('travelrefunds_allow_expenses_without_receipt') ? '1' : '0',
            'travelrefunds_default_approver_ids' => implode(',', array_filter(array_map('intval', (array) $this->request->getPost('default_approver_ids')))),
            'travelrefunds_special_approval_limit' => (float) ($this->request->getPost('travelrefunds_special_approval_limit') ?: 0),
        );

        foreach ($settings as $name => $value) {
            $this->settingsModel->save_setting($name, $value);
        }

        $this->session->setFlashdata('success_message', 'Configuracoes salvas com sucesso.');
        return redirect()->to(get_uri('travelrefunds/settings'));
    }

    protected function loadSettings(): array
    {
        return array(
            'travelrefunds_enabled' => $this->settingsModel->get_setting('travelrefunds_enabled', '1'),
            'travelrefunds_default_currency_symbol' => $this->settingsModel->get_setting('travelrefunds_default_currency_symbol', get_setting('currency_symbol') ?: '$'),
            'travelrefunds_allow_public_receipts' => $this->settingsModel->get_setting('travelrefunds_allow_public_receipts', '0'),
            'travelrefunds_allow_expenses_without_receipt' => $this->settingsModel->get_setting('travelrefunds_allow_expenses_without_receipt', '1'),
            'travelrefunds_default_approver_ids' => $this->settingsModel->get_setting('travelrefunds_default_approver_ids', ''),
            'travelrefunds_special_approval_limit' => $this->settingsModel->get_setting('travelrefunds_special_approval_limit', '0'),
        );
    }

    protected function getDashboardFilters(): array
    {
        $employee_id = (int) $this->request->getGet('employee_id');
        $project_id = (int) $this->request->getGet('project_id');
        $client_id = (int) $this->request->getGet('client_id');
        $category_id = (int) $this->request->getGet('category_id');
        $status = trim((string) $this->request->getGet('status'));
        $start_date = trim((string) $this->request->getGet('start_date'));
        $end_date = trim((string) $this->request->getGet('end_date'));

        if (!$this->login_user->is_admin) {
            $employee_id = $this->login_user->id;
        }

        $filters = array(
            'employee_id' => $employee_id,
            'project_id' => $project_id,
            'client_id' => $client_id,
            'category_id' => $category_id,
            'status' => $status,
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        return array_filter($filters, function ($value) {
            return $value !== null && $value !== '' && $value !== 0;
        });
    }

    protected function normalizeFilterValues(array $filters): array
    {
        return array(
            'employee_id' => get_array_value($filters, 'employee_id'),
            'project_id' => get_array_value($filters, 'project_id'),
            'client_id' => get_array_value($filters, 'client_id'),
            'category_id' => get_array_value($filters, 'category_id'),
            'status' => get_array_value($filters, 'status'),
            'start_date' => get_array_value($filters, 'start_date'),
            'end_date' => get_array_value($filters, 'end_date'),
        );
    }

    protected function getFilterUsers()
    {
        $db = db_connect('default');
        return $db->table($db->prefixTable('users') . ' u')
            ->select('u.id, u.first_name, u.last_name')
            ->where('u.deleted', 0)
            ->where('u.user_type', 'staff')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResult();
    }

    protected function buildReportData(array $filters): array
    {
        $trip_filters = $filters;
        $expense_filters = $filters;

        $trips = $this->tripsModel->get_details($trip_filters)->getResult();
        $expenses = $this->reimbursementsModel->get_details($expense_filters)->getResult();

        $employee_rows = array();
        $project_rows = array();
        $category_rows = array();
        $monthly_rows = array();

        foreach ($expenses as $expense) {
            $employee_key = $expense->employee_name ?: ('#' . (int) $expense->employee_id);
            if (!isset($employee_rows[$employee_key])) {
                $employee_rows[$employee_key] = array('label' => $employee_key, 'count' => 0, 'total' => 0);
            }
            $employee_rows[$employee_key]['count']++;
            $employee_rows[$employee_key]['total'] += (float) $expense->amount;

            $project_key = $expense->project_title ?: $expense->trip_title ?: ('#' . (int) $expense->trip_id);
            if (!isset($project_rows[$project_key])) {
                $project_rows[$project_key] = array('label' => $project_key, 'count' => 0, 'total' => 0);
            }
            $project_rows[$project_key]['count']++;
            $project_rows[$project_key]['total'] += (float) $expense->amount;

            $category_key = $expense->category_name ?: $expense->category_title ?: 'Sem categoria';
            if (!isset($category_rows[$category_key])) {
                $category_rows[$category_key] = array('label' => $category_key, 'count' => 0, 'total' => 0);
            }
            $category_rows[$category_key]['count']++;
            $category_rows[$category_key]['total'] += (float) $expense->amount;
        }

        foreach ($trips as $trip) {
            $month_key = $trip->approved_at ? date('Y-m', strtotime($trip->approved_at)) : 'sem-data';
            if (!isset($monthly_rows[$month_key])) {
                $monthly_rows[$month_key] = array('label' => $month_key, 'approved_total' => 0, 'open_total' => 0, 'submitted' => 0, 'rejected' => 0);
            }

            if ($trip->status === 'approved') {
                $monthly_rows[$month_key]['approved_total'] += (float) $trip->approved_amount;
            } else if (in_array($trip->status, array('draft', 'submitted', 'rejected'), true)) {
                $monthly_rows[$month_key]['open_total'] += (float) $trip->total_amount;
            }

            if ($trip->status === 'submitted') {
                $monthly_rows[$month_key]['submitted']++;
            }
            if ($trip->status === 'rejected') {
                $monthly_rows[$month_key]['rejected']++;
            }
        }

        uasort($employee_rows, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        uasort($project_rows, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        uasort($category_rows, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        krsort($monthly_rows);

        return array(
            'trips' => $trips,
            'expenses' => $expenses,
            'by_employee' => array_values($employee_rows),
            'by_project' => array_values($project_rows),
            'by_category' => array_values($category_rows),
            'monthly' => array_values($monthly_rows),
        );
    }

    protected function buildReportCsv(string $type, array $data): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($type === 'employee') {
            fputcsv($handle, array('Funcionario', 'Quantidade', 'Total'));
            foreach ($data['by_employee'] as $row) {
                fputcsv($handle, array($row['label'], $row['count'], number_format($row['total'], 2, '.', '')));
            }
        } elseif ($type === 'project') {
            fputcsv($handle, array('Projeto/Viagem', 'Quantidade', 'Total'));
            foreach ($data['by_project'] as $row) {
                fputcsv($handle, array($row['label'], $row['count'], number_format($row['total'], 2, '.', '')));
            }
        } elseif ($type === 'category') {
            fputcsv($handle, array('Categoria', 'Quantidade', 'Total'));
            foreach ($data['by_category'] as $row) {
                fputcsv($handle, array($row['label'], $row['count'], number_format($row['total'], 2, '.', '')));
            }
        } else {
            fputcsv($handle, array('Mes', 'Aprovado', 'Em aberto', 'Enviadas', 'Rejeitadas'));
            foreach ($data['monthly'] as $row) {
                fputcsv($handle, array(
                    $row['label'],
                    number_format($row['approved_total'], 2, '.', ''),
                    number_format($row['open_total'], 2, '.', ''),
                    $row['submitted'],
                    $row['rejected'],
                ));
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    protected function makeTripRow($trip, array $traveler_names = array()): array
    {
        $period = trim((string) (($trip->start_date ?: $trip->departure_date) ? ($trip->start_date ?: $trip->departure_date) : '-'));
        $period_end = trim((string) (($trip->end_date ?: $trip->return_date) ? ($trip->end_date ?: $trip->return_date) : '-'));
        $period_label = $period . ' a ' . $period_end;
        $project_label = trim((string) ($trip->project_title ?: '-'));

        $actions = anchor(get_uri('travelrefunds/trips/view/' . $trip->id), "<i data-feather='eye' class='icon-16'></i>", array(
            'class' => 'action-icon me-1',
            'title' => 'Abrir'
        ));

        if (in_array($trip->status, array('draft', 'rejected'), true) && ($this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_edit') == '1')) {
            $actions .= modal_anchor(get_uri('travelrefunds/trips/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                'class' => 'action-icon me-1',
                'title' => 'Editar viagem',
                'data-post-id' => $trip->id
            ));
        }

        if ($this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_delete') == '1') {
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                'title' => 'Excluir viagem',
                'class' => 'action-icon text-danger',
                'data-id' => $trip->id,
                'data-action-url' => get_uri('travelrefunds/trips/delete'),
                'data-action' => 'delete-confirmation',
                'data-reload-on-success' => '1'
            ));
        }

        return array(
            esc($trip->title),
            esc($trip->destination),
            esc($project_label),
            esc($period_label),
            travelrefunds_currency($trip->total_amount ?: $trip->estimated_amount),
            esc(travelrefunds_status_label($trip->status)),
            $actions,
        );
    }

    protected function makeReimbursementRow($item): array
    {
        $actions = '';
        if ($this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_edit') == '1') {
            $actions .= modal_anchor(get_uri('travelrefunds/reimbursements/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                'class' => 'action-icon me-1',
                'title' => 'Editar reembolso',
                'data-post-id' => $item->id
            ));
        }

        if ($this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_delete') == '1') {
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                'title' => 'Excluir reembolso',
                'class' => 'action-icon text-danger',
                'data-id' => $item->id,
                'data-action-url' => get_uri('travelrefunds/reimbursements/delete/' . $item->id),
                'data-action' => 'delete-confirmation',
                'data-reload-on-success' => '1'
            ));
        }

        return array(
            esc($item->description ?: '-'),
            esc($item->trip_title ?: '-'),
            esc($item->project_title ?: '-'),
            esc($item->employee_name ?: '-'),
            esc($item->category_title ?: '-'),
            travelrefunds_currency($item->amount ?: 0),
            esc(travelrefunds_status_label($item->status ?: 'pending')),
            $actions,
        );
    }

    protected function buildTravelerNameMap(): array
    {
        $db = db_connect('default');
        $users_table = $db->prefixTable('users');
        $rows = $db->table($users_table . ' u')
            ->select('u.id, u.first_name, u.last_name')
            ->where('u.deleted', 0)
            ->where('u.user_type', 'staff')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResult();

        $map = array();
        foreach ($rows as $row) {
            $map[(int) $row->id] = trim($row->first_name . ' ' . $row->last_name);
        }

        return $map;
    }

    protected function parseSelectedIds($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        $value = trim((string) $value);
        if ($value === '') {
            return array();
        }

        return array_values(array_filter(array_map('intval', explode(',', $value))));
    }

    protected function isExpenseReceiptAllowed(int $category_id, bool $has_invoice): bool
    {
        if ($has_invoice) {
            return true;
        }

        $category = $category_id ? $this->categoriesModel->get_one($category_id) : null;
        if ($category && isset($category->requires_invoice) && (int) $category->requires_invoice === 1) {
            return false;
        }

        return $this->settingsModel->get_setting('travelrefunds_allow_expenses_without_receipt', '1') === '1';
    }

    protected function hasExpenseReceiptEvidence(array $data): bool
    {
        if (!empty($data['has_invoice'])) {
            return true;
        }

        if (!empty($data['attachment_id'])) {
            return true;
        }

        if (!empty($data['invoice_number'])) {
            return true;
        }

        if (!empty($data['receipt_number'])) {
            return true;
        }

        if (!empty($data['receipt_file'])) {
            return true;
        }

        $request = \Config\Services::request();
        $file_names = $request->getPost('file_names');
        if (is_array($file_names) && !empty($file_names[0])) {
            return true;
        }

        if (!empty($_FILES['manualFiles']) && !empty($_FILES['manualFiles']['name']) && is_array($_FILES['manualFiles']['name'])) {
            foreach ($_FILES['manualFiles']['name'] as $name) {
                if (trim((string) $name) !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    public function reports()
    {
        $this->requirePermission('travelrefunds_view');
        $filters = $this->getDashboardFilters();
        $report_type = trim((string) $this->request->getGet('report_type')) ?: 'summary';
        $data = $this->buildReportData($filters);

        return $this->template->rander('travelrefunds\\Views\\reports\\index', array(
            'filters' => $this->normalizeFilterValues($filters),
            'report_type' => $report_type,
            'users' => $this->getFilterUsers(),
            'projects' => $this->projectsModel->get_details()->getResult(),
            'clients' => $this->clientsModel->get_all_where(array('deleted' => 0), 1000000, 0, 'company_name', 'id, company_name')->getResult(),
            'categories' => $this->categoriesModel->get_details()->getResult(),
            'report_data' => $data,
            'status_options' => array('draft', 'submitted', 'approved', 'rejected', 'closed'),
        ));
    }

    public function exportReport($type = 'summary')
    {
        $this->requirePermission('travelrefunds_view');
        $filters = $this->getDashboardFilters();
        $data = $this->buildReportData($filters);
        $filename = 'travelrefunds_' . $type . '_' . date('Y-m-d_His') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($this->buildReportCsv($type, $data));
    }

    public function exportReportXlsx($type = 'summary')
    {
        $this->requirePermission('travelrefunds_view');
        $filters = $this->getDashboardFilters();
        $data = $this->buildReportData($filters);

        require_once(APPPATH . 'ThirdParty/PHPOffice-PhpSpreadsheet/vendor/autoload.php');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        $write_row = function (array $values) use (&$sheet, &$row) {
            $column = 1;
            foreach ($values as $value) {
                $sheet->setCellValueByColumnAndRow($column, $row, $value);
                $column++;
            }
            $row++;
        };

        if ($type === 'employee') {
            $write_row(array('Funcionario', 'Quantidade', 'Total'));
            foreach ($data['by_employee'] as $item) {
                $write_row(array($item['label'], $item['count'], $item['total']));
            }
        } elseif ($type === 'project') {
            $write_row(array('Projeto', 'Quantidade', 'Total'));
            foreach ($data['by_project'] as $item) {
                $write_row(array($item['label'], $item['count'], $item['total']));
            }
        } elseif ($type === 'category') {
            $write_row(array('Categoria', 'Quantidade', 'Total'));
            foreach ($data['by_category'] as $item) {
                $write_row(array($item['label'], $item['count'], $item['total']));
            }
        } else {
            $write_row(array('Mes', 'Aprovado', 'Em aberto', 'Enviadas', 'Rejeitadas'));
            foreach ($data['monthly'] as $item) {
                $write_row(array($item['label'], $item['approved_total'], $item['open_total'], $item['submitted'], $item['rejected']));
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'travelrefunds_' . $type . '_' . date('Y-m-d_His') . '.xlsx';

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }

    protected function processTripDecision(int $id, string $status)
    {
        $trip = $this->tripsModel->get_one($id);
        if (!$trip || !$trip->id) {
            $this->session->setFlashdata('error_message', 'Registro nao encontrado.');
            return;
        }

        if ($trip->status !== 'submitted') {
            $this->session->setFlashdata('error_message', 'A viagem precisa estar submetida para aprovacao.');
            return;
        }

        $approver_notes = trim((string) $this->request->getPost('approver_notes'));
        $approved_amount = (float) ($this->request->getPost('approved_amount') ?: $trip->approved_amount ?: $trip->total_amount);
        $rejection_reason = trim((string) $this->request->getPost('rejection_reason'));

        if ($status === 'rejected' && !$rejection_reason) {
            $this->session->setFlashdata('error_message', 'Motivo da rejeicao e obrigatorio.');
            return;
        }

        $update = array(
            'status' => $status,
            'approver_notes' => $approver_notes ?: null,
            'rejection_reason' => $status === 'rejected' ? $rejection_reason : null,
        );

        if ($status === 'approved') {
            $update['approved_amount'] = $approved_amount;
            $update['approved_by'] = $this->login_user->id;
            $update['approved_at'] = get_current_utc_time();
            $update['rejected_by'] = null;
            $update['rejected_at'] = null;
            $update['rejection_reason'] = null;
        } else {
            $update['approved_amount'] = 0;
            $update['approved_by'] = null;
            $update['approved_at'] = null;
            $update['rejected_by'] = $this->login_user->id;
            $update['rejected_at'] = get_current_utc_time();
        }

        $this->tripsModel->ci_save($update, $id);

        if ($status === 'approved') {
            $db = db_connect('default');
            $db->table($db->prefixTable('travelrefunds_expenses'))
                ->where('trip_id', $id)
                ->where('deleted', 0)
                ->where('status', 'pending')
                ->update(array(
                    'status' => 'approved',
                    'approved_by' => $this->login_user->id,
                    'approved_at' => get_current_utc_time(),
                    'rejection_reason' => null,
                ));
        }

        $approval_log_data = array(
            'reimbursement_id' => 0,
            'trip_id' => $id,
            'expense_id' => null,
            'approver_id' => $this->login_user->id,
            'action' => 'trip_' . $status,
            'notes' => $status === 'approved' ? $approver_notes : $rejection_reason,
        );
        $this->approvalsModel->ci_save($approval_log_data);

        $this->recalculateTripTotals($id);
        $this->sendTripNotification($trip, $status, $approved_amount, $rejection_reason, $approver_notes);
        $this->session->setFlashdata('success_message', $status === 'approved' ? 'Viagem aprovada.' : 'Viagem rejeitada.');
    }

    protected function processExpenseDecision(int $trip_id, int $expense_id, string $status)
    {
        $trip = $this->tripsModel->get_one($trip_id);
        $expense = $this->reimbursementsModel->get_one($expense_id);
        if (!$trip || !$trip->id || !$expense || !$expense->id || (int) $expense->trip_id !== $trip_id) {
            $this->session->setFlashdata('error_message', 'Registro nao encontrado.');
            return;
        }

        if ($trip->status !== 'submitted') {
            $this->session->setFlashdata('error_message', 'A viagem precisa estar submetida para aprovacao.');
            return;
        }

        $rejection_reason = trim((string) $this->request->getPost('rejection_reason'));
        if ($status === 'rejected' && !$rejection_reason) {
            $this->session->setFlashdata('error_message', 'Motivo da rejeicao e obrigatorio.');
            return;
        }

        $update = array(
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? $rejection_reason : null,
        );

        if ($status === 'approved') {
            $update['approved_by'] = $this->login_user->id;
            $update['approved_at'] = get_current_utc_time();
            $update['rejected_by'] = null;
            $update['rejected_at'] = null;
            $update['rejection_reason'] = null;
        } else {
            $update['approved_by'] = null;
            $update['approved_at'] = null;
            $update['rejected_by'] = $this->login_user->id;
            $update['rejected_at'] = get_current_utc_time();
            $rejected_trip_data = array(
                'status' => 'rejected',
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => $rejection_reason,
                'approver_notes' => $rejection_reason,
                'rejected_by' => $this->login_user->id,
                'rejected_at' => get_current_utc_time(),
            );
            $this->tripsModel->ci_save($rejected_trip_data, $trip_id);
        }

        $this->reimbursementsModel->ci_save($update, $expense_id);
        $expense_approval_log_data = array(
            'reimbursement_id' => $expense_id,
            'trip_id' => $trip_id,
            'expense_id' => $expense_id,
            'approver_id' => $this->login_user->id,
            'action' => 'expense_' . $status,
            'notes' => $rejection_reason,
        );
        $this->approvalsModel->ci_save($expense_approval_log_data);

        $this->recalculateTripTotals($trip_id);
        $this->sendExpenseNotification($trip, $expense, $status, $rejection_reason);
        $this->session->setFlashdata('success_message', $status === 'approved' ? 'Despesa aprovada.' : 'Despesa rejeitada.');
    }

    protected function buildExpenseSummary($expenses): array
    {
        $summary = array();
        foreach ($expenses as $expense) {
            $category = $expense->category_name ?: $expense->category_title ?: 'Sem categoria';
            if (!isset($summary[$category])) {
                $summary[$category] = 0;
            }
            $summary[$category] += (float) $expense->amount;
        }

        arsort($summary);
        return $summary;
    }

    protected function buildTripSummary($trip, $expenses): array
    {
        $total_amount = 0;
        $approved_amount = 0;
        $rejected_amount = 0;
        $pending_amount = 0;
        $has_rejected = false;
        foreach ($expenses as $expense) {
            $total_amount += (float) $expense->amount;
            if ($expense->status === 'approved') {
                $approved_amount += (float) $expense->amount;
            } else if ($expense->status === 'rejected') {
                $rejected_amount += (float) $expense->amount;
                $has_rejected = true;
            } else {
                $pending_amount += (float) $expense->amount;
            }
        }

        return array(
            'total_amount' => $total_amount,
            'approved_amount' => $approved_amount,
            'rejected_amount' => $rejected_amount,
            'pending_amount' => $pending_amount,
            'expense_count' => count($expenses),
            'has_rejected_expenses' => $has_rejected,
            'trip_status' => $trip->status ?? 'draft',
        );
    }

    protected function recalculateTripTotals($trip_id)
    {
        $expenses = $this->reimbursementsModel->get_details(array('trip_id' => $trip_id))->getResult();
        $total = 0;
        $approved = 0;
        foreach ($expenses as $expense) {
            $total += (float) $expense->amount;
            if ($expense->status === 'approved') {
                $approved += (float) $expense->amount;
            }
        }

        $trip = $this->tripsModel->get_one($trip_id);
        $update = array(
            'total_amount' => $total,
        );

        if (!$trip || $trip->status !== 'approved') {
            $update['approved_amount'] = $approved;
        }

        $this->tripsModel->ci_save($update, $trip_id);
    }

    protected function buildExpenseAttachmentUrl($attachment_id = 0, string $receipt_file = ''): string
    {
        $attachment_id = (int) $attachment_id;
        if (!$attachment_id) {
            $receipt_file = trim($receipt_file);
            if (!$receipt_file) {
                return '';
            }

            return base_url('files/travelrefunds/reimbursements/' . rawurlencode($receipt_file));
        }

        return get_uri('file_manager/view_file/' . $attachment_id);
    }

    protected function sendTripNotification($trip, string $status, float $approved_amount = 0, string $rejection_reason = '', string $approver_notes = '')
    {
        if (!$trip || !$trip->employee_id) {
            return;
        }

        $event = $status === 'approved' ? 'travelrefunds_trip_approved' : 'travelrefunds_trip_rejected';
        log_notification($event, array(
            'to_user_id' => $trip->employee_id,
            'plugin_trip_id' => $trip->id,
            'plugin_trip_title' => $trip->title,
            'plugin_employee_name' => $trip->employee_name ?? '',
            'plugin_approved_amount' => $approved_amount,
            'plugin_rejection_reason' => $rejection_reason,
            'plugin_approver_notes' => $approver_notes,
        ), $this->login_user->id);
    }

    protected function sendExpenseNotification($trip, $expense, string $status, string $rejection_reason = '')
    {
        if (!$trip || !$trip->employee_id || !$expense) {
            return;
        }

        $event = $status === 'approved' ? 'travelrefunds_expense_approved' : 'travelrefunds_expense_rejected';
        log_notification($event, array(
            'to_user_id' => $trip->employee_id,
            'plugin_trip_id' => $trip->id,
            'plugin_trip_title' => $trip->title,
            'plugin_expense_id' => $expense->id,
            'plugin_expense_description' => $expense->description,
            'plugin_amount' => $expense->amount,
            'plugin_rejection_reason' => $rejection_reason,
        ), $this->login_user->id);
    }
}
