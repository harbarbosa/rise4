<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;

/**
 * Controller de kits fotovoltaicos e seus itens.
 */
class Kits extends Security_Controller
{
    /** @var \Fotovoltaico\Models\Fv_kits_model */
    private $kits_model;

    /** @var \Fotovoltaico\Models\Fv_kit_items_model */
    private $items_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->kits_model = model('Fotovoltaico\\Models\\Fv_kits_model');
        $this->items_model = model('Fotovoltaico\\Models\\Fv_kit_items_model');
    }

    /**
     * Tela de listagem de kits.
     */
    public function index()
    {
        return $this->template->rander('Fotovoltaico\\Views\\kits\\index');
    }

    /**
     * Lista kits em JSON.
     */
    public function list_data()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_kits');
        $rows = $db->table($table)
            ->select('id,name,description,default_losses')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->_make_row($row);
        }

        return $this->response->setJSON(array('data' => $data));
    }

    /**
     * Modal de criação/edição de kit.
     */
    public function modal_form()
    {
        $this->validate_submitted_data(array('id' => 'numeric'));
        $id = (int)$this->request->getPost('id');
        $kit = $id ? $this->kits_model->get_one($id) : null;

        return $this->template->view('Fotovoltaico\\Views\\kits\\modal_form', array(
            'kit' => $kit
        ));
    }

    /**
     * Modal para gerenciar itens de um kit.
     */
    public function items_modal_form()
    {
        $this->validate_submitted_data(array('kit_id' => 'required|numeric'));
        $kit_id = (int)$this->request->getPost('kit_id');
        $products = $this->_get_products_dropdown();

        return $this->template->view('Fotovoltaico\\Views\\kits\\items_modal_form', array(
            'kit_id' => $kit_id,
            'products' => $products
        ));
    }

    /**
     * Salva kit.
     */
    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'name' => 'required'
        ));

        $id = (int)$this->request->getPost('id');
        $data = array(
            'name' => trim((string)$this->request->getPost('name')),
            'description' => trim((string)$this->request->getPost('description')),
            'default_losses' => $this->_parse_decimal($this->request->getPost('default_losses'))
        );

        $save_id = $this->kits_model->ci_save($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $row_id = $id ? $id : $save_id;
        $row = $this->kits_model->get_one($row_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $row_id,
            'message' => app_lang('record_saved')
        ));
    }

    /**
     * Exclui kit.
     */
    public function delete()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));
        $id = (int)$this->request->getPost('id');

        $db = db_connect('default');
        $table = $db->prefixTable('fv_kits');
        $deleted = $db->table($table)->delete(array('id' => $id));

        return $this->response->setJSON(array('success' => $deleted ? true : false, 'message' => app_lang('record_deleted')));
    }

    /**
     * Lista itens de um kit.
     */
    public function items_list_data()
    {
        $kit_id = (int)$this->request->getPost('kit_id');
        $db = db_connect('default');
        $items_table = $db->prefixTable('fv_kit_items');
        $products_table = $db->prefixTable('fv_products');

        $rows = $db->table($items_table)
            ->select("$items_table.id, $items_table.quantity, $items_table.is_optional, $products_table.brand, $products_table.model, $products_table.type")
            ->join($products_table, "$products_table.id = $items_table.product_id", 'left')
            ->where("$items_table.kit_id", $kit_id)
            ->orderBy("$items_table.id", 'DESC')
            ->get()
            ->getResult();

        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->_make_item_row($row);
        }

        return $this->response->setJSON(array('data' => $data));
    }

    /**
     * Salva item de kit.
     */
    public function save_item()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'kit_id' => 'required|numeric',
            'product_id' => 'required|numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $data = array(
            'kit_id' => (int)$this->request->getPost('kit_id'),
            'product_id' => (int)$this->request->getPost('product_id'),
            'quantity' => (int)$this->request->getPost('quantity'),
            'is_optional' => $this->request->getPost('is_optional') ? 1 : 0
        );

        $save_id = $this->items_model->ci_save($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    /**
     * Exclui item de kit.
     */
    public function delete_item()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));
        $id = (int)$this->request->getPost('id');

        $db = db_connect('default');
        $table = $db->prefixTable('fv_kit_items');
        $deleted = $db->table($table)->delete(array('id' => $id));

        return $this->response->setJSON(array('success' => $deleted ? true : false, 'message' => app_lang('record_deleted')));
    }

    /**
     * Monta linha de kit.
     */
    private function _make_row($row)
    {
        $actions = modal_anchor(get_uri('fotovoltaico/kits_modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $row->id,
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . modal_anchor(get_uri('fotovoltaico/kit_items_modal_form'), "<i data-feather='list' class='icon-16'></i>", array(
            'title' => app_lang('fv_kit_items'),
            'data-post-kit_id' => $row->id,
            'class' => 'btn btn-sm btn-outline-primary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            'title' => app_lang('delete'),
            'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $row->id,
            'data-action-url' => get_uri('fotovoltaico/kits_delete'),
            'data-action' => 'delete-confirmation'
        ));

        return array(
            esc($row->name),
            esc($row->description ?? '-'),
            number_format((float)$row->default_losses, 2, ',', '.') . '%',
            $actions
        );
    }

    /**
     * Monta linha de item de kit.
     */
    private function _make_item_row($row)
    {
        $label = trim(($row->brand ?? '') . ' ' . ($row->model ?? ''));
        if ($label === '') {
            $label = app_lang('fv_product');
        }

        return array(
            esc($row->type ?? '-'),
            esc($label),
            (int)$row->quantity,
            $row->is_optional ? app_lang('yes') : app_lang('no')
        );
    }

    /**
     * Converte texto para decimal.
     */
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

    /**
     * Monta dropdown de produtos para itens de kit.
     */
    private function _get_products_dropdown()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_products');
        $rows = $db->table($table)
            ->select('id,brand,model,sku')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        $options = array('' => '-');
        foreach ($rows as $row) {
            $label = trim(($row->brand ?? '') . ' ' . ($row->model ?? ''));
            if ($label === '') {
                $label = $row->sku ?: ('#' . $row->id);
            }
            $options[$row->id] = $label;
        }

        return $options;
    }
}
