<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\App_Controller;
use Fotovoltaico\Services\CECImportService;

/**
 * Endpoints seguros para cron de integrações.
 */
class Fv_cron extends App_Controller
{
    public function cec_sync()
    {
        $settings = $this->_get_settings();
        $token = $this->request->getGet('token');

        if (!$token || empty($settings['cron_token']) || !hash_equals($settings['cron_token'], $token)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Token inválido.']);
        }
        if (empty($settings['enabled'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Integração desativada.']);
        }

        if ($this->_has_recent_running_log('cec', 30)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sincronização em andamento.']);
        }

        $log_id = $this->_create_log('cec', null);
        $service = new CECImportService($settings, $log_id, null);

        try {
            $summary = $service->runSync($settings['mode'] ?? 'insert');
            $this->_finish_log($log_id, 'success', $summary, null);
            return $this->response->setJSON(['success' => true, 'message' => 'OK', 'data' => $summary]);
        } catch (\Throwable $e) {
            $this->_finish_log($log_id, 'failed', [], $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function _get_settings()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_settings');

        $defaults = [
            'enabled' => 0,
            'cec_modules_url' => 'https://www.energy.ca.gov/sites/default/files/2021-10/PV_Module_List_Full_Data_ADA.xlsx',
            'cec_inverters_url' => 'https://www.energy.ca.gov/sites/default/files/2021-10/Grid_Support_Inverter_List_Full_Data_ADA.xlsm',
            'mode' => 'insert',
            'update_prices' => 0,
            'zero_prices' => 0,
            'deactivate_removed' => 0,
            'allow_external_url' => 0,
            'cron_token' => ''
        ];

        if (!$db->tableExists($table)) {
            return $defaults;
        }

        $row = $db->table($table)->where('provider', 'cec')->get()->getRow();
        if (!$row) {
            return $defaults;
        }

        $settings = $row->settings_json ? json_decode($row->settings_json, true) : [];
        if (!is_array($settings)) {
            $settings = [];
        }

        return array_merge($defaults, $settings);
    }

    private function _create_log($provider, $created_by = null)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_logs');
        $run_id = uniqid('cec_', true);
        $db->table($table)->insert([
            'provider' => $provider,
            'run_id' => $run_id,
            'started_at' => date('Y-m-d H:i:s'),
            'status' => 'running',
            'created_by' => $created_by,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $db->insertID();
    }

    private function _finish_log($log_id, $status, $summary, $error)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_logs');
        $db->table($table)->where('id', $log_id)->update([
            'status' => $status,
            'finished_at' => date('Y-m-d H:i:s'),
            'summary_json' => $summary ? json_encode($summary, JSON_UNESCAPED_UNICODE) : null,
            'error_message' => $error
        ]);
    }

    private function _has_recent_running_log($provider, $minutes)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_logs');
        if (!$db->tableExists($table)) {
            return false;
        }
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $row = $db->table($table)
            ->where('provider', $provider)
            ->where('status', 'running')
            ->where('started_at >=', $since)
            ->get()
            ->getRow();
        return (bool)$row;
    }
}
