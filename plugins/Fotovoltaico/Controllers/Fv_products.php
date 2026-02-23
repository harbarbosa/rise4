<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;

/**
 * Controller de produtos fotovoltaicos (CRUD + importação + APIs).
 */
class Fv_products extends Security_Controller
{
    /** @var \Fotovoltaico\Models\Fv_products_model */
    private $products_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->products_model = model('Fotovoltaico\\Models\\Fv_products_model');
    }

    public function index()
    {
        $this->_check_manage_permission();

        $brands = $this->_get_brand_dropdown();
        $types = $this->_get_type_dropdown();

        return $this->template->rander('Fotovoltaico\\Views\\products\\index', array(
            'brands_dropdown' => $brands,
            'types_dropdown' => $types
        ));
    }

    public function list_data()
    {
        $this->_check_manage_permission();

        $db = db_connect('default');
        $table = $db->prefixTable('fv_products');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(array('data' => array()));
        }

        $filters = array(
            'type' => $this->request->getPost('type'),
            'brand' => $this->request->getPost('brand'),
            'q' => $this->request->getPost('q') ?: $this->request->getPost('search'),
            'is_active' => $this->request->getPost('is_active')
        );

        $rows = $this->products_model->get_list($filters, 200, 0);
        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->_make_row($row);
        }

        return $this->response->setJSON(array('data' => $data));
    }

    public function modal_form()
    {
        $this->_check_manage_permission();
        $this->validate_submitted_data(array('id' => 'numeric'));

        $id = (int)$this->request->getPost('id');
        $item = $id ? $this->products_model->get_one($id) : null;
        $types = $this->_get_type_dropdown();

        return $this->template->view('Fotovoltaico\\Views\\products\\modal_form', array(
            'item' => $item,
            'types' => $types
        ));
    }

    public function save()
    {
        $this->_check_manage_permission();

        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'type' => 'required',
            'brand' => 'required',
            'model' => 'required'
        ));

        $id = (int)$this->request->getPost('id');
        $type = trim((string)$this->request->getPost('type'));
        $specs = $this->_build_specs($type, $this->request->getPost());

        $db = db_connect('default');
        $table = $db->prefixTable('fv_products');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $cost = $this->_parse_decimal($this->request->getPost('cost'));
        $price = $this->_parse_decimal($this->request->getPost('price'));
        if ($cost < 0 || $price < 0) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Custo/Venda deve ser >= 0'));
        }

        $errors = $this->_validate_specs($type, $specs);
        if ($errors) {
            return $this->response->setJSON(array('success' => false, 'message' => implode(' | ', $errors)));
        }

        $data = array(
            'type' => $type,
            'brand' => trim((string)$this->request->getPost('brand')),
            'model' => trim((string)$this->request->getPost('model')),
            'sku' => trim((string)$this->request->getPost('sku')),
            'power_w' => $this->_parse_decimal($this->request->getPost('power_w')),
            'cost' => $cost,
            'price' => $price,
            'warranty_years' => $this->_parse_int($this->request->getPost('warranty_years')),
            'datasheet_url' => trim((string)$this->request->getPost('datasheet_url')),
            'specs_json' => $specs ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'created_by' => $this->login_user->id
        );

        if ($id) {
            $save_id = $this->products_model->update($id, $data);
        } else {
            $save_id = $this->products_model->create($data);
        }

        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $row_id = $id ? $id : $save_id;
        $row = $this->products_model->get_one($row_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row((array)$row),
            'id' => $row_id,
            'message' => app_lang('record_saved')
        ));
    }

    public function delete()
    {
        $this->_check_manage_permission();
        $this->validate_submitted_data(array('id' => 'required|numeric'));

        $id = (int)$this->request->getPost('id');
        $this->products_model->soft_toggle_active($id, 0);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
    }

    public function toggle_active()
    {
        $this->_check_manage_permission();
        $this->validate_submitted_data(array('id' => 'required|numeric'));

        $id = (int)$this->request->getPost('id');
        $is_active = (int)$this->request->getPost('is_active');
        $this->products_model->soft_toggle_active($id, $is_active);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function view($id = 0)
    {
        $this->_check_manage_permission();
        $id = (int)$id;
        $item = $this->products_model->get_one($id);
        if (!$item || !$item->id) {
            show_404();
        }

        return $this->template->rander('Fotovoltaico\\Views\\products\\view', array(
            'item' => $item
        ));
    }

    public function api_products()
    {
        $type = $this->request->getGet('type');
        $q = $this->request->getGet('q');
        $active = $this->request->getGet('active');

        $rows = $this->products_model->get_list(array(
            'type' => $type,
            'q' => $q,
            'is_active' => $active === null ? '' : $active
        ), 200, 0);

        $data = array();
        foreach ($rows as $row) {
            $label = trim($row['brand'] . ' - ' . $row['model']);
            if (!empty($row['power_w'])) {
                $label .= ' (' . number_format((float)$row['power_w'], 0, ',', '.') . 'W)';
            }
            $data[] = array(
                'id' => $row['id'],
                'label' => $label,
                'type' => $row['type'],
                'power_w' => $row['power_w'],
                'price' => $row['price']
            );
        }

        return $this->response->setJSON(array('success' => true, 'message' => '', 'data' => $data));
    }

    public function api_product($id = 0)
    {
        $id = (int)$id;
        $item = $this->products_model->get_one($id);
        if (!$item || !$item->id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found'), 'data' => null));
        }

        return $this->response->setJSON(array('success' => true, 'message' => '', 'data' => $item));
    }

    public function import_modal_form()
    {
        $this->_check_manage_permission();
        $types = $this->_get_type_dropdown();

        return $this->template->view('Fotovoltaico\\Views\\products\\import_modal', array(
            'types' => $types
        ));
    }

    public function import_preview()
    {
        $this->_check_manage_permission();

        $default_type = trim((string)$this->request->getPost('default_type'));
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $temp_dir = WRITEPATH . 'temp/fv_imports/';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0775, true);
        }

        $token = uniqid('fv_', true) . '.csv';
        $file->move($temp_dir, $token, true);

        $path = $temp_dir . $token;
        $parsed = $this->_parse_csv($path, $default_type, 10);

        return $this->response->setJSON(array(
            'success' => true,
            'message' => '',
            'data' => array(
                'token' => $token,
                'preview' => $parsed['rows'],
                'errors' => $parsed['errors']
            )
        ));
    }

    public function import_process()
    {
        $this->_check_manage_permission();

        $default_type = trim((string)$this->request->getPost('default_type'));
        $token = trim((string)$this->request->getPost('token'));
        if (!$token) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $path = WRITEPATH . 'temp/fv_imports/' . $token;
        if (!is_file($path)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $parsed = $this->_parse_csv($path, $default_type, 0);
        $report = $this->_import_rows($parsed['rows']);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved'), 'data' => $report));
    }

    private function _import_rows($rows)
    {
        $inserted = 0;
        $updated = 0;
        $errors = array();

        foreach ($rows as $row) {
            $type = $row['type'] ?? '';
            $brand = $row['brand'] ?? '';
            $model = $row['model'] ?? '';
            if (!$type || !$brand || !$model) {
                $errors[] = 'Linha inválida: type/brand/model';
                continue;
            }

            $specs = $row['specs'] ?? array();
            $data = array(
                'type' => $type,
                'brand' => $brand,
                'model' => $model,
                'sku' => $row['sku'] ?? null,
                'power_w' => $row['power_w'] ?? null,
                'cost' => $row['cost'] ?? 0,
                'price' => $row['price'] ?? 0,
                'warranty_years' => $row['warranty_years'] ?? null,
                'datasheet_url' => $row['datasheet_url'] ?? null,
                'specs_json' => $specs ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null,
                'is_active' => 1,
                'created_by' => $this->login_user->id
            );

            $db = db_connect('default');
            $table = $db->prefixTable('fv_products');
            $existing = $db->table($table)
                ->select('id')
                ->where('type', $type)
                ->where('brand', $brand)
                ->where('model', $model)
                ->get()
                ->getRow();

            if ($existing && $existing->id) {
                $this->products_model->update($existing->id, $data);
                $updated++;
            } else {
                $this->products_model->create($data);
                $inserted++;
            }
        }

        return array(
            'inserted' => $inserted,
            'updated' => $updated,
            'errors' => $errors
        );
    }

    private function _parse_csv($path, $default_type, $limit = 0)
    {
        $rows = array();
        $errors = array();

        $handle = fopen($path, 'r');
        if (!$handle) {
            return array('rows' => array(), 'errors' => array('Falha ao ler CSV'));
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return array('rows' => array(), 'errors' => array('CSV vazio'));
        }

        $delimiter = $this->_detect_delimiter($header);
        if ($delimiter !== ',') {
            rewind($handle);
            $header = fgetcsv($handle, 0, $delimiter);
        }

        $map = $this->_build_header_map($header);
        $count = 0;
        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = $this->_map_csv_row($line, $map, $default_type);
            $rows[] = $row;
            $count++;
            if ($limit && $count >= $limit) {
                break;
            }
        }
        fclose($handle);

        return array('rows' => $rows, 'errors' => $errors);
    }

    private function _build_header_map($header)
    {
        $map = array();
        foreach ($header as $index => $name) {
            $key = strtolower(trim($name));
            $key = str_replace(' ', '_', $key);
            $map[$index] = $key;
        }
        return $map;
    }

    private function _map_csv_row($line, $map, $default_type)
    {
        $row = array(
            'specs' => array()
        );
        foreach ($map as $idx => $key) {
            $value = $line[$idx] ?? '';
            $value = trim((string)$value);

            if (strpos($key, 'specs_') === 0) {
                $spec_key = str_replace('specs_', '', $key);
                $row['specs'][$spec_key] = $this->_parse_decimal($value);
                continue;
            }

            switch ($key) {
                case 'type':
                    $row['type'] = $value ?: $default_type;
                    break;
                case 'brand':
                    $row['brand'] = $value;
                    break;
                case 'model':
                    $row['model'] = $value;
                    break;
                case 'sku':
                    $row['sku'] = $value;
                    break;
                case 'power_w':
                    $row['power_w'] = $this->_parse_decimal($value);
                    break;
                case 'cost':
                    $row['cost'] = $this->_parse_decimal($value);
                    break;
                case 'price':
                    $row['price'] = $this->_parse_decimal($value);
                    break;
                case 'warranty_years':
                    $row['warranty_years'] = $this->_parse_int($value);
                    break;
                case 'datasheet_url':
                    $row['datasheet_url'] = $value;
                    break;
            }
        }

        if (empty($row['type'])) {
            $row['type'] = $default_type;
        }
        if (!in_array($row['type'], array_keys($this->_get_type_dropdown()), true)) {
            $row['type'] = $default_type ?: 'module';
        }

        return $row;
    }

    private function _detect_delimiter($header)
    {
        $raw = implode(',', $header);
        $count_comma = substr_count($raw, ',');
        $count_semicolon = substr_count($raw, ';');
        return $count_semicolon > $count_comma ? ';' : ',';
    }

    private function _make_row($row)
    {
        $is_active = (int)($row['is_active'] ?? 0);
        $badge = $is_active ? "<span class='badge bg-success'>" . app_lang('yes') . "</span>" : "<span class='badge bg-secondary'>" . app_lang('no') . "</span>";

        $actions = modal_anchor(get_uri('fotovoltaico/products_modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $row['id'],
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='power' class='icon-16'></i>", array(
            'title' => app_lang('fv_toggle_active'),
            'class' => 'btn btn-sm btn-outline-primary js-toggle-active',
            'data-id' => $row['id'],
            'data-active' => $is_active ? 0 : 1
        ));
        $actions .= ' ' . anchor(get_uri('fotovoltaico/products/view/' . $row['id']), "<i data-feather='eye' class='icon-16'></i>", array(
            'title' => app_lang('view'),
            'class' => 'btn btn-sm btn-outline-primary'
        ));

        return array(
            $badge,
            esc($row['type']),
            esc($row['brand']),
            esc($row['model']),
            esc($row['power_w'] ?? '-'),
            to_currency($row['cost'] ?? 0),
            to_currency($row['price'] ?? 0),
            esc($row['warranty_years'] ?? '-'),
            $actions
        );
    }

    private function _build_specs($type, $payload)
    {
        $specs = array();
        $specs_data = $payload['specs'] ?? array();
        if (is_array($specs_data)) {
            foreach ($specs_data as $key => $value) {
            $specs[$key] = is_numeric($value) ? $this->_parse_decimal($value) : trim((string)$value);
        }
        }

        if ($type === 'service' || $type === 'other') {
            $specs['description'] = trim((string)($payload['specs_description'] ?? ''));
        }

        return array_filter($specs, function ($value) {
            return $value !== '' && $value !== null;
        });
    }

    private function _validate_specs($type, $specs)
    {
        $errors = array();

        if ($type === 'module') {
            $pmpp = $specs['pmpp'] ?? 0;
            if ($pmpp <= 0) {
                $errors[] = 'Pmpp deve ser maior que 0';
            }
            $keys = array('voc', 'vmpp', 'isc', 'impp');
            foreach ($keys as $key) {
                if (isset($specs[$key]) && $specs[$key] < 0) {
                    $errors[] = strtoupper($key) . ' inválido';
                }
            }
        }

        if ($type === 'inverter') {
            $vdc = $specs['vdc_max'] ?? 0;
            $mppt_min = $specs['mppt_min'] ?? 0;
            $mppt_max = $specs['mppt_max'] ?? 0;
            $mppt_count = $specs['mppt_count'] ?? 0;
            if ($vdc <= 0) {
                $errors[] = 'Vdc máx deve ser maior que 0';
            }
            if ($mppt_min && $mppt_max && $mppt_min >= $mppt_max) {
                $errors[] = 'MPPT min deve ser menor que MPPT max';
            }
            if ($mppt_count < 1) {
                $errors[] = 'Nº MPPT deve ser >= 1';
            }
        }

        return $errors;
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

    private function _parse_int($value)
    {
        $value = trim((string)$value);
        return $value === '' ? null : (int)$value;
    }

    private function _get_brand_dropdown()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_products');
        if (!$db->tableExists($table)) {
            return array('' => '-');
        }

        $rows = $db->table($table)
            ->select('brand')
            ->groupBy('brand')
            ->orderBy('brand', 'ASC')
            ->get()
            ->getResult();

        $options = array('' => '-');
        foreach ($rows as $row) {
            if ($row->brand) {
                $options[$row->brand] = $row->brand;
            }
        }

        return $options;
    }

    private function _get_type_dropdown()
    {
        return array(
            'module' => 'module',
            'inverter' => 'inverter',
            'service' => 'service',
            'structure' => 'structure',
            'stringbox' => 'stringbox',
            'cable' => 'cable',
            'other' => 'other'
        );
    }

    private function _check_manage_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }
        $permissions = $this->login_user->permissions ?? array();
        if (get_array_value($permissions, 'fv_products_manage') == '1') {
            return true;
        }

        app_redirect('forbidden');
    }
}
