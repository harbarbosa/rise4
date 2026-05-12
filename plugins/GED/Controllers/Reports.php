<?php

namespace GED\Controllers;

class Reports extends GedBaseController
{
    private $Documents_model;
    private $Document_submissions_model;
    private $Document_types_model;
    private $Suppliers_model;
    private $Ged_users_model;

    public function __construct()
    {
        parent::__construct();
        $this->Documents_model = model('GED\\Models\\Ged_documents_model');
        $this->Document_submissions_model = model('GED\\Models\\Ged_document_submissions_model');
        $this->Document_types_model = model('GED\\Models\\Ged_document_types_model');
        $this->Suppliers_model = model('GED\\Models\\Ged_suppliers_model');
        $this->Ged_users_model = model('App\\Models\\Users_model');
    }

    public function index()
    {
        if (!$this->_has_view_reports_permission()) {
            app_redirect('forbidden');
        }

        $filters = $this->_get_filters_from_request();

        $view_data = array(
            'can_export' => false,
            'filters' => $filters,
            'filter_options' => $this->_get_filter_options(),
            'summary' => $this->_get_summary($filters),
            'reports' => $this->_get_reports($filters),
            'expiration_labels' => array(
                'valid' => get_document_status_label('valid'),
                'expiring_30' => get_document_status_label('expiring_30'),
                'expiring_15' => get_document_status_label('expiring_15'),
                'expiring_7' => get_document_status_label('expiring_7'),
                'expires_today' => get_document_status_label('expires_today'),
                'expired' => get_document_status_label('expired'),
            ),
        );

        return $this->template->rander('GED\\Views\\reports\\index', $view_data);
    }

    private function _get_filters_from_request()
    {
        return array(
            'document_type_id' => (int) $this->request->getVar('document_type_id'),
            'employee_id' => (int) $this->request->getVar('employee_id'),
            'supplier_id' => (int) $this->request->getVar('supplier_id'),
            'document_status' => trim((string) $this->request->getVar('document_status')),
            'portal_status' => trim((string) $this->request->getVar('portal_status')),
            'expiration_start' => trim((string) $this->request->getVar('expiration_start')),
            'expiration_end' => trim((string) $this->request->getVar('expiration_end')),
        );
    }

    private function _get_filter_options()
    {
        return array(
            'document_types_dropdown' => $this->_get_document_types_dropdown(),
            'employees_dropdown' => $this->_get_employees_dropdown(),
            'suppliers_dropdown' => $this->_get_suppliers_dropdown(),
            'document_status_dropdown' => array(
                '' => '-',
                'pending' => 'Pendente',
                'valid' => 'Valido',
                'expiring' => 'Vencendo',
                'expired' => 'Vencido',
                'archived' => 'Arquivado',
            ),
            'portal_status_dropdown' => array(
                '' => '-',
                'pending' => 'Pendente',
                'submitted' => 'Enviado',
                'approved' => 'Aprovado',
                'rejected' => 'Rejeitado',
                'expired' => 'Expirado',
            ),
        );
    }

    private function _get_summary($filters)
    {
        $documents = $this->Documents_model->get_report_documents($filters);
        $submissions = $this->Document_submissions_model->get_report_submissions_summary($filters);

        return array(
            'documents' => array(
                'expired' => $this->_count_expired_documents($documents),
                'expiring_30' => $this->_count_expiring_documents($documents, 30),
                'total' => count($documents),
            ),
            'submissions' => array(
                'total' => (int) get_array_value((array) $submissions, 'total_submissions'),
                'pending' => (int) get_array_value((array) $submissions, 'pending_submissions'),
                'with_expired_documents' => (int) get_array_value((array) $submissions, 'submissions_with_expired_documents'),
                'with_expiring_documents' => (int) get_array_value((array) $submissions, 'submissions_with_expiring_documents'),
            ),
        );
    }

    private function _get_reports($filters)
    {
        return array(
            'expired_documents' => $this->Documents_model->get_report_documents(array_merge($filters, array('expiration_scope' => 'overdue'))),
            'expiring_30_documents' => $this->Documents_model->get_report_documents(array_merge($filters, array('expiration_scope' => 'expiring_30'))),
            'documents_by_employee' => $this->Documents_model->get_report_documents_grouped_by_employee($filters),
            'documents_by_supplier' => $this->Documents_model->get_report_documents_grouped_by_supplier($filters),
            'portal_submissions' => $this->Document_submissions_model->get_report_submissions($filters),
            'pending_portal_submissions' => $this->Document_submissions_model->get_report_pending_portal_submissions($filters),
            'expired_document_submissions' => $this->Document_submissions_model->get_report_submissions_with_expired_documents($filters),
        );
    }

    private function _count_expired_documents($documents)
    {
        $count = 0;
        foreach ((array) $documents as $document) {
            if (get_expiration_status($document->expiration_date ?? null) === 'expired') {
                $count++;
            }
        }
        return $count;
    }

    private function _count_expiring_documents($documents, $days = 30)
    {
        $count = 0;
        foreach ((array) $documents as $document) {
            if (is_expiring($document->expiration_date ?? null, $days)) {
                $count++;
            }
        }
        return $count;
    }

    private function _get_document_types_dropdown()
    {
        $dropdown = array('' => '-');
        $rows = $this->Document_types_model->get_details(array('is_active' => 1))->getResult();
        foreach ($rows as $row) {
            $dropdown[$row->id] = $row->name;
        }
        return $dropdown;
    }

    private function _get_suppliers_dropdown()
    {
        $dropdown = array('' => '-');
        $rows = $this->Suppliers_model->get_details(array('is_active' => 1))->getResult();
        foreach ($rows as $row) {
            $dropdown[$row->id] = $row->name;
        }
        return $dropdown;
    }

    private function _get_employees_dropdown()
    {
        $dropdown = array('' => '-');
        $rows = $this->Ged_users_model->get_all_where(array('deleted' => 0, 'status' => 'active', 'user_type' => 'staff'))->getResult();
        foreach ($rows as $row) {
            $dropdown[$row->id] = trim($row->first_name . ' ' . $row->last_name);
        }
        return $dropdown;
    }
}
