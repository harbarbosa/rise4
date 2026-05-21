<?php

namespace Fotovoltaico\Services\Providers;

use Config\Services;

class MpfariasTariffProvider extends AbstractSupplierProvider
{
    public function getKey()
    {
        return 'mpfarias_tarifas_energia';
    }

    public function getLabel()
    {
        return 'MP Farias Tarifas Energia API';
    }

    public function authenticate($config = array())
    {
        $config = $this->sanitize_config($config);
        $base_url = rtrim((string) get_array_value($config, 'external_api_base_url'), '/');

        return array(
            'success' => $base_url !== '',
            'provider' => $this->getKey(),
            'message' => $base_url !== '' ? 'Configuration accepted' : 'Missing external_api_base_url',
            'http_status' => $base_url !== '' ? 200 : 400,
            'config' => $config,
        );
    }

    public function testConnection($config = array())
    {
        return $this->getApiStatus($config);
    }

    public function consultProducts($query = array(), $config = array())
    {
        return array(
            'success' => false,
            'provider' => $this->getKey(),
            'message' => 'Products endpoint not supported by this provider',
            'http_status' => 400,
            'payload' => array(),
            'request_payload' => $query,
        );
    }

    public function consultKits($query = array(), $config = array())
    {
        return array(
            'success' => false,
            'provider' => $this->getKey(),
            'message' => 'Kits endpoint not supported by this provider',
            'http_status' => 400,
            'payload' => array(),
            'request_payload' => $query,
        );
    }

    public function consultFreight($query = array(), $config = array())
    {
        return array(
            'success' => false,
            'provider' => $this->getKey(),
            'message' => 'Freight endpoint not supported by this provider',
            'http_status' => 400,
            'payload' => array(),
            'request_payload' => $query,
        );
    }

    public function getQuote($query = array(), $config = array())
    {
        return $this->getCostProjection(
            (float) get_array_value($query, 'consumo_kwh'),
            (string) (get_array_value($query, 'distributor_slug') ?: get_array_value($query, 'distribuidora_slug')),
            $config
        );
    }

    public function getApiStatus($config = array())
    {
        return $this->request('GET', '/distribuidoras/status', array(), $config);
    }

    public function getCurrentFlag($config = array())
    {
        return $this->request('GET', '/distribuidoras/bandeira/atual', array(), $config);
    }

    public function getDistributorsCache($config = array())
    {
        return $this->request('GET', '/distribuidoras/cache', array(), $config);
    }

    public function getDistributorSlugs($config = array())
    {
        return $this->request('GET', '/distribuidoras/slugs', array(), $config);
    }

    public function getSelectableDistributors($config = array())
    {
        return $this->request('GET', '/distribuidoras/selecionaveis', array(), $config);
    }

    public function searchDistributorsByName($name, $config = array())
    {
        return $this->request('GET', '/distribuidoras/buscar', array(
            'nome' => trim((string) $name),
        ), $config);
    }

    public function getDistributorsByUf($uf, $config = array())
    {
        return $this->request('GET', '/distribuidoras/estado/' . rawurlencode(strtoupper(trim((string) $uf))), array(), $config);
    }

    public function getCostProjection($consumo_kwh, $slug, $config = array())
    {
        return $this->request('POST', '/distribuidoras/projecao', array(
            'consumo_kwh' => (float) $consumo_kwh,
            'distribuidora_slug' => trim((string) $slug),
        ), $config);
    }

    public function reloadExternalCache($config = array())
    {
        return $this->request('GET', '/carregar-cache', array(), $config);
    }

    public function request($method, $path, $payload = array(), $config = array())
    {
        $base_url = rtrim((string) get_array_value($config, 'external_api_base_url'), '/');
        if ($base_url === '') {
            $base_url = 'https://tarifas-energia-api.onrender.com';
        }

        $timeout = (int) get_array_value($config, 'external_api_timeout');
        if ($timeout <= 0) {
            $timeout = 20;
        }

        $url = $base_url . '/' . ltrim((string) $path, '/');
        $headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );

        $started_at = microtime(true);
        try {
            $client = Services::curlrequest(array(
                'timeout' => $timeout,
                'http_errors' => false,
            ));

            $options = array(
                'headers' => $headers,
            );

            $method = strtoupper(trim((string) $method));
            if ($method === 'GET' && !empty($payload)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($payload);
            } else if (in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
                $options['json'] = $payload;
            }

            $response = $client->request($method, $url, $options);
            $body = (string) $response->getBody();
            $status_code = (int) $response->getStatusCode();
            $reason = trim((string) $response->getReasonPhrase());
            if ($reason === '') {
                $reason = $status_code >= 200 && $status_code < 400
                    ? 'OK'
                    : 'External API returned HTTP ' . $status_code;
            }
            if ($status_code >= 400 && trim($body) !== '' && stripos($body, '<html') === false) {
                $reason = $reason . ': ' . trim($body);
            }

            return array(
                'success' => $status_code >= 200 && $status_code < 400,
                'provider' => $this->getKey(),
                'url' => $url,
                'method' => $method,
                'http_status' => $status_code,
                'message' => $reason,
                'payload' => $this->decode_payload($body),
                'response_raw' => $body,
                'request_payload' => $payload,
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
            );
        } catch (\Throwable $e) {
            return array(
                'success' => false,
                'provider' => $this->getKey(),
                'url' => $url,
                'method' => strtoupper((string) $method),
                'http_status' => 0,
                'message' => $e->getMessage(),
                'payload' => array(),
                'response_raw' => '',
                'request_payload' => $this->mask_secrets($payload),
                'latency_ms' => (int) round((microtime(true) - $started_at) * 1000),
                'error_message' => $e->getMessage(),
            );
        }
    }

    private function decode_payload($body = '')
    {
        $body = trim((string) $body);
        if ($body === '') {
            return array();
        }

        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return array(
            'raw' => $body,
        );
    }
}
