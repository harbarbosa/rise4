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

    public function index()
    {
        $this->requirePermission('travelrefunds_view');
        $employee_filter = $this->login_user->is_admin ? 0 : $this->login_user->id;
        $trips = $this->tripsModel->get_details(array(
            'employee_id' => $employee_filter,
        ))->getResult();
        $reimbursements = $this->reimbursementsModel->get_details(array(
            'employee_id' => $employee_filter,
        ))->getResult();

        $summary = array(
            'trips_total' => count($trips),
            'reimbursements_total' => count($reimbursements),
            'pending_total' => 0,
            'approved_total' => 0,
            'spent_total' => 0,
        );

        foreach ($reimbursements as $item) {
            $summary['spent_total'] += (float) $item->amount;
            if ($item->status === 'pending') {
                $summary['pending_total']++;
            }
            if ($item->status === 'approved' || $item->status === 'paid') {
                $summary['approved_total']++;
            }
        }

        return $this->template->rander('travelrefunds\\Views\\dashboard\\index', array(
            'summary' => $summary,
            'recent_trips' => array_slice($trips, 0, 5),
            'recent_reimbursements' => array_slice($reimbursements, 0, 5),
        ));
    }

    public function trips()
    {
        $this->requirePermission('travelrefunds_view');
        $db = db_connect('default');
        $trips = $this->tripsModel->get_details(array(
            'employee_id' => $this->login_user->is_admin ? 0 : $this->login_user->id,
        ))->getResult();

        return $this->template->rander('travelrefunds\\Views\\trips\\index', array(
            'trips' => $trips,
            'can_create' => $this->login_user->is_admin || get_array_value($this->login_user->permissions ?? array(), 'travelrefunds_create') == '1',
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
            'users' => $users,
            'projects' => $projects,
            'clients' => $clients,
            'categories' => $categories,
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
            'status' => $save_action === 'submit' ? 'submitted' : ($existing && $existing->status ? $existing->status : 'draft'),
            'total_amount' => (float) ($this->request->getPost('total_amount') ?: $this->request->getPost('estimated_amount')),
            'approved_amount' => (float) ($this->request->getPost('approved_amount') ?: $this->request->getPost('actual_amount')),
            'notes' => trim((string) $this->request->getPost('notes')),
            'departure_date' => $this->request->getPost('departure_date'),
            'return_date' => $this->request->getPost('return_date'),
            'estimated_amount' => (float) $this->request->getPost('estimated_amount'),
            'actual_amount' => (float) $this->request->getPost('actual_amount'),
        );

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            if (!$data['employee_id']) {
                $data['employee_id'] = $this->login_user->id;
            }
        }

        if (!$data['title']) {
            $this->session->setFlashdata('error_message', 'Titulo da viagem e obrigatorio.');
            return redirect()->back();
        }

        $result = $this->tripsModel->ci_save($data, $id ?: null);
        $this->session->setFlashdata('success_message', $result ? 'Registro salvo com sucesso.' : 'Nao foi possivel salvar.');
        if ($result) {
            return redirect()->to(get_uri('travelrefunds/trips/view/' . $result));
        }

        return redirect()->back();
    }

    public function deleteTrip($id)
    {
        $this->requirePermission('travelrefunds_delete');
        $this->tripsModel->delete((int) $id);
        $this->session->setFlashdata('success_message', 'Registro excluido.');
        return redirect()->to(get_uri('travelrefunds/trips'));
    }

    public function reimbursements()
    {
        $this->requirePermission('travelrefunds_view');
        $edit_id = (int) $this->request->getGet('edit_id');
        $edit_row = $edit_id ? $this->reimbursementsModel->get_one($edit_id) : null;

        $categories = $this->categoriesModel->get_details()->getResult();
        $trips = $this->tripsModel->get_details()->getResult();
        $db = db_connect('default');
        $users = $db->table($db->prefixTable('users') . ' u')
            ->select('u.id, u.first_name, u.last_name')
            ->where('u.deleted', 0)
            ->where('u.user_type', 'staff')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResult();
        $reimbursements = $this->reimbursementsModel->get_details(array(
            'employee_id' => $this->login_user->is_admin ? 0 : $this->login_user->id,
        ))->getResult();

        return $this->template->rander('travelrefunds\\Views\\reimbursements\\index', array(
            'reimbursement_edit' => $edit_row,
            'reimbursements' => $reimbursements,
            'categories' => $categories,
            'trips' => $trips,
            'users' => $users,
            'status_options' => array('pending', 'approved', 'rejected', 'paid'),
        ));
    }

    public function saveReimbursement()
    {
        $this->requirePermission($this->request->getPost('id') ? 'travelrefunds_edit' : 'travelrefunds_create');

        $id = (int) $this->request->getPost('id');
        $data = array(
            'trip_id' => (int) $this->request->getPost('trip_id'),
            'employee_id' => (int) $this->request->getPost('employee_id'),
            'category_id' => (int) $this->request->getPost('category_id'),
            'expense_date' => $this->request->getPost('expense_date'),
            'amount' => (float) $this->request->getPost('amount'),
            'description' => trim((string) $this->request->getPost('description')),
            'payment_method' => trim((string) $this->request->getPost('payment_method')),
            'has_invoice' => $this->request->getPost('has_invoice') ? 1 : (($this->request->getPost('receipt_number') || $this->request->getPost('receipt_file')) ? 1 : 0),
            'invoice_number' => trim((string) $this->request->getPost('invoice_number')) ?: trim((string) $this->request->getPost('receipt_number')),
            'supplier_name' => trim((string) $this->request->getPost('supplier_name')) ?: trim((string) $this->request->getPost('vendor')),
            'attachment_id' => (int) $this->request->getPost('attachment_id') ?: null,
            'status' => trim((string) $this->request->getPost('status')) ?: 'pending',
            'rejection_reason' => trim((string) $this->request->getPost('rejection_reason')),
            'notes' => trim((string) $this->request->getPost('notes')),
            'vendor' => trim((string) $this->request->getPost('vendor')),
            'receipt_number' => trim((string) $this->request->getPost('receipt_number')),
            'receipt_file' => trim((string) $this->request->getPost('receipt_file')),
        );

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
        }

        if (!$data['amount']) {
            $this->session->setFlashdata('error_message', 'Valor e obrigatorio.');
            return redirect()->back();
        }

        $result = $this->reimbursementsModel->ci_save($data, $id ?: null);
        $this->session->setFlashdata('success_message', $result ? 'Registro salvo com sucesso.' : 'Nao foi possivel salvar.');
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
        $uploaded_files = move_files_from_temp_dir_to_permanent_dir('files/travelrefunds/' . $trip_id . '/', 'travelrefunds');
        if ($uploaded_files) {
            $uploaded_list = @unserialize($uploaded_files);
            if (is_array($uploaded_list) && get_array_value($uploaded_list, 0)) {
                $attachment_id = (int) get_array_value(get_array_value($uploaded_list, 0), 'file_id');
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
        $pending = $this->reimbursementsModel->get_details(array('status' => 'pending'))->getResult();
        $logs = $this->approvalsModel->get_details()->getResult();

        return $this->template->rander('travelrefunds\\Views\\approvals\\index', array(
            'pending_items' => $pending,
            'logs' => $logs,
        ));
    }

    public function approve($id)
    {
        $this->requirePermission('travelrefunds_approve');
        $this->updateApprovalStatus((int) $id, 'approved', 'Aprovado');
        return redirect()->to(get_uri('travelrefunds/approvals'));
    }

    public function reject($id)
    {
        $this->requirePermission('travelrefunds_approve');
        $this->updateApprovalStatus((int) $id, 'rejected', 'Rejeitado');
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
        ));
    }

    public function saveSettings()
    {
        $this->requirePermission('travelrefunds_manage_settings');

        $settings = array(
            'travelrefunds_enabled' => $this->request->getPost('travelrefunds_enabled') ? '1' : '0',
            'travelrefunds_default_currency_symbol' => trim((string) $this->request->getPost('travelrefunds_default_currency_symbol')),
            'travelrefunds_allow_public_receipts' => $this->request->getPost('travelrefunds_allow_public_receipts') ? '1' : '0',
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
            'travelrefunds_default_currency_symbol' => $this->settingsModel->get_setting('travelrefunds_default_currency_symbol', get_setting('default_currency_symbol') ?: '$'),
            'travelrefunds_allow_public_receipts' => $this->settingsModel->get_setting('travelrefunds_allow_public_receipts', '0'),
        );
    }

    protected function updateApprovalStatus(int $id, string $status, string $label)
    {
        $item = $this->reimbursementsModel->get_one($id);
        if (!$item || !$item->id) {
            $this->session->setFlashdata('error_message', 'Registro nao encontrado.');
            return;
        }

        $this->reimbursementsModel->ci_save(array(
            'status' => $status,
            'approved_by' => $this->login_user->id,
            'approved_at' => get_current_utc_time(),
        ), $id);

        $this->approvalsModel->ci_save(array(
            'reimbursement_id' => $id,
            'approver_id' => $this->login_user->id,
            'action' => $status,
            'notes' => $label,
        ));

        if ($item->trip_id) {
            $trip = $this->tripsModel->get_one((int) $item->trip_id);
            if ($trip && $trip->id) {
                $expenses = $this->reimbursementsModel->get_details(array('trip_id' => $trip->id))->getResult();
                $pending = 0;
                $rejected = 0;
                foreach ($expenses as $expense) {
                    if ($expense->status === 'pending') {
                        $pending++;
                    }
                    if ($expense->status === 'rejected') {
                        $rejected++;
                    }
                }

                $trip_status = $trip->status;
                if ($status === 'rejected') {
                    $trip_status = 'rejected';
                } else if ($status === 'approved' && $pending === 0 && $rejected === 0 && $trip_status === 'submitted') {
                    $trip_status = 'approved';
                }

                $this->tripsModel->ci_save(array(
                    'status' => $trip_status,
                ), $trip->id);
            }
        }

        $this->session->setFlashdata('success_message', 'Solicitacao ' . strtolower($label) . '.');
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
        $has_rejected = false;
        foreach ($expenses as $expense) {
            $total_amount += (float) $expense->amount;
            if ($expense->status === 'approved') {
                $approved_amount += (float) $expense->amount;
            }
            if ($expense->status === 'rejected') {
                $has_rejected = true;
            }
        }

        return array(
            'total_amount' => $total_amount,
            'approved_amount' => $approved_amount,
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

        $this->tripsModel->ci_save(array(
            'total_amount' => $total,
            'approved_amount' => $approved,
        ), $trip_id);
    }
}
