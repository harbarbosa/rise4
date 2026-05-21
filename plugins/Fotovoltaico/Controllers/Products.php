<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;

class Products extends Security_Controller
{
    private $Products_model;
    private $Product_categories_model;
    private $Distributors_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canViewProducts($this->login_user) && !Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();

        $this->Products_model = model('Fotovoltaico\\Models\\Products_model');
        $this->Product_categories_model = model('Fotovoltaico\\Models\\Product_categories_model');
        $this->Distributors_model = model('Fotovoltaico\\Models\\Distributors_model');
    }

    public function index()
    {
        $view_data = array();
        $view_data['categories_dropdown'] = $this->_get_categories_dropdown(true);
        $view_data['product_types_dropdown'] = $this->_get_product_types_dropdown(true);
        $view_data['status_dropdown'] = $this->_get_status_dropdown(true);
        $view_data['can_manage_products'] = Plugin::canManageProducts($this->login_user);

        return $this->template->rander('Fotovoltaico\\Views\\products\\index', $view_data);
    }

    public function list_data()
    {
        $category_id = get_only_numeric_value($this->request->getPost('category_id'));
        $product_type = trim((string) $this->request->getPost('product_type'));
        $active = $this->request->getPost('active');
        $search = $this->_get_search_term();

        $options = array(
            'category_id' => $category_id,
            'product_type' => $product_type,
            'search' => $search,
        );

        if ($active !== '' && $active !== null) {
            $options['active_only'] = (int) $active;
        }

        $list_data = $this->Products_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        echo json_encode(array('data' => $result));
    }

    public function modal_form()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric'
        ));

        $id = (int) $this->request->getPost('id');
        $model_info = $this->Products_model->get_one($id);
        $view_data = array();
        $view_data['model_info'] = $model_info;
        $view_data['categories_dropdown'] = $this->_get_categories_dropdown(false);
        $view_data['distributors_dropdown'] = $this->_get_distributors_dropdown(false);
        $view_data['product_types_dropdown'] = $this->_get_product_types_dropdown(false);
        $view_data['technical_specs_json'] = $this->_normalize_json_for_form($model_info->technical_specs_json ?? '');
        $view_data['can_manage_products'] = Plugin::canManageProducts($this->login_user);

        return $this->template->view('Fotovoltaico\\Views\\products\\modal_form', $view_data);
    }

    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'category_id' => 'required|numeric',
            'product_type' => 'required',
            'title' => 'required'
        ));

        if (!Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $sku = trim((string) $this->request->getPost('sku'));
        if ($sku && $this->_sku_exists($sku, $id)) {
            echo json_encode(array('success' => false, 'message' => app_lang('fotovoltaico_sku_exists')));
            return;
        }

        $technical_specs_json = $this->_normalize_json_for_save((string) $this->request->getPost('technical_specs_json'));
        if ($technical_specs_json === false) {
            echo json_encode(array('success' => false, 'message' => app_lang('fotovoltaico_invalid_json')));
            return;
        }

        $product_type = trim((string) $this->request->getPost('product_type'));
        $has_specs = in_array($product_type, array('modulo', 'inversor'), true);

        $power_rating = $has_specs ? (float) unformat_currency($this->request->getPost('power_rating')) : 0;
        $efficiency = $has_specs ? (float) unformat_currency($this->request->getPost('efficiency')) : 0;

        $data = array(
            'category_id' => (int) $this->request->getPost('category_id'),
            'distributor_id' => ($distributor_id = get_only_numeric_value($this->request->getPost('distributor_id'))) ? (int) $distributor_id : null,
            'product_type' => $product_type,
            'sku' => $sku ?: null,
            'title' => trim((string) $this->request->getPost('title')),
            'description' => trim((string) $this->request->getPost('description')) ?: null,
            'brand' => trim((string) $this->request->getPost('brand')) ?: null,
            'model' => trim((string) $this->request->getPost('model')) ?: null,
            'unit' => trim((string) $this->request->getPost('unit')) ?: 'un',
            'warranty' => trim((string) $this->request->getPost('warranty')) ?: null,
            'power_rating' => $power_rating,
            'efficiency' => $efficiency,
            'voltage' => trim((string) $this->request->getPost('voltage')) ?: null,
            'cost_price' => unformat_currency($this->request->getPost('cost_price')),
            'sale_price' => unformat_currency($this->request->getPost('sale_price')),
            'tax_rate' => unformat_currency($this->request->getPost('tax_rate')),
            'technical_specs_json' => $technical_specs_json ?: null,
            'active' => $this->request->getPost('active') ? 1 : 0,
            'updated_at' => get_my_local_time(),
        );

        $data = clean_data($data);
        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->Products_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array(
                'success' => true,
                'id' => $save_id,
                'data' => $this->_make_row($this->Products_model->get_details(array('id' => $save_id))->getRow()),
                'message' => app_lang('record_saved')
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
        }
    }

    public function delete()
    {
        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        if (!Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        if ($this->Products_model->delete($id)) {
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

        $product = $this->Products_model->get_details(array('id' => $id))->getRow();
        if (!$product) {
            show_404();
        }

        $view_data = array();
        $view_data['product'] = $product;
        $view_data['specs_array'] = $this->_decode_json_to_array($product->technical_specs_json ?? '');
        $view_data['can_manage_products'] = Plugin::canManageProducts($this->login_user);
        $view_data['product_type_label'] = $this->_product_type_label($product->product_type ?? '');

        return $this->template->rander('Fotovoltaico\\Views\\products\\view', $view_data);
    }

    private function _make_row($data)
    {
        if (!$data) {
            return array();
        }

        $title = anchor(get_uri('fotovoltaico/products/view/' . $data->id), esc($data->title), array(
            'title' => app_lang('fotovoltaico_product_details')
        ));

        $row = array(
            $title,
            esc($data->category_title ?: '-'),
            esc($this->_product_type_label($data->product_type ?: '')),
            esc($data->sku ?: '-'),
            esc($data->brand ?: '-'),
            esc($data->model ?: '-'),
            to_currency((float) $data->cost_price, 'R$'),
            to_currency((float) $data->sale_price, 'R$'),
            $this->_status_badge((int) $data->active),
        );

        $actions = anchor(get_uri('fotovoltaico/products/view/' . $data->id), "<i data-feather='eye' class='icon-16'></i>", array(
            'class' => 'view',
            'title' => app_lang('fotovoltaico_product_details'),
        ));

        if (Plugin::canManageProducts($this->login_user)) {
            $actions .= modal_anchor(get_uri('fotovoltaico/products/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                'class' => 'edit',
                'title' => app_lang('fotovoltaico_edit_product'),
                'data-post-id' => $data->id,
            ));

            $actions .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => app_lang('delete'),
                'class' => 'delete',
                'data-id' => $data->id,
                'data-action-url' => get_uri('fotovoltaico/products/delete'),
                'data-action' => 'delete'
            ));
        }

        $row[] = $actions;

        return $row;
    }

    private function _get_categories_dropdown($for_filter = false)
    {
        $query = $for_filter
            ? $this->Product_categories_model->get_details(array('active_only' => 1))
            : $this->Product_categories_model->get_details();
        $categories = $query ? $query->getResult() : array();
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
        $distributors = $this->Distributors_model->get_dropdown();
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

    private function _get_product_types_dropdown($for_filter = false)
    {
        $types = $this->Products_model->get_product_types();
        if ($for_filter) {
            $list = array(array('id' => '', 'text' => '-'));
            foreach ($types as $value => $label) {
                $list[] = array('id' => $value, 'text' => $label);
            }
            return json_encode($list);
        }

        $dropdown = array('' => '-');
        foreach ($types as $value => $label) {
            $dropdown[$value] = $label;
        }
        return $dropdown;
    }

    private function _get_status_dropdown($for_filter = false)
    {
        $statuses = array(
            '' => '-',
            '1' => app_lang('active'),
            '0' => app_lang('inactive'),
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

    private function _product_type_label($product_type)
    {
        $types = $this->Products_model->get_product_types();
        return get_array_value($types, $product_type) ?: $product_type;
    }

    private function _status_badge($active)
    {
        if ($active) {
            return "<span class='badge bg-success'>" . esc(app_lang('active')) . "</span>";
        }

        return "<span class='badge bg-secondary'>" . esc(app_lang('inactive')) . "</span>";
    }

    private function _get_search_term()
    {
        $search = $this->request->getPost('search');
        if (is_array($search)) {
            return trim((string) get_array_value($search, 'value'));
        }

        return trim((string) $search);
    }

    private function _normalize_json_for_save($json_text)
    {
        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return '';
        }

        $decoded = json_decode($json_text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function _normalize_json_for_form($json_text)
    {
        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return '';
        }

        $decoded = json_decode($json_text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $json_text;
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function _decode_json_to_array($json_text)
    {
        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return array();
        }

        $decoded = json_decode($json_text, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : array();
    }

    private function _sku_exists($sku, $ignore_id = 0)
    {
        $table = db_connect('default')->prefixTable('fv_products');
        $builder = db_connect('default')->table($table);
        $builder->where('sku', $sku);
        if ($ignore_id) {
            $builder->where('id !=', $ignore_id);
        }

        return $builder->countAllResults() > 0;
    }
}
