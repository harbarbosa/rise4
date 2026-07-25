<?php

namespace LicitaIA\Controllers;

use CodeIgniter\CLI\CLI;

class Alerts extends Licitaia_Base_Controller
{
    public function run_cron()
    {
        if (!is_cli()) {
            $this->ensureManageAccess();
        }

        $service = new \LicitaIA\Libraries\AlertsService();

        try {
            $result = $service->run();

            if (is_cli()) {
                CLI::write('LicitaIA alerts cron completed.', 'green');
                CLI::write('Processed opportunities: ' . (int) get_array_value($result, 'processed_opportunities', 0));
                CLI::write('Alerts sent: ' . (int) get_array_value($result, 'sent_alerts', 0));
                CLI::write('Alerts skipped: ' . (int) get_array_value($result, 'skipped_alerts', 0));
                foreach ((array) get_array_value($result, 'messages', array()) as $message) {
                    CLI::write((string) $message);
                }
                return;
            }

            return $this->response->setJSON($result);
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA] Alerts cron error: ' . $e->getMessage());

            $result = array(
                'success' => false,
                'message' => app_lang('licitaia_alerts_cron_failed'),
            );

            if (is_cli()) {
                CLI::error('LicitaIA alerts cron failed: ' . $e->getMessage());
                return;
            }

            return $this->response->setJSON($result);
        }
    }
}
