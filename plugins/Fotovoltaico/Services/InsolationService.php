<?php

namespace Fotovoltaico\Services;

use Fotovoltaico\Models\Insolation_cache_model;
use Fotovoltaico\Models\Integration_logs_model;
use Fotovoltaico\Services\Providers\NasaProvider;
use Fotovoltaico\Services\Providers\PvgisProvider;

class InsolationService
{
    private $cache_model;
    private $logs_model;
    private $pvgis_provider;
    private $nasa_provider;
    private $cache_ttl_seconds = 2592000;

    public function __construct()
    {
        $this->cache_model = model(Insolation_cache_model::class);
        $this->logs_model = model(Integration_logs_model::class);
        $this->pvgis_provider = new PvgisProvider();
        $this->nasa_provider = new NasaProvider();
    }

    public function get_data($latitude, $longitude, $options = array())
    {
        $latitude = $this->_normalize_coordinate($latitude);
        $longitude = $this->_normalize_coordinate($longitude);
        if ($latitude === null || $longitude === null) {
            return array(
                'success' => false,
                'message' => 'Invalid coordinates',
            );
        }

        $manual_value = get_array_value($options, 'manual_value');
        $cache_key = $this->_build_cache_key($latitude, $longitude);

        $cached = $this->_get_cached_payload($cache_key);
        if ($cached) {
            $payload = $this->_prepare_payload_from_cache($cached, $manual_value, true);
            $this->_register_log(array(
                'provider' => $cached->provider,
                'endpoint' => 'cache:' . $cache_key,
                'method' => 'GET',
                'request_json' => json_encode(array('latitude' => $latitude, 'longitude' => $longitude), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'http_status' => 200,
                'cache_hit' => 1,
                'success' => 1,
                'created_by' => (int) get_array_value($options, 'created_by'),
            ));

            return $payload;
        }

        $attempts = array(
            $this->pvgis_provider,
            $this->nasa_provider,
        );

        $last_error = '';
        foreach ($attempts as $provider) {
            $result = $provider->get_annual_insolation($latitude, $longitude);
            if (get_array_value($result, 'success')) {
                $payload = $this->_build_result_payload($result, $latitude, $longitude, $manual_value, false);
                $this->_store_cache($cache_key, $payload, get_array_value($result, 'provider'), $latitude, $longitude, get_array_value($options, 'created_by'));
                $this->_register_log(array(
                    'provider' => get_array_value($result, 'provider'),
                    'endpoint' => get_array_value($result, 'endpoint'),
                    'method' => 'GET',
                    'request_json' => json_encode(array('latitude' => $latitude, 'longitude' => $longitude), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'response_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'http_status' => (int) get_array_value($result, 'http_status'),
                    'cache_hit' => 0,
                    'success' => 1,
                    'created_by' => (int) get_array_value($options, 'created_by'),
                ));

                return $payload;
            }

            $last_error = get_array_value($result, 'error_message') ?: 'Unknown error';
            $this->_register_log(array(
                'provider' => get_array_value($result, 'provider') ?: get_class($provider),
                'endpoint' => get_array_value($result, 'endpoint'),
                'method' => 'GET',
                'request_json' => json_encode(array('latitude' => $latitude, 'longitude' => $longitude), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response_json' => '',
                'http_status' => (int) get_array_value($result, 'http_status'),
                'cache_hit' => 0,
                'success' => 0,
                'error_message' => $last_error,
                'created_by' => (int) get_array_value($options, 'created_by'),
            ));
        }

        return array(
            'success' => false,
            'message' => $last_error ?: 'Unable to retrieve insolation data',
        );
    }

    private function _build_result_payload($result, $latitude, $longitude, $manual_value = null, $cache_hit = false)
    {
        $annual = (float) get_array_value($result, 'annual_insolation');
        $monthly = get_array_value($result, 'monthly_insolation') ?: array();
        $adjusted = $annual;
        $manual_applied = false;
        if ($manual_value !== null && $manual_value !== '' && is_numeric($manual_value)) {
            $adjusted = (float) $manual_value;
            $manual_applied = true;
        }

        return array(
            'success' => true,
            'cache_hit' => $cache_hit ? 1 : 0,
            'provider' => get_array_value($result, 'provider'),
            'endpoint' => get_array_value($result, 'endpoint'),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'annual_insolation' => round($annual, 4),
            'adjusted_annual_insolation' => round($adjusted, 4),
            'manual_override_applied' => $manual_applied ? 1 : 0,
            'monthly_insolation' => $monthly,
            'raw' => array(
                'payload' => get_array_value($result, 'payload'),
            ),
        );
    }

    private function _prepare_payload_from_cache($cache_row, $manual_value = null, $cache_hit = true)
    {
        $payload = $this->_decode_json($cache_row->payload_json ?? '');
        if (!is_array($payload)) {
            $payload = array();
        }

        $annual = (float) get_array_value($payload, 'annual_insolation');
        $adjusted = $annual;
        $manual_applied = false;
        if ($manual_value !== null && $manual_value !== '' && is_numeric($manual_value)) {
            $adjusted = (float) $manual_value;
            $manual_applied = true;
        }

        $payload['success'] = true;
        $payload['cache_hit'] = $cache_hit ? 1 : 0;
        $payload['adjusted_annual_insolation'] = round($adjusted, 4);
        $payload['manual_override_applied'] = $manual_applied ? 1 : 0;
        return $payload;
    }

    private function _store_cache($cache_key, $payload, $provider, $latitude, $longitude, $created_by = 0)
    {
        $data = array(
            'cache_key' => $cache_key,
            'provider' => $provider,
            'location_label' => $latitude . ',' . $longitude,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->cache_ttl_seconds),
            'fetched_at' => get_my_local_time(),
            'created_by' => (int) $created_by,
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
            'deleted' => 0,
        );

        return $this->cache_model->ci_save($data);
    }

    private function _get_cached_payload($cache_key)
    {
        $row = $this->cache_model->get_cache_by_key($cache_key);
        if (!$row || !$row->id) {
            return null;
        }

        $expires_at = trim((string) ($row->expires_at ?? ''));
        if ($expires_at !== '' && strtotime($expires_at) < time()) {
            return null;
        }

        return $row;
    }

    private function _register_log($data)
    {
        return $this->logs_model->register_log(array(
            'provider' => get_array_value($data, 'provider'),
            'endpoint' => get_array_value($data, 'endpoint'),
            'method' => get_array_value($data, 'method') ?: 'GET',
            'request_json' => get_array_value($data, 'request_json'),
            'response_json' => get_array_value($data, 'response_json'),
            'http_status' => get_array_value($data, 'http_status'),
            'cache_hit' => get_array_value($data, 'cache_hit') ? 1 : 0,
            'success' => get_array_value($data, 'success') ? 1 : 0,
            'error_message' => get_array_value($data, 'error_message'),
            'created_by' => get_array_value($data, 'created_by'),
        ));
    }

    private function _normalize_coordinate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(',', '.', (string) $value);
        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 6);
    }

    private function _build_cache_key($latitude, $longitude)
    {
        return 'insolation:' . number_format((float) $latitude, 4, '.', '') . ':' . number_format((float) $longitude, 4, '.', '');
    }

    private function _decode_json($json_text)
    {
        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return array();
        }

        $decoded = json_decode($json_text, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : array();
    }
}
