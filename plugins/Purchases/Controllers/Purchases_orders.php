<?php

namespace Purchases\Controllers;

use App\Controllers\Security_Controller;

class Purchases_orders extends Security_Controller
{
    private $Purchases_orders_model;
    private $Purchases_order_items_model;
    private $Purchases_suppliers_model;
    private $Purchases_requests_model;
    private $Purchases_goods_receipts_model;
    private $Purchases_goods_receipt_items_model;
    private $Purchases_attachments_model;
    public $Users_model;
    private $Purchases_logs_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Purchases_orders_model = model('Purchases\\Models\\Purchases_orders_model');
        $this->Purchases_order_items_model = model('Purchases\\Models\\Purchases_order_items_model');
        $this->Purchases_suppliers_model = model('Purchases\\Models\\Purchases_suppliers_model');
        $this->Purchases_requests_model = model('Purchases\\Models\\Purchases_requests_model');
        $this->Purchases_goods_receipts_model = model('Purchases\\Models\\Purchases_goods_receipts_model');
        $this->Purchases_goods_receipt_items_model = model('Purchases\\Models\\Purchases_goods_receipt_items_model');
        $this->Purchases_attachments_model = model('Purchases\\Models\\Purchases_attachments_model');
        $this->Users_model = model('App\\Models\\Users_model');
        $this->Purchases_logs_model = model('Purchases\\Models\\Purchases_logs_model');
    }

    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $view_data['statuses_dropdown'] = json_encode($this->_get_statuses_dropdown());
        $view_data['suppliers_dropdown'] = json_encode($this->_get_suppliers_dropdown_list_data());
        return $this->template->rander('Purchases\\Views\\orders\\index', $view_data);
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

            $supplier_id = $this->request->getPost('supplier_id');
            if ($supplier_id) {
                $options['supplier_id'] = get_only_numeric_value($supplier_id);
            }

            $query = $this->Purchases_orders_model->get_details($options);
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

    public function view($id = 0)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        if (!$id) {
            show_404();
        }

        $order = $this->Purchases_orders_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$order) {
            show_404();
        }

        $items = $this->Purchases_order_items_model->get_details(array(
            'order_id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        $receipts = $this->Purchases_goods_receipts_model->get_details(array(
            'order_id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        $receipt_items = array();
        $receipt_files = array();
        foreach ($receipts as $receipt) {
            $receipt_items[$receipt->id] = $this->Purchases_goods_receipt_items_model->get_details(array(
                'receipt_id' => $receipt->id,
                'company_id' => $this->_get_company_id()
            ))->getResult();

            $receipt_files[$receipt->id] = $this->Purchases_attachments_model->get_details(array(
                'context_type' => 'goods_receipt',
                'context_id' => $receipt->id,
                'company_id' => $this->_get_company_id()
            ))->getResult();

            if (!empty($receipt->received_by)) {
                $user = $this->Users_model->get_one($receipt->received_by);
                $receipt->received_by_name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '-';
            } else {
                $receipt->received_by_name = '-';
            }
        }

        $view_data = array(
            'order_info' => $order,
            'order_items' => $items,
            'receipts' => $receipts,
            'receipt_items' => $receipt_items,
            'receipt_files' => $receipt_files,
            'status_label' => $this->_get_status_label($order->status),
            'can_update_status' => $this->_has_manage_permission() && in_array($order->status, array('open', 'sent', 'partial_received', 'received'))
        );

        return $this->template->rander('Purchases\\Views\\orders\\view', $view_data);
    }

    public function update_status($id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        $status = $this->request->getPost('status');
        if (!$id || !$status) {
            return $this->response->setJSON(array('success' => false));
        }

        $allowed = array('open', 'sent', 'partial_received', 'received', 'canceled');
        if (!in_array($status, $allowed)) {
            return $this->response->setJSON(array('success' => false));
        }

        $order = $this->Purchases_orders_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$order) {
            return $this->response->setJSON(array('success' => false));
        }

        $old_status = $order->status;
        $data = array(
            'status' => $status,
            'updated_at' => get_my_local_time()
        );
        $ok = $this->Purchases_orders_model->ci_save($data, $id);
        if ($ok && $old_status !== $status) {
            $this->_log_status_change('order', $id, $old_status, $status);
            if (!empty($order->request_id)) {
                $this->_sync_request_status_from_orders((int)$order->request_id);
            }
        }
        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    public function print_view($id = 0)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $order = $this->Purchases_orders_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$order) {
            show_404();
        }

        $items = $this->Purchases_order_items_model->get_details(array(
            'order_id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        $view_data = array(
            'order_info' => $order,
            'order_items' => $items
        );

        return $this->template->view('Purchases\\Views\\orders\\print', $view_data);
    }

    private function _make_row($data)
    {
        $po_code = $data->po_code ? $data->po_code : ('#' . $data->id);
        $request_code = $data->request_code ? $data->request_code : ($data->request_id ? ('#' . $data->request_id) : '-');
        $request_link = $data->request_id ? anchor(get_uri('purchases_requests/view/' . $data->request_id), esc($request_code), array('title' => app_lang('purchases_request'))) : esc($request_code);
        $supplier = $data->supplier_name ? $data->supplier_name : '-';
        $project = $data->project_title ? $data->project_title : ($data->cost_center ? $data->cost_center : '-');
        $order_date = $data->order_date ? format_to_date($data->order_date, false) : '-';

        $actions = anchor(get_uri('purchases_orders/view/' . $data->id), "<i data-feather='external-link' class='icon-16'></i>", array('title' => app_lang('view_details'), 'class' => 'btn btn-sm btn-outline-secondary'));

        return array(
            esc($po_code),
            $request_link,
            esc($supplier),
            esc($project),
            $this->_get_status_label($data->status),
            $order_date,
            to_currency($data->total),
            $actions
        );
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

    private function _get_statuses_dropdown()
    {
        return array(
            array('id' => '', 'text' => '- ' . app_lang('status') . ' -'),
            array('id' => 'open', 'text' => app_lang('purchases_po_status_open')),
            array('id' => 'sent', 'text' => app_lang('purchases_po_status_sent')),
            array('id' => 'partial_received', 'text' => app_lang('purchases_po_status_partial_received')),
            array('id' => 'received', 'text' => app_lang('purchases_po_status_received')),
            array('id' => 'canceled', 'text' => app_lang('purchases_po_status_canceled'))
        );
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

        return $dropdown;
    }

    private function _get_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return $this->login_user->company_id;
        }

        return get_default_company_id();
    }

    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_view') == '1'
            || get_array_value($permissions, 'purchases_manage') == '1'
            || get_array_value($permissions, 'purchases_approve') == '1';
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

    private function _sync_request_status_from_orders($request_id)
    {
        $orders = $this->Purchases_orders_model->get_details(array(
            'request_id' => $request_id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        if (!$orders) {
            return;
        }

        $all_received = true;
        $any_partial = false;
        $any_sent = false;
        foreach ($orders as $order) {
            $status = $order->status;
            if ($status !== 'received') {
                $all_received = false;
            }
            if ($status === 'partial_received') {
                $any_partial = true;
            }
            if ($status === 'sent') {
                $any_sent = true;
            }
        }

        $new_status = 'po_created';
        if ($all_received) {
            $new_status = 'received';
        } elseif ($any_partial) {
            $new_status = 'partial_received';
        } elseif ($any_sent) {
            $new_status = 'po_sent';
        }

        $request = $this->Purchases_requests_model->get_details(array(
            'id' => $request_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$request || $request->status === $new_status) {
            return;
        }

        $old_status = $request->status;
        $update_data = array(
            'status' => $new_status,
            'updated_at' => get_my_local_time()
        );
        $this->Purchases_requests_model->ci_save($update_data, $request_id);
        $this->_log_status_change('request', $request_id, $old_status, $new_status);
        $this->_notify_request_status($request, $new_status);
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
}
