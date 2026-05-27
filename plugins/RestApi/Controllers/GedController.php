<?php

namespace RestApi\Controllers;

use GED\Libraries\GedNotificationService;
use GED\Models\Ged_document_submissions_model;
use GED\Models\Ged_document_types_model;
use GED\Models\Ged_documents_model;
use GED\Models\Ged_settings_model;
use GED\Models\Ged_suppliers_model;

class GedController extends ModuleApiController
{
    protected Ged_documents_model $documentsModel;
    protected Ged_document_types_model $documentTypesModel;
    protected Ged_suppliers_model $suppliersModel;
    protected Ged_settings_model $settingsModel;
    protected Ged_document_submissions_model $submissionsModel;

    public function __construct()
    {
        parent::__construct();
        $this->documentsModel = model(Ged_documents_model::class);
        $this->documentTypesModel = model(Ged_document_types_model::class);
        $this->suppliersModel = model(Ged_suppliers_model::class);
        $this->settingsModel = model(Ged_settings_model::class);
        $this->submissionsModel = model(Ged_document_submissions_model::class);
    }

    public function documents(int $id = 0)
    {
        if ($id > 0) {
            $row = $this->documentsModel->get_one_with_details($id);
            if (!$row) {
                return $this->failNotFound('Document not found.');
            }

            return $this->respondData((array) $row, ['resource' => 'ged_document', 'id' => $id]);
        }

        $rows = $this->documentsModel->get_details($this->documentFilters())->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'ged_documents',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveDocument(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('ged_documents', $payload, ['id']);

        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        if (array_key_exists('expiration_date', $data) && $data['expiration_date'] === '') {
            $data['expiration_date'] = null;
        }

        $saved = $this->documentsModel->save_document($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save document.');
        }

        $newId = $id ?: (int) db_connect('default')->insertID();
        return $this->respondCreated([
            'status' => true,
            'message' => 'Document saved successfully.',
            'id' => $newId,
            'data' => $this->documentsModel->get_one_with_details($newId),
        ]);
    }

    public function deleteDocument(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid document id.');
        }

        if (!$this->documentsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete document.');
        }

        return $this->respondDeleted(['status' => true, 'message' => 'Document deleted successfully.']);
    }

    public function documentTypes(int $id = 0)
    {
        if ($id > 0) {
            $row = $this->documentTypesModel->get_one($id);
            if (!$row || !$row->id) {
                return $this->failNotFound('Document type not found.');
            }

            return $this->respondData((array) $row, ['resource' => 'ged_document_type', 'id' => $id]);
        }

        $rows = $this->documentTypesModel->get_details($this->typeFilters())->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'ged_document_types',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveDocumentType(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('ged_document_types', $payload, ['id']);
        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        $saved = $this->documentTypesModel->save_type($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save document type.');
        }

        $newId = $id ?: (int) db_connect('default')->insertID();
        return $this->respondCreated([
            'status' => true,
            'message' => 'Document type saved successfully.',
            'id' => $newId,
            'data' => (array) $this->documentTypesModel->get_one($newId),
        ]);
    }

    public function toggleDocumentTypeStatus(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        $row = $this->documentTypesModel->get_one($id);
        if (!$row || !$row->id) {
            return $this->failNotFound('Document type not found.');
        }

        $data = ['is_active' => (int) $row->is_active ? 0 : 1, 'updated_at' => get_my_local_time()];
        if (!$this->documentTypesModel->save_type($data, $id)) {
            return $this->failValidationErrors('Could not update document type status.');
        }

        return $this->respond(['status' => true, 'id' => $id, 'message' => 'Document type status updated.']);
    }

    public function deleteDocumentType(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid document type id.');
        }

        if (!$this->documentTypesModel->delete($id)) {
            return $this->failValidationErrors('Could not delete document type.');
        }

        return $this->respondDeleted(['status' => true, 'message' => 'Document type deleted successfully.']);
    }

    public function suppliers(int $id = 0)
    {
        if ($id > 0) {
            $row = $this->suppliersModel->get_one($id);
            if (!$row || !$row->id) {
                return $this->failNotFound('Supplier not found.');
            }

            return $this->respondData((array) $row, ['resource' => 'ged_supplier', 'id' => $id]);
        }

        $rows = $this->suppliersModel->get_details($this->supplierFilters())->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'ged_suppliers',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveSupplier(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('ged_suppliers', $payload, ['id']);
        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        $saved = $this->suppliersModel->save_supplier($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save supplier.');
        }

        $newId = $id ?: (int) db_connect('default')->insertID();
        return $this->respondCreated([
            'status' => true,
            'message' => 'Supplier saved successfully.',
            'id' => $newId,
            'data' => (array) $this->suppliersModel->get_one($newId),
        ]);
    }

    public function deleteSupplier(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid supplier id.');
        }

        if (!$this->suppliersModel->delete($id)) {
            return $this->failValidationErrors('Could not delete supplier.');
        }

        return $this->respondDeleted(['status' => true, 'message' => 'Supplier deleted successfully.']);
    }

    public function submissions(int $id = 0)
    {
        if ($id > 0) {
            $row = $this->submissionsModel->get_one_with_details($id);
            if (!$row) {
                return $this->failNotFound('Submission not found.');
            }

            return $this->respondData((array) $row, ['resource' => 'ged_submission', 'id' => $id]);
        }

        $rows = $this->submissionsModel->get_details($this->submissionFilters())->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'ged_submissions',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveSubmission(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('ged_document_submissions', $payload, ['id']);
        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        $saved = $this->submissionsModel->save_submission($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save submission.');
        }

        $newId = $id ?: (int) db_connect('default')->insertID();
        return $this->respondCreated([
            'status' => true,
            'message' => 'Submission saved successfully.',
            'id' => $newId,
            'data' => (array) $this->submissionsModel->get_one_with_details($newId),
        ]);
    }

    public function deleteSubmission(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid submission id.');
        }

        if (!$this->submissionsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete submission.');
        }

        return $this->respondDeleted(['status' => true, 'message' => 'Submission deleted successfully.']);
    }

    public function settings()
    {
        return $this->respond([
            'status' => true,
            'resource' => 'ged_settings',
            'data' => $this->settingsModel->get_all_settings(),
        ]);
    }

    public function saveSettings()
    {
        $payload = $this->payload();
        $settings = $this->normalizeSettingsPayload($payload);
        if (!$settings) {
            return $this->failValidationErrors('No valid settings were provided.');
        }

        $this->settingsModel->save_settings($settings);

        return $this->respond([
            'status' => true,
            'message' => 'Settings saved successfully.',
        ]);
    }

    public function reports()
    {
        $filters = $this->reportFilters();
        return $this->respond([
            'status' => true,
            'resource' => 'ged_reports',
            'summary' => [
                'documents' => $this->documentsModel->get_dashboard_kpis(),
                'submissions' => $this->submissionsModel->get_dashboard_summary(),
            ],
            'reports' => [
                'expired_documents' => $this->documentsModel->get_report_documents(array_merge($filters, ['expiration_scope' => 'overdue'])),
                'expiring_30_documents' => $this->documentsModel->get_report_documents(array_merge($filters, ['expiration_scope' => 'expiring_30'])),
                'documents_by_employee' => $this->documentsModel->get_report_documents_grouped_by_employee($filters),
                'documents_by_supplier' => $this->documentsModel->get_report_documents_grouped_by_supplier($filters),
                'portal_submissions' => $this->submissionsModel->get_report_submissions($filters),
                'pending_portal_submissions' => $this->submissionsModel->get_report_pending_portal_submissions($filters),
                'expired_document_submissions' => $this->submissionsModel->get_report_submissions_with_expired_documents($filters),
                'submissions_summary' => $this->submissionsModel->get_report_submissions_summary($filters),
            ],
        ]);
    }

    public function notificationsRun()
    {
        $service = new GedNotificationService();
        $result = $service->run(['source' => 'restapi']);

        return $this->respond([
            'status' => true,
            'resource' => 'ged_notifications',
            'data' => $result,
        ]);
    }

    private function documentFilters(): array
    {
        return [
            'id' => (int) $this->request->getGet('id'),
            'document_type_id' => (int) $this->request->getGet('document_type_id'),
            'owner_type' => clean_data($this->request->getGet('owner_type')),
            'employee_id' => (int) $this->request->getGet('employee_id'),
            'supplier_id' => (int) $this->request->getGet('supplier_id'),
            'status' => clean_data($this->request->getGet('status')),
            'expiration_scope' => clean_data($this->request->getGet('expiration_scope')),
            'expiration_start' => clean_data($this->request->getGet('expiration_start')),
            'expiration_end' => clean_data($this->request->getGet('expiration_end')),
            'search' => clean_data($this->request->getGet('search')),
        ];
    }

    private function typeFilters(): array
    {
        return [
            'id' => (int) $this->request->getGet('id'),
            'is_active' => $this->request->getGet('is_active'),
            'search' => clean_data($this->request->getGet('search')),
        ];
    }

    private function supplierFilters(): array
    {
        return [
            'id' => (int) $this->request->getGet('id'),
            'is_active' => $this->request->getGet('is_active'),
            'search' => clean_data($this->request->getGet('search')),
        ];
    }

    private function submissionFilters(): array
    {
        return [
            'id' => (int) $this->request->getGet('id'),
            'document_id' => (int) $this->request->getGet('document_id'),
            'supplier_id' => (int) $this->request->getGet('supplier_id'),
            'portal_status' => clean_data($this->request->getGet('portal_status')),
            'search' => clean_data($this->request->getGet('search')),
        ];
    }

    private function reportFilters(): array
    {
        return [
            'document_type_id' => (int) $this->request->getGet('document_type_id'),
            'employee_id' => (int) $this->request->getGet('employee_id'),
            'supplier_id' => (int) $this->request->getGet('supplier_id'),
            'status' => clean_data($this->request->getGet('status')),
            'expiration_scope' => clean_data($this->request->getGet('expiration_scope')),
            'expiration_start' => clean_data($this->request->getGet('expiration_start')),
            'expiration_end' => clean_data($this->request->getGet('expiration_end')),
        ];
    }

    private function normalizeSettingsPayload(array $payload): array
    {
        $allowed = [
            'alert_days',
            'enable_native_notifications',
            'notify_admins',
            'notify_document_creator',
            'upload_max_size_mb',
            'allowed_file_extensions',
            'default_document_status',
            'default_submission_status',
        ];

        $settings = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $settings[$key] = $payload[$key];
            }
        }

        if (isset($settings['upload_max_size_mb'])) {
            $settings['upload_max_size_mb'] = (string) max(1, (int) $settings['upload_max_size_mb']);
        }

        return $settings;
    }
}
