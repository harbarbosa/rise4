<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;

/**
 * Controller de tarifas por distribuidora.
 */
class Tariffs extends Security_Controller
{
    /** @var \Fotovoltaico\Models\Fv_tariffs_model */
    private $tariffs_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->tariffs_model = model('Fotovoltaico\\Models\\Fv_tariffs_model');
    }

    /**
     * Tela de tarifas por distribuidora.
     */
    public function index($utility_id = 0)
    {
        $utility_id = (int)$utility_id;
        return $this->template->rander('Fotovoltaico\\Views\\tariffs\\index', array('utility_id' => $utility_id));
    }

    /**
     * Lista tarifas em JSON.
     */
    public function list_data($utility_id = 0)
    {
        $utility_id = (int)$utility_id;
        $db = db_connect('default');
        $table = $db->prefixTable('fv_tariffs');
        $rows = $db->table($table)
            ->select('id,utility_id,group_type,modality,te_value,tusd_value,flags_value,valid_from,valid_to')
            ->where('utility_id', $utility_id)
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
        $tariff = $id ? $this->tariffs_model->get_one($id) : null;

        return $this->template->view('Fotovoltaico\\Views\\tariffs\\modal_form', array(
            'tariff' => $tariff,
            'utility_id' => (int)$this->request->getPost('utility_id')
        ));
    }

    /**
     * Salva tarifa.
     */
    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'utility_id' => 'required|numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $other_raw = trim((string)$this->request->getPost('other'));
        $other = $this->_normalize_json($other_raw);

        $data = array(
            'utility_id' => (int)$this->request->getPost('utility_id'),
            'group_type' => trim((string)$this->request->getPost('group_type')) ?: 'B',
            'modality' => trim((string)$this->request->getPost('modality')),
            'te_value' => $this->_parse_decimal($this->request->getPost('te_value')),
            'tusd_value' => $this->_parse_decimal($this->request->getPost('tusd_value')),
            'flags_value' => $this->_parse_decimal($this->request->getPost('flags_value')),
            'other' => $other,
            'valid_from' => $this->request->getPost('valid_from'),
            'valid_to' => $this->request->getPost('valid_to')
        );

        if (array_key_exists('te', $this->request->getPost())) {
            $data['te'] = $this->_parse_decimal($this->request->getPost('te'));
        }
        if (array_key_exists('tusd', $this->request->getPost())) {
            $data['tusd'] = $this->_parse_decimal($this->request->getPost('tusd'));
        }

        $save_id = $this->tariffs_model->ci_save($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $row_id = $id ? $id : $save_id;
        $row = $this->tariffs_model->get_one($row_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_row($row),
            'id' => $row_id,
            'message' => app_lang('record_saved')
        ));
    }

    /**
     * Exclui tarifa.
     */
    public function delete()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));
        $id = (int)$this->request->getPost('id');

        $db = db_connect('default');
        $table = $db->prefixTable('fv_tariffs');
        $deleted = $db->table($table)->delete(array('id' => $id));

        return $this->response->setJSON(array('success' => $deleted ? true : false, 'message' => app_lang('record_deleted')));
    }

    /**
     * API: tarifas por distribuidora (vigentes).
     */
    public function api_by_utility($utility_id = 0)
    {
        $utility_id = (int)$utility_id;
        $db = db_connect('default');
        $table = $db->prefixTable('fv_tariffs');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(['success' => true, 'data' => []]);
        }

        $date = $this->request->getGet('date');
        $today = $date ? $date : date('Y-m-d');
        $rows = $db->table($table)
            ->where('utility_id', $utility_id)
            ->groupStart()
                ->where('valid_from <=', $today)
                ->orWhere('valid_from', null)
            ->groupEnd()
            ->groupStart()
                ->where('valid_to >=', $today)
                ->orWhere('valid_to', null)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $data = [];
        foreach ($rows as $row) {
            $total = (float)($row['te_value'] ?? 0) + (float)($row['tusd_value'] ?? 0) + (float)($row['flags_value'] ?? 0);
            $data[] = [
                'id' => $row['id'],
                'label' => ($row['modality'] ?: '-') . ' | ' . $row['group_type'] . ' | ' . number_format($total, 4, ',', '.'),
                'te_value' => (float)($row['te_value'] ?? 0),
                'tusd_value' => (float)($row['tusd_value'] ?? 0),
                'flags_value' => (float)($row['flags_value'] ?? 0),
                'total_value' => $total,
                'valid_from' => $row['valid_from'],
                'valid_to' => $row['valid_to']
            ];
        }

        return $this->response->setJSON(['success' => true, 'data' => $data]);
    }

    /**
     * Monta linha de tarifa.
     */
    private function _make_row($row)
    {
        $actions = modal_anchor(get_uri('fotovoltaico/tariffs_modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $row->id,
            'data-post-utility_id' => $row->utility_id ?? 0,
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            'title' => app_lang('delete'),
            'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $row->id,
            'data-action-url' => get_uri('fotovoltaico/tariffs_delete'),
            'data-action' => 'delete-confirmation'
        ));

        $total = (float)($row->te_value ?? 0) + (float)($row->tusd_value ?? 0) + (float)($row->flags_value ?? 0);

        return array(
            esc($row->group_type),
            esc($row->modality ?? '-'),
            to_currency($row->te_value ?? 0),
            to_currency($row->tusd_value ?? 0),
            to_currency($row->flags_value ?? 0),
            to_currency($total),
            esc($row->valid_from ?? '-'),
            esc($row->valid_to ?? '-'),
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
