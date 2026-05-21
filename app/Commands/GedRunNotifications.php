<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GedRunNotifications extends BaseCommand
{
    protected $group = 'GED';
    protected $name = 'ged:run-notifications';
    protected $description = 'Executa o scanner de notificacoes do GED.';
    protected $usage = 'ged:run-notifications';

    public function run(array $params)
    {
        try {
            $plugin_index = FCPATH . 'plugins/GED/index.php';
            if (is_file($plugin_index)) {
                require_once $plugin_index;
            }

            if (class_exists('\\GED\\Plugin')) {
                \GED\Plugin::runMigrations();
            }

            $service = new \GED\Libraries\GedNotificationService();
            $result = $service->run(array('source' => 'cli'));

            CLI::write('GED notification scan completed.', 'green');
            CLI::write('Documents processed: ' . (int) get_array_value($result, 'processed_documents'));
            CLI::write('Submissions processed: ' . (int) get_array_value($result, 'processed_submissions'));
            CLI::write('Notifications sent: ' . (int) get_array_value($result, 'sent_notifications'));
            CLI::write('Notifications skipped: ' . (int) get_array_value($result, 'skipped_notifications'));

            foreach ((array) get_array_value($result, 'messages') as $message) {
                CLI::write('- ' . $message, 'yellow');
            }

            return EXIT_SUCCESS;
        } catch (\Throwable $e) {
            CLI::error('GED notification scan failed: ' . $e->getMessage());
            return EXIT_ERROR;
        }
    }
}
