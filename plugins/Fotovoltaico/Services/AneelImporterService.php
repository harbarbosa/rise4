<?php

namespace Fotovoltaico\Services;

use Config\Services;
use Fotovoltaico\Plugin;
use Fotovoltaico\Models\Distributors_model;
use Fotovoltaico\Models\Import_logs_model;
use Fotovoltaico\Models\Integration_logs_model;
use Fotovoltaico\Models\Settings_model;
use Fotovoltaico\Models\Tariffs_model;
use Fotovoltaico\Services\Providers\AneelCkanProvider;

class AneelImporterService
{
    private $db;
    private $Distributors_model;
    private $Tariffs_model;
    private $Settings_model;
    private $Integration_logs_model;
    private $Import_logs_model;
    private $provider;

    public function __construct()
    {
        Plugin::ensureSchema();
        Plugin::ensureAneelImportSchema();
        $this->db = db_connect();
        $this->Distributors_model = model(Distributors_model::class);
        $this->Tariffs_model = model(Tariffs_model::class);
        $this->Settings_model = model(Settings_model::class);
        $this->Integration_logs_model = model(Integration_logs_model::class);
        $this->Import_logs_model = model(Import_logs_model::class);
        $this->provider = new AneelCkanProvider();
    }

    public function importOfficial($options = array())
    {
        Plugin::ensureSchema();
        Plugin::ensureAneelImportSchema();
        $config = $this->_get_integration_config();
        $created_by = (int) get_array_value($options, 'created_by');
        $distributors_only = (bool) get_array_value($options, 'distributors_only');
        $tariffs_file = null;
        $flags_file = null;

        try {
            if ($distributors_only) {
                $distributors_result = $this->provider->getUniqueDistributors($config);
                $this->_register_integration_log($distributors_result, 'aneel_distributors_download', $created_by);

                if (!get_array_value($distributors_result, 'success')) {
                    return $this->_result(false, get_array_value($distributors_result, 'message') ?: 'ANEEL distributor download failed', array(), array(get_array_value($distributors_result, 'message')));
                }

                return $this->_import_distributors_payload(get_array_value($distributors_result, 'payload'), array_merge($options, array(
                    'source_type' => 'official',
                    'source_path' => 'aneel:tarifas-distribuidoras-energia-eletrica',
                )));
            }

            $tariffs_download = $this->provider->downloadTariffsCsvToTempFile($config);
            $this->_register_integration_log($tariffs_download, 'aneel_tariffs_download', $created_by);
            if (!get_array_value($tariffs_download, 'success')) {
                return $this->_result(false, get_array_value($tariffs_download, 'message') ?: 'ANEEL tariff download failed', array(), array(get_array_value($tariffs_download, 'message')));
            }

            $tariffs_file = (string) get_array_value($tariffs_download, 'file_path');

            $flags_download = $this->provider->downloadFlagsCsvToTempFile($config);
            $this->_register_integration_log($flags_download, 'aneel_flags_download', $created_by);
            if (get_array_value($flags_download, 'success')) {
                $flags_file = (string) get_array_value($flags_download, 'file_path');
            }

            return $this->_import_csv_file($tariffs_file, array_merge($options, array(
                'source_type' => 'official',
                'source_path' => 'aneel:tarifas-distribuidoras-energia-eletrica',
                'flags_file' => $flags_file,
            )));
        } finally {
            if ($tariffs_file && is_file($tariffs_file)) {
                @unlink($tariffs_file);
            }
            if ($flags_file && is_file($flags_file)) {
                @unlink($flags_file);
            }
        }
    }

    private function _import_distributors_payload($rows, $options = array())
    {
        $created_by = (int) get_array_value($options, 'created_by');
        $started_at = get_current_utc_time();
        $summary = array(
            'rows_read' => 0,
            'created_distributors' => 0,
            'updated_distributors' => 0,
            'created_tariffs' => 0,
            'updated_tariffs' => 0,
            'ignored_rows' => 0,
            'error_count' => 0,
            'errors' => array(),
        );

        $distributor_index = $this->_load_distributor_index();
        $this->db->transBegin();

        try {
            foreach ((array) $rows as $row) {
                $summary['rows_read']++;

                try {
                    $mapped = $this->_map_distributor_row($row);
                    if (!$mapped || !get_array_value($mapped, 'title')) {
                        $summary['ignored_rows']++;
                        continue;
                    }

                    $this->_upsert_distributor($mapped, $distributor_index, $summary);
                } catch (\Throwable $e) {
                    $summary['error_count']++;
                    if (count($summary['errors']) < 50) {
                        $summary['errors'][] = array(
                            'line' => $summary['rows_read'],
                            'message' => $e->getMessage(),
                        );
                    }
                }
            }

            $this->_flush_batch_transaction(false);
        } catch (\Throwable $e) {
            if ($this->db->transStatus()) {
                $this->db->transRollback();
            }
            $summary['error_count']++;
            $summary['errors'][] = array('line' => $summary['rows_read'], 'message' => $e->getMessage());
        }

        $summary['processed'] = $summary['rows_read'];
        $summary['created'] = $summary['created_distributors'];
        $summary['updated'] = $summary['updated_distributors'];

        $this->Import_logs_model->register_log(array(
            'import_type' => 'aneel_distributors',
            'source_type' => get_array_value($options, 'source_type') ?: 'official',
            'source_path' => get_array_value($options, 'source_path'),
            'status' => $summary['error_count'] ? 'completed_with_errors' : 'completed',
            'rows_read' => $summary['rows_read'],
            'created_count' => $summary['created'],
            'updated_count' => $summary['updated'],
            'ignored_count' => $summary['ignored_rows'],
            'error_count' => $summary['error_count'],
            'errors_json' => json_encode($summary['errors'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'summary_json' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'started_at' => $started_at,
            'finished_at' => get_current_utc_time(),
            'created_by' => $created_by,
        ));

        return $this->_result(true, 'ANEEL distributor sync completed', $summary, array());
    }

    public function importFromFile($file_path, $options = array())
    {
        Plugin::ensureSchema();
        Plugin::ensureAneelImportSchema();
        $file_path = trim((string) $file_path);
        if ($file_path === '' || !is_file($file_path)) {
            return $this->_result(false, 'CSV file not found', array(), array('CSV file not found'));
        }

        return $this->_import_csv_file($file_path, array_merge($options, array(
            'source_type' => 'file',
            'source_path' => $file_path,
        )));
    }

    public function importFromUrl($url, $options = array())
    {
        Plugin::ensureSchema();
        Plugin::ensureAneelImportSchema();
        $url = trim((string) $url);
        if ($url === '') {
            return $this->_result(false, 'URL is required', array(), array('URL is required'));
        }

        $download = $this->_download_to_temp_file($url);
        $this->_register_integration_log($download, 'aneel_custom_url_download', (int) get_array_value($options, 'created_by'));
        if (!get_array_value($download, 'success')) {
            return $this->_result(false, get_array_value($download, 'message') ?: 'CSV download failed', array(), array(get_array_value($download, 'message')));
        }

        $file_path = (string) get_array_value($download, 'file_path');
        try {
            return $this->_import_csv_file($file_path, array_merge($options, array(
                'source_type' => 'url',
                'source_path' => $url,
            )));
        } finally {
            if ($file_path && is_file($file_path)) {
                @unlink($file_path);
            }
        }
    }

    public function getAvailableDistributors($state_code = null)
    {
        return $this->Distributors_model->get_available_distributors($state_code);
    }

    public function getCurrentTariffsByDistributor($distributor_id)
    {
        return $this->Tariffs_model->get_current_tariffs_by_distributor((int) $distributor_id);
    }

    public function getTariffHistoryByDistributor($distributor_id)
    {
        return $this->Tariffs_model->get_tariff_history_by_distributor((int) $distributor_id);
    }

    public function syncDisplayFlags()
    {
        $this->Tariffs_model->sync_current_flags();
        return $this->Distributors_model->sync_display_flags();
    }

    public function findExistingDistributor($data = array())
    {
        return $this->Distributors_model->find_existing_distributor($data);
    }

    private function _import_csv_file($file_path, $options = array())
    {
        $created_by = (int) get_array_value($options, 'created_by');
        $batch_size = (int) get_array_value($options, 'batch_size');
        if ($batch_size <= 0) {
            $batch_size = 250;
        }

        $flags_file = trim((string) get_array_value($options, 'flags_file'));
        $current_flag = $flags_file && is_file($flags_file) ? $this->_extract_current_flag_from_csv($flags_file) : array();

        $summary = array(
            'rows_read' => 0,
            'created_distributors' => 0,
            'updated_distributors' => 0,
            'created_tariffs' => 0,
            'updated_tariffs' => 0,
            'ignored_rows' => 0,
            'error_count' => 0,
            'errors' => array(),
        );

        $started_at = get_current_utc_time();
        $distributor_index = $this->_load_distributor_index();
        $tariff_index = $this->_load_tariff_index();
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return $this->_result(false, 'Unable to open CSV file', array(), array('Unable to open CSV file'));
        }

        $stats = @fstat($handle);
        if (!$stats || (int) get_array_value($stats, 'size') <= 0) {
            fclose($handle);
            return $this->_result(false, 'Downloaded CSV is empty', array(), array('Downloaded CSV is empty'));
        }

        $delimiter = $this->_detect_delimiter($handle);
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!is_array($headers)) {
            fclose($handle);
            return $this->_result(false, 'CSV header not found', array(), array('CSV header not found', 'File size: ' . (int) get_array_value($stats, 'size')));
        }

        $headers = array_map(array($this, '_normalize_header'), $headers);
        $line_number = 1;
        $batch_count = 0;

        $this->db->transBegin();

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line_number++;
                if (!$this->_row_has_content($row)) {
                    continue;
                }

                $summary['rows_read']++;
                try {
                    $data = $this->_map_tariff_row($headers, $row);
                    if (!$data || !get_array_value($data, 'title')) {
                        $summary['ignored_rows']++;
                        continue;
                    }

                    $distributor_id = $this->_upsert_distributor($data, $distributor_index, $summary);
                    $this->_upsert_tariff($distributor_id, $data, $current_flag, $tariff_index, $summary);
                } catch (\Throwable $e) {
                    $summary['error_count']++;
                    if (count($summary['errors']) < 50) {
                        $summary['errors'][] = array(
                            'line' => $line_number,
                            'message' => $e->getMessage(),
                        );
                    }
                }

                $batch_count++;
                if ($batch_count >= $batch_size) {
                    $this->_flush_batch_transaction();
                    $batch_count = 0;
                }
            }

            fclose($handle);
            $this->_flush_batch_transaction(false);
        } catch (\Throwable $e) {
            fclose($handle);
            if ($this->db->transStatus()) {
                $this->db->transRollback();
            }
            $summary['error_count']++;
            $summary['errors'][] = array('line' => $line_number, 'message' => $e->getMessage());
        }

        $this->Tariffs_model->sync_current_flags();
        $this->Distributors_model->sync_display_flags();

        $finished_at = get_current_utc_time();
        $summary['processed'] = $summary['rows_read'];
        $summary['created'] = $summary['created_distributors'] + $summary['created_tariffs'];
        $summary['updated'] = $summary['updated_distributors'] + $summary['updated_tariffs'];

        $this->Import_logs_model->register_log(array(
            'import_type' => 'aneel_tariffs',
            'source_type' => get_array_value($options, 'source_type') ?: 'official',
            'source_path' => get_array_value($options, 'source_path'),
            'status' => $summary['error_count'] ? 'completed_with_errors' : 'completed',
            'rows_read' => $summary['rows_read'],
            'created_count' => $summary['created'],
            'updated_count' => $summary['updated'],
            'ignored_count' => $summary['ignored_rows'],
            'error_count' => $summary['error_count'],
            'errors_json' => json_encode($summary['errors'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'summary_json' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'started_at' => $started_at,
            'finished_at' => $finished_at,
            'created_by' => $created_by,
        ));

        return $this->_result(true, 'ANEEL import completed', $summary, array());
    }

    private function _upsert_distributor($data, &$index, &$summary)
    {
        $existing = $this->_find_existing_distributor_in_index($index, $data);

        $payload = array(
            'title' => get_array_value($data, 'title'),
            'legal_name' => get_array_value($data, 'title'),
            'document' => get_array_value($data, 'document') ?: null,
            'aneel_code' => get_array_value($data, 'aneel_code') ?: null,
            'acronym' => get_array_value($data, 'acronym') ?: null,
            'state_code' => get_array_value($data, 'state_code') ?: null,
            'external_slug' => get_array_value($data, 'external_slug'),
            'agent_type' => get_array_value($data, 'agent_type') ?: 'desconhecido',
            'source' => 'aneel',
            'is_synced' => 1,
            'raw_payload' => json_encode(get_array_value($data, 'raw_payload'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'origin_hash' => get_array_value($data, 'distributor_origin_hash'),
            'sync_notes' => get_array_value($data, 'sync_notes'),
            'updated_at' => get_my_local_time(),
        );

        $table = $this->db->prefixTable('fv_distributors');
        if ($existing && $existing->id) {
            $source = trim((string) ($existing->source ?? ''));
            if (!in_array($source, array('manual', 'local'), true)) {
                $payload['active'] = 1;
                $payload['show_in_registration'] = 0;
                $payload['notes'] = ($existing->notes ?? null);
                $this->db->table($table)->where('id', (int) $existing->id)->update($payload);
                $summary['updated_distributors']++;
            } else {
                $manual_payload = array(
                    'document' => $payload['document'] ?: ($existing->document ?? null),
                    'aneel_code' => $payload['aneel_code'] ?: ($existing->aneel_code ?? null),
                    'external_slug' => $payload['external_slug'] ?: ($existing->external_slug ?? null),
                    'agent_type' => ($existing->agent_type ?? '') !== '' ? $existing->agent_type : $payload['agent_type'],
                    'origin_hash' => $payload['origin_hash'],
                    'raw_payload' => $payload['raw_payload'],
                    'sync_notes' => $payload['sync_notes'],
                    'is_synced' => 1,
                    'updated_at' => get_my_local_time(),
                );
                $this->db->table($table)->where('id', (int) $existing->id)->update($manual_payload);
                $summary['updated_distributors']++;
                $payload = array_merge((array) $existing, $manual_payload);
            }

            $this->_update_distributor_index($index, (int) $existing->id, array_merge((array) $existing, $payload));
            return (int) $existing->id;
        }

        $payload['active'] = 1;
        $payload['show_in_registration'] = 0;
        $payload['created_at'] = get_my_local_time();
        $payload['deleted'] = 0;
        $payload['notes'] = null;
        $this->db->table($table)->insert($payload);
        $insert_id = (int) $this->db->insertID();
        $this->_update_distributor_index($index, $insert_id, $payload);
        $summary['created_distributors']++;

        return $insert_id;
    }

    private function _upsert_tariff($distributor_id, $data, $current_flag, &$tariff_index, &$summary)
    {
        $fingerprint = $this->_build_tariff_fingerprint(array(
            'distributor_id' => $distributor_id,
            'valid_from' => get_array_value($data, 'valid_from'),
            'valid_to' => get_array_value($data, 'valid_to'),
            'tariff_class' => get_array_value($data, 'tariff_class'),
            'tariff_subclass' => get_array_value($data, 'tariff_subclass'),
            'group_name' => get_array_value($data, 'group_name'),
            'subgroup' => get_array_value($data, 'subgroup'),
            'modality' => get_array_value($data, 'modality'),
            'time_slot' => get_array_value($data, 'time_slot'),
            'unit' => get_array_value($data, 'unit'),
            'resolution' => get_array_value($data, 'resolution'),
            'tariff_base' => get_array_value($data, 'tariff_base'),
        ));

        $payload = array(
            'distributor_id' => $distributor_id,
            'modality' => get_array_value($data, 'modality'),
            'subgroup' => get_array_value($data, 'subgroup'),
            'tariff_class' => get_array_value($data, 'tariff_class'),
            'tariff_subclass' => get_array_value($data, 'tariff_subclass'),
            'group_name' => get_array_value($data, 'group_name'),
            'time_slot' => get_array_value($data, 'time_slot'),
            'unit' => get_array_value($data, 'unit'),
            'resolution' => get_array_value($data, 'resolution'),
            'tariff_detail' => get_array_value($data, 'tariff_detail'),
            'tariff_base' => get_array_value($data, 'tariff_base'),
            'te' => (float) get_array_value($data, 'te'),
            'tusd' => (float) get_array_value($data, 'tusd'),
            'flag_name' => get_array_value($current_flag, 'flag_name') ?: null,
            'flag_value' => (float) get_array_value($current_flag, 'flag_value'),
            'source' => 'aneel',
            'origin_hash' => get_array_value($data, 'tariff_origin_hash'),
            'valid_from' => get_array_value($data, 'valid_from'),
            'valid_to' => get_array_value($data, 'valid_to'),
            'sync_notes' => get_array_value($data, 'sync_notes'),
            'notes' => null,
            'active' => 1,
            'is_current' => 0,
            'updated_at' => get_my_local_time(),
        );

        $table = $this->db->prefixTable('fv_tariffs');
        $existing = get_array_value($tariff_index, $fingerprint);
        if ($existing) {
            if (in_array((string) get_array_value($existing, 'source'), array('manual', 'local'), true)) {
                $summary['ignored_rows']++;
                return;
            }

            $this->db->table($table)->where('id', (int) get_array_value($existing, 'id'))->update($payload);
            $summary['updated_tariffs']++;
            $tariff_index[$fingerprint] = array_merge($existing, $payload);
            return;
        }

        $payload['created_at'] = get_my_local_time();
        $payload['deleted'] = 0;
        $this->db->table($table)->insert($payload);
        $id = (int) $this->db->insertID();
        $payload['id'] = $id;
        $tariff_index[$fingerprint] = $payload;
        $summary['created_tariffs']++;
    }

    private function _map_tariff_row($headers, $row)
    {
        $map = array();
        foreach ($headers as $index => $header) {
            $map[$header] = array_key_exists($index, $row) ? $this->_normalize_text(trim((string) $row[$index], "\" \t\n\r\0\x0B")) : '';
        }

        $title = trim((string) $this->_pick($map, array('sigagente', 'nome', 'distribuidora')));
        if ($title === '') {
            return array();
        }

        $document = preg_replace('/\D+/', '', (string) $this->_pick($map, array('numcnpjdistribuidora', 'cnpj')));
        $subgroup = trim((string) $this->_pick($map, array('dscsubgrupo', 'subgrupo')));
        $group_name = $subgroup !== '' ? strtoupper(substr($subgroup, 0, 1)) : '';
        $valid_from = $this->_normalize_date($this->_pick($map, array('datiniciovigencia', 'vigencia_inicial')));
        $valid_to = $this->_normalize_date($this->_pick($map, array('datfimvigencia', 'vigencia_final')));
        $resolution = trim((string) $this->_pick($map, array('dscreh', 'resolucao')));

        $payload = array(
            'title' => $title,
            'document' => $document,
            'aneel_code' => trim((string) $this->_pick($map, array('codaneel', 'codigoaneel'))),
            'acronym' => $this->_build_acronym($title),
            'state_code' => strtoupper(trim((string) $this->_pick($map, array('siguf', 'uf')))),
            'external_slug' => $this->_slugify($title),
            'agent_type' => $this->_detect_agent_type($title),
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
            'tariff_class' => trim((string) $this->_pick($map, array('dscclasse', 'classe'))),
            'tariff_subclass' => trim((string) $this->_pick($map, array('dscsubclasse', 'subclasse'))),
            'group_name' => $group_name,
            'subgroup' => $subgroup,
            'modality' => trim((string) $this->_pick($map, array('dscmodalidadetarifaria', 'modalidadetarifaria', 'modalidade'))),
            'time_slot' => trim((string) $this->_pick($map, array('nompostotarifario', 'postotarifario', 'posto'))),
            'unit' => trim((string) $this->_pick($map, array('dscunidadeterciaria', 'unidade'))),
            'resolution' => $resolution,
            'tariff_detail' => trim((string) $this->_pick($map, array('dscdetalhe', 'detalhe'))),
            'tariff_base' => trim((string) $this->_pick($map, array('dscbasetarifaria', 'basetarifaria'))),
            'te' => $this->_parse_decimal($this->_pick($map, array('vlrte', 'te'))),
            'tusd' => $this->_parse_decimal($this->_pick($map, array('vlrtusd', 'tusd'))),
        );

        $payload['raw_payload'] = $map;
        $payload['distributor_origin_hash'] = hash('sha256', json_encode(array(
            'title' => $payload['title'],
            'document' => $payload['document'],
            'aneel_code' => $payload['aneel_code'],
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $payload['tariff_origin_hash'] = hash('sha256', json_encode(array(
            'title' => $payload['title'],
            'document' => $payload['document'],
            'valid_from' => $payload['valid_from'],
            'valid_to' => $payload['valid_to'],
            'subgroup' => $payload['subgroup'],
            'modality' => $payload['modality'],
            'time_slot' => $payload['time_slot'],
            'resolution' => $payload['resolution'],
            'tariff_base' => $payload['tariff_base'],
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $payload['sync_notes'] = 'Importado da ANEEL em ' . date('Y-m-d H:i:s');

        return $payload;
    }

    private function _map_distributor_row($row)
    {
        $title = trim((string) (get_array_value($row, 'SigAgente') ?: get_array_value($row, 'name') ?: ''));
        if ($title === '') {
            return array();
        }

        $document = preg_replace('/\D+/', '', (string) (get_array_value($row, 'NumCNPJDistribuidora') ?: get_array_value($row, 'document') ?: ''));
        $payload = array(
            'title' => $title,
            'document' => $document,
            'aneel_code' => trim((string) get_array_value($row, 'CodAneel')),
            'acronym' => $this->_build_acronym($title),
            'state_code' => strtoupper(trim((string) (get_array_value($row, 'SigUF') ?: get_array_value($row, 'UF') ?: ''))),
            'external_slug' => trim((string) (get_array_value($row, 'external_slug') ?: $this->_slugify($title))),
            'agent_type' => $this->_detect_agent_type($title),
            'raw_payload' => $row,
            'distributor_origin_hash' => hash('sha256', json_encode(array(
                'title' => $title,
                'document' => $document,
                'aneel_code' => trim((string) get_array_value($row, 'CodAneel')),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'sync_notes' => 'Distribuidora importada da ANEEL em ' . date('Y-m-d H:i:s'),
        );

        return $payload;
    }

    private function _extract_current_flag_from_csv($file_path)
    {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array();
        }

        $delimiter = $this->_detect_delimiter($handle);
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!is_array($headers)) {
            fclose($handle);
            return array();
        }

        $headers = array_map(array($this, '_normalize_header'), $headers);
        $latest = array();
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!$this->_row_has_content($row)) {
                continue;
            }

            $item = array();
            foreach ($headers as $index => $header) {
                $item[$header] = array_key_exists($index, $row) ? $this->_normalize_text(trim((string) $row[$index], "\" \t\n\r\0\x0B")) : '';
            }

            $competence = $this->_normalize_date(get_array_value($item, 'datcompetencia'));
            if ($competence === '') {
                continue;
            }

            if (!$latest || $competence >= get_array_value($latest, 'reference_date')) {
                $latest = array(
                    'reference_date' => $competence,
                    'flag_name' => trim((string) get_array_value($item, 'nombandeiraacionada')),
                    'flag_value' => $this->_parse_decimal(get_array_value($item, 'vlradicionalbandeira')),
                );
            }
        }

        fclose($handle);
        return $latest;
    }

    private function _load_distributor_index()
    {
        $table = $this->db->prefixTable('fv_distributors');
        if (!$this->db->tableExists($table)) {
            return array('by_aneel' => array(), 'by_document' => array(), 'by_acronym' => array(), 'by_name' => array(), 'rows' => array());
        }

        $rows = $this->db->table($table)->where('deleted', 0)->get()->getResultArray();
        $index = array(
            'by_aneel' => array(),
            'by_document' => array(),
            'by_acronym' => array(),
            'by_name' => array(),
            'rows' => array(),
        );

        foreach ($rows as $row) {
            $this->_update_distributor_index($index, (int) $row['id'], $row);
        }

        return $index;
    }

    private function _load_tariff_index()
    {
        $table = $this->db->prefixTable('fv_tariffs');
        if (!$this->db->tableExists($table)) {
            return array();
        }

        $rows = $this->db->table($table)->where('deleted', 0)->get()->getResultArray();
        $index = array();
        foreach ($rows as $row) {
            $fingerprint = $this->_build_tariff_fingerprint($row);
            if ($fingerprint !== '') {
                $index[$fingerprint] = $row;
            }
        }

        return $index;
    }

    private function _build_tariff_fingerprint($data)
    {
        $parts = array(
            (int) get_array_value($data, 'distributor_id'),
            trim((string) get_array_value($data, 'valid_from')),
            trim((string) get_array_value($data, 'valid_to')),
            trim((string) get_array_value($data, 'tariff_class')),
            trim((string) get_array_value($data, 'tariff_subclass')),
            trim((string) get_array_value($data, 'group_name')),
            trim((string) get_array_value($data, 'subgroup')),
            trim((string) get_array_value($data, 'modality')),
            trim((string) get_array_value($data, 'time_slot')),
            trim((string) get_array_value($data, 'unit')),
            trim((string) get_array_value($data, 'resolution')),
            trim((string) get_array_value($data, 'tariff_base')),
        );

        return implode('|', $parts);
    }

    private function _flush_batch_transaction($continue = true)
    {
        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            throw new \RuntimeException('Database transaction failed during ANEEL import.');
        }

        $this->db->transCommit();
        if ($continue) {
            $this->db->transBegin();
        }
    }

    private function _download_to_temp_file($url)
    {
        $config = $this->_get_integration_config();
        $timeout = (int) get_array_value($config, 'external_api_timeout');
        if ($timeout <= 0) {
            $timeout = 60;
        }

        $temp_file = tempnam(sys_get_temp_dir(), 'aneel_import_');
        $started_at = microtime(true);

        try {
            $client = Services::curlrequest(array(
                'timeout' => $timeout,
                'http_errors' => false,
            ));
            $response = $client->request('GET', $url, array('sink' => $temp_file));
            $status_code = (int) $response->getStatusCode();

            if ($status_code < 200 || $status_code >= 400) {
                @unlink($temp_file);
                return array(
                    'success' => false,
                    'provider' => 'manual_url',
                    'url' => $url,
                    'method' => 'GET',
                    'http_status' => $status_code,
                    'message' => trim((string) $response->getReasonPhrase()) ?: 'Download failed',
                    'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
                );
            }

            return array(
                'success' => true,
                'provider' => 'manual_url',
                'url' => $url,
                'method' => 'GET',
                'http_status' => $status_code,
                'message' => 'OK',
                'file_path' => $temp_file,
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        } catch (\Throwable $e) {
            @unlink($temp_file);
            return array(
                'success' => false,
                'provider' => 'manual_url',
                'url' => $url,
                'method' => 'GET',
                'http_status' => 0,
                'message' => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        }
    }

    private function _register_integration_log($payload, $endpoint, $created_by)
    {
        $this->Integration_logs_model->register_log(array(
            'provider' => trim((string) get_array_value($payload, 'provider')),
            'endpoint' => $endpoint,
            'method' => get_array_value($payload, 'method') ?: 'GET',
            'request_json' => json_encode(array('url' => get_array_value($payload, 'url')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_json' => json_encode(array(
                'message' => get_array_value($payload, 'message'),
                'http_status' => get_array_value($payload, 'http_status'),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'http_status' => (int) get_array_value($payload, 'http_status'),
            'latency_ms' => (int) get_array_value($payload, 'latency_ms'),
            'cache_hit' => 0,
            'success' => get_array_value($payload, 'success') ? 1 : 0,
            'error_message' => get_array_value($payload, 'success') ? null : get_array_value($payload, 'message'),
            'created_by' => $created_by,
        ));
    }

    private function _get_integration_config()
    {
        $raw = $this->Settings_model->get_setting('energy_tariff_api_config_json');
        $config = json_decode((string) $raw, true);
        if (!is_array($config)) {
            $config = array();
        }

        if (!isset($config['external_api_timeout'])) {
            $config['external_api_timeout'] = 60;
        }
        if (!isset($config['external_api_base_url'])) {
            $config['external_api_base_url'] = 'https://dadosabertos.aneel.gov.br';
        }

        return $config;
    }

    private function _detect_delimiter($handle)
    {
        $position = ftell($handle);
        $line = fgets($handle);
        fseek($handle, $position);
        if ($line === false) {
            return ';';
        }

        $semicolon_count = substr_count($line, ';');
        $comma_count = substr_count($line, ',');
        return $semicolon_count >= $comma_count ? ';' : ',';
    }

    private function _normalize_header($header)
    {
        $header = $this->_normalize_text($header);
        $header = function_exists('mb_strtolower') ? mb_strtolower($header, 'UTF-8') : strtolower($header);
        return preg_replace('/[^a-z0-9]+/', '', $header);
    }

    private function _normalize_text($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return trim((string) @mb_convert_encoding($value, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1'));
    }

    private function _normalize_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime(str_replace('/', '-', $value));
        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    private function _parse_decimal($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }

    private function _pick($map, $keys = array())
    {
        foreach ($keys as $key) {
            $value = get_array_value($map, $key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function _slugify($value)
    {
        $value = $this->_normalize_text($value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false && $ascii !== '') {
            $value = $ascii;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');
        return $value !== '' ? $value : 'dist-' . substr(md5((string) microtime(true)), 0, 10);
    }

    private function _build_acronym($title)
    {
        $title = $this->_normalize_text($title);
        $words = preg_split('/\s+/', $title);
        $letters = '';
        foreach ((array) $words as $word) {
            $word = preg_replace('/[^A-Za-z0-9]/', '', (string) $word);
            if ($word === '') {
                continue;
            }
            $letters .= strtoupper(substr($word, 0, 1));
        }

        if ($letters === '') {
            $letters = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $title), 0, 20));
        }

        return substr($letters, 0, 20);
    }

    private function _detect_agent_type($title)
    {
        $normalized = strtoupper($this->_normalize_text($title));
        if (strpos($normalized, 'PERMISSION') !== false || strpos($normalized, 'COOPER') !== false) {
            return 'permissionaria';
        }
        if (strpos($normalized, 'DESIGNAD') !== false) {
            return 'designada';
        }
        if (strpos($normalized, 'CONCESS') !== false) {
            return 'concessionaria';
        }

        return 'desconhecido';
    }

    private function _row_has_content($row)
    {
        foreach ((array) $row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function _result($success, $message, $data = array(), $errors = array())
    {
        return array(
            'success' => (bool) $success,
            'message' => (string) $message,
            'data' => $data,
            'errors' => array_values(array_filter((array) $errors)),
        );
    }

    private function _find_existing_distributor_in_index($index, $data = array())
    {
        $aneel_code = trim((string) get_array_value($data, 'aneel_code'));
        if ($aneel_code !== '' && isset($index['by_aneel'][$aneel_code])) {
            return (object) get_array_value($index['rows'], $index['by_aneel'][$aneel_code]);
        }

        $document = preg_replace('/\D+/', '', (string) get_array_value($data, 'document'));
        if ($document !== '' && isset($index['by_document'][$document])) {
            return (object) get_array_value($index['rows'], $index['by_document'][$document]);
        }

        $acronym = $this->_normalize_lookup_key(get_array_value($data, 'acronym'));
        if ($acronym !== '' && isset($index['by_acronym'][$acronym])) {
            return (object) get_array_value($index['rows'], $index['by_acronym'][$acronym]);
        }

        $name = $this->_normalize_lookup_key(get_array_value($data, 'title'));
        if ($name !== '' && isset($index['by_name'][$name])) {
            return (object) get_array_value($index['rows'], $index['by_name'][$name]);
        }

        return null;
    }

    private function _update_distributor_index(&$index, $id, $row)
    {
        $id = (int) $id;
        $row['id'] = $id;
        $index['rows'][$id] = $row;

        $aneel_code = trim((string) get_array_value($row, 'aneel_code'));
        if ($aneel_code !== '') {
            $index['by_aneel'][$aneel_code] = $id;
        }

        $document = preg_replace('/\D+/', '', (string) get_array_value($row, 'document'));
        if ($document !== '') {
            $index['by_document'][$document] = $id;
        }

        $acronym = $this->_normalize_lookup_key(get_array_value($row, 'acronym'));
        if ($acronym !== '') {
            $index['by_acronym'][$acronym] = $id;
        }

        $name = $this->_normalize_lookup_key(get_array_value($row, 'title') ?: get_array_value($row, 'legal_name'));
        if ($name !== '') {
            $index['by_name'][$name] = $id;
        }
    }

    private function _normalize_lookup_key($value)
    {
        $value = $this->_normalize_text($value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false && $ascii !== '') {
            $value = $ascii;
        }

        $value = strtoupper($value);
        return preg_replace('/[^A-Z0-9]+/', '', $value);
    }
}
