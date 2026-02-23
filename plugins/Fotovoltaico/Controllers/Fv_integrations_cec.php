<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Services\CECImportService;

/**
 * Controller de integrações CEC.
 */
class Fv_integrations_cec extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_admin();
    }

    public function index()
    {
        $settings = $this->_get_settings();
        $last_log = $this->_get_last_log();
        return $this->template->rander('Fotovoltaico\Views\integrations\cec\index', [
            'settings' => $settings,
            'last_log' => $last_log
        ]);
    }

    public function save()
    {
        $settings = $this->_get_settings();
        $data = [
            'enabled' => $this->request->getPost('enabled') ? 1 : 0,
            'cec_modules_url' => trim((string)$this->request->getPost('cec_modules_url')),
            'cec_inverters_url' => trim((string)$this->request->getPost('cec_inverters_url')),
            'mode' => $this->request->getPost('mode') ?: 'insert',
            'update_prices' => $this->request->getPost('update_prices') ? 1 : 0,
            'zero_prices' => $this->request->getPost('zero_prices') ? 1 : 0,
            'deactivate_removed' => $this->request->getPost('deactivate_removed') ? 1 : 0,
            'allow_external_url' => $this->request->getPost('allow_external_url') ? 1 : 0,
            'cron_token' => $settings['cron_token'] ?? bin2hex(random_bytes(16))
        ];

        $this->_save_settings($data);
        return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved'), 'data' => $data]);
    }

    public function test()
    {
        $settings = $this->_get_settings();
        $service = new CECImportService($settings);
        try {
            $modules = $service->testDownload($settings['cec_modules_url']);
            $inverters = $service->testDownload($settings['cec_inverters_url']);

            return $this->response->setJSON([
                'success' => true,
                'message' => app_lang('record_saved'),
                'data' => ['modules' => $modules, 'inverters' => $inverters]
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function run()
    {
        $settings = $this->_get_settings();
        if (empty($settings['enabled'])) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred')]);
        }

        $force = $this->request->getPost('force') ? true : false;

        $log_id = $this->_create_log('cec', $this->login_user->id);
        $service = new CECImportService($settings, $log_id, null);

        try {
            $summary = $service->runSync($settings['mode'] ?? 'insert', $force);
            $this->_finish_log($log_id, 'success', $summary, null);
            return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved'), 'data' => $summary]);
        } catch (\Throwable $e) {
            $this->_finish_log($log_id, 'failed', [], $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function logs()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_logs');
        $rows = $db->table($table)
            ->where('provider', 'cec')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        return $this->template->view('Fotovoltaico\Views\integrations\cec\logs', ['rows' => $rows]);
    }

    public function log_view($id = 0)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_logs');
        $row = $db->table($table)->where('id', (int)$id)->get()->getRow();
        if (!$row) {
            show_404();
        }

        $summary = $row->summary_json ? json_decode($row->summary_json, true) : null;
        return $this->template->view('Fotovoltaico\Views\integrations\cec\log_view', ['row' => $row, 'summary' => $summary]);
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
            'cron_token' => bin2hex(random_bytes(16))
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

    private function _save_settings($settings)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_settings');

        $payload = [
            'provider' => 'cec',
            'settings_json' => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'updated_by' => $this->login_user->id,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!$db->tableExists($table)) {
            return false;
        }

        $exists = $db->table($table)->where('provider', 'cec')->get()->getRow();
        if ($exists) {
            $db->table($table)->where('id', $exists->id)->update($payload);
        } else {
            $db->table($table)->insert($payload);
        }
        return true;
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

    private function _get_last_log()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_integrations_logs');
        if (!$db->tableExists($table)) {
            return null;
        }
        return $db->table($table)
            ->where('provider', 'cec')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();
    }
}
