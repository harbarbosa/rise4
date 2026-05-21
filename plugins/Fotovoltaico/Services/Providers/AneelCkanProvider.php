<?php

namespace Fotovoltaico\Services\Providers;

use Config\Services;

class AneelCkanProvider extends AbstractSupplierProvider
{
    private $tariffs_package = 'tarifas-distribuidoras-energia-eletrica';
    private $flags_package = 'bandeiras-tarifarias';

    public function getKey()
    {
        return 'aneel_ckan';
    }

    public function getLabel()
    {
        return 'ANEEL Dados Abertos';
    }

    public function authenticate($config = array())
    {
        $config = $this->sanitize_config($config);
        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'message' => 'Configuration accepted',
            'http_status' => 200,
            'config' => $config,
        );
    }

    public function testConnection($config = array())
    {
        return $this->getApiStatus($config);
    }

    public function consultProducts($query = array(), $config = array())
    {
        return $this->_unsupported('Products endpoint not supported by ANEEL official data', $query);
    }

    public function consultKits($query = array(), $config = array())
    {
        return $this->_unsupported('Kits endpoint not supported by ANEEL official data', $query);
    }

    public function consultFreight($query = array(), $config = array())
    {
        return $this->_unsupported('Freight endpoint not supported by ANEEL official data', $query);
    }

    public function getQuote($query = array(), $config = array())
    {
        return $this->_unsupported('Quote projection is not provided directly by ANEEL CKAN', $query);
    }

    public function getApiStatus($config = array())
    {
        return $this->_package_show($this->tariffs_package, $config);
    }

    public function getCurrentFlag($config = array())
    {
        $csv = $this->_download_resource_rows($this->flags_package, 'acionamento', $config);
        if (!get_array_value($csv, 'success')) {
            return $csv;
        }

        $rows = get_array_value($csv, 'payload');
        usort($rows, function ($a, $b) {
            return strcmp((string) get_array_value($b, 'DatCompetencia'), (string) get_array_value($a, 'DatCompetencia'));
        });

        $row = count($rows) ? $rows[0] : array();
        return array(
            'success' => !empty($row),
            'provider' => $this->getKey(),
            'url' => get_array_value($csv, 'url'),
            'method' => 'GET',
            'http_status' => get_array_value($csv, 'http_status'),
            'message' => !empty($row) ? 'OK' : 'No official flag data found',
            'payload' => array(
                'flag_name' => get_array_value($row, 'NomBandeiraAcionada'),
                'flag_value' => get_array_value($row, 'VlrAdicionalBandeira'),
                'reference_date' => get_array_value($row, 'DatCompetencia'),
                'source' => 'aneel',
                'raw_payload' => $row,
            ),
            'request_payload' => array(),
            'latency_ms' => get_array_value($csv, 'latency_ms'),
        );
    }

    public function getDistributorsCache($config = array())
    {
        return $this->getUniqueDistributors($config);
    }

    public function getUniqueDistributors($config = array())
    {
        $started_at = microtime(true);
        $package = $this->_package_show($this->tariffs_package, $config);
        if (!get_array_value($package, 'success')) {
            return $package;
        }

        $resource = $this->_find_resource(get_array_value(get_array_value($package, 'payload'), 'result'), '.csv');
        if (!$resource) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'url' => get_array_value($package, 'url'),
                'method' => 'GET',
                'http_status' => 404,
                'message' => 'ANEEL resource not found for distributors CSV',
                'payload' => array(),
                'request_payload' => array(),
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        }

        $download = $this->_download_to_temp_file((string) get_array_value($resource, 'url'), $config);
        if (!get_array_value($download, 'success')) {
            return $download;
        }

        $rows = $this->_extract_unique_distributors_from_csv_file((string) get_array_value($download, 'file_path'));
        @unlink((string) get_array_value($download, 'file_path'));

        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'url' => get_array_value($resource, 'url'),
            'method' => 'GET',
            'http_status' => 200,
            'message' => 'OK',
            'payload' => $rows,
            'request_payload' => array(),
            'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
        );
    }

    public function downloadTariffsCsvToTempFile($config = array())
    {
        return $this->_download_package_resource_to_temp_file($this->tariffs_package, '.csv', $config);
    }

    public function downloadFlagsCsvToTempFile($config = array())
    {
        return $this->_download_package_resource_to_temp_file($this->flags_package, 'acionamento', $config);
    }

    public function getDistributorSlugs($config = array())
    {
        $result = $this->getUniqueDistributors($config);
        if (!get_array_value($result, 'success')) {
            return $result;
        }

        $rows = get_array_value($result, 'payload');
        $slugs = array();
        foreach ($rows as $row) {
            $name = trim((string) (get_array_value($row, 'SigAgente') ?: get_array_value($row, 'name')));
            if ($name === '') {
                continue;
            }
            $slugs[$this->_slugify($name)] = true;
        }

        $result['payload'] = array_keys($slugs);
        return $result;
    }

    public function getSelectableDistributors($config = array())
    {
        return $this->getUniqueDistributors($config);
    }

    public function searchDistributorsByName($name, $config = array())
    {
        $result = $this->getUniqueDistributors($config);
        if (!get_array_value($result, 'success')) {
            return $result;
        }

        $term = mb_strtolower(trim((string) $name));
        $rows = array_values(array_filter(get_array_value($result, 'payload'), function ($row) use ($term) {
            $label = (string) (get_array_value($row, 'SigAgente') ?: get_array_value($row, 'name'));
            return $term === '' || mb_stripos($label, $term) !== false;
        }));
        $result['payload'] = $rows;
        return $result;
    }

    public function getDistributorsByUf($uf, $config = array())
    {
        $result = $this->getUniqueDistributors($config);
        if (!get_array_value($result, 'success')) {
            return $result;
        }

        $uf = strtoupper(trim((string) $uf));
        $rows = array_values(array_filter(get_array_value($result, 'payload'), function ($row) use ($uf) {
            $row_uf = strtoupper(trim((string) (get_array_value($row, 'SigUF') ?: get_array_value($row, 'UF') ?: get_array_value($row, 'uf') ?: '')));
            return $uf !== '' && $row_uf === $uf;
        }));
        $result['payload'] = $rows;
        return $result;
    }

    public function getCostProjection($consumo_kwh, $slug, $config = array())
    {
        return $this->_unsupported('Direct projection endpoint is not provided by ANEEL CKAN', array(
            'consumo_kwh' => $consumo_kwh,
            'distribuidora_slug' => $slug,
        ));
    }

    public function reloadExternalCache($config = array())
    {
        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'url' => '',
            'method' => 'CACHE',
            'http_status' => 200,
            'message' => 'No remote cache reload required for ANEEL CKAN source',
            'payload' => array(),
            'request_payload' => array(),
            'latency_ms' => 0,
        );
    }

    private function _unsupported($message, $query = array())
    {
        return array(
            'success' => false,
            'provider' => $this->getKey(),
            'message' => $message,
            'http_status' => 400,
            'payload' => array(),
            'request_payload' => $query,
            'latency_ms' => 0,
        );
    }

    private function _package_show($package_name, $config = array())
    {
        $base_url = $this->_get_base_url($config);
        $url = $base_url . '/api/3/action/package_show?id=' . rawurlencode($package_name);

        return $this->_http_json_request($url, $config);
    }

    private function _download_resource_rows($package_name, $resource_hint, $config = array())
    {
        $started_at = microtime(true);
        $package = $this->_package_show($package_name, $config);
        if (!get_array_value($package, 'success')) {
            return $package;
        }

        $resource = $this->_find_resource(get_array_value(get_array_value($package, 'payload'), 'result'), $resource_hint);
        if (!$resource) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'url' => get_array_value($package, 'url'),
                'method' => 'GET',
                'http_status' => 404,
                'message' => 'ANEEL resource not found for ' . $resource_hint,
                'payload' => array(),
                'request_payload' => array(),
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        }

        $csv_result = $this->_http_raw_request((string) get_array_value($resource, 'url'), $config);
        if (!get_array_value($csv_result, 'success')) {
            return $csv_result;
        }

        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'url' => get_array_value($resource, 'url'),
            'method' => 'GET',
            'http_status' => get_array_value($csv_result, 'http_status'),
            'message' => 'OK',
            'payload' => $this->_parse_csv(get_array_value($csv_result, 'response_raw')),
            'request_payload' => array(),
            'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
        );
    }

    private function _download_package_resource_to_temp_file($package_name, $resource_hint, $config = array())
    {
        $started_at = microtime(true);
        $package = $this->_package_show($package_name, $config);
        if (!get_array_value($package, 'success')) {
            return $package;
        }

        $resource = $this->_find_resource(get_array_value(get_array_value($package, 'payload'), 'result'), $resource_hint);
        if (!$resource) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'url' => get_array_value($package, 'url'),
                'method' => 'GET',
                'http_status' => 404,
                'message' => 'ANEEL resource not found for ' . $resource_hint,
                'payload' => array(),
                'request_payload' => array(),
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        }

        return $this->_download_to_temp_file((string) get_array_value($resource, 'url'), $config);
    }

    private function _find_resource($package_result, $resource_hint)
    {
        $resources = get_array_value($package_result, 'resources');
        if (!is_array($resources)) {
            return null;
        }

        foreach ($resources as $resource) {
            $format = strtoupper(trim((string) get_array_value($resource, 'format')));
            $name = strtolower(trim((string) get_array_value($resource, 'name')));
            $url = strtolower(trim((string) get_array_value($resource, 'url')));
            $hint = strtolower(trim((string) $resource_hint));

            if ($hint === '.csv' && $format === 'CSV') {
                return $resource;
            }

            if (($format === 'CSV' || str_ends_with($url, '.csv')) && (strpos($name, $hint) !== false || strpos($url, $hint) !== false)) {
                return $resource;
            }
        }

        return null;
    }

    private function _http_json_request($url, $config = array())
    {
        $response = $this->_http_raw_request($url, $config);
        if (!get_array_value($response, 'success')) {
            return $response;
        }

        $payload = json_decode((string) get_array_value($response, 'response_raw'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'url' => $url,
                'method' => 'GET',
                'http_status' => get_array_value($response, 'http_status'),
                'message' => 'Invalid JSON response from ANEEL CKAN',
                'payload' => array(),
                'request_payload' => array(),
                'latency_ms' => get_array_value($response, 'latency_ms'),
            );
        }

        return array(
            'success' => (bool) get_array_value($payload, 'success'),
            'provider' => $this->getKey(),
            'url' => $url,
            'method' => 'GET',
            'http_status' => get_array_value($response, 'http_status'),
            'message' => (bool) get_array_value($payload, 'success') ? 'OK' : 'ANEEL CKAN request failed',
            'payload' => $payload,
            'request_payload' => array(),
            'latency_ms' => get_array_value($response, 'latency_ms'),
        );
    }

    private function _http_raw_request($url, $config = array())
    {
        $timeout = (int) get_array_value($config, 'external_api_timeout');
        if ($timeout <= 0) {
            $timeout = 30;
        }

        $started_at = microtime(true);
        try {
            $client = Services::curlrequest(array(
                'timeout' => $timeout,
                'http_errors' => false,
            ));

            $response = $client->request('GET', $url, array(
                'headers' => array(
                    'Accept' => '*/*',
                ),
            ));

            $status_code = (int) $response->getStatusCode();
            $body = (string) $response->getBody();
            $reason = trim((string) $response->getReasonPhrase());
            if ($reason === '') {
                $reason = $status_code >= 200 && $status_code < 400 ? 'OK' : 'ANEEL request failed';
            }

            return array(
                'success' => $status_code >= 200 && $status_code < 400,
                'provider' => $this->getKey(),
                'url' => $url,
                'method' => 'GET',
                'http_status' => $status_code,
                'message' => $reason,
                'response_raw' => $body,
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        } catch (\Throwable $e) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'url' => $url,
                'method' => 'GET',
                'http_status' => 0,
                'message' => $e->getMessage(),
                'response_raw' => '',
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        }
    }

    private function _download_to_temp_file($url, $config = array())
    {
        $timeout = (int) get_array_value($config, 'external_api_timeout');
        if ($timeout <= 0) {
            $timeout = 30;
        }

        $started_at = microtime(true);
        $temp_file = tempnam(sys_get_temp_dir(), 'aneel_csv_');

        try {
            $client = Services::curlrequest(array(
                'timeout' => $timeout,
                'http_errors' => false,
            ));

            $response = $client->request('GET', $url, array(
                'headers' => array(
                    'Accept' => '*/*',
                ),
                'sink' => $temp_file,
            ));

            $status_code = (int) $response->getStatusCode();
            if ($status_code < 200 || $status_code >= 400) {
                @unlink($temp_file);
                return array(
                    'success' => false,
                    'provider' => $this->getKey(),
                    'url' => $url,
                    'method' => 'GET',
                    'http_status' => $status_code,
                    'message' => trim((string) $response->getReasonPhrase()) ?: 'ANEEL request failed',
                    'payload' => array(),
                    'request_payload' => array(),
                    'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
                );
            }

            clearstatcache(true, $temp_file);
            if (!is_file($temp_file) || filesize($temp_file) <= 0) {
                return $this->_download_to_temp_file_native($url, $timeout, $started_at, $temp_file);
            }

            return array(
                'success' => true,
                'provider' => $this->getKey(),
                'url' => $url,
                'method' => 'GET',
                'http_status' => $status_code,
                'message' => 'OK',
                'file_path' => $temp_file,
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        } catch (\Throwable $e) {
            return $this->_download_to_temp_file_native($url, $timeout, $started_at, $temp_file, $e->getMessage());
        }
    }

    private function _download_to_temp_file_native($url, $timeout, $started_at, $temp_file, $previous_error = '')
    {
        if (function_exists('curl_init')) {
            $fp = fopen($temp_file, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min($timeout, 15));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: */*'));
            curl_exec($ch);
            $curl_error = curl_error($ch);
            $status_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);

            clearstatcache(true, $temp_file);
            if ($status_code >= 200 && $status_code < 400 && is_file($temp_file) && filesize($temp_file) > 0) {
                return array(
                    'success' => true,
                    'provider' => $this->getKey(),
                    'url' => $url,
                    'method' => 'GET',
                    'http_status' => $status_code,
                    'message' => 'OK',
                    'file_path' => $temp_file,
                    'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
                );
            }

            @unlink($temp_file);
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'url' => $url,
                'method' => 'GET',
                'http_status' => $status_code,
                'message' => $curl_error ?: ($previous_error ?: 'ANEEL request failed'),
                'payload' => array(),
                'request_payload' => array(),
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        }

        @unlink($temp_file);
        return array(
            'success' => false,
            'provider' => $this->getKey(),
            'url' => $url,
            'method' => 'GET',
            'http_status' => 0,
            'message' => $previous_error ?: 'Unable to download ANEEL CSV',
            'payload' => array(),
            'request_payload' => array(),
            'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
        );
    }

    private function _parse_csv($content)
    {
        $content = trim((string) $content);
        if ($content === '') {
            return array();
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $headers = fgetcsv($stream, 0, ';');
        if (!is_array($headers)) {
            fclose($stream);
            return array();
        }

        $headers = array_map(function ($value) {
            $value = trim((string) $value);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            return $value;
        }, $headers);

        $rows = array();
        while (($row = fgetcsv($stream, 0, ';')) !== false) {
            if (!is_array($row) || !count(array_filter($row, function ($value) {
                return trim((string) $value) !== '';
            }))) {
                continue;
            }

            $item = array();
            foreach ($headers as $index => $header) {
                $item[$header] = array_key_exists($index, $row) ? trim((string) $row[$index], "\" \t\n\r\0\x0B") : '';
            }
            $rows[] = $item;
        }

        fclose($stream);
        return $rows;
    }

    private function _extract_unique_distributors_from_csv_file($file_path = '')
    {
        $file_path = trim((string) $file_path);
        if ($file_path === '' || !is_file($file_path)) {
            return array();
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array();
        }

        $headers = fgetcsv($handle, 0, ';');
        if (!is_array($headers)) {
            fclose($handle);
            return array();
        }

        $headers = array_map(function ($value) {
            $value = trim((string) $value);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            return $value;
        }, $headers);

        $unique = array();
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (!is_array($row)) {
                continue;
            }

            $item = array();
            foreach ($headers as $index => $header) {
                if (in_array($header, array('SigAgente', 'NumCNPJDistribuidora', 'DatInicioVigencia', 'DatFimVigencia'), true)) {
                    $item[$header] = array_key_exists($index, $row) ? trim((string) $row[$index], "\" \t\n\r\0\x0B") : '';
                }
            }

            $name = trim((string) get_array_value($item, 'SigAgente'));
            if ($name === '') {
                continue;
            }

            $key = $this->_slugify($name);
            if ($key === '') {
                continue;
            }

            if (!isset($unique[$key])) {
                $unique[$key] = array(
                    'SigAgente' => $name,
                    'external_slug' => $key,
                    'NumCNPJDistribuidora' => get_array_value($item, 'NumCNPJDistribuidora'),
                    'DatInicioVigencia' => get_array_value($item, 'DatInicioVigencia'),
                    'DatFimVigencia' => get_array_value($item, 'DatFimVigencia'),
                    'source' => 'aneel',
                );
            }
        }

        fclose($handle);
        return array_values($unique);
    }

    private function _slugify($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($normalized === false) {
            $normalized = $text;
        }

        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized);
        return trim((string) $normalized, '-');
    }

    private function _get_base_url($config = array())
    {
        $base_url = rtrim((string) get_array_value($config, 'external_api_base_url'), '/');
        if ($base_url === '') {
            $base_url = 'https://dadosabertos.aneel.gov.br';
        }

        return $base_url;
    }
}
