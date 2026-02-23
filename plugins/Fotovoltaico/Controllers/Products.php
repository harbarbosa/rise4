<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;

/**
 * Controller de produtos fotovoltaicos.
 */
class Products extends Security_Controller
{
    /** @var \Fotovoltaico\Models\Fv_product_model */
    private $products_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->products_model = model('Fotovoltaico\\Models\\Fv_product_model');
    }

    /**
     * Tela de listagem de produtos.
     */
    public function index()
    {
        return $this->template->rander('Fotovoltaico\\Views\\products\\index');
    }

    /**
     * Lista produtos em JSON para appTable.
     */
    public function list_data()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_product');
        $rows = $db->table($table)
            ->select('id,type,brand,model,sku,cost,price')
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
     * Modal de criação/edição.
     */
    public function modal_form()
    {
        $this->validate_submitted_data(array('id' => 'numeric'));
        $id = (int)$this->request->getPost('id');
        $item = $id ? $this->products_model->get_one($id) : null;

        return $this->template->view('Fotovoltaico\\Views\\products\\modal_form', array(
            'item' => $item
        ));
    }

    /**
     * Salva produto fotovoltaico.
     */
    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'type' => 'required',
            'brand' => 'permit_empty',
            'model' => 'permit_empty'
        ));

        $id = (int)$this->request->getPost('id');
        $specs_raw = trim((string)$this->request->getPost('specs'));
        $specs = $this->_normalize_json($specs_raw);

        $data = array(
            'type' => trim((string)$this->request->getPost('type')),
            'brand' => trim((string)$this->request->getPost('brand')),
            'model' => trim((string)$this->request->getPost('model')),
            'sku' => trim((string)$this->request->getPost('sku')),
            'cost' => $this->_parse_decimal($this->request->getPost('cost')),
            'price' => $this->_parse_decimal($this->request->getPost('price')),
            'specs' => $specs,
            'datasheet_url' => trim((string)$this->request->getPost('datasheet_url'))
        );

        $save_id = $this->products_model->ci_save($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $row_id = $id ? $id : $save_id;
        $row = $this->products_model->get_one($row_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $row_id,
            'message' => app_lang('record_saved')
        ));
    }

    /**
     * Exclui produto fotovoltaico.
     */
    public function delete()
    {
        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $db = db_connect('default');
        $table = $db->prefixTable('fv_product');
        $deleted = $db->table($table)->delete(array('id' => $id));

        return $this->response->setJSON(array('success' => $deleted ? true : false, 'message' => app_lang('record_deleted')));
    }

    /**
     * Retorna produtos filtrados por tipo (para AJAX).
     */
    public function by_type()
    {
        $type = trim((string)$this->request->getPost('type'));
        $db = db_connect('default');
        $table = $db->prefixTable('fv_product');

        $query = $db->table($table)->select('id,brand,model,sku,price,cost')->orderBy('id', 'DESC');
        if ($type) {
            $query->where('type', $type);
        }

        $rows = $query->get()->getResultArray();
        return $this->response->setJSON(array('success' => true, 'data' => $rows));
    }

    /**
     * Monta linha de listagem.
     */
    private function _make_row($row)
    {
        $actions = modal_anchor(get_uri('fotovoltaico/products_modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $row->id,
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            'title' => app_lang('delete'),
            'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $row->id,
            'data-action-url' => get_uri('fotovoltaico/products_delete'),
            'data-action' => 'delete-confirmation'
        ));

        return array(
            esc($row->type),
            esc($row->brand ?? '-'),
            esc($row->model ?? '-'),
            esc($row->sku ?? '-'),
            to_currency($row->cost ?? 0),
            to_currency($row->price ?? 0),
            $actions
        );
    }

    /**
     * Normaliza texto JSON ou retorna null.
     */
    private function _normalize_json($value)
    {
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
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
}
