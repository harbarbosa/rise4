<?php

namespace Purchases\Controllers;

use App\Controllers\Security_Controller;

class Purchase_requests extends Security_Controller
{
    private $Purchases_requests_model;
    private $Purchases_request_items_model;
    private $Purchases_quotations_model;
    private $Purchases_quotation_items_model;
    private $Purchases_quotation_item_prices_model;
    private $Purchases_orders_model;
    private $Purchases_logs_model;
    private $Purchases_request_approvals_model;
    private $Purchases_approvers_model;
    private $Purchases_settings_model;
    public $Invoice_items_model;
    public $Projects_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Purchases_requests_model = model('Purchases\\Models\\Purchases_requests_model');
        $this->Purchases_request_items_model = model('Purchases\\Models\\Purchases_request_items_model');
        $this->Purchases_quotations_model = model('Purchases\\Models\\Purchases_quotations_model');
        $this->Purchases_quotation_items_model = model('Purchases\\Models\\Purchases_quotation_items_model');
        $this->Purchases_quotation_item_prices_model = model('Purchases\\Models\\Purchases_quotation_item_prices_model');
        $this->Purchases_orders_model = model('Purchases\\Models\\Purchases_orders_model');
        $this->Purchases_logs_model = model('Purchases\\Models\\Purchases_logs_model');
        $this->Purchases_request_approvals_model = model('Purchases\\Models\\Purchases_request_approvals_model');
        $this->Purchases_approvers_model = model('Purchases\\Models\\Purchases_approvers_model');
        $this->Purchases_settings_model = model('Purchases\\Models\\Purchases_settings_model');
        $this->Invoice_items_model = model('App\\Models\\Invoice_items_model');
        $this->Projects_model = model('App\\Models\\Projects_model');
        $this->Users_model = model('App\\Models\\Users_model');
    }

    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $view_data = array();
        $view_data['projects_dropdown'] = $this->_get_projects_dropdown_list_data();
        $view_data['statuses_dropdown'] = json_encode($this->_get_statuses_dropdown());

        
        return $this->template->rander('Purchases\\Views\\requests\\index', $view_data);
    }

    public function list_data()
    {
        try {
            if (!$this->_has_view_permission()) {
                return $this->_json_permission_denied();
            }

            $options = array(
                'company_id' => $this->_get_company_id()
            );

            $status = $this->request->getPost('status');
            if ($status) {
                $options['status'] = $status;
            }

            $project_id = $this->request->getPost('project_id');
            if ($project_id) {
                $options['project_id'] = get_only_numeric_value($project_id);
            }

            $start_date = $this->request->getPost('start_date');
            $end_date = $this->request->getPost('end_date');
            if ($start_date) {
                $options['start_date'] = $start_date . ' 00:00:00';
            }
            if ($end_date) {
                $options['end_date'] = $end_date . ' 23:59:59';
            }

            if (!$this->login_user->is_admin) {
                $options['visibility_user_id'] = $this->login_user->id;
            }

            $query = $this->Purchases_requests_model->get_details($options);
            $list_data = ($query && method_exists($query, 'getResult')) ? $query->getResult() : array();
            $result = array();
            foreach ($list_data as $data) {
                $result[] = $this->_make_row($data);
            }

            return $this->response->setJSON(array('data' => $result));
        } catch (\Throwable $e) {
            return $this->response->setJSON(array(
                'data' => array(),
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function request_form($id = 0)
    {
        if (!$this->_has_manage_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();

        if ($id) {
            $request = $this->Purchases_requests_model->get_details(array(
                'id' => $id,
                'company_id' => $this->_get_company_id()
            ))->getRow();
            if (!$request) {
                show_404();
            }
            if (!$this->_can_edit($request)) {
                app_redirect('forbidden');
            }
            $view_data['request_info'] = $request;
            $view_data['request_items'] = $this->Purchases_request_items_model->get_details(array(
                'request_id' => $id,
                'company_id' => $this->_get_company_id()
            ))->getResult();
        } else {
            $view_data['request_info'] = (object) array(
                'id' => 0,
                'request_code' => '',
                'project_id' => '',
                'os_id' => '',
                'is_internal' => 0,
                'cost_center' => '',
                'priority' => 'medium',
                'note' => ''
            );
            $view_data['request_items'] = array();
        }

        $view_data['projects_dropdown'] = $this->_get_projects_dropdown();
        $view_data['os_dropdown'] = $this->_get_os_dropdown();
        $view_data['items_dropdown_list'] = $this->_get_items_dropdown_list();

        return $this->template->rander('Purchases\\Views\\requests\\form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $company_id = $this->_get_company_id();

        if ($id) {
            $request = $this->Purchases_requests_model->get_details(array(
                'id' => $id,
                'company_id' => $company_id
            ))->getRow();
            if (!$request) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
            }
            if (!$this->_can_edit($request)) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
            }
        }

        $project_id = get_only_numeric_value($this->request->getPost('project_id'));
        $os_id = get_only_numeric_value($this->request->getPost('os_id'));
        $is_internal = $this->request->getPost('is_internal') ? 1 : 0;
        $priority = $this->request->getPost('priority') ?: 'medium';
        $note = trim((string)$this->request->getPost('note_header'));

        if ($is_internal) {
            $project_id = null;
            $os_id = null;
        } elseif ($os_id) {
            $project_id = null;
        }

        $data = array(
            'company_id' => $company_id,
            'project_id' => $project_id ? $project_id : null,
            'os_id' => $os_id ? $os_id : null,
            'is_internal' => $is_internal,
            'cost_center' => trim((string)$this->request->getPost('cost_center')),
            'priority' => $priority,
            'note' => $note,
            'updated_at' => get_my_local_time()
        );

        if (!$id) {
            $code_data = $this->Purchases_requests_model->get_next_request_code_data($company_id);
            $data['request_code_number'] = $code_data['request_code_number'];
            $data['request_code'] = $code_data['request_code'];
            $data['requested_by'] = $this->login_user->id;
            $data['requester_id'] = $this->login_user->id;
            $data['request_date'] = get_my_local_time();
            $data['created_at'] = get_my_local_time();
            $data['created_by'] = $this->login_user->id;
            $data['status'] = 'draft';
        }

        $save_id = $this->Purchases_requests_model->ci_save($data, $id);
        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        if (!$save_id) {
            $save_id = $id;
        }
        if (!is_int($save_id)) {
            $save_id = $id ?: db_connect('default')->insertID();
        }

        $this->_save_request_items($save_id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(array('success' => true, 'id' => $save_id));
        }

        return redirect()->to(get_uri('purchases_requests/view/' . $save_id));
    }

    public function view($id = 0)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        if (!$id) {
            show_404();
        }

        $request = $this->Purchases_requests_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();

        if (!$request) {
            show_404();
        }

        $view_data = array();
        $view_data['request_info'] = $request;
        $view_data['request_items'] = $this->Purchases_request_items_model->get_details(array(
            'request_id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        $view_data['can_edit'] = $this->_can_edit($request);
        $view_data['can_submit'] = $this->_can_submit($request);
        $view_data['can_approve'] = $this->_can_approve($request);
        $view_data['can_convert'] = $this->_can_convert($request);
        $view_data['status_label'] = $this->_get_status_label($request->status);
        $quotation = $this->Purchases_quotations_model->get_one_by_request($id, $this->_get_company_id());
        $view_data['quotation_id'] = $quotation ? $quotation->id : 0;
        $view_data['has_quotation'] = $quotation ? true : false;
        $view_data['quotation_suppliers'] = array();
        $view_data['quotation_items'] = array();
        $view_data['quotation_prices_map'] = array();
        $view_data['quotation_winner_map'] = array();
        $view_data['quotation_totals'] = array();
        $view_data['quotation_winner_totals'] = array();
        if ($quotation) {
            $Quotation_suppliers_model = model('Purchases\\Models\\Purchases_quotation_suppliers_model');
            $quotation_suppliers = $Quotation_suppliers_model->get_details(array(
                'quotation_id' => $quotation->id,
                'company_id' => $this->_get_company_id()
            ))->getResult();
            $quotation_items = $this->Purchases_quotation_items_model->get_details(array(
                'quotation_id' => $quotation->id,
                'company_id' => $this->_get_company_id()
            ))->getResult();
            $quotation_prices = $this->Purchases_quotation_item_prices_model->get_details(array(
                'quotation_id' => $quotation->id,
                'company_id' => $this->_get_company_id()
            ))->getResult();

            $price_map = array();
            $winner_map = array();
            foreach ($quotation_prices as $price) {
                if (!isset($price_map[$price->request_item_id])) {
                    $price_map[$price->request_item_id] = array();
                }
                $price_map[$price->request_item_id][$price->supplier_id] = $price;
                if ($price->is_winner) {
                    $winner_map[$price->request_item_id] = (int)$price->supplier_id;
                }
            }

            $totals = array();
            $winner_totals = array();
            foreach ($quotation_suppliers as $supplier) {
                $totals[$supplier->supplier_id] = 0;
                $winner_totals[$supplier->supplier_id] = 0;
            }
            foreach ($quotation_items as $item) {
                $qty = $item->qty ? (float)$item->qty : 0;
                foreach ($quotation_suppliers as $supplier) {
                    $price = get_array_value(get_array_value($price_map, $item->request_item_id, array()), $supplier->supplier_id);
                    if ($price) {
                        $line_total = ($qty * (float)$price->unit_price) + (float)$price->freight_value;
                        $totals[$supplier->supplier_id] += $line_total;
                        if ($price->is_winner) {
                            $winner_totals[$supplier->supplier_id] += $line_total;
                        }
                    }
                }
            }

            $view_data['quotation_suppliers'] = $quotation_suppliers;
            $view_data['quotation_items'] = $quotation_items;
            $view_data['quotation_prices_map'] = $price_map;
            $view_data['quotation_winner_map'] = $winner_map;
            $view_data['quotation_totals'] = $totals;
            $view_data['quotation_winner_totals'] = $winner_totals;
        }
        $orders_query = $this->Purchases_orders_model->get_details(array(
            'request_id' => $id,
            'company_id' => $this->_get_company_id()
        ));
        $orders = ($orders_query && method_exists($orders_query, 'getResult')) ? $orders_query->getResult() : array();
        $view_data['has_order'] = $orders ? true : false;
        $view_data['can_create_quotation'] = (in_array($request->status, array('sent_to_quotation', 'submitted')) && !$view_data['has_quotation'] && !$view_data['has_order']);
        $view_data['can_generate_po_from_request'] = ($this->_has_manage_permission() && $request->status === 'approved_for_po' && $view_data['has_quotation'] && !$view_data['has_order']);
        $view_data['approvals'] = $this->Purchases_request_approvals_model->get_details(array(
            'request_id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $view_data['can_approve_requester'] = $this->_can_approve_requester($request);
        $view_data['can_approve_financial'] = $this->_can_approve_financial_with_limit($request);
        $view_data['has_financial_permission'] = $this->_can_approve_financial($request);
        $view_data['can_reject_approval'] = $this->_can_reject_approval($request);
        $view_data['approval_total'] = $this->_get_request_quotation_total($id);
        $view_data['financial_limit'] = $this->_get_financial_limit_for_user($this->login_user->id, $this->_get_company_id());
        $view_data['is_admin'] = $this->login_user->is_admin ? true : false;
        $view_data['show_success_message'] = $this->request->getGet('purchases_success');

        return $this->template->rander('Purchases\\Views\\requests\\view', $view_data);
    }

    public function submit()
    {
        try {
            if (!$this->_has_manage_permission()) {
                return $this->_json_permission_denied();
            }

            $id = (int)$this->request->getPost('id');
            $request = $this->_get_request_or_404($id);
            if (!$this->_can_submit($request)) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
            }

            $old_status = $request->status;
            $data = array(
                'status' => 'sent_to_quotation',
                'submitted_at' => get_my_local_time(),
                'updated_at' => get_my_local_time()
            );

              $ok = $this->Purchases_requests_model->ci_save($data, $id);
              if ($ok) {
                  $quotation = $this->Purchases_quotations_model->get_one_by_request($id, $this->_get_company_id());
                  if ($quotation && $quotation->status === 'finalized') {
                      $quotation_update = array('status' => 'draft');
                      $this->Purchases_quotations_model->ci_save($quotation_update, (int)$quotation->id);
                  }
                  $this->_log_status_change('request', $id, $old_status, 'sent_to_quotation');
                  $this->_notify_request_status($request, 'sent_to_quotation');
                  $this->_notify_request_sent_for_quotation($id);
              }
            return $this->response->setJSON(array(
                'success' => $ok ? true : false,
                'message' => $ok ? app_lang('purchases_request_submitted') : app_lang('error_occurred')
            ));
        } catch (\Throwable $e) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function approve()
    {
        if (!$this->_has_approval_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        $request = $this->_get_request_or_404($id);
        if (!$this->_can_approve($request)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $old_status = $request->status;
        $data = array(
            'status' => 'approved',
            'approved_by' => $this->login_user->id,
            'approved_at' => get_my_local_time(),
            'updated_at' => get_my_local_time()
        );

        $ok = $this->Purchases_requests_model->ci_save($data, $id);
        if ($ok) {
            $this->_log_status_change('request', $id, $old_status, 'approved');
        }
        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    public function reject()
    {
        if (!$this->_has_approval_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        $request = $this->_get_request_or_404($id);
        if (!$this->_can_approve($request)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $old_status = $request->status;
        $reason = trim((string)$this->request->getPost('rejected_reason'));
        $data = array(
            'status' => 'rejected',
            'rejected_by' => $this->login_user->id,
            'rejected_at' => get_my_local_time(),
            'rejected_reason' => $reason,
            'updated_at' => get_my_local_time()
        );

        $ok = $this->Purchases_requests_model->ci_save($data, $id);
        if ($ok) {
            $this->_log_status_change('request', $id, $old_status, 'rejected', $reason);
        }
        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    public function convert()
    {
        if (!$this->_has_approval_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        $request = $this->_get_request_or_404($id);
        if (!$this->_can_convert($request)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $old_status = $request->status;
        $data = array(
            'status' => 'converted',
            'converted_by' => $this->login_user->id,
            'converted_at' => get_my_local_time(),
            'updated_at' => get_my_local_time()
        );

        $ok = $this->Purchases_requests_model->ci_save($data, $id);
        if ($ok) {
            $this->_log_status_change('request', $id, $old_status, 'converted');
        }
        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    public function update_status($id = 0, $status = "")
    {
        if (!$this->_has_manage_permission() && !$this->_has_approval_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        $status = $status ? $status : $this->request->getPost('status');
        if (!$id || !$status) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $allowed_statuses = $this->_get_status_keys();
        if (!in_array($status, $allowed_statuses)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $request = $this->Purchases_requests_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$request) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $old_status = $request->status;
        if ($old_status === $status) {
            return $this->response->setJSON(array('success' => true));
        }

        $data = array(
            'status' => $status,
            'updated_at' => get_my_local_time()
        );

        if ($status === 'sent_to_quotation' || $status === 'submitted') {
            $data['submitted_at'] = get_my_local_time();
        } else if ($status === 'approved') {
            $data['approved_at'] = get_my_local_time();
        } else if ($status === 'rejected') {
            $data['rejected_at'] = get_my_local_time();
        } else if ($status === 'converted') {
            $data['converted_at'] = get_my_local_time();
        }

        $ok = $this->Purchases_requests_model->ci_save($data, $id);
        if ($ok) {
            $this->_log_status_change('request', $id, $old_status, $status);
            $this->_notify_request_status($request, $status);
        }

        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    public function delete()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $request = $this->Purchases_requests_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();

        if (!$request) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        if (!$this->_can_edit($request)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $data = array(
            'deleted' => 1,
            'updated_at' => get_my_local_time()
        );

        $ok = $this->Purchases_requests_model->ci_save($data, $id);
        if ($ok) {
            $db = db_connect('default');
            $items_table = $db->prefixTable('purchases_request_items');
            if ($db->tableExists($items_table)) {
                $db->table($items_table)->where('request_id', $id)->update(array('deleted' => 1));
            }
        }

        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    public function approve_requester()
    {
        return $this->_handle_approval("requester");
    }

    public function approve_financial()
    {
        return $this->_handle_approval("financial");
    }

    public function reject_approval()
    {
        if (!$this->_has_manage_permission() && !$this->_has_approval_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        $request = $this->_get_request_or_404($id);
        if ($request->status !== 'awaiting_approval') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $reason = trim((string)$this->request->getPost('comment'));
        $old_status = $request->status;

        $update_data = array(
            'status' => 'rejected',
            'rejected_by' => $this->login_user->id,
            'rejected_at' => get_my_local_time(),
            'rejected_reason' => $reason,
            'updated_at' => get_my_local_time()
        );

        $ok = $this->Purchases_requests_model->ci_save($update_data, $id);

        if ($ok) {
            $this->_log_status_change('request', $id, $old_status, 'rejected', $reason);
            $this->_notify_request_status($request, 'rejected');
            $this->_notify_buyers($request->id, 'purchase_request_rejected');
        }

        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    public function get_item_suggestion()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $key = trim((string)$this->request->getPost('q'));
        if ($key === '') {
            return $this->response->setJSON(array());
        }

        $db = db_connect('default');
        $items_table = $db->prefixTable('items');
        $keyword = $db->escapeLikeString($key);

        $sql = "SELECT $items_table.id, $items_table.title
            FROM $items_table
            WHERE $items_table.deleted=0 AND $items_table.title LIKE '%$keyword%' ESCAPE '!'
            ORDER BY $items_table.title ASC
            LIMIT 20";
        $items = $db->query($sql)->getResult();

        $suggestion = array();
        foreach ($items as $item) {
            $suggestion[] = array('id' => $item->id, 'text' => $item->title);
        }

        return $this->response->setJSON($suggestion);
    }

    public function get_item_info_suggestion()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $item = $this->Invoice_items_model->get_item_info_suggestion(array(
            'item_id' => $this->request->getPost('item_id')
        ));

        if ($item) {
            $item->rate = $item->rate ? to_decimal_format($item->rate) : '';
            return $this->response->setJSON(array('success' => true, 'item_info' => $item));
        }

        return $this->response->setJSON(array('success' => false));
    }

    private function _make_row($data)
    {
        $request_code = $data->request_code ? $data->request_code : ('#' . $data->id);
        $project = $data->project_title ? $data->project_title : ($data->cost_center ? $data->cost_center : '-');
        $context = $project;
        if (!empty($data->is_internal)) {
            $context = app_lang('purchases_internal');
        } else if (!empty($data->os_id)) {
            $context = isset($data->os_title) && $data->os_title ? $data->os_title : ('OS #' . $data->os_id);
        }
        $priority_key = 'purchases_priority_' . $data->priority;
        $priority = app_lang($priority_key) ? app_lang($priority_key) : $data->priority;

                $actions = anchor(get_uri('purchases_requests/view/' . $data->id), "<i data-feather='external-link' class='icon-16'></i>", array('title' => app_lang('view_details'), 'class' => 'btn btn-sm btn-outline-secondary'));
        if ($this->_can_edit($data)) {
            $actions .= ' ' . anchor(get_uri('purchases_requests/request_form/' . $data->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
        }

        $status_actions = "";
        if ($this->_has_manage_permission() || $this->_has_approval_permission()) {
            foreach ($this->_get_status_keys() as $status_key) {
                if ($status_key === $data->status) {
                    continue;
                }
                $status_actions .= '<li role="presentation">' . js_anchor("<i data-feather='check-circle' class='icon-16'></i> " . app_lang('purchases_status_' . $status_key), array(
                    'title' => app_lang('purchases_status_' . $status_key),
                    "class" => "dropdown-item",
                    "data-action-url" => get_uri("purchases_requests/update_status/" . $data->id . "/" . $status_key),
                    "data-action" => "update",
                    "data-success-callback" => "reloadPurchasesRequestsTable"
                )) . '</li>';
            }
        }

        $delete_action = "";
        if ($this->_can_edit($data)) {
            $delete_action = js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                'title' => app_lang('delete'),
                "class" => "delete btn btn-sm btn-outline-danger",
                "data-id" => $data->id,
                "data-action-url" => get_uri("purchases_requests/delete"),
                "data-action" => "delete-confirmation"
            ));
        }

        $status_display = $this->_get_status_label($data->status);
        if ($status_actions) {
            $status_display = '
                <span class="dropdown inline-block">
                    <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="true" data-bs-display="static">
                        ' . $this->_get_status_label($data->status) . '
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" role="menu">' . $status_actions . '</ul>
                </span>';
        }

        if ($delete_action) {
            $actions .= ' ' . $delete_action;
        }

        $created_at = $data->created_at ? format_to_date($data->created_at, false) : '-';

        return array(
            esc($request_code),
            esc($context),
            esc($priority),
            $status_display,
            esc($data->requested_by_name ? $data->requested_by_name : '-'),
            $created_at,
            $actions
        );
    }

    private function _make_approval_row($data, $can_requester, $can_financial)
    {
        $request_code = $data->request_code ? $data->request_code : ('#' . $data->id);
        $project = $data->project_title ? $data->project_title : ($data->cost_center ? $data->cost_center : '-');
        $context = $project;
        if (!empty($data->is_internal)) {
            $context = app_lang('purchases_internal');
        } else if (!empty($data->os_id)) {
            $context = isset($data->os_title) && $data->os_title ? $data->os_title : ('OS #' . $data->os_id);
        }

        $approval_role = '-';
        if ($can_requester) {
            $approval_role = app_lang('purchases_approval_requester');
        } else if ($can_financial) {
            $approval_role = app_lang('purchases_approval_financial');
        }

        $total = $this->_get_request_quotation_total($data->id);
                $actions = anchor(get_uri('purchases_requests/view/' . $data->id), "<i data-feather='external-link' class='icon-16'></i>", array('title' => app_lang('view_details'), 'class' => 'btn btn-sm btn-outline-secondary'));
        if ($this->_can_edit($data)) {
            $actions .= ' ' . anchor(get_uri('purchases_requests/request_form/' . $data->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
        }

        $status_actions = "";
        if ($this->_has_manage_permission() || $this->_has_approval_permission()) {
            foreach ($this->_get_status_keys() as $status_key) {
                if ($status_key === $data->status) {
                    continue;
                }
                $status_actions .= '<li role="presentation">' . js_anchor("<i data-feather='check-circle' class='icon-16'></i> " . app_lang('purchases_status_' . $status_key), array(
                    'title' => app_lang('purchases_status_' . $status_key),
                    "class" => "dropdown-item",
                    "data-action-url" => get_uri("purchases_requests/update_status/" . $data->id . "/" . $status_key),
                    "data-action" => "update",
                    "data-success-callback" => "reloadPurchasesRequestsTable"
                )) . '</li>';
            }
        }

        $delete_action = "";
        if ($this->_can_edit($data)) {
            $delete_action = js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                'title' => app_lang('delete'),
                "class" => "delete btn btn-sm btn-outline-danger",
                "data-id" => $data->id,
                "data-action-url" => get_uri("purchases_requests/delete"),
                "data-action" => "delete-confirmation"
            ));
        }

        $status_display = $this->_get_status_label($data->status);
        if ($status_actions) {
            $status_display = '
                <span class="dropdown inline-block">
                    <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="true" data-bs-display="static">
                        ' . $this->_get_status_label($data->status) . '
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" role="menu">' . $status_actions . '</ul>
                </span>';
        }

        if ($delete_action) {
            $actions .= ' ' . $delete_action;
        }

        $created_at = $data->created_at ? format_to_date($data->created_at, false) : '-';

        return array(
            esc($request_code),
            esc($context),
            esc($priority),
            $status_display,
            esc($data->requested_by_name ? $data->requested_by_name : '-'),
            $created_at,
            $actions
        );
    }

    private function _get_status_label($status)
    {
        $class = 'secondary';
        if ($status === 'sent_to_quotation' || $status === 'submitted') {
            $class = 'primary';
        } else if ($status === 'quotation_in_progress') {
            $class = 'info';
        } else if ($status === 'approved') {
            $class = 'success';
        } else if ($status === 'awaiting_approval') {
            $class = 'warning';
        } else if ($status === 'approved_for_po') {
            $class = 'success';
        } else if ($status === 'po_created') {
            $class = 'info';
        } else if ($status === 'po_sent') {
            $class = 'primary';
        } else if ($status === 'partial_received') {
            $class = 'warning';
        } else if ($status === 'received') {
            $class = 'success';
        } else if ($status === 'rejected') {
            $class = 'danger';
        } else if ($status === 'converted') {
            $class = 'info';
        }

        return "<span class='badge bg-" . $class . "'>" . app_lang('purchases_status_' . $status) . "</span>";
    }

    private function _get_statuses_dropdown()
    {
        return array(
            array('id' => '', 'text' => '- ' . app_lang('status') . ' -'),
            array('id' => 'draft', 'text' => app_lang('purchases_status_draft')),
            array('id' => 'sent_to_quotation', 'text' => app_lang('purchases_status_sent_to_quotation')),
            array('id' => 'quotation_in_progress', 'text' => app_lang('purchases_status_quotation_in_progress')),
            array('id' => 'submitted', 'text' => app_lang('purchases_status_sent_to_quotation')),
            array('id' => 'awaiting_approval', 'text' => app_lang('purchases_status_awaiting_approval')),
            array('id' => 'approved_for_po', 'text' => app_lang('purchases_status_approved_for_po')),
            array('id' => 'po_created', 'text' => app_lang('purchases_status_po_created')),
            array('id' => 'po_sent', 'text' => app_lang('purchases_status_po_sent')),
            array('id' => 'partial_received', 'text' => app_lang('purchases_status_partial_received')),
            array('id' => 'received', 'text' => app_lang('purchases_status_received')),
            array('id' => 'rejected', 'text' => app_lang('purchases_status_rejected')),
            array('id' => 'approved', 'text' => app_lang('purchases_status_approved')),
            array('id' => 'converted', 'text' => app_lang('purchases_status_converted'))
        );
    }

    private function _get_status_keys()
    {
        return array(
            'draft',
            'sent_to_quotation',
            'quotation_in_progress',
            'submitted',
            'awaiting_approval',
            'approved_for_po',
            'po_created',
            'po_sent',
            'partial_received',
            'received',
            'rejected',
            'approved',
            'converted'
        );
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

        $client_names = array();
        if ($projects) {
            $client_ids = array();
            foreach ($projects as $project) {
                if (!empty($project->client_id)) {
                    $client_ids[] = (int)$project->client_id;
                }
            }
            $client_ids = array_unique($client_ids);
            if ($client_ids) {
                $db = db_connect('default');
                $clients_table = $db->prefixTable('clients');
                $rows = $db->table($clients_table)
                    ->select('id, company_name')
                    ->whereIn('id', $client_ids)
                    ->get()
                    ->getResult();
                foreach ($rows as $row) {
                    $client_names[(int)$row->id] = $row->company_name;
                }
            }
        }

        foreach ($projects as $project) {
            $client_name = isset($project->company_name) && $project->company_name ? $project->company_name : '';
            if (!$client_name && !empty($project->client_id)) {
                $client_name = get_array_value($client_names, (int)$project->client_id);
            }
            $label = $client_name ? ($project->title . " - " . $client_name) : $project->title;
            $dropdown[] = array('id' => $project->id, 'text' => $label);
        }

        return json_encode($dropdown);
    }

    protected function _get_projects_dropdown()
    {
        $project_options = array("status_id" => 1);
        if ($this->login_user->user_type === "staff") {
            if (!$this->can_manage_all_projects()) {
                $project_options["user_id"] = $this->login_user->id;
            }
        } else {
            $project_options["client_id"] = $this->login_user->client_id;
        }

        $projects = $this->Projects_model->get_details($project_options)->getResult();
        $projects_dropdown = array("" => "-");

        $client_names = array();
        if ($projects) {
            $client_ids = array();
            foreach ($projects as $project) {
                if (!empty($project->client_id)) {
                    $client_ids[] = (int)$project->client_id;
                }
            }
            $client_ids = array_unique($client_ids);
            if ($client_ids) {
                $db = db_connect('default');
                $clients_table = $db->prefixTable('clients');
                $rows = $db->table($clients_table)
                    ->select('id, company_name')
                    ->whereIn('id', $client_ids)
                    ->get()
                    ->getResult();
                foreach ($rows as $row) {
                    $client_names[(int)$row->id] = $row->company_name;
                }
            }
        }

        if ($projects) {
            foreach ($projects as $project) {
                $client_name = isset($project->company_name) && $project->company_name ? $project->company_name : '';
                if (!$client_name && !empty($project->client_id)) {
                    $client_name = get_array_value($client_names, (int)$project->client_id);
                }
                $label = $client_name ? ($project->title . " - " . $client_name) : $project->title;
                $projects_dropdown[$project->id] = $label;
            }
        }

        return $projects_dropdown;
    }

    private function _get_os_dropdown()
    {
        $db = db_connect('default');
        $os_table = $db->prefixTable('os_ordens');
        $clients_table = $db->prefixTable('clients');
        $has_os_table = false;

        try {
            $like = $db->query("SHOW TABLES LIKE '" . $os_table . "'");
            $has_os_table = ($like && method_exists($like, 'getResult') && count($like->getResult()) > 0);
        } catch (\Throwable $e) {
            $has_os_table = false;
        }

        $dropdown = array('' => '-');
        if (!$has_os_table) {
            return $dropdown;
        }

        $client_column = '';
        try {
            $fields = $db->getFieldNames($os_table);
            if (is_array($fields)) {
                if (in_array('cliente_id', $fields)) {
                    $client_column = 'cliente_id';
                } else if (in_array('client_id', $fields)) {
                    $client_column = 'client_id';
                }
            }
        } catch (\Throwable $e) {
            $client_column = '';
        }

        try {
            $query = $db->table($os_table)->select("$os_table.id, $os_table.titulo");
            if ($client_column) {
                $query->select("$clients_table.company_name");
                $query->join($clients_table, "$clients_table.id = $os_table.$client_column", "left");
            }

            $rows = $query
                ->where("$os_table.deleted", 0)
                ->orderBy("$os_table.id", 'DESC')
                ->limit(200)
                ->get()
                ->getResult();
        } catch (\Throwable $e) {
            return $dropdown;
        }

        foreach ($rows as $row) {
            $label = $row->titulo ? $row->titulo : ('OS #' . $row->id);
            if ($client_column && isset($row->company_name) && $row->company_name) {
                $label .= " - " . $row->company_name;
            }
            $dropdown[$row->id] = $label;
        }

        return $dropdown;
    }

    private function _get_items_dropdown_list()
    {
        $db = db_connect('default');
        $items_table = $db->prefixTable('items');
        $rows = $db->table($items_table)
            ->select('id, title')
            ->where('deleted', 0)
            ->orderBy('title', 'ASC')
            ->get()
            ->getResult();

        $dropdown = array('' => '-');
        foreach ($rows as $row) {
            $dropdown[$row->id] = $row->title;
        }

        return $dropdown;
    }

    private function _can_edit($request)
    {
        $request_id = (int)($request->id ?? 0);
        if ($request_id && $this->_has_order_for_request($request_id)) {
            return false;
        }

        $status = $request->status ?? '';
        if (!in_array($status, array('draft', 'rejected'))) {
            return false;
        }

        if ($this->login_user->is_admin) {
            return true;
        }

        if (!$this->_has_manage_permission()) {
            return false;
        }

        $requested_by = (int)($request->requested_by ?? 0);
        if (!$requested_by) {
            $requested_by = (int)($request->created_by ?? 0);
        }

        return $requested_by === (int)$this->login_user->id;
    }

    private function _can_submit($request)
    {
        $request_id = (int)($request->id ?? 0);
        if ($request_id && $this->_has_order_for_request($request_id)) {
            return false;
        }

        if ($this->login_user->is_admin) {
            return in_array($request->status, array('draft', 'rejected'));
        }

        if (!$this->_has_manage_permission()) {
            return false;
        }

        $requested_by = (int)($request->requested_by ?? 0);
        if (!$requested_by) {
            $requested_by = (int)($request->created_by ?? 0);
        }

        return $requested_by === (int)$this->login_user->id && in_array($request->status, array('draft', 'rejected'));
    }

    private function _has_approval_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_approve') == '1';
    }

    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_view') == '1'
            || get_array_value($permissions, 'purchases_manage') == '1'
            || get_array_value($permissions, 'purchases_approve') == '1'
            || get_array_value($permissions, 'purchases_financial_approve') == '1';
    }

    private function _has_manage_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_manage') == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
    }

    private function _get_linked_task_ids($request_id)
    {
        $request_id = (int)$request_id;
        if (!$request_id) {
            return array();
        }

        $db = db_connect('default');
        $table = $db->prefixTable('purchases_request_task_links_custom');
        if (!$db->tableExists($table)) {
            return array();
        }

        $rows = $db->table($table)->select('task_id')->where('request_id', $request_id)->where('deleted', 0)->get()->getResult();
        if (!$rows) {
            return array();
        }

        return array_values(array_filter(array_map(function ($row) {
            return (int)($row->task_id ?? 0);
        }, $rows)));
    }

    private function _get_linked_reminder_ids($request_id)
    {
        $request_id = (int)$request_id;
        if (!$request_id) {
            return array();
        }

        $db = db_connect('default');
        $table = $db->prefixTable('purchases_request_reminder_links_custom');
        if (!$db->tableExists($table)) {
            return array();
        }

        $rows = $db->table($table)->select('event_id')->where('request_id', $request_id)->where('deleted', 0)->get()->getResult();
        if (!$rows) {
            return array();
        }

        return array_values(array_filter(array_map(function ($row) {
            return (int)($row->event_id ?? 0);
        }, $rows)));
    }

    private function _get_request_title_prefix($request)
    {
        if (!$request) {
            return "";
        }

        $code = $request->request_code ? $request->request_code : ("RC-" . str_pad($request->id, 6, "0", STR_PAD_LEFT));
        return $code . " - ";
    }

    private function _make_task_row_simple($data)
    {
        $title_value = $data->title;
        $title = modal_anchor(get_uri("tasks/view"), $title_value, array(
            "title" => app_lang('task_info') . " #$data->id",
            "data-post-id" => $data->id,
            "data-modal-lg" => "1"
        ));

        $assigned_to = "-";
        if (!empty($data->assigned_to)) {
            $assigned_name = $data->assigned_to_user ?? "";
            if ($assigned_name) {
                if (!empty($data->user_type) && $data->user_type !== "staff") {
                    $assigned_to = get_client_contact_profile_link($data->assigned_to, $assigned_name);
                } else {
                    $assigned_to = get_team_member_profile_link($data->assigned_to, $assigned_name);
                }
            }
        }

        $status_text = $data->status_key_name ? app_lang($data->status_key_name) : ($data->status_title ?? "-");
        $status = "<span class='badge bg-secondary'>" . esc($status_text) . "</span>";

        $options = "";
        if ($this->login_user->is_admin || (int)$data->created_by === (int)$this->login_user->id) {
            $options .= modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array(
                "class" => "edit",
                "title" => app_lang('edit_task'),
                "data-post-id" => $data->id
            ));
            $options .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => app_lang('delete_task'),
                "class" => "delete",
                "data-id" => $data->id,
                "data-action-url" => get_uri("tasks/delete"),
                "data-action" => "delete-confirmation"
            ));
        }

        return array(
            $data->id,
            $title,
            $assigned_to,
            $status,
            $options
        );
    }

    private function _make_reminder_row($data)
    {
        $context_info = get_reminder_context_info($data);
        $context_icon = get_array_value($context_info, "context_icon");
        $context_icon = $context_icon ? "<i class='icon-14 text-off' data-feather='$context_icon'></i> " : "";
        $title_text = $data->title;
        $title_value = "<span class='strong'>$context_icon" . link_it($title_text) . "</span>";

        $icon = "";
        $target_date = "";
        if ($data->snoozing_time) {
            $icon = "<span class='icon-14 text-off'>" . view("reminders/svg_icons/snooze") . "</span>";
            $target_date = new \DateTime($data->snoozing_time);
        } else if ($data->recurring) {
            $icon = "<i class='icon-14 text-off' data-feather='repeat'></i>";
            if ($data->next_recurring_time) {
                $target_date = new \DateTime($data->next_recurring_time);
            }
        }

        if ($target_date) {
            $data->start_date = $target_date->format("Y-m-d");
            $data->start_time = $target_date->format("H:i:s");
        }

        $data->end_date = $data->start_date;
        $time_value = view("events/event_time", array("model_info" => $data, "is_reminder" => true));
        $time_value = "<div class='small'>$icon " . $time_value . "</div>";

        $missed_reminder_class = "";
        $local_time = get_my_local_time("Y-m-d H:i") . ":00";

        if ($data->reminder_status === 'new' && ($data->start_date . ' ' . $data->start_time) < $local_time) {
            $missed_reminder_class = "missed-reminder";
        }

        $title = "<span class='$missed_reminder_class'>" . $title_value . $time_value . "</span>";

        $options = js_anchor("<i data-feather='check-circle' class='icon-16'></i>", array(
            "class" => "mark-as-done text-success reminder-action",
            "title" => app_lang('mark_as_done'),
            "data-action-url" => get_uri("events/mark_as_done"),
            "data-action" => "close-reminder-confirmation",
            "data-post-id" => $data->id
        ));

        $options .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            "class" => "delete text-danger reminder-action",
            "title" => app_lang('delete_reminder'),
            "data-action-url" => get_uri("events/delete"),
            "data-action" => "delete-confirmation",
            "data-id" => $data->id
        ));

        return array(
            $data->id,
            $title,
            $options
        );
    }

    private function _log_status_change($context_type, $context_id, $old_status, $new_status, $note = '')
    {
        $data = array(
            'company_id' => $this->_get_company_id(),
            'context_type' => $context_type,
            'context_id' => $context_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'note' => $note,
            'created_at' => get_my_local_time(),
            'created_by' => $this->login_user->id
        );

        $this->Purchases_logs_model->ci_save($data, 0);
    }

    private function _can_approve($request)
    {
        return $this->_has_approval_permission() && $request->status === 'submitted';
    }

    private function _can_convert($request)
    {
        return $this->_has_approval_permission() && $request->status === 'approved';
    }

    private function _get_request_or_404($id)
    {
        $request = $this->Purchases_requests_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();

        if (!$request) {
            show_404();
        }

        return $request;
    }

    private function _save_request_items($request_id)
    {
        $item_ids = $this->request->getPost('item_id');
        $descriptions = $this->request->getPost('description');
        $quantities = $this->request->getPost('quantity');
        $units = $this->request->getPost('unit');
        $desired_dates = $this->request->getPost('desired_date');
        $notes = $this->request->getPost('note');

        if (!is_array($item_ids)) {
            $item_ids = array();
        }

        $db = db_connect('default');
        $items_table = $db->prefixTable('purchases_request_items');
        $db->table($items_table)->where('request_id', $request_id)->update(array('deleted' => 1));

        $max = count($item_ids);
        for ($i = 0; $i < $max; $i++) {
            $raw_item_id = get_array_value($item_ids, $i);
            $item_id = get_only_numeric_value($raw_item_id);
            $description = trim((string)get_array_value($descriptions, $i));
            $qty = unformat_currency(get_array_value($quantities, $i));
            $unit = trim((string)get_array_value($units, $i));
              $desired_date = get_array_value($desired_dates, $i);
            $note = trim((string)get_array_value($notes, $i));

            if (!$item_id && $raw_item_id && !is_numeric($raw_item_id) && !$description) {
                $description = trim((string)$raw_item_id);
            }

              if (!$description && !$item_id) {
                  continue;
              }
              if (!$desired_date) {
                  return $this->response->setJSON(array(
                      'success' => false,
                      'message' => app_lang('purchases_desired_date_required')
                  ));
              }

            $data = array(
                'company_id' => $this->_get_company_id(),
                'request_id' => $request_id,
                'item_id' => $item_id ? $item_id : null,
                'description' => $description,
                'unit' => $unit ? $unit : 'UN',
                'quantity' => $qty ? $qty : 0,
                'rate' => 0,
                'total' => 0,
                'desired_date' => $desired_date ? $desired_date : null,
                'note' => $note,
                'created_at' => get_my_local_time(),
                'created_by' => $this->login_user->id
            );

            $this->Purchases_request_items_model->save($data);
        }
    }

    public function approvals()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        return $this->template->rander('Purchases\\Views\\approvals\\index');
    }

    public function approvals_list_data()
    {
        try {
            if (!$this->_has_view_permission()) {
                return $this->_json_permission_denied();
            }

            $options = array(
                'company_id' => $this->_get_company_id(),
                'status' => 'awaiting_approval'
            );

            $mine_only = $this->request->getPost('mine_only') ? true : false;

            $query = $this->Purchases_requests_model->get_details($options);
            $list_data = ($query && method_exists($query, 'getResult')) ? $query->getResult() : array();
            $result = array();
            foreach ($list_data as $data) {
                $can_requester = $this->_can_approve_requester($data);
                $can_financial = $this->_can_approve_financial_with_limit($data);

                if ($mine_only && !$can_requester && !$can_financial) {
                    continue;
                }

                $result[] = $this->_make_approval_row($data, $can_requester, $can_financial);
            }

            return $this->response->setJSON(array('data' => $result));
        } catch (\Throwable $e) {
            return $this->response->setJSON(array(
                'data' => array(),
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function tasks_list_data($request_id = 0)
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $request_id = (int)$request_id;
        $request = $this->Purchases_requests_model->get_details(array(
            'id' => $request_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$request) {
            return $this->response->setJSON(array('data' => array()));
        }

        $prefix = $this->_get_request_title_prefix($request);
        $task_ids = $this->_get_linked_task_ids($request_id);
        if (!$task_ids) {
            return $this->response->setJSON(array('data' => array()));
        }

        $Custom_fields_model = model('App\\Models\\Custom_fields_model');
        $Tasks_model = model('App\\Models\\Tasks_model');
        $custom_fields = $Custom_fields_model->get_available_fields_for_table("tasks", $this->login_user->is_admin, $this->login_user->user_type);

        $options = array(
            "task_ids" => implode(",", $task_ids),
            "custom_fields" => $custom_fields,
            "unread_status_user_id" => $this->login_user->id
        );

        $list_data = $Tasks_model->get_details($options);
        $rows = array();
        $tasks = ($list_data && method_exists($list_data, 'getResult')) ? $list_data->getResult() : array();
        foreach ($tasks as $task) {
            $rows[] = $this->_make_task_row_simple($task);
        }

        return $this->response->setJSON(array("data" => $rows));
    }

    public function reminders_list_data($request_id = 0, $type = "reminders")
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        if (!function_exists('can_access_reminders_module') || !can_access_reminders_module()) {
            return $this->_json_permission_denied();
        }

        $request_id = (int)$request_id;
        $request = $this->Purchases_requests_model->get_details(array(
            'id' => $request_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$request) {
            return $this->response->setJSON(array('data' => array()));
        }

        $prefix = $this->_get_request_title_prefix($request);
        $event_ids = $this->_get_linked_reminder_ids($request_id);
        if (!$event_ids) {
            return $this->response->setJSON(array('data' => array()));
        }

        $db = db_connect('default');
        $events_table = $db->prefixTable('events');
        $ids_sql = implode(',', $event_ids);

        $sql = "SELECT * FROM $events_table WHERE $events_table.deleted=0 AND $events_table.type='reminder' AND $events_table.id IN ($ids_sql) AND $events_table.created_by=" . (int)$this->login_user->id;
        $list_data = $db->query($sql)->getResult();

        $rows = array();
        foreach ($list_data as $data) {
            $rows[] = $this->_make_reminder_row($data);
        }

        return $this->response->setJSON(array("data" => $rows));
    }

    private function _has_order_for_request($request_id)
    {
        $orders_query = $this->Purchases_orders_model->get_details(array(
            'request_id' => (int)$request_id,
            'company_id' => $this->_get_company_id()
        ));

        $orders = ($orders_query && method_exists($orders_query, 'getResult')) ? $orders_query->getResult() : array();
        return !empty($orders);
    }

    private function _get_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return $this->login_user->company_id;
        }

        return get_default_company_id();
    }

    private function _can_approve_requester($request)
    {
        if ($request->status !== 'awaiting_approval') {
            return false;
        }

        $requester_id = (int)($request->requested_by ?? 0);
        if (!$requester_id) {
            $requester_id = (int)($request->created_by ?? 0);
        }

        return $requester_id === (int)$this->login_user->id;
    }

    private function _can_approve_financial($request)
    {
        if ($request->status !== 'awaiting_approval') {
            return false;
        }

        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_financial_approve') == '1';
    }

    private function _can_approve_financial_with_limit($request)
    {
        if (!$this->_can_approve_financial($request)) {
            return false;
        }

        if ($this->login_user->is_admin) {
            return true;
        }

        $company_id = $this->_get_company_id();
        $limit = $this->_get_financial_limit_for_user($this->login_user->id, $company_id);
        if ($limit <= 0) {
            return false;
        }

        $total = $this->_get_request_quotation_total($request->id);
        return $total <= $limit;
    }

    private function _can_reject_approval($request)
    {
        return $request->status === 'awaiting_approval' && ($this->_can_approve_requester($request) || $this->_can_approve_financial_with_limit($request));
    }

    private function _handle_approval($approval_role)
    {
        if (!$this->_has_manage_permission() && !$this->_has_approval_permission() && !$this->_can_approve_financial((object) array("status" => "awaiting_approval"))) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        $comment = trim((string)$this->request->getPost('comment'));
        $request = $this->_get_request_or_404($id);
        if ($request->status !== 'awaiting_approval') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $total = $this->_get_request_quotation_total($id);
        $company_id = $this->_get_company_id();

        if ($approval_role === "requester" && !$this->_can_approve_requester($request)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        if ($approval_role === "financial" && !$this->_can_approve_financial($request)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $approvals = $this->Purchases_request_approvals_model->get_details(array(
            'request_id' => $id,
            'company_id' => $company_id
        ))->getResult();
        $approval_map = array();
        foreach ($approvals as $approval) {
            $approval_map[$approval->approval_type] = $approval;
        }

        if (!isset($approval_map[$approval_role])) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        if (!empty($approval_map[$approval_role]->approved)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_saved')));
        }

        $approval_limit_used = null;
        if ($approval_role === "financial") {
            $limit = $this->_get_financial_limit_for_user($this->login_user->id, $company_id);
            if (!$this->login_user->is_admin) {
                if ($limit <= 0 || $total > $limit) {
                    return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_financial_limit_exceeded')));
                }
            } else if ($limit <= 0) {
                $limit = $total;
            }
            $approval_limit_used = $limit > 0 ? $limit : null;
        }

        $data = array(
            'approved' => 1,
            'approved_by' => $this->login_user->id,
            'approved_at' => get_my_local_time(),
            'comment' => $comment,
            'approval_limit_used' => $approval_limit_used,
            'total_value_at_approval' => $total
        );
        $this->Purchases_request_approvals_model->ci_save($data, $approval_map[$approval_role]->id);

        $this->_apply_small_limit_approvals($request, $total);
        $this->_update_request_status_from_approvals($request);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    private function _apply_small_limit_approvals($request, $total)
    {
        $company_id = $this->_get_company_id();
        $optional = (int)$this->Purchases_settings_model->get_setting('small_purchase_financial_optional', $company_id) === 1;
        $buyer_limit = (float)$this->Purchases_settings_model->get_setting('buyer_small_limit', $company_id);
        $requester_limit = (float)$this->Purchases_settings_model->get_setting('requester_small_limit', $company_id);

        if (!$optional) {
            return;
        }

        $approvals = $this->Purchases_request_approvals_model->get_details(array(
            'request_id' => $request->id,
            'company_id' => $company_id
        ))->getResult();
        $approval_map = array();
        foreach ($approvals as $approval) {
            $approval_map[$approval->approval_type] = $approval;
        }

        if (!empty($approval_map["financial"]->approved)) {
            return;
        }

        if ($total > 0 && $requester_limit > 0 && $total <= $requester_limit) {
            if (!empty($approval_map["requester"]->approved)) {
                $data = array(
                    'approved' => 1,
                    'approved_by' => $approval_map["requester"]->approved_by,
                    'approved_at' => get_my_local_time(),
                    'comment' => app_lang('purchases_auto_financial_by_requester'),
                    'approval_limit_used' => $requester_limit,
                    'total_value_at_approval' => $total
                );
                $this->Purchases_request_approvals_model->ci_save($data, $approval_map["financial"]->id);
            }
        } elseif ($total > 0 && $buyer_limit > 0 && $total <= $buyer_limit) {
            if ($this->_has_manage_permission()) {
                $data = array(
                    'approved' => 1,
                    'approved_by' => $this->login_user->id,
                    'approved_at' => get_my_local_time(),
                    'comment' => app_lang('purchases_auto_financial_by_buyer'),
                    'approval_limit_used' => $buyer_limit,
                    'total_value_at_approval' => $total
                );
                $this->Purchases_request_approvals_model->ci_save($data, $approval_map["financial"]->id);
            }
        }
    }

    private function _update_request_status_from_approvals($request)
    {
        $company_id = $this->_get_company_id();
        $approvals = $this->Purchases_request_approvals_model->get_details(array(
            'request_id' => $request->id,
            'company_id' => $company_id
        ))->getResult();

        $approved_all = true;
        foreach ($approvals as $approval) {
            if (!$approval->approved) {
                $approved_all = false;
                break;
            }
        }

        if ($approved_all) {
            $old_status = $request->status;
            $update_data = array(
                'status' => 'approved_for_po',
                'updated_at' => get_my_local_time()
            );
            $this->Purchases_requests_model->ci_save($update_data, $request->id);
            $this->_log_status_change('request', $request->id, $old_status, 'approved_for_po');
            $this->_notify_request_status($request, 'approved_for_po');
            $this->_notify_buyers($request->id, 'purchase_request_approved_for_po');
        } else {
            $this->_notify_request_status($request, 'approval_partial');
        }
    }

    private function _get_financial_limit_for_user($user_id, $company_id)
    {
        $row = $this->Purchases_approvers_model->get_one_by_user($user_id, $company_id);
        if ($row && $row->financial_limit !== null && $row->financial_limit !== '') {
            return (float)$row->financial_limit;
        }

        $Roles_model = model('App\\Models\\Roles_model');
        $user = $this->Users_model->get_one($user_id);
        if ($user && $user->role_id) {
            $role = $Roles_model->get_one($user->role_id);
            $permissions = $role && $role->permissions ? unserialize($role->permissions) : array();
            if (is_array($permissions)) {
                $limit = get_array_value($permissions, 'purchases_financial_limit');
                if ($limit !== null && $limit !== '') {
                    return (float)$limit;
                }
            }
        }

        return 0;
    }

    private function _get_request_quotation_total($request_id)
    {
        $db = db_connect('default');
        $items_table = $db->prefixTable('purchases_quotation_items');
        $prices_table = $db->prefixTable('purchases_quotation_item_prices');

        $sql = "SELECT SUM((qi.qty * qp.unit_price) + qp.freight_value) AS total
            FROM $items_table AS qi
            LEFT JOIN $prices_table AS qp ON qp.request_item_id=qi.request_item_id AND qp.quotation_id=qi.quotation_id AND qp.is_winner=1
            WHERE qi.deleted=0 AND qp.deleted=0 AND qi.request_id=" . (int)$request_id;

        $query = $db->query($sql);
        if (!$query || !method_exists($query, 'getRow')) {
            return 0;
        }

        $row = $query->getRow();
        return $row && $row->total ? (float)$row->total : 0;
    }

    private function _notify_request_status($request, $status)
    {
        $requester_id = (int)($request->requested_by ?? 0);
        if (!$requester_id) {
            $requester_id = (int)($request->created_by ?? 0);
        }

        if (!$requester_id) {
            return;
        }

        $event = "purchase_request_" . $status;
        $Notification_settings_model = model('App\\Models\\Notification_settings_model');
        $notification_settings = $Notification_settings_model->get_one_where(array("event" => $event, "deleted" => 0));
        if (!$notification_settings || !$notification_settings->id) {
            $settings_data = array(
                "event" => $event,
                "category" => "purchases",
                "enable_email" => 1,
                "enable_web" => 1,
                "enable_slack" => 0,
                "notify_to_team" => "",
                "notify_to_team_members" => "",
                "notify_to_terms" => "",
                "sort" => 900,
                "deleted" => 0
            );
            $notification_settings_id = $Notification_settings_model->ci_save($settings_data, 0);
            if ($notification_settings_id) {
                $notification_settings = $Notification_settings_model->get_one($notification_settings_id);
            }
        }

        if ($notification_settings && $notification_settings->id) {
            $update_settings = array("notify_to_team_members" => (string)$requester_id);
            $Notification_settings_model->ci_save($update_settings, $notification_settings->id);
        }

        log_notification($event, array("estimate_request_id" => $request->id), $this->login_user->id);
    }

    private function _notify_request_sent_for_quotation($request_id)
    {
        $event = "purchase_request_sent_for_quotation";
        $notification_user_ids = $this->_get_users_with_purchases_manage_permission();

        $Notification_settings_model = model('App\\Models\\Notification_settings_model');
        $notification_settings = $Notification_settings_model->get_one_where(array("event" => $event, "deleted" => 0));
        if (!$notification_settings || !$notification_settings->id) {
            $settings_data = array(
                "event" => $event,
                "category" => "purchases",
                "enable_email" => 1,
                "enable_web" => 1,
                "enable_slack" => 0,
                "notify_to_team" => "",
                "notify_to_team_members" => "",
                "notify_to_terms" => "",
                "sort" => 900,
                "deleted" => 0
            );
            $notification_settings_id = $Notification_settings_model->ci_save($settings_data, 0);

            if ($notification_settings_id) {
                $notification_settings = $Notification_settings_model->get_one($notification_settings_id);
            }
        }

        if ($notification_settings && $notification_settings->id) {
            $update_settings = array("notify_to_team_members" => implode(",", $notification_user_ids));
            $Notification_settings_model->ci_save($update_settings, $notification_settings->id);
        }

        log_notification($event, array(
            "estimate_request_id" => $request_id
        ), $this->login_user->id);
    }

    private function _notify_buyers($request_id, $event)
    {
        $user_ids = $this->_get_users_with_purchases_manage_permission();
        if (!$user_ids) {
            return;
        }

        $this->_notify_users($event, $user_ids, $request_id);
    }

    private function _notify_users($event, $user_ids, $request_id)
    {
        $Notification_settings_model = model('App\\Models\\Notification_settings_model');
        $notification_settings = $Notification_settings_model->get_one_where(array("event" => $event, "deleted" => 0));
        if (!$notification_settings || !$notification_settings->id) {
            $settings_data = array(
                "event" => $event,
                "category" => "purchases",
                "enable_email" => 1,
                "enable_web" => 1,
                "enable_slack" => 0,
                "notify_to_team" => "",
                "notify_to_team_members" => "",
                "notify_to_terms" => "",
                "sort" => 900,
                "deleted" => 0
            );
            $notification_settings_id = $Notification_settings_model->ci_save($settings_data, 0);
            if ($notification_settings_id) {
                $notification_settings = $Notification_settings_model->get_one($notification_settings_id);
            }
        }

        if ($notification_settings && $notification_settings->id) {
            $update_settings = array("notify_to_team_members" => implode(",", $user_ids));
            $Notification_settings_model->ci_save($update_settings, $notification_settings->id);
        }

        log_notification($event, array("estimate_request_id" => $request_id), $this->login_user->id);
    }

    private function _get_users_with_purchases_manage_permission()
    {
        $Users_model = model('App\\Models\\Users_model');
        $Roles_model = model('App\\Models\\Roles_model');
        $users = $Users_model->get_details(array(
            "user_type" => "staff",
            "status" => "active"
        ))->getResult();

        $role_permissions_map = array();
        $user_ids = array();

        foreach ($users as $user) {
            if ($user->is_admin) {
                $user_ids[] = $user->id;
                continue;
            }

            if (!$user->role_id) {
                continue;
            }

            if (!isset($role_permissions_map[$user->role_id])) {
                $role = $Roles_model->get_one($user->role_id);
                $permissions = $role && $role->permissions ? unserialize($role->permissions) : array();
                $role_permissions_map[$user->role_id] = is_array($permissions) ? $permissions : array();
            }

            if (get_array_value($role_permissions_map[$user->role_id], "purchases_manage") == "1") {
                $user_ids[] = $user->id;
            }
        }

        return array_unique($user_ids);
    }
}




















