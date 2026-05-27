<?php

namespace RestApi\Controllers;

use travelrefunds\Models\TravelRefundsApprovals_model;
use travelrefunds\Models\TravelRefundsCategories_model;
use travelrefunds\Models\TravelRefundsReimbursements_model;
use travelrefunds\Models\TravelRefundsSettings_model;
use travelrefunds\Models\TravelRefundsTrips_model;

class TravelRefundsController extends ModuleApiController
{
    protected TravelRefundsTrips_model $tripsModel;
    protected TravelRefundsReimbursements_model $reimbursementsModel;
    protected TravelRefundsCategories_model $categoriesModel;
    protected TravelRefundsApprovals_model $approvalsModel;
    protected TravelRefundsSettings_model $settingsModel;

    public function __construct()
    {
        parent::__construct();
        $this->tripsModel = model(TravelRefundsTrips_model::class);
        $this->reimbursementsModel = model(TravelRefundsReimbursements_model::class);
        $this->categoriesModel = model(TravelRefundsCategories_model::class);
        $this->approvalsModel = model(TravelRefundsApprovals_model::class);
        $this->settingsModel = model(TravelRefundsSettings_model::class);
    }

    public function dashboard()
    {
        [$summary, $spendByCategory, $filters] = $this->dashboardPayload();

        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_dashboard',
            'summary' => $summary,
            'spend_by_category' => $spendByCategory,
            'filters' => $filters,
        ]);
    }

    public function trips(int $id = 0)
    {
        if ($id > 0) {
            $trip = $this->tripsModel->get_details(['id' => $id])->getRowArray();
            if (!$trip) {
                return $this->failNotFound('Trip not found.');
            }

            $trip['expenses'] = $this->reimbursementsModel->get_details(['trip_id' => $id])->getResultArray();
            $trip['approvals'] = $this->approvalsModel->get_details(['trip_id' => $id])->getResultArray();

            return $this->respond([
                'status' => true,
                'resource' => 'travelrefunds_trip',
                'data' => $trip,
            ]);
        }

        $rows = $this->tripsModel->get_details($this->travelFilters())->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_trips',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveTrip(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('travelrefunds_trips', $payload, ['id']);

        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        $this->normalizeDecimalFields($data, ['total_amount', 'approved_amount']);
        $this->normalizeIntFields($data, ['employee_id', 'project_id', 'client_id']);

        if (array_key_exists('traveler_ids', $data) && is_array($data['traveler_ids'])) {
            $data['traveler_ids'] = json_encode(array_values(array_filter(array_map('intval', $data['traveler_ids']))));
        }

        if (!array_key_exists('status', $data) && !$id) {
            $data['status'] = 'draft';
        }

        $saved = $this->tripsModel->ci_save($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save trip.');
        }

        $newId = $id ?: (int) db_connect('default')->insertID();
        return $this->respondCreated([
            'status' => true,
            'message' => 'Trip saved successfully.',
            'id' => $newId,
            'data' => $this->tripsModel->get_details(['id' => $newId])->getRowArray(),
        ]);
    }

    public function deleteTrip(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid trip id.');
        }

        if (!$this->tripsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete trip.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Trip deleted successfully.',
        ]);
    }

    public function reimbursements(int $id = 0)
    {
        if ($id > 0) {
            $row = $this->reimbursementsModel->get_details(['id' => $id])->getRowArray();
            if (!$row) {
                return $this->failNotFound('Reimbursement not found.');
            }

            return $this->respond([
                'status' => true,
                'resource' => 'travelrefunds_reimbursement',
                'data' => $row,
            ]);
        }

        $rows = $this->reimbursementsModel->get_details($this->travelFilters())->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_reimbursements',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function expenses(int $tripId = 0, int $expenseId = 0)
    {
        $tripId = $tripId > 0 ? $tripId : (int) $this->request->getGet('trip_id');

        if ($expenseId > 0) {
            $row = $this->reimbursementsModel->get_details(['id' => $expenseId, 'trip_id' => $tripId])->getRowArray();
            if (!$row) {
                return $this->failNotFound('Expense not found.');
            }

            return $this->respond([
                'status' => true,
                'resource' => 'travelrefunds_expense',
                'data' => $row,
            ]);
        }

        $filters = $this->travelFilters();
        if ($tripId > 0) {
            $filters['trip_id'] = $tripId;
        }

        $rows = $this->reimbursementsModel->get_details($filters)->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_expenses',
            'trip_id' => $tripId,
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveReimbursement(int $id = 0)
    {
        $payload = $this->payload();
        return $this->saveReimbursementPayload($payload, $id);
    }

    public function saveExpense(int $tripId = 0, int $expenseId = 0)
    {
        $payload = $this->payload();
        if ($tripId > 0 && !array_key_exists('trip_id', $payload)) {
            $payload['trip_id'] = $tripId;
        }
        if ($expenseId > 0 && !array_key_exists('id', $payload)) {
            $payload['id'] = $expenseId;
        }

        return $this->saveReimbursementPayload($payload, $expenseId);
    }

    protected function saveReimbursementPayload(array $payload, int $id = 0)
    {
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('travelrefunds_expenses', $payload, ['id']);

        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        $this->normalizeDecimalFields($data, ['amount']);
        $this->normalizeIntFields($data, ['trip_id', 'project_id', 'category_id', 'employee_id', 'attachment_id', 'approved_by']);
        if (array_key_exists('has_invoice', $data)) {
            $data['has_invoice'] = $this->toBool($data['has_invoice']) ? 1 : 0;
        }

        $saved = $this->reimbursementsModel->ci_save($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save reimbursement.');
        }

        $newId = $id ?: (int) db_connect('default')->insertID();
        return $this->respondCreated([
            'status' => true,
            'message' => 'Reimbursement saved successfully.',
            'id' => $newId,
            'data' => $this->reimbursementsModel->get_details(['id' => $newId])->getRowArray(),
        ]);
    }

    public function deleteReimbursement(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid reimbursement id.');
        }

        if (!$this->reimbursementsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete reimbursement.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Reimbursement deleted successfully.',
        ]);
    }

    public function deleteExpense(int $tripId = 0, int $expenseId = 0)
    {
        $expenseId = $expenseId > 0 ? $expenseId : (int) $this->request->getPost('id');
        return $this->deleteReimbursement($expenseId);
    }

    public function approvals(int $id = 0)
    {
        if ($id > 0) {
            $trip = $this->tripsModel->get_details(['id' => $id])->getRowArray();
            if (!$trip) {
                return $this->failNotFound('Approval not found.');
            }

            return $this->respond([
                'status' => true,
                'resource' => 'travelrefunds_approval',
                'data' => [
                    'trip' => $trip,
                    'expenses' => $this->reimbursementsModel->get_details(['trip_id' => $id])->getResultArray(),
                    'logs' => $this->approvalsModel->get_details(['trip_id' => $id])->getResultArray(),
                ],
            ]);
        }

        $rows = $this->tripsModel->get_details(['status' => 'submitted'])->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_approvals',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function approveTrip(int $id = 0)
    {
        return $this->setTripDecision($id, 'approved');
    }

    public function rejectTrip(int $id = 0)
    {
        return $this->setTripDecision($id, 'rejected');
    }

    public function approveExpense(int $tripId = 0, int $expenseId = 0)
    {
        return $this->setExpenseDecision($tripId, $expenseId, 'approved');
    }

    public function rejectExpense(int $tripId = 0, int $expenseId = 0)
    {
        return $this->setExpenseDecision($tripId, $expenseId, 'rejected');
    }

    public function categories(int $id = 0)
    {
        if ($id > 0) {
            $row = $this->categoriesModel->get_details(['id' => $id])->getRowArray();
            if (!$row) {
                return $this->failNotFound('Category not found.');
            }

            return $this->respond([
                'status' => true,
                'resource' => 'travelrefunds_category',
                'data' => $row,
            ]);
        }

        $rows = $this->categoriesModel->get_details($this->travelFilters())->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_categories',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveCategory(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('travelrefunds_categories', $payload, ['id']);

        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        $this->normalizeIntFields($data, ['requires_invoice', 'active', 'sort_order']);

        $saved = $this->categoriesModel->ci_save($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save category.');
        }

        $newId = $id ?: (int) db_connect('default')->insertID();
        return $this->respondCreated([
            'status' => true,
            'message' => 'Category saved successfully.',
            'id' => $newId,
            'data' => $this->categoriesModel->get_details(['id' => $newId])->getRowArray(),
        ]);
    }

    public function deleteCategory(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid category id.');
        }

        if (!$this->categoriesModel->delete($id)) {
            return $this->failValidationErrors('Could not delete category.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function settings()
    {
        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_settings',
            'data' => $this->settingsModel->get_all_settings(),
        ]);
    }

    public function saveSettings()
    {
        $payload = $this->payload();
        $allowed = [
            'allow_expenses_without_receipt',
            'require_invoice_by_category',
            'default_approvers',
            'special_approval_limit',
            'currency_symbol',
            'default_status',
        ];

        $settings = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $settings[$key] = $payload[$key];
            }
        }

        if (!$settings) {
            return $this->failValidationErrors('No valid settings were provided.');
        }

        foreach ($settings as $name => $value) {
            $this->settingsModel->save_setting($name, $value);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Settings saved successfully.',
        ]);
    }

    public function reports()
    {
        [$summary, $spendByCategory, $filters] = $this->dashboardPayload();

        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_reports',
            'summary' => $summary,
            'spend_by_category' => $spendByCategory,
            'filters' => $filters,
            'trips' => $this->tripsModel->get_details($filters)->getResultArray(),
            'reimbursements' => $this->reimbursementsModel->get_details($filters)->getResultArray(),
        ]);
    }

    public function exportReport(string $type = 'summary')
    {
        return $this->respond([
            'status' => true,
            'resource' => 'travelrefunds_report_export',
            'type' => $type,
            'message' => 'Export endpoint available on module UI. Use the JSON API endpoints for data.',
        ]);
    }

    public function exportReportXlsx(string $type = 'summary')
    {
        return $this->exportReport($type);
    }

    protected function travelFilters(): array
    {
        return [
            'id' => (int) $this->request->getGet('id'),
            'employee_id' => (int) $this->request->getGet('employee_id'),
            'project_id' => (int) $this->request->getGet('project_id'),
            'client_id' => (int) $this->request->getGet('client_id'),
            'trip_id' => (int) $this->request->getGet('trip_id'),
            'category_id' => (int) $this->request->getGet('category_id'),
            'status' => clean_data($this->request->getGet('status')),
            'status_not' => clean_data($this->request->getGet('status_not')),
            'start_date' => clean_data($this->request->getGet('start_date')),
            'end_date' => clean_data($this->request->getGet('end_date')),
            'search_by' => clean_data($this->request->getGet('q') ?? $this->request->getGet('search')),
        ];
    }

    protected function dashboardPayload(): array
    {
        $filters = $this->travelFilters();
        $trips = $this->tripsModel->get_details($filters)->getResultArray();
        $expenses = $this->reimbursementsModel->get_details($filters)->getResultArray();

        $summary = [
            'trips_total' => count($trips),
            'reimbursements_total' => count($expenses),
            'pending_total' => 0,
            'approved_total' => 0,
            'spent_total' => 0,
            'open_total' => 0,
            'rejected_total' => 0,
        ];

        foreach ($trips as $trip) {
            $status = (string) ($trip['status'] ?? '');
            $totalAmount = (float) ($trip['total_amount'] ?? 0);
            if ($status === 'submitted') {
                $summary['pending_total']++;
                $summary['open_total'] += $totalAmount;
            } elseif ($status === 'rejected') {
                $summary['rejected_total']++;
                $summary['open_total'] += $totalAmount;
            } elseif ($status === 'draft') {
                $summary['open_total'] += $totalAmount;
            }

            if ($status === 'approved' && !empty($trip['approved_at']) && date('Y-m', strtotime($trip['approved_at'])) === date('Y-m')) {
                $summary['approved_total'] += (float) ($trip['approved_amount'] ?? 0);
            }
        }

        $spendByCategory = [];
        foreach ($expenses as $expense) {
            $amount = (float) ($expense['amount'] ?? 0);
            $summary['spent_total'] += $amount;
            $category = $expense['category_name'] ?? $expense['category_title'] ?? 'Sem categoria';
            $spendByCategory[$category] = ($spendByCategory[$category] ?? 0) + $amount;
        }

        arsort($spendByCategory);

        return [$summary, $spendByCategory, $filters];
    }

    protected function setTripDecision(int $id, string $status)
    {
        $id = $id > 0 ? $id : (int) $this->request->getPost('id');
        $trip = $this->tripsModel->get_one($id);
        if (!$trip || !$trip->id) {
            return $this->failNotFound('Trip not found.');
        }

        $data = [
            'status' => $status,
            'updated_at' => get_current_utc_time(),
        ];
        if ($status === 'approved') {
            $data['approved_amount'] = (float) ($trip->total_amount ?? 0);
        }

        if (!$this->tripsModel->ci_save($data, $id)) {
            return $this->failValidationErrors('Could not update trip status.');
        }

        return $this->respond(['status' => true, 'message' => 'Trip status updated.', 'id' => $id, 'status_value' => $status]);
    }

    protected function setExpenseDecision(int $tripId, int $expenseId, string $status)
    {
        $tripId = $tripId > 0 ? $tripId : (int) $this->request->getPost('trip_id');
        $expenseId = $expenseId > 0 ? $expenseId : (int) $this->request->getPost('expense_id');
        $expense = $this->reimbursementsModel->get_one($expenseId);
        if (!$expense || !$expense->id || (int) $expense->trip_id !== $tripId) {
            return $this->failNotFound('Expense not found.');
        }

        $data = [
            'status' => $status,
            'updated_at' => get_current_utc_time(),
        ];

        if (!$this->reimbursementsModel->ci_save($data, $expenseId)) {
            return $this->failValidationErrors('Could not update expense status.');
        }

        return $this->respond(['status' => true, 'message' => 'Expense status updated.', 'trip_id' => $tripId, 'expense_id' => $expenseId, 'status_value' => $status]);
    }
}
