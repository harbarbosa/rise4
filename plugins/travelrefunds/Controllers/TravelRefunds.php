<?php

namespace travelrefunds\Controllers;

use App\Controllers\Security_Controller;
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
        $edit_id = (int) $this->request->getGet('edit_id');
        $edit_row = $edit_id ? $this->tripsModel->get_one($edit_id) : null;

        $db = db_connect('default');
        $users = $db->table($db->prefixTable('users') . ' u')
            ->select('u.id, u.first_name, u.last_name')
            ->where('u.deleted', 0)
            ->where('u.user_type', 'staff')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResult();
        $projects = $this->projectsModel->get_details()->getResult();
        $trips = $this->tripsModel->get_details(array(
            'employee_id' => $this->login_user->is_admin ? 0 : $this->login_user->id,
        ))->getResult();

        return $this->template->rander('travelrefunds\\Views\\trips\\index', array(
            'trip_edit' => $edit_row,
            'trips' => $trips,
            'users' => $users,
            'projects' => $projects,
            'status_options' => array('draft', 'submitted', 'approved', 'rejected', 'closed'),
        ));
    }

    public function saveTrip()
    {
        $this->requirePermission($this->request->getPost('id') ? 'travelrefunds_edit' : 'travelrefunds_create');

        $id = (int) $this->request->getPost('id');
        $data = array(
            'title' => trim((string) $this->request->getPost('title')),
            'employee_id' => (int) $this->request->getPost('employee_id'),
            'project_id' => (int) $this->request->getPost('project_id'),
            'client_id' => (int) $this->request->getPost('client_id'),
            'destination' => trim((string) $this->request->getPost('destination')),
            'purpose' => trim((string) $this->request->getPost('purpose')),
            'start_date' => $this->request->getPost('start_date') ?: $this->request->getPost('departure_date'),
            'end_date' => $this->request->getPost('end_date') ?: $this->request->getPost('return_date'),
            'status' => trim((string) $this->request->getPost('status')) ?: 'draft',
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
        }

        if (!$data['title']) {
            $this->session->setFlashdata('error_message', 'Titulo da viagem e obrigatorio.');
            return redirect()->back();
        }

        $result = $this->tripsModel->ci_save($data, $id ?: null);
        $this->session->setFlashdata('success_message', $result ? 'Registro salvo com sucesso.' : 'Nao foi possivel salvar.');
        return redirect()->to(get_uri('travelrefunds/trips'));
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

        $this->session->setFlashdata('success_message', 'Solicitacao ' . strtolower($label) . '.');
    }
}
