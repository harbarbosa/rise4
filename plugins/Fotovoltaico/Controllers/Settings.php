<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;

/**
 * Controller de configurações do plugin Fotovoltaico.
 */
class Settings extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_admin();
    }

    /**
     * Tela de configurações.
     */
    public function index()
    {
        $settings = $this->_get_electrical_settings();
        return $this->template->rander('Fotovoltaico\\Views\\settings\\index', [
            'electrical_settings' => $settings
        ]);
    }

    /**
     * Salva configurações básicas.
     */
    public function save()
    {
        $data = [
            'temp_min_c' => $this->request->getPost('temp_min_c') ?? 5,
            'temp_max_c' => $this->request->getPost('temp_max_c') ?? 70,
            'safety_margin_vdc_percent' => $this->request->getPost('safety_margin_vdc_percent') ?? 2,
            'safety_margin_current_percent' => $this->request->getPost('safety_margin_current_percent') ?? 0,
            'assume_voc_temp_coeff_if_missing' => $this->request->getPost('assume_voc_temp_coeff_if_missing') ?? -0.28,
            'assume_vmpp_ratio' => $this->request->getPost('assume_vmpp_ratio') ?? 0.83
        ];

        $this->_save_electrical_settings($data);
        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved')
        ));
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

    private function _save_electrical_settings($settings)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_settings');
        if (!$db->tableExists($table)) {
            return false;
        }

        $payload = [
            'provider' => 'electrical',
            'settings_json' => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'updated_by' => $this->login_user->id,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $exists = $db->table($table)->where('provider', 'electrical')->get()->getRow();
        if ($exists) {
            $db->table($table)->where('id', $exists->id)->update($payload);
        } else {
            $db->table($table)->insert($payload);
        }
        return true;
    }
}
