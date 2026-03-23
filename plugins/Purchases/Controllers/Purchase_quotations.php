<?php

namespace Purchases\Controllers;

use App\Controllers\Security_Controller;

class Purchase_quotations extends Security_Controller
{
    private $Purchases_requests_model;
    private $Purchases_request_items_model;
    private $Purchases_suppliers_model;
    private $Purchases_quotations_model;
    private $Purchases_quotation_suppliers_model;
    private $Purchases_quotation_items_model;
    private $Purchases_quotation_item_prices_model;
    private $Purchases_orders_model;
    private $Purchases_order_items_model;
    private $Purchases_logs_model;
    private $Purchases_request_approvals_model;
    private $Purchases_approvers_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Purchases_requests_model = model('Purchases\\Models\\Purchases_requests_model');
        $this->Purchases_request_items_model = model('Purchases\\Models\\Purchases_request_items_model');
        $this->Purchases_suppliers_model = model('Purchases\\Models\\Purchases_suppliers_model');
        $this->Purchases_quotations_model = model('Purchases\\Models\\Purchases_quotations_model');
        $this->Purchases_quotation_suppliers_model = model('Purchases\\Models\\Purchases_quotation_suppliers_model');
        $this->Purchases_quotation_items_model = model('Purchases\\Models\\Purchases_quotation_items_model');
        $this->Purchases_quotation_item_prices_model = model('Purchases\\Models\\Purchases_quotation_item_prices_model');
        $this->Purchases_orders_model = model('Purchases\\Models\\Purchases_orders_model');
        $this->Purchases_order_items_model = model('Purchases\\Models\\Purchases_order_items_model');
        $this->Purchases_logs_model = model('Purchases\\Models\\Purchases_logs_model');
        $this->Purchases_request_approvals_model = model('Purchases\\Models\\Purchases_request_approvals_model');
        $this->Purchases_approvers_model = model('Purchases\\Models\\Purchases_approvers_model');
        $this->Users_model = model('App\\Models\\Users_model');
    }

    public function create_from_request($request_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            app_redirect('forbidden');
        }

        $request_id = (int)$request_id;
        if (!$request_id) {
            show_404();
        }

        $request = $this->_get_request($request_id);
        if (!$request || !in_array($request->status, array('sent_to_quotation', 'submitted', 'quotation_in_progress'))) {
            app_redirect('forbidden');
        }

        $existing = $this->Purchases_quotations_model->get_one_by_request($request_id, $this->_get_company_id());
        if ($existing) {
            return redirect()->to(get_uri('purchases_quotations/view/' . $existing->id));
        }

        $view_data = array();
        $view_data['request_info'] = $request;
        $view_data['request_items'] = $this->Purchases_request_items_model->get_details(array(
            'request_id' => $request_id,
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $view_data['suppliers_dropdown'] = $this->_get_suppliers_dropdown_list();

        return $this->template->rander('Purchases\\Views\\quotations\\create_from_request', $view_data);
    }

    public function save_from_request()
    {
        try {
            if (!$this->_has_manage_permission()) {
                return $this->_json_permission_denied();
            }

            $request_id = (int)$this->request->getPost('request_id');
            if (!$request_id) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
            }

            $request = $this->_get_request($request_id);
            if (!$request || !in_array($request->status, array('sent_to_quotation', 'submitted', 'quotation_in_progress'))) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
            }

            $existing = $this->Purchases_quotations_model->get_one_by_request($request_id, $this->_get_company_id());
            if ($existing) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_quotation_exists')));
            }

            $supplier_ids = $this->request->getPost('supplier_ids');
            if ($supplier_ids === null) {
                $supplier_ids = $this->request->getPost('supplier_ids[]');
            }
            if (is_string($supplier_ids) && $supplier_ids !== '') {
                if (strpos($supplier_ids, ',') !== false) {
                    $supplier_ids = preg_split('/\s*,\s*/', $supplier_ids);
                } else {
                    $supplier_ids = array($supplier_ids);
                }
            }
            if (!is_array($supplier_ids)) {
                $supplier_ids = array();
            }
            $supplier_ids = array_filter(array_map('intval', $supplier_ids));
            $supplier_ids = array_values(array_unique($supplier_ids));
            if (count($supplier_ids) < 1 || count($supplier_ids) > 3) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_select_suppliers_limit')));
            }

            $quotation_data = array(
                'company_id' => $this->_get_company_id(),
                'request_id' => $request_id,
                'status' => 'draft',
                'created_at' => get_my_local_time(),
                'created_by' => $this->login_user->id
            );
            $quotation_id = $this->Purchases_quotations_model->ci_save($quotation_data, 0);
            if (!$quotation_id) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }
            if (!is_int($quotation_id)) {
                $quotation_id = db_connect('default')->insertID();
            }

            foreach ($supplier_ids as $supplier_id) {
                $supplier_data = array(
                    'company_id' => $this->_get_company_id(),
                    'quotation_id' => $quotation_id,
                    'supplier_id' => $supplier_id,
                    'created_at' => get_my_local_time(),
                    'created_by' => $this->login_user->id
                );
                $this->Purchases_quotation_suppliers_model->ci_save($supplier_data, 0);
            }

            $request_items = $this->Purchases_request_items_model->get_details(array(
                'request_id' => $request_id,
                'company_id' => $this->_get_company_id()
            ))->getResult();

            foreach ($request_items as $item) {
                $quotation_item_data = array(
                    'company_id' => $this->_get_company_id(),
                    'quotation_id' => $quotation_id,
                    'request_item_id' => $item->id,
                    'qty' => $item->quantity,
                    'created_at' => get_my_local_time(),
                    'created_by' => $this->login_user->id
                );
                $this->Purchases_quotation_items_model->ci_save($quotation_item_data, 0);

                foreach ($supplier_ids as $supplier_id) {
                    $price_data = array(
                        'company_id' => $this->_get_company_id(),
                        'quotation_id' => $quotation_id,
                        'request_item_id' => $item->id,
                        'supplier_id' => $supplier_id,
                        'unit_price' => 0,
                        'lead_time_days' => null,
                        'freight_value' => 0,
                        'payment_terms' => '',
                        'notes' => '',
                        'created_at' => get_my_local_time(),
                        'created_by' => $this->login_user->id
                    );
                    $this->Purchases_quotation_item_prices_model->ci_save($price_data, 0);
                }
            }

            if ($request && $request->status !== 'quotation_in_progress') {
                $old_status = $request->status;
                $update_data = array(
                    'status' => 'quotation_in_progress',
                    'updated_at' => get_my_local_time()
                );
                $this->Purchases_requests_model->ci_save($update_data, (int)$request->id);
                $this->_log_status_change('request', (int)$request->id, $old_status, 'quotation_in_progress');

                $requester_id = (int)($request->requested_by ?? 0);
                if (!$requester_id) {
                    $requester_id = (int)($request->created_by ?? 0);
                }
                if ($requester_id) {
                    $this->_notify_users('purchase_request_quotation_in_progress', array($requester_id), (int)$request->id);
                }
            }

            return $this->response->setJSON(array('success' => true, 'id' => $quotation_id, 'redirect' => get_uri('purchases_quotations/view/' . $quotation_id)));
        } catch (\Throwable $e) {
            return $this->response->setJSON(array('success' => false, 'message' => $e->getMessage()));
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

        $quotation = $this->Purchases_quotations_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$quotation) {
            show_404();
        }

        $request = $this->_get_request((int)$quotation->request_id);
        $items = $this->Purchases_quotation_items_model->get_details(array(
            'quotation_id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $suppliers = $this->Purchases_quotation_suppliers_model->get_details(array(
            'quotation_id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $prices = $this->Purchases_quotation_item_prices_model->get_details(array(
            'quotation_id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        $price_map = array();
        foreach ($prices as $price) {
            if (!isset($price_map[$price->request_item_id])) {
                $price_map[$price->request_item_id] = array();
            }
            $price_map[$price->request_item_id][$price->supplier_id] = $price;
        }

        $totals = $this->_calculate_totals($items, $suppliers, $price_map);
        $winner_totals = $this->_calculate_winner_totals($items, $suppliers, $price_map);
        $winner_map = $this->_get_winner_map($items, $suppliers, $price_map);
        $has_order = $this->_has_order_for_request((int)$quotation->request_id);
        $selected_supplier_ids = array();
        foreach ($suppliers as $supplier) {
            $selected_supplier_ids[] = (int)$supplier->supplier_id;
        }

        $can_manage = $this->_has_manage_permission();
        $view_data = array(
            'quotation_info' => $quotation,
            'request_info' => $request,
            'items' => $items,
            'suppliers' => $suppliers,
            'suppliers_all' => $this->_get_suppliers_dropdown_list(),
            'selected_supplier_ids' => $selected_supplier_ids,
            'price_map' => $price_map,
            'totals' => $totals,
            'winner_totals' => $winner_totals,
            'winner_map' => $winner_map,
            'can_edit' => $can_manage && $quotation->status === 'draft',
            'can_finalize' => $can_manage && $quotation->status === 'draft',
            'can_generate_po' => ($can_manage && $quotation->status === 'finalized' && !$has_order && $request && $request->status === 'approved_for_po'),
            'has_order' => $has_order
        );

        return $this->template->rander('Purchases\\Views\\quotations\\view', $view_data);
    }

    public function save_prices($id = 0)
    {
        try {
            if (!$this->_has_manage_permission()) {
                return $this->_json_permission_denied();
            }

            $id = (int)$id;
            $quotation = $this->_get_quotation($id);
            if (!$quotation || $quotation->status !== 'draft') {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
            }

            $qtys = $this->request->getPost('qty');
            if (is_array($qtys)) {
                foreach ($qtys as $request_item_id => $qty) {
                    $qty_value = unformat_currency($qty);
                    $qty_data = array('qty' => $qty_value);
                    $this->Purchases_quotation_items_model->ci_save($qty_data, (int)$this->_get_quotation_item_id($id, (int)$request_item_id));
                }
            }

            $unit_prices = $this->request->getPost('unit_price');
            $delivery_dates = $this->request->getPost('delivery_date');
            $freight_values = $this->request->getPost('freight_value');
            $payment_terms = $this->request->getPost('payment_terms');
            $notes = $this->request->getPost('notes');
            $winner_suppliers = $this->request->getPost('winner_supplier');

            if (is_array($unit_prices)) {
                foreach ($unit_prices as $supplier_id => $items) {
                    foreach ($items as $request_item_id => $value) {
                        $price = $this->_get_price_row($id, (int)$request_item_id, (int)$supplier_id);
                        if (!$price) {
                            continue;
                        }
                        $data = array(
                            'unit_price' => unformat_currency($value),
                            'delivery_date' => get_array_value(get_array_value($delivery_dates, $supplier_id, array()), $request_item_id),
                            'freight_value' => unformat_currency(get_array_value(get_array_value($freight_values, $supplier_id, array()), $request_item_id)),
                            'payment_terms' => trim((string)get_array_value(get_array_value($payment_terms, $supplier_id, array()), $request_item_id)),
                            'notes' => trim((string)get_array_value(get_array_value($notes, $supplier_id, array()), $request_item_id)),
                            'updated_at' => get_my_local_time()
                        );
                        $this->Purchases_quotation_item_prices_model->ci_save($data, $price->id);
                    }
                }
            }

            if (is_array($winner_suppliers)) {
                $db = db_connect('default');
                $prices_table = $db->prefixTable('purchases_quotation_item_prices');
                foreach ($winner_suppliers as $request_item_id => $supplier_id) {
                    $request_item_id = (int)$request_item_id;
                    $supplier_id = (int)$supplier_id;
                    if (!$request_item_id || !$supplier_id) {
                        continue;
                    }

                    $db->table($prices_table)
                        ->where('quotation_id', $id)
                        ->where('request_item_id', $request_item_id)
                        ->update(array('is_winner' => 0));

                    $db->table($prices_table)
                        ->where('quotation_id', $id)
                        ->where('request_item_id', $request_item_id)
                        ->where('supplier_id', $supplier_id)
                        ->update(array('is_winner' => 1));
                }
            } else {
                $this->_auto_select_winners($id);
            }

            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        } catch (\Throwable $e) {
            return $this->response->setJSON(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function update_suppliers($id = 0)
    {
        try {
            if (!$this->_has_manage_permission()) {
                return $this->_json_permission_denied();
            }

            $id = (int)$id;
            $quotation = $this->_get_quotation($id);
            if (!$quotation || $quotation->status !== 'draft') {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
            }

            $supplier_ids = $this->request->getPost('supplier_ids');
            if (!is_array($supplier_ids)) {
                $supplier_ids = array();
            }

            $supplier_ids = array_values(array_unique(array_filter(array_map('intval', $supplier_ids))));
            if (count($supplier_ids) < 1 || count($supplier_ids) > 3) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_select_suppliers_limit')));
            }

            $company_id = $this->_get_company_id();
            $current_rows = $this->Purchases_quotation_suppliers_model->get_details(array(
                'quotation_id' => $id,
                'company_id' => $company_id
            ))->getResult();
            $current_ids = array();
            foreach ($current_rows as $row) {
                $current_ids[] = (int)$row->supplier_id;
            }

            $to_add = array_values(array_diff($supplier_ids, $current_ids));
            $to_remove = array_values(array_diff($current_ids, $supplier_ids));

            $db = db_connect('default');
            $suppliers_table = $db->prefixTable('purchases_quotation_suppliers');
            $prices_table = $db->prefixTable('purchases_quotation_item_prices');

            if (!empty($to_remove)) {
                $db->table($suppliers_table)
                    ->where('quotation_id', $id)
                    ->whereIn('supplier_id', $to_remove)
                    ->update(array('deleted' => 1));

                $db->table($prices_table)
                    ->where('quotation_id', $id)
                    ->whereIn('supplier_id', $to_remove)
                    ->update(array('deleted' => 1, 'is_winner' => 0));
            }

            foreach ($to_add as $supplier_id) {
                $existing = $db->table($suppliers_table)
                    ->where('quotation_id', $id)
                    ->where('supplier_id', $supplier_id)
                    ->get()
                    ->getRow();

                if ($existing) {
                    $db->table($suppliers_table)
                        ->where('id', $existing->id)
                        ->update(array('deleted' => 0));
                } else {
                    $data = array(
                        'company_id' => $company_id,
                        'quotation_id' => $id,
                        'supplier_id' => $supplier_id,
                        'created_at' => get_my_local_time(),
                        'created_by' => $this->login_user->id
                    );
                    $this->Purchases_quotation_suppliers_model->ci_save($data, 0);
                }
            }

            if (!empty($to_add)) {
                $items = $this->Purchases_quotation_items_model->get_details(array(
                    'quotation_id' => $id,
                    'company_id' => $company_id
                ))->getResult();

                foreach ($items as $item) {
                    foreach ($to_add as $supplier_id) {
                        $existing = $db->table($prices_table)
                            ->where('quotation_id', $id)
                            ->where('request_item_id', (int)$item->request_item_id)
                            ->where('supplier_id', $supplier_id)
                            ->get()
                            ->getRow();

                        if ($existing) {
                            $db->table($prices_table)
                                ->where('id', $existing->id)
                                ->update(array('deleted' => 0));
                        } else {
                            $price_data = array(
                                'company_id' => $company_id,
                                'quotation_id' => $id,
                                'request_item_id' => (int)$item->request_item_id,
                                'supplier_id' => $supplier_id,
                                'unit_price' => 0,
                                'freight_value' => 0,
                                'created_at' => get_my_local_time(),
                                'created_by' => $this->login_user->id
                            );
                            $this->Purchases_quotation_item_prices_model->ci_save($price_data, 0);
                        }
                    }
                }
            }

            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        } catch (\Throwable $e) {
            return $this->response->setJSON(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function finalize($id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        $quotation = $this->_get_quotation($id);
        if (!$quotation || $quotation->status !== 'draft') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        if (!$this->_has_all_winners($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_select_winners_required')));
        }

        $old_status = $quotation->status;
        $finalize_data = array(
            'status' => 'finalized',
            'updated_at' => get_my_local_time()
        );
        $ok = $this->Purchases_quotations_model->ci_save($finalize_data, $id);
        if ($ok) {
            $this->_log_status_change('quotation', $id, $old_status, 'finalized');
            $request = $this->_get_request((int)$quotation->request_id);
            if ($request) {
                $old_request_status = $request->status;
                $update_data = array(
                    'status' => 'awaiting_approval',
                    'updated_at' => get_my_local_time()
                );
                $this->Purchases_requests_model->ci_save($update_data, (int)$request->id);
                $this->_log_status_change('request', (int)$request->id, $old_request_status, 'awaiting_approval');
                $requester_id = (int)($request->requested_by ?? 0);
                if (!$requester_id) {
                    $requester_id = (int)($request->created_by ?? 0);
                }
                if ($requester_id) {
                    $this->_notify_users('purchase_request_quotation_finalized', array($requester_id), (int)$request->id);
                }
                $this->_init_request_approvals($request);
                $this->_notify_requester_and_financial($request, 'awaiting_approval');
            }
        }

        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')
        ));
    }

    public function choose_winner($id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_use_item_winners')));
    }

    public function generate_po($id = 0)
    {
        try {
            if (!$this->_has_manage_permission()) {
                return $this->_json_permission_denied();
            }

            $id = (int)$id;
            $quotation = $this->_get_quotation($id);
            if (!$quotation || $quotation->status !== 'finalized') {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
            }

            $request = $this->_get_request((int)$quotation->request_id);
            if (!$request || $request->status !== 'approved_for_po') {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_not_approved_for_po')));
            }

            if ($this->_has_order_for_request((int)$quotation->request_id)) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_order_exists')));
            }

            $items_query = $this->Purchases_quotation_items_model->get_details(array(
                'quotation_id' => $id,
                'company_id' => $this->_get_company_id()
            ));
            $items = ($items_query && method_exists($items_query, 'getResult')) ? $items_query->getResult() : array();

            $prices_query = $this->Purchases_quotation_item_prices_model->get_details(array(
                'quotation_id' => $id,
                'company_id' => $this->_get_company_id()
            ));
            $prices = ($prices_query && method_exists($prices_query, 'getResult')) ? $prices_query->getResult() : array();

            $winner_prices_map = array();
            foreach ($prices as $price) {
                if ($price->is_winner) {
                    $winner_prices_map[$price->request_item_id] = $price;
                }
            }

            $orders_created = array();
            $grouped = array();
            foreach ($items as $item) {
                $price = get_array_value($winner_prices_map, $item->request_item_id);
                if (!$price || !$price->supplier_id) {
                    continue;
                }
                $supplier_id = (int)$price->supplier_id;
                if (!isset($grouped[$supplier_id])) {
                    $grouped[$supplier_id] = array();
                }
                $grouped[$supplier_id][] = array('item' => $item, 'price' => $price);
            }

            if (!$grouped) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('purchases_select_winners_required')));
            }

            foreach ($grouped as $supplier_id => $rows) {
                $total = 0;
                foreach ($rows as $row) {
                    $unit_price = (float)$row['price']->unit_price;
                    $freight = (float)$row['price']->freight_value;
                    $total += ((float)$row['item']->qty * $unit_price) + $freight;
                }

                $request = $this->_get_request((int)$quotation->request_id);
                $order_data = array(
                    'company_id' => $this->_get_company_id(),
                    'request_id' => $quotation->request_id,
                    'supplier_id' => $supplier_id,
                    'po_code_number' => null,
                    'po_code' => null,
                    'project_id' => $request ? $request->project_id : null,
                    'cost_center' => $request ? $request->cost_center : null,
                    'status' => 'open',
                    'order_date' => get_my_local_time(),
                    'expected_delivery_date' => $this->_get_max_delivery_date($rows),
                    'payment_terms' => $this->_get_first_payment_terms($rows),
                    'note' => 'Generated from quotation #' . $quotation->id,
                    'total' => $total,
                    'created_at' => get_my_local_time(),
                    'created_by' => $this->login_user->id
                );
                $order_id = $this->Purchases_orders_model->ci_save($order_data, 0);
                if (!$order_id) {
                    $db = db_connect('default');
                    $db_error = method_exists($db, 'error') ? $db->error() : array();
                    $db_message = is_array($db_error) ? ($db_error['message'] ?? '') : '';
                    $message = $db_message ? $db_message : app_lang('error_occurred');
                    return $this->response->setJSON(array('success' => false, 'message' => $message));
                }
                if (!is_int($order_id)) {
                    $order_id = db_connect('default')->insertID();
                }

                $code_data = $this->Purchases_orders_model->get_next_po_code_data($this->_get_company_id());
                $code_update = array(
                    'po_code_number' => $code_data['po_code_number'],
                    'po_code' => $code_data['po_code'],
                    'updated_at' => get_my_local_time()
                );
                $this->Purchases_orders_model->ci_save($code_update, $order_id);

                foreach ($rows as $row) {
                    $unit_price = (float)$row['price']->unit_price;
                    $line_total = ((float)$row['item']->qty * $unit_price);
                    $order_item_data = array(
                        'company_id' => $this->_get_company_id(),
                        'order_id' => $order_id,
                        'item_id' => $row['item']->item_id,
                        'description' => $row['item']->request_description,
                        'unit' => $row['item']->request_unit,
                        'quantity' => $row['item']->qty,
                        'rate' => $unit_price,
                        'total' => $line_total,
                        'created_at' => get_my_local_time(),
                        'created_by' => $this->login_user->id
                    );
                    $save_item_ok = $this->Purchases_order_items_model->ci_save($order_item_data, 0);
                    if (!$save_item_ok) {
                        $db = db_connect('default');
                        $db_error = method_exists($db, 'error') ? $db->error() : array();
                        $db_message = is_array($db_error) ? ($db_error['message'] ?? '') : '';
                        $message = $db_message ? $db_message : app_lang('error_occurred');
                        return $this->response->setJSON(array('success' => false, 'message' => $message));
                    }
                }

                $orders_created[] = $order_id;
            }

            $request_update_data = array(
                'status' => 'po_created',
                'converted_by' => $this->login_user->id,
                'converted_at' => get_my_local_time(),
                'updated_at' => get_my_local_time()
            );
            $this->Purchases_requests_model->ci_save($request_update_data, (int)$quotation->request_id);
            if ($request) {
                $this->_log_status_change('request', (int)$quotation->request_id, $request->status, 'po_created');
                $this->_notify_requester_and_financial($request, 'po_created', array($request->requested_by));
            }

            return $this->response->setJSON(array(
                'success' => true,
                'message' => app_lang('purchases_order_created'),
                'order_ids' => $orders_created
            ));
        } catch (\Throwable $e) {
            return $this->response->setJSON(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    private function _get_request($request_id)
    {
        $request = $this->Purchases_requests_model->get_details(array(
            'id' => $request_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();

        return $request;
    }

    private function _init_request_approvals($request)
    {
        $company_id = $this->_get_company_id();
        $existing = $this->Purchases_request_approvals_model->get_details(array(
            'request_id' => $request->id,
            'company_id' => $company_id
        ))->getResult();

        if ($existing && count($existing)) {
            return;
        }

        $rows = array(
            array(
                'company_id' => $company_id,
                'request_id' => $request->id,
                'approval_type' => 'requester',
                'approved' => 0,
                'created_at' => get_my_local_time(),
                'created_by' => $this->login_user->id
            ),
            array(
                'company_id' => $company_id,
                'request_id' => $request->id,
                'approval_type' => 'financial',
                'approved' => 0,
                'created_at' => get_my_local_time(),
                'created_by' => $this->login_user->id
            )
        );

        foreach ($rows as $row) {
            $this->Purchases_request_approvals_model->ci_save($row, 0);
        }
    }

    private function _notify_requester_and_financial($request, $status, $extra_user_ids = array())
    {
        $requester_id = (int)($request->requested_by ?? 0);
        if (!$requester_id) {
            $requester_id = (int)($request->created_by ?? 0);
        }

        $user_ids = array();
        if ($requester_id) {
            $user_ids[] = $requester_id;
        }

        $financial_users = $this->_get_financial_approvers();
        $user_ids = array_merge($user_ids, $financial_users, $extra_user_ids);
        $user_ids = array_unique(array_filter($user_ids));

        if (!$user_ids) {
            return;
        }

        $this->_notify_users("purchase_request_" . $status, $user_ids, $request->id);
    }

    private function _get_financial_approvers()
    {
        $company_id = $this->_get_company_id();
        $db = db_connect('default');
        $approvers_table = $db->prefixTable('purchases_approvers');
        $users_table = $db->prefixTable('users');

        $sql = "SELECT $users_table.id
            FROM $users_table
            LEFT JOIN $approvers_table ON $approvers_table.user_id=$users_table.id AND $approvers_table.deleted=0 AND $approvers_table.company_id=$company_id
            WHERE $users_table.deleted=0 AND $users_table.user_type='staff' AND $users_table.status='active' AND (
                $users_table.is_admin=1 OR $approvers_table.financial_limit IS NOT NULL
            )";

        $result = $db->query($sql)->getResult();
        $ids = array();
        foreach ($result as $row) {
            $ids[] = (int)$row->id;
        }
        return $ids;
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

    private function _get_quotation($id)
    {
        return $this->Purchases_quotations_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
    }

    private function _supplier_in_quotation($quotation_id, $supplier_id)
    {
        $row = $this->Purchases_quotation_suppliers_model->get_details(array(
            'quotation_id' => $quotation_id,
            'company_id' => $this->_get_company_id(),
            'supplier_id' => $supplier_id
        ))->getRow();

        return $row ? true : false;
    }

    private function _get_suppliers_dropdown_list()
    {
        $suppliers = $this->Purchases_suppliers_model->get_details(array(
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $dropdown = array();
        foreach ($suppliers as $supplier) {
            $dropdown[$supplier->id] = $supplier->name;
        }

        return $dropdown;
    }

    private function _get_quotation_item_id($quotation_id, $request_item_id)
    {
        $row = $this->Purchases_quotation_items_model->get_details(array(
            'quotation_id' => $quotation_id,
            'request_item_id' => $request_item_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();

        return $row ? $row->id : 0;
    }

    private function _get_price_row($quotation_id, $request_item_id, $supplier_id)
    {
        return $this->Purchases_quotation_item_prices_model->get_details(array(
            'quotation_id' => $quotation_id,
            'request_item_id' => $request_item_id,
            'supplier_id' => $supplier_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
    }

    private function _calculate_totals($items, $suppliers, $price_map)
    {
        $totals = array();
        foreach ($suppliers as $supplier) {
            $sum = 0;
            foreach ($items as $item) {
                $price = get_array_value(get_array_value($price_map, $item->request_item_id, array()), $supplier->supplier_id);
                $unit_price = $price ? (float)$price->unit_price : 0;
                if ($unit_price <= 0) {
                    continue;
                }
                $freight = $price ? (float)$price->freight_value : 0;
                $sum += ((float)$item->qty * $unit_price) + $freight;
            }
            $totals[$supplier->supplier_id] = $sum;
        }

        return $totals;
    }

    private function _calculate_winner_totals($items, $suppliers, $price_map)
    {
        $totals = array();
        foreach ($suppliers as $supplier) {
            $sum = 0;
            foreach ($items as $item) {
                $price = get_array_value(get_array_value($price_map, $item->request_item_id, array()), $supplier->supplier_id);
                if (!$price || !$price->is_winner) {
                    continue;
                }
                $unit_price = (float)$price->unit_price;
                if ($unit_price <= 0) {
                    continue;
                }
                $freight = (float)$price->freight_value;
                $sum += ((float)$item->qty * $unit_price) + $freight;
            }
            $totals[$supplier->supplier_id] = $sum;
        }

        return $totals;
    }

    private function _get_winner_map($items, $suppliers, $price_map)
    {
        $winner_map = array();
        foreach ($items as $item) {
            $chosen = 0;
            foreach ($suppliers as $supplier) {
                $price = get_array_value(get_array_value($price_map, $item->request_item_id, array()), $supplier->supplier_id);
                if ($price && $price->is_winner) {
                    $chosen = (int)$supplier->supplier_id;
                    break;
                }
            }
            if (!$chosen) {
                $chosen = $this->_suggest_winner_for_item($item, $suppliers, $price_map);
            }
            $winner_map[$item->request_item_id] = $chosen;
        }

        return $winner_map;
    }

    private function _suggest_winner_for_item($item, $suppliers, $price_map)
    {
        $best_supplier = 0;
        $best_total = null;
        foreach ($suppliers as $supplier) {
            $price = get_array_value(get_array_value($price_map, $item->request_item_id, array()), $supplier->supplier_id);
            if (!$price) {
                continue;
            }
            $unit_price = (float)$price->unit_price;
            if ($unit_price <= 0) {
                continue;
            }
            $freight = (float)$price->freight_value;
            $total = ((float)$item->qty * $unit_price) + $freight;
            if ($best_total === null || $total < $best_total) {
                $best_total = $total;
                $best_supplier = (int)$supplier->supplier_id;
            }
        }

        return $best_supplier;
    }

    private function _auto_select_winners($quotation_id)
    {
        $items = $this->Purchases_quotation_items_model->get_details(array(
            'quotation_id' => $quotation_id,
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $suppliers = $this->Purchases_quotation_suppliers_model->get_details(array(
            'quotation_id' => $quotation_id,
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $prices = $this->Purchases_quotation_item_prices_model->get_details(array(
            'quotation_id' => $quotation_id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        $price_map = array();
        foreach ($prices as $price) {
            if (!isset($price_map[$price->request_item_id])) {
                $price_map[$price->request_item_id] = array();
            }
            $price_map[$price->request_item_id][$price->supplier_id] = $price;
        }

        $db = db_connect('default');
        $prices_table = $db->prefixTable('purchases_quotation_item_prices');
        foreach ($items as $item) {
            $winner_supplier = $this->_suggest_winner_for_item($item, $suppliers, $price_map);
            if (!$winner_supplier) {
                continue;
            }
            $db->table($prices_table)
                ->where('quotation_id', $quotation_id)
                ->where('request_item_id', $item->request_item_id)
                ->update(array('is_winner' => 0));

            $db->table($prices_table)
                ->where('quotation_id', $quotation_id)
                ->where('request_item_id', $item->request_item_id)
                ->where('supplier_id', $winner_supplier)
                ->update(array('is_winner' => 1));
        }
    }

    private function _has_all_winners($quotation_id)
    {
        $items = $this->Purchases_quotation_items_model->get_details(array(
            'quotation_id' => $quotation_id,
            'company_id' => $this->_get_company_id()
        ))->getResult();
        $prices = $this->Purchases_quotation_item_prices_model->get_details(array(
            'quotation_id' => $quotation_id,
            'company_id' => $this->_get_company_id()
        ))->getResult();

        $winner_count = array();
        foreach ($prices as $price) {
            if ($price->is_winner) {
                $winner_count[$price->request_item_id] = true;
            }
        }

        foreach ($items as $item) {
            if (empty($winner_count[$item->request_item_id])) {
                return false;
            }
        }

        return true;
    }

    private function _get_max_delivery_date($rows)
    {
        $max = '';
        foreach ($rows as $row) {
            $delivery = $row['price']->delivery_date ?? '';
            if ($delivery && (!$max || strtotime($delivery) > strtotime($max))) {
                $max = $delivery;
            }
        }

        return $max ? $max : null;
    }

    private function _get_first_payment_terms($rows)
    {
        foreach ($rows as $row) {
            $terms = trim((string)($row['price']->payment_terms ?? ''));
            if ($terms !== '') {
                return $terms;
            }
        }

        return null;
    }

    private function _has_order_for_request($request_id)
    {
        $orders_query = $this->Purchases_orders_model->get_details(array(
            'request_id' => $request_id,
            'company_id' => $this->_get_company_id()
        ));
        $orders = ($orders_query && method_exists($orders_query, 'getResult')) ? $orders_query->getResult() : array();

        return $orders ? true : false;
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
