<?php

namespace Fotovoltaico\Services;

use Config\Services;

/**
 * Serviço de importação CEC (módulos e inversores).
 */
class CECImportService
{
    private $db;
    private $settings;
    private $log_id;
    private $run_id;
    private $last_error_row;

    public function __construct($settings = array(), $log_id = null, $run_id = null)
    {
        $this->db = db_connect('default');
        $this->settings = $settings;
        $this->log_id = $log_id;
        $this->run_id = $run_id;
        $this->last_error_row = null;
    }

    public function testDownload($url)
    {
        $url = $this->sanitizeUrl($url);
        $client = Services::curlrequest();
        $response = $client->request('HEAD', $url, ['timeout' => 30]);
        $code = $response->getStatusCode();
        $contentType = $response->getHeaderLine('Content-Type');

        return [
            'success' => $code >= 200 && $code < 400,
            'status' => $code,
            'content_type' => $contentType
        ];
    }

    public function runSync($mode, $force = false)
    {
        $start = microtime(true);
        $summary = [
            'modules' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            'inverters' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            'warnings' => []
        ];

        $modules_url = $this->settings['cec_modules_url'] ?? '';
        $inverters_url = $this->settings['cec_inverters_url'] ?? '';

        if (!$modules_url || !$inverters_url) {
            throw new \RuntimeException('URLs CEC não configuradas.');
        }

        $modules_file = $this->downloadFile($modules_url, 'cec_modules');
        $inverters_file = $this->downloadFile($inverters_url, 'cec_inverters');

        $skip_modules = !$force && $this->isChecksumAlreadyImported('cec', 'modules', $modules_file['checksum']);
        $skip_inverters = !$force && $this->isChecksumAlreadyImported('cec', 'inverters', $inverters_file['checksum']);

        if ($skip_modules) {
            $summary['warnings'][] = 'Módulos: checksum igual ao último importado. Importação ignorada.';
            $modules_result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'checksum' => $modules_file['checksum']];
        } else {
            $modules_result = $this->importModules($modules_file['path'], $mode, $modules_file);
        }

        if ($skip_inverters) {
            $summary['warnings'][] = 'Inversores: checksum igual ao último importado. Importação ignorada.';
            $inverters_result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'checksum' => $inverters_file['checksum']];
        } else {
            $inverters_result = $this->importInverters($inverters_file['path'], $mode, $inverters_file);
        }

        $summary['modules'] = $modules_result;
        $summary['inverters'] = $inverters_result;
        $summary['files'] = [
            'modules_checksum' => $modules_file['checksum'],
            'inverters_checksum' => $inverters_file['checksum']
        ];
        $summary['duration_seconds'] = (int)round(microtime(true) - $start);

        return $summary;
    }

    public function importModules($filepath, $mode, $fileMeta = [])
    {
        $data = $this->parseFile($filepath);
        return $this->importRows($data, 'module', $mode, $fileMeta);
    }

    public function importInverters($filepath, $mode, $fileMeta = [])
    {
        $data = $this->parseFile($filepath);
        return $this->importRows($data, 'inverter', $mode, $fileMeta);
    }

    public function normalizeRow($row, $type, $headers = [])
    {
        $normalized = [
            'type' => $type,
            'brand' => '',
            'model' => '',
            'power_w' => null,
            'source_ref' => null,
            'specs' => []
        ];

        $brand_key = $this->matchColumn(
            ['manufacturer', 'brand', 'company', 'mfr', 'manufacturer_name'],
            $headers
        );
        $model_key = $this->matchColumn(
            ['model', 'model_number', 'model_name'],
            $headers
        );
        $power_key = $this->matchColumn(
            ['pmax_stc_w', 'pmax', 'max_power', 'rated_power', 'rated_ac_power', 'rated_ac_power_w', 'stc_power'],
            $headers
        );
        $source_ref_key = $this->matchColumn(
            ['cec_id', 'listing_id', 'id'],
            $headers
        );

        if ($brand_key && isset($row[$brand_key])) {
            $normalized['brand'] = $this->normalizeText($row[$brand_key]);
        }
        if ($model_key && isset($row[$model_key])) {
            $normalized['model'] = $this->normalizeText($row[$model_key]);
        }
        if ($power_key && isset($row[$power_key])) {
            $normalized['power_w'] = $this->toDecimal($row[$power_key]);
        }
        if ($source_ref_key && isset($row[$source_ref_key])) {
            $normalized['source_ref'] = $this->normalizeText($row[$source_ref_key]);
        }

        foreach ($row as $key => $value) {
            $value = $this->normalizeValue($value);
            if ($value === '') {
                continue;
            }
            $spec_key = $this->mapSpecs($key, $type);
            if ($spec_key) {
                $normalized['specs'][$spec_key] = $this->toDecimal($value, true);
            }
        }

        $normalized['brand'] = $this->normalizeText($normalized['brand']);
        $normalized['model'] = $this->normalizeText($normalized['model']);

        return $normalized;
    }

    public function upsertProduct($type, $brand, $model, $data, $mode)
    {
        $table = $this->db->prefixTable('fv_products');

        $query = $this->db->table($table)->select('*');
        if (!empty($data['source_ref'])) {
            $query->where('source', 'cec')->where('source_ref', $data['source_ref']);
        } else {
            $query->where('type', $type)->where('brand', $brand)->where('model', $model);
        }

        $existing = $query->get()->getRowArray();
        if ($existing) {
            if ($mode === 'insert') {
                return ['action' => 'skipped', 'id' => $existing['id']];
            }

            $update = $data;
            $update['last_synced_at'] = date('Y-m-d H:i:s');
            $update['external_hash'] = $data['external_hash'] ?? null;

            if (!empty($existing['external_hash']) && $existing['external_hash'] === ($data['external_hash'] ?? null)) {
                return ['action' => 'skipped', 'id' => $existing['id']];
            }

            unset($update['cost'], $update['price'], $update['sku'], $update['warranty_years']);

            if (!empty($existing['source']) && $existing['source'] === 'manual') {
                unset($update['source']);
            }

            $this->db->table($table)->where('id', $existing['id'])->update($update);
            return ['action' => 'updated', 'id' => $existing['id']];
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table($table)->insert($data);
        return ['action' => 'inserted', 'id' => $this->db->insertID()];
    }

    public function mapSpecs($key, $type)
    {
        $key = $this->normalizeHeader($key);

        $module_map = [
            'pmax_stc_w' => 'pmax_w',
            'pmax' => 'pmax_w',
            'max_power' => 'pmax_w',
            'rated_power' => 'pmax_w',
            'voc' => 'voc_v',
            'voc_v' => 'voc_v',
            'open_circuit_voltage' => 'voc_v',
            'isc' => 'isc_a',
            'isc_a' => 'isc_a',
            'short_circuit_current' => 'isc_a',
            'vmpp' => 'vmpp_v',
            'vmpp_v' => 'vmpp_v',
            'impp' => 'impp_a',
            'impp_a' => 'impp_a',
            'efficiency' => 'efficiency_percent',
            'efficiency_percent' => 'efficiency_percent',
            'temp_coeff_pmax' => 'temp_coeff_pmax',
            'temp_coeff_voc' => 'temp_coeff_voc',
            'technology' => 'technology',
            'bifacial' => 'bifacial',
            'dimensions' => 'dimensions_mm',
            'dimensions_mm' => 'dimensions_mm',
            'weight' => 'weight_kg',
            'weight_kg' => 'weight_kg'
        ];

        $inverter_map = [
            'ac_power' => 'ac_power_w',
            'rated_ac_power' => 'ac_power_w',
            'rated_ac_power_w' => 'ac_power_w',
            'dc_power_max' => 'dc_power_max_w',
            'dc_power_max_w' => 'dc_power_max_w',
            'vdc_max' => 'vdc_max_v',
            'vdc_max_v' => 'vdc_max_v',
            'mppt_min' => 'mppt_min_v',
            'mppt_min_v' => 'mppt_min_v',
            'mppt_max' => 'mppt_max_v',
            'mppt_max_v' => 'mppt_max_v',
            'mppt_count' => 'mppt_count',
            'strings_per_mppt' => 'strings_per_mppt',
            'max_current_mppt' => 'max_current_mppt_a',
            'max_current_mppt_a' => 'max_current_mppt_a',
            'efficiency' => 'efficiency_percent',
            'phases' => 'phases',
            'grid_type' => 'grid_type'
        ];

        $map = $type === 'module' ? $module_map : $inverter_map;
        return $map[$key] ?? null;
    }

    private function importRows($data, $type, $mode, $fileMeta = [])
    {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $rows = $data['rows'] ?? [];
        $headers = $data['headers'] ?? [];

        $refs = [];
        foreach ($rows as $row) {
            try {
                $norm = $this->normalizeRow($row, $type, $headers);
            } catch (\Throwable $e) {
                $errors++;
                $this->last_error_row = $row;
                continue;
            }
            if (!$norm['brand'] || !$norm['model']) {
                $errors++;
                continue;
            }

            $data = [
                'type' => $type,
                'brand' => $norm['brand'],
                'model' => $norm['model'],
                'power_w' => $norm['power_w'],
                'specs_json' => $norm['specs'] ? json_encode($norm['specs'], JSON_UNESCAPED_UNICODE) : null,
                'is_active' => 1,
                'source' => 'cec',
                'source_ref' => $norm['source_ref'],
                'last_synced_at' => date('Y-m-d H:i:s')
            ];

            $hash_payload = [
                'brand' => $norm['brand'],
                'model' => $norm['model'],
                'power_w' => $norm['power_w'],
                'specs' => $norm['specs']
            ];
            $data['external_hash'] = hash('sha256', json_encode($hash_payload));

            $result = $this->upsertProduct($type, $norm['brand'], $norm['model'], $data, $mode);
            if ($result['action'] === 'inserted') {
                $inserted++;
            } elseif ($result['action'] === 'updated') {
                $updated++;
            } else {
                $skipped++;
            }

            if (!empty($norm['source_ref'])) {
                $refs[] = $norm['source_ref'];
            }
        }

        if (!empty($this->settings['deactivate_removed']) && $refs) {
            $this->deactivateRemoved($type, $refs);
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'checksum' => $fileMeta['checksum'] ?? null,
            'error_preview' => $errors ? $this->last_error_row : null
        ];
    }

    private function deactivateRemoved($type, $refs)
    {
        $table = $this->db->prefixTable('fv_products');
        $this->db->table($table)
            ->where('source', 'cec')
            ->where('type', $type)
            ->whereNotIn('source_ref', $refs)
            ->set('is_active', 0)
            ->update();
    }

    private function parseFile($filepath)
    {
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            return $this->parseCsv($filepath);
        }
        if ($ext === 'xlsx' || $ext === 'xlsm') {
            if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                return $this->parseXlsx($filepath);
            }
            throw new \RuntimeException('XLSX não suportado. Instale PhpSpreadsheet ou use CSV.');
        }
        throw new \RuntimeException('Formato de arquivo inválido.');
    }

    private function parseCsv($filepath)
    {
        $rows = [];
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return ['headers' => [], 'rows' => []];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['headers' => [], 'rows' => []];
        }

        $delimiter = $this->detectDelimiter($header);
        if ($delimiter !== ',') {
            rewind($handle);
            $header = fgetcsv($handle, 0, $delimiter);
        }

        $header = array_map([$this, 'normalizeHeader'], $header);

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = [];
            foreach ($header as $i => $name) {
                $row[$name] = $line[$i] ?? '';
            }
            if ($this->isRowEmpty($row)) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);
        return ['headers' => $header, 'rows' => $rows];
    }

    private function parseXlsx($filepath)
    {
        $rows = [];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $sheet = $this->getFirstDataSheet($spreadsheet);
        if (!$sheet) {
            return ['headers' => [], 'rows' => []];
        }

        $data = $sheet->toArray(null, true, true, true);
        if (!$data) {
            return ['headers' => [], 'rows' => []];
        }

        $headerRow = array_shift($data);
        $headers = [];
        foreach ($headerRow as $key => $value) {
            $headers[$key] = $this->normalizeHeader($value);
        }

        foreach ($data as $line) {
            $row = [];
            foreach ($headers as $key => $name) {
                $row[$name] = $line[$key] ?? '';
            }
            if ($this->isRowEmpty($row)) {
                continue;
            }
            $rows[] = $row;
        }

        return ['headers' => array_values($headers), 'rows' => $rows];
    }

    private function downloadFile($url, $prefix)
    {
        $url = $this->sanitizeUrl($url);
        $client = Services::curlrequest();
        $response = $client->request('GET', $url, ['timeout' => 30]);
        $body = $response->getBody();
        $contentType = $response->getHeaderLine('Content-Type');

        if ($this->isHtmlResponse($contentType, $body)) {
            $htmlLink = $this->extractFirstSpreadsheetLink($body);
            if ($htmlLink) {
                $url = $this->sanitizeUrl($htmlLink);
                $response = $client->request('GET', $url, ['timeout' => 30]);
                $body = $response->getBody();
                $contentType = $response->getHeaderLine('Content-Type');
            }
        }

        $ext = 'csv';
        if (stripos($contentType, 'sheet') !== false || stripos($url, '.xlsx') !== false || stripos($url, '.xlsm') !== false) {
            $ext = 'xlsx';
        }

        $dir = WRITEPATH . 'uploads/fotovoltaico/integrations/cec/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = $prefix . '_' . date('Ymd') . '.' . $ext;
        $path = $dir . $filename;
        file_put_contents($path, $body);

        return [
            'path' => $path,
            'checksum' => hash_file('sha256', $path),
            'content_type' => $contentType
        ];
    }

    private function isChecksumAlreadyImported($provider, $type, $checksum)
    {
        if (!$checksum) {
            return false;
        }
        $table = $this->db->prefixTable('fv_integrations_logs');
        if (!$this->db->tableExists($table)) {
            return false;
        }
        $row = $this->db->table($table)
            ->where('provider', $provider)
            ->where('status', 'success')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();
        if (!$row || empty($row->summary_json)) {
            return false;
        }
        $summary = json_decode($row->summary_json, true);
        if (!is_array($summary) || empty($summary['files'][$type . '_checksum'])) {
            return false;
        }
        return $summary['files'][$type . '_checksum'] === $checksum;
    }

    private function normalizeHeader($value)
    {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/\s+/', '_', $value);
        $value = preg_replace('/[^a-z0-9_]/', '_', $value);
        return $value;
    }

    private function normalizeValue($value)
    {
        $value = trim((string)$value);
        if (function_exists('mb_detect_encoding')) {
            $enc = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($enc && $enc !== 'UTF-8') {
                $value = mb_convert_encoding($value, 'UTF-8', $enc);
            }
        }
        return $value;
    }

    private function normalizeText($value)
    {
        $value = preg_replace('/\s+/', ' ', trim((string)$value));
        return $value;
    }

    private function detectDelimiter($header)
    {
        $raw = implode(',', $header);
        $countComma = substr_count($raw, ',');
        $countSemi = substr_count($raw, ';');
        return $countSemi > $countComma ? ';' : ',';
    }

    private function toDecimal($value, $allowText = false)
    {
        if ($allowText && !is_numeric($value)) {
            return $value;
        }
        $text = preg_replace('/[^\d,\.\-]/', '', (string)$value);
        $lastComma = strrpos($text, ',');
        $lastDot = strrpos($text, '.');
        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($lastComma !== false) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            $text = str_replace(',', '', $text);
        }
        return $text === '' ? null : (float)$text;
    }

    private function sanitizeUrl($url)
    {
        $url = trim((string)$url);
        if (!$url || !preg_match('~^https?://~i', $url)) {
            throw new \RuntimeException('URL inválida');
        }
        if (stripos($url, 'file://') === 0) {
            throw new \RuntimeException('URL inválida');
        }

        $allow_external = !empty($this->settings['allow_external_url']);
        if (!$allow_external) {
            $host = parse_url($url, PHP_URL_HOST);
            if (!$host || !preg_match('/(^|\\.)energy\\.ca\\.gov$|(^|\\.)solarequipment\\.energy\\.ca\\.gov$/i', $host)) {
                throw new \RuntimeException('URL externa não permitida');
            }
        }
        return $url;
    }

    private function isRowEmpty($row)
    {
        foreach ($row as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function getFirstDataSheet($spreadsheet)
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $data = $sheet->toArray(null, true, true, true);
            if (!$data || count($data) < 2) {
                continue;
            }
            $header = array_shift($data);
            $headerText = implode(' ', array_map('strtolower', array_filter($header)));
            if (strpos($headerText, 'instruction') !== false || strpos($headerText, 'cover') !== false) {
                continue;
            }
            $nonEmpty = 0;
            foreach ($data as $row) {
                if (!$this->isRowEmpty($row)) {
                    $nonEmpty++;
                }
                if ($nonEmpty >= 2) {
                    return $sheet;
                }
            }
        }
        return $spreadsheet->getActiveSheet();
    }

    private function matchColumn($possibleNames, $normalizedHeaders)
    {
        foreach ($normalizedHeaders as $header) {
            if (in_array($header, $possibleNames, true)) {
                return $header;
            }
        }
        return null;
    }

    private function isHtmlResponse($contentType, $body)
    {
        if (stripos($contentType, 'text/html') !== false) {
            return true;
        }
        $snippet = strtolower(substr($body, 0, 200));
        return strpos($snippet, '<html') !== false;
    }

    private function extractFirstSpreadsheetLink($html)
    {
        if (preg_match('/https?:\\/\\/[^\\s"\']+\\.(xlsx|xlsm)/i', $html, $m)) {
            return $m[0];
        }
        if (preg_match('/href=["\']([^"\']+\\.(xlsx|xlsm))["\']/i', $html, $m)) {
            $url = $m[1];
            if (strpos($url, 'http') === 0) {
                return $url;
            }
        }
        return null;
    }
}
