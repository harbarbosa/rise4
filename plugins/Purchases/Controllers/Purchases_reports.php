<?php

namespace Purchases\Controllers;

use App\Controllers\Security_Controller;

class Purchases_reports extends Security_Controller
{
    private $Purchases_reports_model;
    private $Purchases_suppliers_model;
    public $Projects_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Purchases_reports_model = model('Purchases\\Models\\Purchases_reports_model');
        $this->Purchases_suppliers_model = model('Purchases\\Models\\Purchases_suppliers_model');
        $this->Projects_model = model('App\\Models\\Projects_model');
    }

    public function index()
    {
        $this->_access_report_permission();

        $view_data = array(
            'projects_dropdown' => $this->_get_projects_dropdown_list_data(),
            'suppliers_dropdown' => $this->_get_suppliers_dropdown_list_data()
        );

        return $this->template->rander('Purchases\\Views\\reports\\index', $view_data);
    }

    public function purchases_by_period()
    {
        $this->_access_report_permission();

        $options = array(
            'company_id' => $this->_get_company_id()
        );

        $supplier_id = $this->request->getPost('supplier_id');
        if ($supplier_id) {
            $options['supplier_id'] = get_only_numeric_value($supplier_id);
        }

        $project_id = $this->request->getPost('project_id');
        if ($project_id) {
            $options['project_id'] = get_only_numeric_value($project_id);
        }

        $start_date = $this->request->getPost('start_date');
        if ($start_date) {
            $options['start_date'] = $start_date . ' 00:00:00';
        }

        $end_date = $this->request->getPost('end_date');
        if ($end_date) {
            $options['end_date'] = $end_date . ' 23:59:59';
        }

        $query = $this->Purchases_reports_model->get_purchases_by_period($options);
        $list_data = ($query && method_exists($query, 'getResult')) ? $query->getResult() : array();
        $result = array();

        foreach ($list_data as $row) {
            $result[] = array(
                esc($row->project_title ? $row->project_title : '-'),
                esc($row->supplier_name ? $row->supplier_name : '-'),
                to_decimal_format($row->orders_count),
                to_currency($row->total)
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function open_overdue()
    {
        $this->_access_report_permission();

        $options = array(
            'company_id' => $this->_get_company_id(),
            'open_statuses' => array('open', 'sent', 'partial_received', 'draft')
        );

        $supplier_id = $this->request->getPost('supplier_id');
        if ($supplier_id) {
            $options['supplier_id'] = get_only_numeric_value($supplier_id);
        }

        $query = $this->Purchases_reports_model->get_open_overdue($options);
        $list_data = ($query && method_exists($query, 'getResult')) ? $query->getResult() : array();

        $today = get_my_local_time("Y-m-d");
        $result = array();
        foreach ($list_data as $row) {
            $expected = $row->expected_delivery_date ? $row->expected_delivery_date : '';
            $is_overdue = ($expected && $expected < $today);

            $result[] = array(
                esc($row->po_code ? $row->po_code : ('#' . $row->id)),
                esc($row->supplier_name ? $row->supplier_name : '-'),
                esc($row->project_title ? $row->project_title : ($row->cost_center ? $row->cost_center : '-')),
                $this->_get_status_label($row->status),
                $expected ? format_to_date($expected, false) : '-',
                to_currency($row->total),
                $is_overdue ? "<span class='badge bg-danger'>" . app_lang('purchases_overdue') . "</span>" : "-"
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function top_items()
    {
        $this->_access_report_permission();

        $options = array(
            'company_id' => $this->_get_company_id()
        );

        $start_date = $this->request->getPost('start_date');
        if ($start_date) {
            $options['start_date'] = $start_date . ' 00:00:00';
        }

        $end_date = $this->request->getPost('end_date');
        if ($end_date) {
            $options['end_date'] = $end_date . ' 23:59:59';
        }

        $query = $this->Purchases_reports_model->get_top_items($options);
        $list_data = ($query && method_exists($query, 'getResult')) ? $query->getResult() : array();
        $result = array();
        foreach ($list_data as $row) {
            $result[] = array(
                esc($row->description ? $row->description : '-'),
                esc($row->unit ? $row->unit : '-'),
                to_decimal_format($row->total_qty),
                to_currency($row->total_amount)
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _access_report_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        $can_view = get_array_value($permissions, 'purchases_view') == '1';
        $can_manage = get_array_value($permissions, 'purchases_manage') == '1';
        $can_approve = get_array_value($permissions, 'purchases_approve') == '1';

        if (!($can_view || $can_manage || $can_approve)) {
            app_redirect('forbidden');
        }
    }

    private function _get_projects_dropdown_list_data()
    {
        $project_options = array('status_id' => 1);
        if ($this->login_user->user_type === 'staff' && !$this->can_manage_all_projects()) {
            $project_options['user_id'] = $this->login_user->id;
        }

        $projects = $this->Projects_model->get_details($project_options)->getResult();
        $dropdown = array(
            array('id' => '', 'text' => '- ' . app_lang('project') . ' -')
        );
        foreach ($projects as $project) {
            $dropdown[] = array('id' => $project->id, 'text' => $project->title);
        }

        return json_encode($dropdown);
    }

    private function _get_suppliers_dropdown_list_data()
    {
        $suppliers = $this->Purchases_suppliers_model->get_details(array(
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $dropdown = array(
            array('id' => '', 'text' => '- ' . app_lang('purchases_suppliers') . ' -')
        );
        foreach ($suppliers as $supplier) {
            $dropdown[] = array('id' => $supplier->id, 'text' => $supplier->name);
        }

        return json_encode($dropdown);
    }

    private function _get_status_label($status)
    {
        $class_map = array(
            'draft' => 'secondary',
            'open' => 'dark',
            'sent' => 'info',
            'partial_received' => 'warning',
            'received' => 'success',
            'canceled' => 'danger'
        );
        $class = get_array_value($class_map, $status, 'secondary');

        return "<span class='badge bg-" . $class . "'>" . app_lang('purchases_po_status_' . $status) . "</span>";
    }

    private function _get_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return $this->login_user->company_id;
        }

        return get_default_company_id();
    }
}
