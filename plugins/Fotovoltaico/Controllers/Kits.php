<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\AuditService;

class Kits extends Security_Controller
{
    private $Kits_model;
    private $Kit_items_model;
    private $Products_model;
    private $Product_categories_model;
    private $AuditService;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canViewKits($this->login_user) && !Plugin::canManageKits($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();

        $this->Kits_model = model('Fotovoltaico\\Models\\Kits_model');
        $this->Kit_items_model = model('Fotovoltaico\\Models\\Kit_items_model');
        $this->Products_model = model('Fotovoltaico\\Models\\Products_model');
        $this->Product_categories_model = model('Fotovoltaico\\Models\\Product_categories_model');
        $this->AuditService = new AuditService();
    }

    public function index()
    {
        $view_data = array();
        $view_data['categories_dropdown'] = $this->_get_categories_dropdown(true);
        $view_data['status_dropdown'] = $this->_get_status_dropdown(true);
        $view_data['can_manage_kits'] = Plugin::canManageKits($this->login_user);

        return $this->template->rander('Fotovoltaico\\Views\\kits\\index', $view_data);
    }

    public function list_data()
    {
        $search = $this->_get_search_term();
        $category_id = get_only_numeric_value($this->request->getPost('category_id'));
        $status = trim((string) $this->request->getPost('status'));

        $options = array(
            'search' => $search,
        );
        if ($category_id) {
            $options['category_id'] = $category_id;
        }
        if ($status !== '') {
            $options['status'] = $status;
        }

        $list_data = $this->Kits_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        echo json_encode(array('data' => $result));
    }

    public function modal_form()
    {
        $this->validate_submitted_data(array('id' => 'numeric'));

        $id = (int) $this->request->getPost('id');
        $view_data = array();
        $view_data['model_info'] = $this->Kits_model->get_one($id);
        $view_data['categories_dropdown'] = $this->_get_categories_dropdown(false);
        $view_data['distributors_dropdown'] = $this->_get_distributors_dropdown(false);
        $view_data['status_dropdown'] = $this->_get_status_dropdown(false);

        return $this->template->view('Fotovoltaico\\Views\\kits\\modal_form', $view_data);
    }

    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'title' => 'required'
        ));

        if (!Plugin::canManageKits($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $title = trim((string) $this->request->getPost('title'));
        $code = trim((string) $this->request->getPost('code'));
        if ($code && $this->_code_exists($code, $id)) {
            echo json_encode(array('success' => false, 'message' => app_lang('fotovoltaico_kit_code_exists')));
            return;
        }

        $data = array(
            'category_id' => ($category_id = get_only_numeric_value($this->request->getPost('category_id'))) ? (int) $category_id : null,
            'distributor_id' => ($distributor_id = get_only_numeric_value($this->request->getPost('distributor_id'))) ? (int) $distributor_id : null,
            'title' => $title,
            'code' => $code ?: null,
            'description' => trim((string) $this->request->getPost('description')) ?: null,
            'power_kwp' => (float) unformat_currency($this->request->getPost('power_kwp')),
            'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            'status' => trim((string) $this->request->getPost('status')) ?: 'draft',
            'active' => $this->request->getPost('active') ? 1 : 0,
            'updated_at' => get_my_local_time(),
        );

        $data = clean_data($data);
        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->Kits_model->ci_save($data, $id);
        if ($save_id) {
            $this->Kits_model->recalculate_totals($save_id);
            $row_data = $this->Kits_model->get_details(array('id' => $save_id))->getRow();
            $this->_audit('kit', $save_id, $id ? 'kit_updated' : 'kit_created', array('title' => $title), array(
                'title' => $title,
                'status' => $data['status'],
                'power_kwp' => $data['power_kwp'],
            ));
            echo json_encode(array(
                'success' => true,
                'id' => $save_id,
                'data' => $this->_make_row($row_data),
                'message' => app_lang('record_saved')
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
        }
    }

    public function delete()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));

        if (!Plugin::canManageKits($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $items = $this->Kit_items_model->get_items_by_kit($id)->getResult();
        foreach ($items as $item) {
            $this->Kit_items_model->delete($item->id);
        }

        if ($this->Kits_model->delete($id)) {
            $this->_audit('kit', $id, 'kit_deleted', array(
                'items_count' => count($items),
            ));
            echo json_encode(array('success' => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    public function view($id = 0)
    {
        $id = (int) $id;
        if (!$id) {
            show_404();
        }

        $kit = $this->Kits_model->get_kit_with_items($id);
        if (!$kit) {
            show_404();
        }

        $view_data = array();
        $view_data['kit'] = $kit;
        $view_data['items'] = $kit->items ?? array();
        $view_data['product_options'] = $this->_get_products_dropdown(false, true);
        $view_data['product_lookup_json'] = $this->_get_products_lookup_json();
        $view_data['can_manage_kits'] = Plugin::canManageKits($this->login_user);
        $view_data['totals'] = $this->_calculate_totals_from_items($view_data['items']);
        $view_data['status_label'] = $this->_status_label($kit->status ?: 'draft');

        return $this->template->rander('Fotovoltaico\\Views\\kits\\view', $view_data);
    }

    public function items_list_data($kit_id = 0)
    {
        $kit_id = (int) $kit_id;
        $rows = array();
        if (!$kit_id) {
            echo json_encode(array('data' => $rows));
            return;
        }

        $items = $this->Kit_items_model->get_items_by_kit($kit_id)->getResult();
        foreach ($items as $item) {
            $rows[] = $this->_make_item_row($item);
        }

        echo json_encode(array('data' => $rows));
    }

    public function add_item()
    {
        $this->validate_submitted_data(array(
            'kit_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'quantity' => 'required'
        ));

        if (!Plugin::canManageKits($this->login_user)) {
            app_redirect('forbidden');
        }

        $kit_id = (int) $this->request->getPost('kit_id');
        $product_id = (int) $this->request->getPost('product_id');
        $quantity = (float) unformat_currency($this->request->getPost('quantity'));
        if ($quantity <= 0) {
            $quantity = 1;
        }

        $product = $this->Products_model->get_details(array('id' => $product_id, 'active_only' => 1))->getRow();
        if (!$product) {
            echo json_encode(array('success' => false, 'message' => app_lang('record_not_found')));
            return;
        }

        $unit_price = trim((string) $this->request->getPost('unit_price')) !== '' ? (float) unformat_currency($this->request->getPost('unit_price')) : (float) $product->sale_price;
        $unit_cost = trim((string) $this->request->getPost('unit_cost')) !== '' ? (float) unformat_currency($this->request->getPost('unit_cost')) : (float) $product->cost_price;
        $total_price = $quantity * $unit_price;
        $total_cost = $quantity * $unit_cost;
        $notes = trim((string) $this->request->getPost('notes')) ?: null;
        $sort = (int) $this->request->getPost('sort');

        $existing = $this->Kit_items_model->get_details(array('kit_id' => $kit_id, 'product_id' => $product_id))->getRow();
        $data = array(
            'kit_id' => $kit_id,
            'product_id' => $product_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'unit_cost' => $unit_cost,
            'total_price' => $total_price,
            'total_cost' => $total_cost,
            'notes' => $notes,
            'sort' => $sort,
            'updated_at' => get_my_local_time(),
        );
        if (!$existing) {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->Kit_items_model->ci_save($data, $existing ? (int) $existing->id : 0);
        if (!$save_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $this->Kits_model->recalculate_totals($kit_id);
        $item = $this->Kit_items_model->get_details(array('id' => $save_id))->getRow();
        $this->_audit('kit', $kit_id, $existing ? 'kit_item_updated' : 'kit_item_added', array(), array(
            'kit_id' => $kit_id,
            'product_id' => $product_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'unit_cost' => $unit_cost,
        ));
        echo json_encode(array(
            'success' => true,
            'id' => $save_id,
            'data' => $this->_make_item_row($item),
            'totals' => $this->_calculate_totals_from_items($this->Kit_items_model->get_items_by_kit($kit_id)->getResult()),
            'message' => app_lang('record_saved')
        ));
    }

    public function remove_item()
    {
        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        if (!Plugin::canManageKits($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $item = $this->Kit_items_model->get_one($id);
        if (!$item || !$item->id) {
            echo json_encode(array('success' => false, 'message' => app_lang('record_not_found')));
            return;
        }

        if ($this->Kit_items_model->delete($id)) {
            $this->Kits_model->recalculate_totals($item->kit_id);
            $this->_audit('kit', $item->kit_id, 'kit_item_removed', array(
                'item_id' => $id,
                'product_id' => $item->product_id,
            ));
            echo json_encode(array('success' => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    private function _make_row($data)
    {
        if (!$data) {
            return array();
        }

        $title = anchor(get_uri('fotovoltaico/kits/view/' . $data->id), esc($data->title), array(
            'title' => app_lang('fotovoltaico_kit_details')
        ));

        $actions = anchor(get_uri('fotovoltaico/kits/view/' . $data->id), "<i data-feather='eye' class='icon-16'></i>", array(
            'class' => 'view',
            'title' => app_lang('fotovoltaico_kit_details'),
        ));

        if (Plugin::canManageKits($this->login_user)) {
            $actions .= modal_anchor(get_uri('fotovoltaico/kits/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                'class' => 'edit',
                'title' => app_lang('fotovoltaico_edit_kit'),
                'data-post-id' => $data->id,
            ));
            $actions .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => app_lang('delete'),
                'class' => 'delete',
                'data-id' => $data->id,
                'data-action-url' => get_uri('fotovoltaico/kits/delete'),
                'data-action' => 'delete'
            ));
        }

        return array(
            $title,
            esc($data->code ?: '-'),
            esc($data->category_title ?: '-'),
            esc(number_format((float) $data->power_kwp, 3, ',', '.')),
            $this->_status_label($data->status ?: 'draft'),
            to_currency((float) $data->total_cost, 'R$'),
            to_currency((float) $data->total_price, 'R$'),
            esc(number_format((float) $data->margin_percent, 2, ',', '.') . '%'),
            $actions
        );
    }

    private function _make_item_row($item)
    {
        if (!$item) {
            return array();
        }

        $actions = '';
        if (Plugin::canManageKits($this->login_user)) {
            $actions = js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => app_lang('delete'),
                'class' => 'delete',
                'data-id' => $item->id,
                'data-action-url' => get_uri('fotovoltaico/kits/remove_item'),
                'data-action' => 'delete-confirmation',
                'data-success-callback' => 'reloadKitBom'
            ));
        }

        return array(
            esc($item->product_title ?: '-'),
            esc($item->product_type ?: '-'),
            number_format((float) $item->quantity, 4, ',', '.'),
            to_currency((float) $item->unit_price, 'R$'),
            to_currency((float) $item->unit_cost, 'R$'),
            to_currency((float) $item->total_price, 'R$'),
            to_currency((float) $item->total_cost, 'R$'),
            esc($item->notes ?: '-'),
            $actions
        );
    }

    private function _get_categories_dropdown($for_filter = false)
    {
        $categories = $this->Product_categories_model->get_details()->getResult();
        if ($for_filter) {
            $list = array(array('id' => '', 'text' => '-'));
            foreach ($categories as $category) {
                $list[] = array('id' => (int) $category->id, 'text' => $category->title);
            }
            return json_encode($list);
        }

        $dropdown = array('' => '-');
        foreach ($categories as $category) {
            $dropdown[$category->id] = $category->title;
        }
        return $dropdown;
    }

    private function _get_distributors_dropdown($for_filter = false)
    {
        $distributors = model('Fotovoltaico\\Models\\Distributors_model')->get_dropdown();
        if ($for_filter) {
            $list = array(array('id' => '', 'text' => '-'));
            foreach ($distributors as $id => $text) {
                if ($id === '') {
                    continue;
                }
                $list[] = array('id' => (int) $id, 'text' => $text);
            }
            return json_encode($list);
        }

        return $distributors;
    }

    private function _get_status_dropdown($for_filter = false)
    {
        $statuses = array(
            '' => '-',
            'draft' => app_lang('fotovoltaico_kit_status_draft'),
            'active' => app_lang('fotovoltaico_kit_status_active'),
            'inactive' => app_lang('fotovoltaico_kit_status_inactive'),
        );

        if ($for_filter) {
            $list = array();
            foreach ($statuses as $value => $label) {
                $list[] = array('id' => $value, 'text' => $label);
            }
            return json_encode($list);
        }

        return $statuses;
    }

    private function _status_label($status)
    {
        $map = $this->_get_status_dropdown(false);
        $text = get_array_value($map, $status) ?: $status;
        $class = 'bg-secondary';
        if ($status === 'active') {
            $class = 'bg-success';
        } elseif ($status === 'draft') {
            $class = 'bg-warning text-dark';
        }

        return "<span class='badge {$class}'>" . esc($text) . "</span>";
    }

    private function _get_products_dropdown($for_filter = false, $active_only = false)
    {
        $options = array();
        if ($active_only) {
            $options['active_only'] = 1;
        }
        $products = $this->Products_model->get_details($options)->getResult();
        if ($for_filter) {
            $list = array(array('id' => '', 'text' => '-'));
            foreach ($products as $product) {
                $list[] = array('id' => (int) $product->id, 'text' => $product->title);
            }
            return json_encode($list);
        }

        $dropdown = array('' => '-');
        foreach ($products as $product) {
            $dropdown[$product->id] = $product->title;
        }
        return $dropdown;
    }

    private function _get_products_lookup_json()
    {
        $products = $this->Products_model->get_details(array('active_only' => 1))->getResult();
        $lookup = array();
        foreach ($products as $product) {
            $lookup[$product->id] = array(
                'title' => $product->title,
                'sale_price' => (float) $product->sale_price,
                'cost_price' => (float) $product->cost_price,
                'product_type' => $product->product_type,
            );
        }

        return json_encode($lookup);
    }

    private function _get_search_term()
    {
        $search = $this->request->getPost('search');
        if (is_array($search)) {
            return trim((string) get_array_value($search, 'value'));
        }

        return trim((string) $search);
    }

    private function _audit($entity_type, $entity_id, $action, $old_data = array(), $new_data = array())
    {
        try {
            return $this->AuditService->record($entity_type, $entity_id, $action, $old_data, $new_data, array(
                'created_by' => $this->login_user->id,
            ));
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Audit error: ' . $e->getMessage());
            return false;
        }
    }

    private function _calculate_totals_from_items($items)
    {
        $total_cost = 0;
        $total_price = 0;
        foreach ($items as $item) {
            $total_cost += (float) $item->total_cost;
            $total_price += (float) $item->total_price;
        }

        $margin_value = $total_price - $total_cost;
        $margin_percent = $total_price > 0 ? (($margin_value / $total_price) * 100) : 0;

        return array(
            'total_cost' => $total_cost,
            'total_price' => $total_price,
            'margin_value' => $margin_value,
            'margin_percent' => $margin_percent,
        );
    }

    private function _code_exists($code, $ignore_id = 0)
    {
        $table = db_connect('default')->prefixTable('fv_kits');
        $builder = db_connect('default')->table($table);
        $builder->where('code', $code);
        if ($ignore_id) {
            $builder->where('id !=', $ignore_id);
        }

        return $builder->countAllResults() > 0;
    }
}
