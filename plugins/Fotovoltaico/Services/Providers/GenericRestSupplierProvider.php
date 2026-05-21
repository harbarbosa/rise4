<?php

namespace Fotovoltaico\Services\Providers;

use Config\Services;

class GenericRestSupplierProvider extends AbstractSupplierProvider
{
    public function getKey()
    {
        return 'generic_rest';
    }

    public function getLabel()
    {
        return 'Generic REST Supplier';
    }

    public function authenticate($config = array())
    {
        $config = $this->sanitize_config($config);
        if (!get_array_value($config, 'base_url')) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'message' => 'Missing base_url',
                'http_status' => 400,
            );
        }

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
        $config = $this->sanitize_config($config);
        $base_url = rtrim((string) get_array_value($config, 'base_url'), '/');
        $health_endpoint = ltrim((string) get_array_value($config, 'healthcheck_endpoint'), '/');

        if (!$base_url) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'message' => 'Base URL not configured',
                'http_status' => 400,
            );
        }

        $url = $health_endpoint ? $base_url . '/' . $health_endpoint : $base_url;
        $method = strtoupper((string) get_array_value($config, 'auth_method') ?: 'GET');
        $response = $this->_request($method, $url, array(), $config);

        return array(
            'success' => (int) get_array_value($response, 'http_status') >= 200 && (int) get_array_value($response, 'http_status') < 400,
            'provider' => $this->getKey(),
            'message' => get_array_value($response, 'message') ?: 'Connection attempted',
            'http_status' => get_array_value($response, 'http_status') ?: 500,
            'response' => get_array_value($response, 'response'),
            'url' => $url,
        );
    }

    public function consultProducts($query = array(), $config = array())
    {
        return $this->_consult('products_endpoint', 'GET', $query, $config);
    }

    public function consultKits($query = array(), $config = array())
    {
        return $this->_consult('kits_endpoint', 'GET', $query, $config);
    }

    public function consultFreight($query = array(), $config = array())
    {
        return $this->_consult('freight_endpoint', 'POST', $query, $config);
    }

    public function getQuote($query = array(), $config = array())
    {
        $config = $this->sanitize_config($config);
        $quote_endpoint = ltrim((string) get_array_value($config, 'quote_endpoint'), '/');
        if ($quote_endpoint) {
            $base_url = rtrim((string) get_array_value($config, 'base_url'), '/');
            if ($base_url) {
                $url = $base_url . '/' . $quote_endpoint;
                $response = $this->_request('POST', $url, $query, $config);
                $payload = get_array_value($response, 'response');
                if (is_string($payload)) {
                    $decoded = json_decode($payload, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $payload = $decoded;
                    }
                }

                return array(
                    'success' => (int) get_array_value($response, 'http_status') >= 200 && (int) get_array_value($response, 'http_status') < 400,
                    'provider' => $this->getKey(),
                    'http_status' => get_array_value($response, 'http_status'),
                    'message' => get_array_value($response, 'message'),
                    'payload' => $payload,
                    'url' => $url,
                );
            }
        }

        $products = $this->consultProducts($query, $config);
        $kits = $this->consultKits($query, $config);
        $freight = $this->consultFreight($query, $config);

        return array(
            'success' => (bool) (get_array_value($products, 'success') || get_array_value($kits, 'success') || get_array_value($freight, 'success')),
            'provider' => $this->getKey(),
            'http_status' => 200,
            'payload' => array(
                'products' => get_array_value($products, 'payload'),
                'kits' => get_array_value($kits, 'payload'),
                'freight' => get_array_value($freight, 'payload'),
                'grand_total' => (float) get_array_value(get_array_value($freight, 'payload'), 'freight_value'),
                'availability' => 'provider_dependent',
            ),
            'response' => array(
                'products' => $products,
                'kits' => $kits,
                'freight' => $freight,
            ),
        );
    }

    private function _consult($endpoint_key, $method, $query, $config)
    {
        $config = $this->sanitize_config($config);
        $base_url = rtrim((string) get_array_value($config, 'base_url'), '/');
        $endpoint = ltrim((string) get_array_value($config, $endpoint_key), '/');
        if (!$base_url || !$endpoint) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'message' => 'Provider endpoint not configured',
                'http_status' => 400,
            );
        }

        $url = $base_url . '/' . $endpoint;
        $response = $this->_request($method, $url, $query, $config);
        $payload = get_array_value($response, 'response');
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded;
            }
        }

        return array(
            'success' => (int) get_array_value($response, 'http_status') >= 200 && (int) get_array_value($response, 'http_status') < 400,
            'provider' => $this->getKey(),
            'type' => $endpoint_key,
            'http_status' => get_array_value($response, 'http_status'),
            'message' => get_array_value($response, 'message'),
            'payload' => $payload,
            'url' => $url,
        );
    }

    private function _request($method, $url, $payload, $config)
    {
        $timeout = (int) get_array_value($config, 'timeout_seconds');
        if ($timeout <= 0) {
            $timeout = 20;
        }

        $headers = array('Accept' => 'application/json');
        $auth_type = strtolower((string) get_array_value($config, 'auth_type'));
        $token = trim((string) get_array_value($config, 'token'));
        $username = trim((string) get_array_value($config, 'username'));
        $password = trim((string) get_array_value($config, 'password'));

        if ($auth_type === 'bearer' && $token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        } else if ($auth_type === 'basic' && $username !== '') {
            $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        } else if ($token !== '') {
            $headers['X-API-Key'] = $token;
        }

        try {
            $client = Services::curlrequest(array('timeout' => $timeout));
            $options = array('headers' => $headers);
            if (in_array(strtoupper($method), array('POST', 'PUT', 'PATCH'), true)) {
                $options['json'] = $payload;
            } else if (!empty($payload)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($payload);
            }

            $response = $client->request($method, $url, $options);
            return array(
                'http_status' => $response->getStatusCode(),
                'message' => $response->getReason(),
                'response' => (string) $response->getBody(),
            );
        } catch (\Throwable $e) {
            return array(
                'http_status' => 0,
                'message' => $e->getMessage(),
                'response' => '',
            );
        }
    }
}
