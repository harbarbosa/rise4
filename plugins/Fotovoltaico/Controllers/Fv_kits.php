<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Services\ElectricalValidatorService;

/**
 * Controller de kits fotovoltaicos e kit builder.
 */
class Fv_kits extends Security_Controller
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

    public function index()
    {
        return $this->template->rander('Fotovoltaico\\Views\\kits\\index');
    }

    public function list_data()
    {
        $filters = array(
            'is_active' => $this->request->getPost('is_active'),
            'q' => $this->request->getPost('q') ?: $this->request->getPost('search')
        );

        $rows = $this->kits_model->get_list($filters);
        $data = array();
        foreach ($rows as $row) {
            $totals = $this->items_model->get_totals($row['id']);
            $data[] = $this->_make_row($row, $totals);
        }

        return $this->response->setJSON(array('data' => $data));
    }

    public function modal_form()
    {
        $this->validate_submitted_data(array('id' => 'numeric'));
        $id = (int)$this->request->getPost('id');
        $kit = $id ? $this->kits_model->get_one($id) : null;

        return $this->template->view('Fotovoltaico\\Views\\kits\\modal_form', array(
            'kit' => $kit
        ));
    }

    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'name' => 'required'
        ));

        $db = db_connect('default');
        $table = $db->prefixTable('fv_kits');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $id = (int)$this->request->getPost('id');
        $data = array(
            'name' => trim((string)$this->request->getPost('name')),
            'description' => trim((string)$this->request->getPost('description')),
            'default_losses_percent' => $this->_parse_decimal($this->request->getPost('default_losses_percent')),
            'default_markup_percent' => $this->_parse_decimal($this->request->getPost('default_markup_percent')),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'created_by' => $this->login_user->id
        );

        $save_id = $id ? $this->kits_model->update($id, $data) : $this->kits_model->create($data);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $row_id = $id ? $id : $save_id;
        $row = $this->kits_model->get_one($row_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row((array)$row, $this->items_model->get_totals($row_id)),
            'id' => $row_id,
            'message' => app_lang('record_saved')
        ));
    }

    public function toggle_active()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));
        $id = (int)$this->request->getPost('id');
        $is_active = (int)$this->request->getPost('is_active');
        $this->kits_model->toggle_active($id, $is_active);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function delete()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));
        $id = (int)$this->request->getPost('id');
        $this->kits_model->toggle_active($id, 0);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
    }

    public function view($id = 0)
    {
        $id = (int)$id;
        $kit = $this->kits_model->get_one($id);
        if (!$kit || !$kit->id) {
            show_404();
        }

        return $this->template->rander('Fotovoltaico\\Views\\kits\\view', array(
            'kit' => $kit
        ));
    }

    public function items($kit_id = 0)
    {
        $kit_id = (int)$kit_id;
        $items = $this->items_model->get_by_kit($kit_id);
        $totals = $this->items_model->get_totals($kit_id);

        return $this->response->setJSON(array('success' => true, 'message' => '', 'data' => array(
            'items' => $items,
            'totals' => $totals
        )));
    }

    public function add_item()
    {
        $this->validate_submitted_data(array(
            'kit_id' => 'required|numeric',
            'item_type' => 'required'
        ));

        $kit_id = (int)$this->request->getPost('kit_id');
        $item_type = $this->request->getPost('item_type');
        $next_sort = $this->_get_next_sort_order($kit_id);

        if ($item_type === 'product') {
            $product_id = (int)$this->request->getPost('product_id');
            if (!$product_id) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }

            $data = array(
                'kit_id' => $kit_id,
                'item_type' => 'product',
                'product_id' => $product_id,
                'qty' => $this->_parse_decimal($this->request->getPost('qty')) ?: 1,
                'is_optional' => $this->request->getPost('is_optional') ? 1 : 0,
                'sort_order' => $next_sort
            );
        } else {
            $name = trim((string)$this->request->getPost('name'));
            if (!$name) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }

            $data = array(
                'kit_id' => $kit_id,
                'item_type' => 'custom',
                'name' => $name,
                'description' => trim((string)$this->request->getPost('description')),
                'unit' => trim((string)$this->request->getPost('unit')),
                'qty' => $this->_parse_decimal($this->request->getPost('qty')) ?: 1,
                'cost' => $this->_parse_decimal($this->request->getPost('cost')),
                'price' => $this->_parse_decimal($this->request->getPost('price')),
                'is_optional' => $this->request->getPost('is_optional') ? 1 : 0,
                'sort_order' => $next_sort
            );
        }

        $save_id = $this->items_model->add_item($data);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function update_item()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));
        $id = (int)$this->request->getPost('id');

        $item = $this->items_model->get_one($id);
        if (!$item || !$item->id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $data = array(
            'qty' => $this->_parse_decimal($this->request->getPost('qty')) ?: $item->qty,
            'is_optional' => $this->request->getPost('is_optional') ? 1 : 0
        );

        if ($item->item_type === 'custom') {
            $data['name'] = trim((string)$this->request->getPost('name')) ?: $item->name;
            $data['description'] = trim((string)$this->request->getPost('description'));
            $data['unit'] = trim((string)$this->request->getPost('unit'));
            $data['cost'] = $this->_parse_decimal($this->request->getPost('cost'));
            $data['price'] = $this->_parse_decimal($this->request->getPost('price'));
        }

        $this->items_model->update_item($id, $data);
        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function delete_item()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));
        $id = (int)$this->request->getPost('id');
        $this->items_model->delete_item($id);
        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
    }

    public function reorder_items()
    {
        $this->validate_submitted_data(array(
            'kit_id' => 'required|numeric',
            'ordered_ids' => 'required'
        ));

        $kit_id = (int)$this->request->getPost('kit_id');
        $ordered_ids = $this->request->getPost('ordered_ids');
        if (!is_array($ordered_ids)) {
            $ordered_ids = array_filter(explode(',', (string)$ordered_ids));
        }

        $this->items_model->reorder_items($kit_id, $ordered_ids);
        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function validate_electrical()
    {
        $this->validate_submitted_data(array('kit_id' => 'required|numeric'));
        $kit_id = (int)$this->request->getPost('kit_id');
        $save = $this->request->getPost('save') ? true : false;

        $settings = $this->_get_electrical_settings();
        $service = new ElectricalValidatorService($settings);
        $result = $service->validateKit($kit_id, $save);

        return $this->response->setJSON(array('success' => true, 'message' => '', 'data' => $result));
    }

    public function api_kits()
    {
        $rows = $this->kits_model->get_list(array('is_active' => 1));
        $data = array();
        foreach ($rows as $row) {
            $totals = $this->items_model->get_totals($row['id']);
            $data[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'active' => $row['is_active'],
                'summary' => $totals
            );
        }

        return $this->response->setJSON(array('success' => true, 'message' => '', 'data' => $data));
    }

    public function api_kit($id = 0)
    {
        $id = (int)$id;
        $kit = $this->kits_model->get_one($id);
        if (!$kit || !$kit->id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found'), 'data' => null));
        }

        $items = $this->items_model->get_by_kit($id);
        $totals = $this->items_model->get_totals($id);

        return $this->response->setJSON(array(
            'success' => true,
            'message' => '',
            'data' => array(
                'kit' => $kit,
                'items' => $items,
                'totals' => $totals
            )
        ));
    }

    public function api_kit_totals($id = 0)
    {
        $id = (int)$id;
        $totals = $this->items_model->get_totals($id);
        return $this->response->setJSON(array('success' => true, 'message' => '', 'data' => $totals));
    }

    private function _make_row($row, $totals)
    {
        $is_active = (int)($row['is_active'] ?? 0);
        $badge = $is_active ? "<span class='badge bg-success'>" . app_lang('yes') . "</span>" : "<span class='badge bg-secondary'>" . app_lang('no') . "</span>";

        $actions = modal_anchor(get_uri('fotovoltaico/kits_modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $row['id'],
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . anchor(get_uri('fotovoltaico/kits/view/' . $row['id']), "<i data-feather='sliders' class='icon-16'></i>", array(
            'title' => app_lang('fv_kit_builder'),
            'class' => 'btn btn-sm btn-outline-primary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='power' class='icon-16'></i>", array(
            'title' => app_lang('fv_toggle_active'),
            'class' => 'btn btn-sm btn-outline-primary js-toggle-active',
            'data-id' => $row['id'],
            'data-active' => $is_active ? 0 : 1
        ));
        $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            'title' => app_lang('delete'),
            'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $row['id'],
            'data-action-url' => get_uri('fotovoltaico/kits_delete'),
            'data-action' => 'delete-confirmation'
        ));

        return array(
            $badge,
            esc($row['name']),
            number_format((float)($row['default_losses_percent'] ?? 0), 2, ',', '.') . '%',
            number_format((float)($row['default_markup_percent'] ?? 0), 2, ',', '.') . '%',
            (int)($totals['item_count'] ?? 0),
            number_format((float)($totals['power_kwp'] ?? 0), 2, ',', '.') . ' kWp',
            to_currency($totals['cost_total'] ?? 0),
            to_currency($totals['price_total'] ?? 0),
            $actions
        );
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

    private function _get_next_sort_order($kit_id)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_kit_items');
        $row = $db->table($table)
            ->selectMax('sort_order')
            ->where('kit_id', (int)$kit_id)
            ->get()
            ->getRow();
        $max = $row && isset($row->sort_order) ? (int)$row->sort_order : 0;
        return $max + 1;
    }

    private function _get_electrical_settings()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_settings');
        $defaults = [
            'temp_min_c' => 5,
            'temp_max_c' => 70,
            'safety_margin_vdc_percent' => 2,
            'safety_margin_current_percent' => 0,
            'assume_voc_temp_coeff_if_missing' => -0.28,
            'assume_vmpp_ratio' => 0.83
        ];
        if (!$db->tableExists($table)) {
            return $defaults;
        }
        $row = $db->table($table)->where('provider', 'electrical')->get()->getRow();
        $settings = $row && $row->settings_json ? json_decode($row->settings_json, true) : [];
        if (!is_array($settings)) {
            $settings = [];
        }
        return array_merge($defaults, $settings);
    }
}
