<?php

namespace Fotovoltaico\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\AneelImporterService;

class PhotovoltaicImportAneelTariffs extends BaseCommand
{
    protected $group = 'Photovoltaic';
    protected $name = 'photovoltaic:import-aneel-tariffs';
    protected $description = 'Importa distribuidoras e tarifas da ANEEL para o plugin fotovoltaico.';
    protected $usage = 'photovoltaic:import-aneel-tariffs [--file path] [--url url] [--batch-size 250] [--user-id 1]';
    protected $arguments = array();
    protected $options = array(
        '--file' => 'Caminho local do CSV da ANEEL.',
        '--url' => 'URL do CSV da ANEEL.',
        '--batch-size' => 'Tamanho do lote de transacao.',
        '--user-id' => 'Usuario responsavel pela importacao.',
    );

    public function run(array $params)
    {
        Plugin::ensureSchema();

        $service = new AneelImporterService();
        $file = CLI::getOption('file');
        $url = CLI::getOption('url');
        $batch_size = (int) (CLI::getOption('batch-size') ?: 250);
        $user_id = (int) (CLI::getOption('user-id') ?: 0);

        if ($file) {
            $result = $service->importFromFile($file, array(
                'batch_size' => $batch_size,
                'created_by' => $user_id,
            ));
        } elseif ($url) {
            $result = $service->importFromUrl($url, array(
                'batch_size' => $batch_size,
                'created_by' => $user_id,
            ));
        } else {
            $result = $service->importOfficial(array(
                'batch_size' => $batch_size,
                'created_by' => $user_id,
            ));
        }

        if (!get_array_value($result, 'success')) {
            CLI::error(get_array_value($result, 'message') ?: 'Import failed');
            foreach ((array) get_array_value($result, 'errors') as $error) {
                CLI::error(is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $error);
            }
            return EXIT_ERROR;
        }

        $data = (array) get_array_value($result, 'data');
        CLI::write('ANEEL import completed.', 'green');
        CLI::write('Rows read: ' . (int) get_array_value($data, 'rows_read'));
        CLI::write('Distributors created: ' . (int) get_array_value($data, 'created_distributors'));
        CLI::write('Distributors updated: ' . (int) get_array_value($data, 'updated_distributors'));
        CLI::write('Tariffs created: ' . (int) get_array_value($data, 'created_tariffs'));
        CLI::write('Tariffs updated: ' . (int) get_array_value($data, 'updated_tariffs'));
        CLI::write('Ignored rows: ' . (int) get_array_value($data, 'ignored_rows'));
        CLI::write('Errors: ' . (int) get_array_value($data, 'error_count'));

        foreach ((array) get_array_value($data, 'errors') as $error) {
            CLI::write('- ' . json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'yellow');
        }

        return EXIT_SUCCESS;
    }
}
