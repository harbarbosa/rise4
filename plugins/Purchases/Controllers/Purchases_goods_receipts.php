<?php

namespace Purchases\Controllers;

use App\Controllers\Security_Controller;

class Purchases_goods_receipts extends Security_Controller
{
    private $Purchases_orders_model;
    private $Purchases_order_items_model;
    private $Purchases_goods_receipts_model;
    private $Purchases_goods_receipt_items_model;
    private $Purchases_attachments_model;
    private $Purchases_requests_model;
    public $Users_model;
    private $Purchases_logs_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Purchases_orders_model = model('Purchases\\Models\\Purchases_orders_model');
        $this->Purchases_order_items_model = model('Purchases\\Models\\Purchases_order_items_model');
        $this->Purchases_goods_receipts_model = model('Purchases\\Models\\Purchases_goods_receipts_model');
        $this->Purchases_goods_receipt_items_model = model('Purchases\\Models\\Purchases_goods_receipt_items_model');
        $this->Purchases_attachments_model = model('Purchases\\Models\\Purchases_attachments_model');
        $this->Purchases_requests_model = model('Purchases\\Models\\Purchases_requests_model');
        $this->Users_model = model('App\\Models\\Users_model');
        $this->Purchases_logs_model = model('Purchases\\Models\\Purchases_logs_model');
    }

    public function modal_form()
    {
        if (!$this->_has_manage_permission()) {
            app_redirect('forbidden');
        }

        $order_id = (int)$this->request->getPost('order_id');
        if (!$order_id) {
            show_404();
        }

        $order = $this->Purchases_orders_model->get_details(array(
            'id' => $order_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$order) {
            show_404();
        }

        $items = $this->_get_order_items_with_pending($order_id);
        $view_data = array(
            'order_info' => $order,
            'items' => $items,
            'received_by_dropdown' => $this->_get_staff_dropdown()
        );

        return $this->template->view('Purchases\\Views\\receipts\\modal_form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'order_id' => 'required|numeric',
            'receipt_date' => 'required'
        ));

        $order_id = (int)$this->request->getPost('order_id');
        $order = $this->Purchases_orders_model->get_details(array(
            'id' => $order_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$order) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $items_pending = $this->_get_order_items_with_pending($order_id);
        $pending_map = array();
        foreach ($items_pending as $item) {
            $pending_map[$item->id] = $item;
        }

        $order_item_ids = $this->request->getPost('order_item_id');
        $qty_received_now = $this->request->getPost('qty_received_now');
        $item_notes = $this->request->getPost('item_note');

        if (!is_array($order_item_ids)) {
            $order_item_ids = array();
        }

        $receipt_date = $this->request->getPost('receipt_date');
        $received_by = (int)$this->request->getPost('received_by');
        $nf_number = trim((string)$this->request->getPost('nf_number'));
        $note = trim((string)$this->request->getPost('note'));

        $receipt_data = array(
            'company_id' => $this->_get_company_id(),
            'order_id' => $order_id,
            'received_by' => $received_by ? $received_by : null,
            'nf_number' => $nf_number,
            'status' => 'received',
            'receipt_date' => $receipt_date,
            'note' => $note,
            'created_at' => get_my_local_time(),
            'created_by' => $this->login_user->id
        );

        $receipt_id = $this->Purchases_goods_receipts_model->ci_save($receipt_data, 0);
        if (!$receipt_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }
        if (!is_int($receipt_id)) {
            $receipt_id = db_connect('default')->insertID();
        }

        $total_received = 0;
        foreach ($order_item_ids as $index => $order_item_id) {
            $order_item_id = (int)$order_item_id;
            $pending_item = get_array_value($pending_map, $order_item_id);
            if (!$pending_item) {
                continue;
            }

            $qty_now = unformat_currency(get_array_value($qty_received_now, $index));
            if ($qty_now <= 0) {
                continue;
            }

            if ($qty_now > $pending_item->pending_qty) {
                return $this->response->setJSON(array(
                    'success' => false,
                    'message' => app_lang('purchases_receipt_qty_exceeded') . ': ' . ($pending_item->description ? $pending_item->description : '#' . $order_item_id)
                ));
            }

            $item_data = array(
                'company_id' => $this->_get_company_id(),
                'receipt_id' => $receipt_id,
                'order_item_id' => $order_item_id,
                'item_id' => $pending_item->item_id,
                'description' => $pending_item->description,
                'unit' => $pending_item->unit,
                'quantity_received' => $qty_now,
                'note' => trim((string)get_array_value($item_notes, $index)),
                'created_at' => get_my_local_time(),
                'created_by' => $this->login_user->id
            );
            $this->Purchases_goods_receipt_items_model->ci_save($item_data, 0);
            $total_received += $qty_now;
        }

        if ($total_received <= 0) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_receipt_qty_required')));
        }

        $target_path = get_setting('timeline_file_path');
        $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, 'purchases_receipt');
        $new_files = $files_data ? @unserialize($files_data) : array();
        if (is_array($new_files) && count($new_files)) {
            foreach ($new_files as $file) {
                $file_name = get_array_value($file, 'file_name');
                $original = get_array_value($file, 'file_name');
                $file_size = get_array_value($file, 'file_size');

                $attachment_data = array(
                    'company_id' => $this->_get_company_id(),
                    'context_type' => 'goods_receipt',
                    'context_id' => $receipt_id,
                    'file_name' => $file_name,
                    'original_file_name' => $original,
                    'file_size' => $file_size,
                    'mime_type' => null,
                    'created_at' => get_my_local_time(),
                    'created_by' => $this->login_user->id
                );
                $this->Purchases_attachments_model->ci_save($attachment_data, 0);
            }
        }

        $this->_update_order_status_after_receipt($order_id);
        if (!empty($order->request_id)) {
            $this->_sync_request_status_from_orders((int)$order->request_id);
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function file_preview($id = 0)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        if (!$id) {
            show_404();
        }

        $file_info = $this->Purchases_attachments_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id(),
            'context_type' => 'goods_receipt'
        ))->getRow();
        if (!$file_info) {
            show_404();
        }

        $file = array(
            'file_name' => $file_info->file_name,
            'file_id' => '',
            'service_type' => ''
        );

        $view_data = array(
            'file_url' => get_source_url_of_file($file, get_setting('timeline_file_path')),
            'is_image_file' => is_image_file($file_info->file_name),
            'is_iframe_preview_available' => is_iframe_preview_available($file_info->file_name),
            'is_google_preview_available' => is_google_preview_available($file_info->file_name)
        );

        return $this->template->view('expenses/file_preview', $view_data);
    }

    private function _get_order_items_with_pending($order_id)
    {
        $order_items = $this->Purchases_order_items_model->get_details(array(
            'order_id' => $order_id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        $order_item_ids = array();
        foreach ($order_items as $item) {
            $order_item_ids[] = $item->id;
        }

        $received_map = array();
        if ($order_item_ids) {
            $db = db_connect('default');
            $items_table = $db->prefixTable('purchases_goods_receipt_items');
            $ids = implode(',', array_map('intval', $order_item_ids));
            $sql = "SELECT order_item_id, SUM(quantity_received) AS received_qty FROM $items_table WHERE deleted=0 AND order_item_id IN ($ids) GROUP BY order_item_id";
            $rows = $db->query($sql)->getResult();
            foreach ($rows as $row) {
                $received_map[$row->order_item_id] = (float)$row->received_qty;
            }
        }

        foreach ($order_items as $item) {
            $received = (float)get_array_value($received_map, $item->id, 0);
            $pending = (float)$item->quantity - $received;
            $item->received_qty = $received;
            $item->pending_qty = $pending > 0 ? $pending : 0;
        }

        return $order_items;
    }

    private function _update_order_status_after_receipt($order_id)
    {
        $order = $this->Purchases_orders_model->get_details(array(
            'id' => $order_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        $old_status = $order ? $order->status : '';

        $items = $this->_get_order_items_with_pending($order_id);
        $all_received = true;
        $any_received = false;
        foreach ($items as $item) {
            if ($item->received_qty > 0) {
                $any_received = true;
            }
            if ($item->pending_qty > 0) {
                $all_received = false;
            }
        }

        $status = $all_received ? 'received' : ($any_received ? 'partial_received' : 'open');
        $data = array(
            'status' => $status,
            'updated_at' => get_my_local_time()
        );
        $this->Purchases_orders_model->ci_save($data, $order_id);

        if ($old_status && $old_status !== $status) {
            $this->_log_status_change('order', $order_id, $old_status, $status);
        }
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

    private function _get_staff_dropdown()
    {
        $db = db_connect('default');
        $users_table = $db->prefixTable('users');
        $rows = $db->table($users_table)
            ->select("id, CONCAT(first_name, ' ', last_name) AS name", false)
            ->where('user_type', 'staff')
            ->where('status', 'active')
            ->where('deleted', 0)
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get()->getResult();

        $dropdown = array();
        foreach ($rows as $row) {
            $dropdown[$row->id] = $row->name;
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
}
