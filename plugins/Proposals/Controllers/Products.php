<?php

namespace Proposals\Controllers;

use App\Controllers\Security_Controller;

class Products extends Security_Controller
{
    public $Items_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Items_model = model('App\\Models\\Items_model');
    }

    public function index()
    {
        if (!$this->_has_manage_permission()) {
            app_redirect('forbidden');
        }

        return $this->template->rander('Proposals\\Views\\proposals\\products_index');
    }

    public function list_data()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $db = db_connect('default');
        $items_table = $db->prefixTable('items');
        $has_ca_code = $db->fieldExists("ca_code", $items_table);
        $search = trim((string)$this->request->getPost('search'));
        $search_like = $search ? $db->escapeLikeString($search) : '';
        $where = "WHERE $items_table.deleted=0";
        if ($search_like) {
            $where .= $has_ca_code
                ? " AND ($items_table.title LIKE '%$search_like%' ESCAPE '!' OR $items_table.ca_code LIKE '%$search_like%' ESCAPE '!')"
                : " AND ($items_table.title LIKE '%$search_like%' ESCAPE '!')";
        }

        $has_cost = $db->fieldExists("cost", $items_table);
        $has_sale = $db->fieldExists("sale", $items_table);
        $has_markup = $db->fieldExists("markup", $items_table);

        $select = array(
            "$items_table.id",
            "$items_table.title",
            "$items_table.unit_type",
            "$items_table.rate"
        );
        if ($has_ca_code) { $select[] = "$items_table.ca_code"; }
        if ($has_cost) { $select[] = "$items_table.cost"; }
        if ($has_sale) { $select[] = "$items_table.sale"; }
        if ($has_markup) { $select[] = "$items_table.markup"; }

        $sql = "SELECT " . implode(", ", $select) . "
            FROM $items_table
            $where
            ORDER BY $items_table.id DESC";

        $query = $db->query($sql);
        if (!$query) {
            return $this->response->setJSON(array("data" => array()));
        }
        $rows = $query->getResult();
        $result = array();
        foreach ($rows as $row) {
            $result[] = $this->_make_row($row);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form()
    {
        if (!$this->_has_manage_permission()) {
            app_redirect('forbidden');
        }

        $this->validate_submitted_data(array(
            'id' => 'numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $item = $id ? $this->Items_model->get_one($id) : null;
        if ($id && (!$item || $item->deleted)) {
            show_404();
        }

        $settings_model = model('Proposals\\Models\\Proposals_module_settings_model');
        $settings = $settings_model->get_settings($this->_get_company_id());
        $default_markup_percent = isset($settings->default_markup_percent) ? (float)$settings->default_markup_percent : 0;

        $view_data = array(
            'item' => $item,
            'default_markup_percent' => $default_markup_percent
        );

        return $this->template->view('Proposals\\Views\\proposals\\products_modal_form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'title' => 'required'
        ));

        $id = (int)$this->request->getPost('id');
        $category_id = $this->_get_default_item_category_id();
        if (!$category_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $cost = $this->_parse_decimal($this->request->getPost('cost'));
        $sale = $this->_parse_decimal($this->request->getPost('sale'));
        $markup = $this->_parse_decimal($this->request->getPost('markup'));

        $db = db_connect('default');
        $items_table = $db->prefixTable('items');
        $has_ca_code = $db->fieldExists("ca_code", $items_table);
        $has_cost = $db->fieldExists("cost", $items_table);
        $has_sale = $db->fieldExists("sale", $items_table);
        $has_markup = $db->fieldExists("markup", $items_table);

        $data = array(
            'title' => trim((string)$this->request->getPost('title')),
            'category_id' => $category_id,
            'unit_type' => trim((string)$this->request->getPost('unit_type')),
            'rate' => $cost
        );
        if ($has_ca_code) {
            $data['ca_code'] = trim((string)$this->request->getPost('ca_code'));
        }
        if ($has_cost) {
            $data['cost'] = $cost;
        }
        if ($has_sale) {
            $data['sale'] = $sale;
        }
        if ($has_markup) {
            $data['markup'] = $markup;
        }

        $save_id = $this->Items_model->ci_save($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $item_id = $id ? $id : (is_int($save_id) ? $save_id : db_connect('default')->insertID());
        $row = $this->Items_model->get_one($item_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $item_id,
            'message' => app_lang('record_saved')
        ));
    }

    public function delete()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $success = $this->Items_model->delete($id);
        return $this->response->setJSON(array('success' => $success ? true : false, 'message' => app_lang('record_deleted')));
    }

    private function _make_row($row)
    {
            $cost = isset($row->cost) && is_numeric($row->cost) ? $row->cost : (is_numeric($row->rate) ? $row->rate : 0);
            $sale = isset($row->sale) && is_numeric($row->sale) ? $row->sale : 0;
            $markup = isset($row->markup) && is_numeric($row->markup) ? $row->markup : 0;

        $title = esc($row->title);
        if (isset($row->ca_code) && $row->ca_code !== "") {
            $title .= " <span class='mt0 badge ms-1' style='background-color:#1f78d1;' title='Conta Azul'>CA</span>";
        }

        $actions = modal_anchor(get_uri('propostas/products_modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $row->id,
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            'title' => app_lang('delete'),
            'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $row->id,
            'data-action-url' => get_uri('propostas/products_delete'),
            'data-action' => 'delete-confirmation'
        ));

        return array(
            $title,
            esc(isset($row->ca_code) ? $row->ca_code : '-'),
            esc($row->unit_type ?? '-'),
            to_currency($cost),
            to_currency($sale),
            number_format((float)$markup, 2, ",", ".") . "%",
            $actions
        );
    }

    private function _get_default_item_category_id()
    {
        $db = db_connect('default');
        $categories_table = $db->prefixTable('item_categories');
        $row = $db->table($categories_table)
            ->select('id')
            ->where('deleted', 0)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRow();

        if ($row && $row->id) {
            return (int)$row->id;
        }

        $db->table($categories_table)->insert(array(
            'title' => 'Geral',
            'deleted' => 0
        ));
        $new_id = $db->insertID();
        return $new_id ? (int)$new_id : 0;
    }

    private function _parse_decimal($value)
    {
        $text = trim((string)$value);
        if ($text === '') {
            return 0;
        }

        $text = preg_replace('/[^\d,\.\-]/', '', $text);
        $last_comma = strrpos($text, ',');
        $last_dot = strrpos($text, '.');

        if ($last_comma !== false && $last_dot !== false) {
            if ($last_comma > $last_dot) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($last_comma !== false) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            $text = str_replace(',', '', $text);
        }

        return (float)$text;
    }

    private function _has_manage_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'proposals_manage') == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
    }

    private function _get_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return $this->login_user->company_id;
        }

        return get_default_company_id();
    }
}
