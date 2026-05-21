<?php

namespace Fotovoltaico\Services;

use Fotovoltaico\Models\Integration_logs_model;
use Fotovoltaico\Models\Settings_model;
use Fotovoltaico\Services\Providers\GenericRestSupplierProvider;
use Fotovoltaico\Services\Providers\MockSupplierProvider;

class SupplierIntegrationService
{
    private $Settings_model;
    private $Integration_logs_model;
    private $providers = array();

    public function __construct()
    {
        $this->Settings_model = model(Settings_model::class);
        $this->Integration_logs_model = model(Integration_logs_model::class);
        $this->providers = array(
            'mock' => new MockSupplierProvider(),
            'generic_rest' => new GenericRestSupplierProvider(),
        );
    }

    public function get_provider_definitions()
    {
        $definitions = array();
        foreach ($this->providers as $provider) {
            $definitions[] = array(
                'key' => $provider->getKey(),
                'label' => $provider->getLabel(),
            );
        }

        return $definitions;
    }

    public function get_configuration()
    {
        $config = $this->Settings_model->get_setting('supplier_integrations_config_json');
        $config = $this->_decode_json($config);
        return $this->_normalize_configuration($config);
    }

    public function save_configuration($config = array())
    {
        $config = $this->_normalize_configuration($config);
        return $this->Settings_model->save_setting(
            'supplier_integrations_config_json',
            json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'app'
        );
    }

    public function test_connection($input = array())
    {
        $provider_key = $this->_get_provider_key($input);
        $config = $this->get_configuration();
        $provider = $this->get_provider($provider_key);
        $created_by = (int) get_array_value($input, 'created_by');

        if (!$provider) {
            return $this->_result_error('Provider not found', $provider_key, 404, array(), array(), false);
        }

        $result = $provider->testConnection($config);
        $this->_register_log(array(
            'provider' => $provider_key,
            'endpoint' => get_array_value($result, 'url') ?: 'test_connection',
            'method' => 'GET',
            'request_json' => $this->_safe_json(array('provider' => $provider_key, 'config' => $config)),
            'response_json' => $this->_safe_json($result),
            'http_status' => get_array_value($result, 'http_status'),
            'cache_hit' => 0,
            'success' => get_array_value($result, 'success'),
            'error_message' => get_array_value($result, 'message'),
            'created_by' => $created_by,
        ));

        return $result;
    }

    public function get_quote($input = array())
    {
        $provider_key = $this->_get_provider_key($input);
        $config = $this->get_configuration();
        $provider = $this->get_provider($provider_key);
        $created_by = (int) get_array_value($input, 'created_by');

        if (!$provider) {
            return $this->_result_error('Provider not found', $provider_key, 404, array(), array(), false);
        }

        $cache = service('cache');
        $cache_ttl = (int) get_array_value($config, 'cache_ttl_seconds');
        if ($cache_ttl <= 0) {
            $cache_ttl = 300;
        }

        $cache_key = $this->_quote_cache_key($provider_key, $input, $config);
        $cached = $cache->get($cache_key);
        if ($cached !== null && $cached !== false) {
            $result = is_string($cached) ? $this->_decode_json($cached) : $cached;
            if (!is_array($result)) {
                $result = array();
            }
            $result['cache_hit'] = true;
            $result['provider'] = $provider_key;
            $this->_register_log(array(
                'provider' => $provider_key,
                'endpoint' => 'cache:' . $cache_key,
                'method' => 'CACHE',
                'request_json' => $this->_safe_json($input),
                'response_json' => $this->_safe_json($result),
                'http_status' => 200,
                'cache_hit' => 1,
                'success' => true,
                'error_message' => '',
            ));

            return $result;
        }

        $result = $provider->getQuote($input, $config);
        if (get_array_value($result, 'success')) {
            $result['cache_hit'] = false;
            $cache->save($cache_key, $result, $cache_ttl);
        }

        $this->_register_log(array(
            'provider' => $provider_key,
            'endpoint' => get_array_value($result, 'url') ?: 'get_quote',
            'method' => 'POST',
            'request_json' => $this->_safe_json($input),
            'response_json' => $this->_safe_json($result),
            'http_status' => get_array_value($result, 'http_status'),
            'cache_hit' => 0,
            'success' => get_array_value($result, 'success'),
            'error_message' => get_array_value($result, 'message'),
            'created_by' => $created_by,
        ));

        return $result;
    }

    public function list_logs($filters = array())
    {
        return $this->Integration_logs_model->get_details($filters)->getResult();
    }

    public function get_provider($provider_key = '')
    {
        $provider_key = $provider_key ?: $this->_get_provider_key();
        return get_array_value($this->providers, $provider_key);
    }

    public function get_default_provider_key()
    {
        $config = $this->get_configuration();
        $provider_key = trim((string) get_array_value($config, 'provider_key'));
        return $provider_key ?: 'mock';
    }

    private function _get_provider_key($input = array())
    {
        $provider_key = trim((string) get_array_value($input, 'provider_key'));
        if ($provider_key !== '') {
            return $provider_key;
        }

        return $this->get_default_provider_key();
    }

    private function _register_log($data = array())
    {
        return $this->Integration_logs_model->register_log(array(
            'provider' => get_array_value($data, 'provider'),
            'endpoint' => get_array_value($data, 'endpoint'),
            'method' => get_array_value($data, 'method'),
            'request_json' => get_array_value($data, 'request_json'),
            'response_json' => get_array_value($data, 'response_json'),
            'http_status' => get_array_value($data, 'http_status'),
            'cache_hit' => get_array_value($data, 'cache_hit'),
            'success' => get_array_value($data, 'success'),
            'error_message' => get_array_value($data, 'error_message'),
            'created_by' => get_array_value($data, 'created_by'),
        ));
    }

    private function _result_error($message, $provider_key, $http_status = 400, $request = array(), $response = array(), $cache_hit = false)
    {
        return array(
            'success' => false,
            'provider' => $provider_key,
            'http_status' => $http_status,
            'message' => $message,
            'request' => $request,
            'response' => $response,
            'cache_hit' => (bool) $cache_hit,
        );
    }

    private function _quote_cache_key($provider_key, $input, $config)
    {
        $payload = array(
            'provider' => $provider_key,
            'input' => $this->_sanitize_for_cache($input),
            'config_hash' => hash('sha256', json_encode($this->_sanitize_for_cache($config), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        );

        return 'fv_supplier_quote_' . hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function _sanitize_for_cache($payload)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $secret_keys = array('token', 'secret', 'password', 'api_key', 'apikey', 'authorization', 'client_secret');
        $result = array();
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->_sanitize_for_cache($value);
                continue;
            }

            if (in_array(strtolower((string) $key), $secret_keys, true)) {
                $result[$key] = '***';
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function _safe_json($payload)
    {
        return json_encode($this->_sanitize_for_cache($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    private function _normalize_configuration($config)
    {
        if (!is_array($config)) {
            $config = array();
        }

        $defaults = array(
            'provider_key' => 'mock',
            'base_url' => '',
            'auth_type' => 'bearer',
            'token' => '',
            'username' => '',
            'password' => '',
            'healthcheck_endpoint' => '',
            'products_endpoint' => '',
            'kits_endpoint' => '',
            'freight_endpoint' => '',
            'quote_endpoint' => '',
            'timeout_seconds' => 20,
            'cache_ttl_seconds' => 300,
            'notes' => '',
        );

        $config = array_merge($defaults, $config);
        $config['provider_key'] = trim((string) $config['provider_key']) ?: 'mock';
        $config['auth_type'] = trim((string) $config['auth_type']) ?: 'bearer';
        $config['timeout_seconds'] = max(1, (int) $config['timeout_seconds']);
        $config['cache_ttl_seconds'] = max(30, (int) $config['cache_ttl_seconds']);

        return $config;
    }
}
