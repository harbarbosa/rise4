<?php

namespace Fotovoltaico\Services;

use Config\Services;
use Fotovoltaico\Models\Belenus_cache_model;
use Fotovoltaico\Models\Integration_logs_model;
use Fotovoltaico\Models\Settings_model;

class BelenusApiService
{
    private $Settings_model;
    private $Cache_model;
    private $Integration_logs_model;
    private $config;

    public function __construct()
    {
        $this->Settings_model = model(Settings_model::class);
        $this->Cache_model = model(Belenus_cache_model::class);
        $this->Integration_logs_model = model(Integration_logs_model::class);
        $this->config = $this->getConfiguration();
    }

    public function getConfiguration()
    {
        $config = $this->Settings_model->get_setting('belenus_integration_config_json');
        $config = $this->decodeJson($config);

        return $this->normalizeConfiguration($config);
    }

    public function saveConfiguration(array $config)
    {
        $config = $this->normalizeConfiguration($config);
        return $this->Settings_model->save_setting(
            'belenus_integration_config_json',
            json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'app'
        );
    }

    public function authenticate()
    {
        $config = $this->getConfiguration();
        $email = trim((string) get_array_value($config, 'api_email'));
        $senha = trim((string) get_array_value($config, 'api_password'));

        if ($email === '' || $senha === '') {
            return $this->errorResult('Credenciais da Belenus não configuradas.', 422, '/autenticacao/Usuario/Login/PessoaJuridicaByEmail', 'POST');
        }

        $payload = array(
            'email' => $email,
            'senha' => $senha,
        );

        $result = $this->request('POST', '/autenticacao/Usuario/Login/PessoaJuridicaByEmail', array(), $payload, array(
            'skip_auth' => true,
            'cache_type' => 'token',
            'cache_ttl' => (int) get_array_value($config, 'token_ttl_seconds'),
            'force_refresh' => true,
        ));

        $auth_data = get_array_value($result, 'data');
        if (get_array_value($result, 'success') && is_array($auth_data) && !empty($auth_data['access_token'])) {
            $this->storeToken((string) $auth_data['access_token'], (int) get_array_value($auth_data, 'expires_in'));
        }

        return $result;
    }

    public function getValidToken()
    {
        $cached = $this->Cache_model->get_valid_cache('belenus_token');
        if ($cached && !empty($cached->payload_json)) {
            $payload = $this->decodeJson($cached->payload_json);
            $token = trim((string) get_array_value($payload, 'access_token'));
            if ($token !== '') {
                return $token;
            }
        }

        $cache = service('cache');
        $token = $cache->get('belenus_token');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $auth = $this->authenticate();
        if (get_array_value($auth, 'success')) {
            $auth_data = get_array_value($auth, 'data');
            if (is_array($auth_data) && !empty($auth_data['access_token'])) {
                return (string) $auth_data['access_token'];
            }
        }

        return '';
    }

    public function request($method, $endpoint, $params = array(), $body = array(), $options = array())
    {
        $config = $this->getConfiguration();
        $base_url = rtrim((string) get_array_value($config, 'base_url'), '/');
        if ($base_url === '') {
            $base_url = 'https://belenus.com.br/api';
        }

        $method = strtoupper(trim((string) $method));
        $endpoint = '/' . ltrim((string) $endpoint, '/');
        $timeout = (int) get_array_value($config, 'timeout_seconds');
        if ($timeout <= 0) {
            $timeout = 20;
        }

        $cache_ttl = (int) get_array_value($options, 'cache_ttl');
        if ($cache_ttl <= 0) {
            $cache_ttl = $this->defaultCacheTtl($endpoint, $config);
        }

        $cache_type = trim((string) get_array_value($options, 'cache_type'));
        $cache_key = $this->buildCacheKey($method, $endpoint, $params, $body);
        $use_cache = ($method === 'GET' || get_array_value($options, 'cacheable')) && !get_array_value($options, 'force_refresh');
        $cache_hit = 0;

        if ($use_cache) {
            $cached = $this->Cache_model->get_valid_cache($cache_key);
            if ($cached && !empty($cached->payload_json)) {
                $response = $this->decodeJson($cached->payload_json);
                $response['success'] = isset($response['success']) ? (bool) $response['success'] : true;
                $response['cache_hit'] = true;
                $this->registerLog(array(
                    'provider' => 'belenus',
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'request_json' => $this->safeJson(array('params' => $params, 'body' => $body)),
                    'response_json' => $this->safeJson($response),
                    'http_status' => 200,
                    'latency_ms' => 0,
                    'cache_hit' => 1,
                    'success' => true,
                    'error_message' => '',
                    'created_by' => (int) get_array_value($options, 'created_by'),
                ));
                return $response;
            }
        }

        $client = Services::curlrequest(array('timeout' => $timeout, 'http_errors' => false));
        $query_url = $base_url . $endpoint;
        if ($method === 'GET' && $params) {
            $query_url .= '?' . http_build_query($params);
        }

        $headers = array('Accept' => 'application/json');
        if (!get_array_value($options, 'skip_auth')) {
            $token = $this->getValidToken();
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
        }

        $request_options = array('headers' => $headers);
        if ($method !== 'GET') {
            $request_options['json'] = $body;
        }

        $started = microtime(true);
        $response = $client->request($method, $query_url, $request_options);
        $latency_ms = (int) round((microtime(true) - $started) * 1000);
        $status = (int) $response->getStatusCode();
        $content = (string) $response->getBody();
        $decoded = $this->decodeJson($content);
        $invalid_json = $status >= 200 && $status < 300 && trim($content) !== '' && !$decoded;
        $raw_snippet = substr(trim(preg_replace('/^\xEF\xBB\xBF/', '', strip_tags($content))), 0, 1000);
        $result = array(
            'success' => ($status >= 200 && $status < 300) && !$invalid_json,
            'http_status' => $status,
            'message' => $invalid_json ? ('Resposta inválida da API Belenus em ' . $endpoint . ' (HTTP ' . $status . ').') : $this->extractMessage($decoded, $status),
            'data' => $invalid_json ? array('raw' => $raw_snippet) : $decoded,
            'cache_hit' => false,
            'endpoint' => $endpoint,
            'method' => $method,
        );

        if ($status === 401 && !get_array_value($options, 'skip_auth') && !get_array_value($options, 'retried')) {
            $auth = $this->authenticate();
            if (get_array_value($auth, 'success')) {
                $options['retried'] = true;
                return $this->request($method, $endpoint, $params, $body, $options);
            }
        }

        if ($result['success'] && $use_cache) {
            $this->Cache_model->put_cache(array(
                'cache_key' => $cache_key,
                'cache_type' => $cache_type ?: $endpoint,
                'payload_json' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'expires_at' => date('Y-m-d H:i:s', time() + $cache_ttl),
                'created_by' => (int) get_array_value($options, 'created_by'),
            ));
        }

        $this->registerLog(array(
            'provider' => 'belenus',
            'endpoint' => $endpoint,
            'method' => $method,
            'request_json' => $this->safeJson(array('params' => $params, 'body' => $body)),
            'response_json' => $this->safeJson($this->summarizeResponse($result, $content)),
            'http_status' => $status,
            'latency_ms' => $latency_ms,
            'cache_hit' => $cache_hit,
            'success' => $result['success'],
            'error_message' => $result['success'] ? '' : $result['message'],
            'created_by' => (int) get_array_value($options, 'created_by'),
        ));

        return $result;
    }

    public function getProducts($filters = array())
    {
        return $this->request('GET', '/produto', $filters, array(), array('cache_type' => 'products'));
    }

    public function getProductById($id)
    {
        return $this->request('GET', '/produto/' . (int) $id, array(), array(), array('cache_type' => 'product'));
    }

    public function getProductPrice($id)
    {
        return $this->request('GET', '/produto/' . (int) $id . '/preco', array(), array(), array('cache_type' => 'product_price'));
    }

    public function getProductPricesBatch($productIds = array())
    {
        $productIds = array_values(array_filter(array_map('intval', (array) $productIds)));
        return $this->request('POST', '/produto/precos', array(), array('produtos' => $productIds), array('cache_type' => 'product_prices_batch', 'cacheable' => true));
    }

    public function getKits($filters = array())
    {
        return $this->request('GET', '/kits', $filters, array(), array('cache_type' => 'kits'));
    }

    public function getKitById($id)
    {
        return $this->request('GET', '/kits/' . (int) $id, array(), array(), array('cache_type' => 'kit'));
    }

    public function getKitPrice($id)
    {
        return $this->request('GET', '/kits/' . (int) $id . '/preco', array(), array(), array('cache_type' => 'kit_price'));
    }

    public function testConnection()
    {
        $auth = $this->authenticate();
        if (!get_array_value($auth, 'success')) {
            return $auth;
        }

        return $this->getProducts(array('page' => 1, 'pageSize' => 1));
    }

    public function clearCache()
    {
        $this->Cache_model->clear_all();
        $cache = service('cache');
        if (method_exists($cache, 'delete')) {
            $cache->delete('belenus_token');
        }

        return array('success' => true, 'message' => 'Cache limpo com sucesso.', 'data' => array());
    }

    public function getCacheModel()
    {
        return $this->Cache_model;
    }

    private function storeToken($access_token, $expires_in = 3600)
    {
        $expires_in = (int) $expires_in;
        if ($expires_in <= 0) {
            $expires_in = 3600;
        }

        $expires_at = date('Y-m-d H:i:s', time() + $expires_in - 60);
        $payload = array(
            'access_token' => $access_token,
            'expires_in' => $expires_in,
        );

        $this->Cache_model->put_cache(array(
            'cache_key' => 'belenus_token',
            'cache_type' => 'token',
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'expires_at' => $expires_at,
        ));

        $ttl = $expires_in - 60;
        if ($ttl <= 0) {
            $ttl = $expires_in > 0 ? $expires_in : 3600;
        }
        service('cache')->save('belenus_token', $access_token, $ttl);
    }

    private function defaultCacheTtl($endpoint, $config)
    {
        if (strpos($endpoint, '/preco') !== false) {
            return (int) get_array_value($config, 'price_cache_ttl_seconds') ?: 300;
        }

        if ($endpoint === '/produto' || strpos($endpoint, '/produto/') === 0) {
            return (int) get_array_value($config, 'products_cache_ttl_seconds') ?: 900;
        }

        if ($endpoint === '/kits' || strpos($endpoint, '/kits/') === 0) {
            return (int) get_array_value($config, 'kits_cache_ttl_seconds') ?: 900;
        }

        return (int) get_array_value($config, 'cache_ttl_seconds') ?: 300;
    }

    private function buildCacheKey($method, $endpoint, $params, $body)
    {
        $payload = array(
            'method' => strtoupper((string) $method),
            'endpoint' => $endpoint,
            'params' => $this->sanitizeForCache($params),
            'body' => $this->sanitizeForCache($body),
        );

        return 'belenus_' . hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function registerLog($data)
    {
        return $this->Integration_logs_model->register_log(array(
            'provider' => get_array_value($data, 'provider') ?: 'belenus',
            'endpoint' => get_array_value($data, 'endpoint'),
            'method' => get_array_value($data, 'method'),
            'request_json' => get_array_value($data, 'request_json'),
            'response_json' => get_array_value($data, 'response_json'),
            'http_status' => get_array_value($data, 'http_status'),
            'latency_ms' => get_array_value($data, 'latency_ms'),
            'cache_hit' => get_array_value($data, 'cache_hit') ? 1 : 0,
            'success' => get_array_value($data, 'success') ? 1 : 0,
            'error_message' => get_array_value($data, 'error_message'),
            'created_by' => get_array_value($data, 'created_by'),
        ));
    }

    private function summarizeResponse($result, $raw_content = '')
    {
        if (!is_array($result)) {
            return array('raw' => substr((string) $raw_content, 0, 500));
        }

        $summary = $result;
        if (isset($summary['data']) && is_array($summary['data'])) {
            $data = $summary['data'];
            if (isset($data['items']) && is_array($data['items'])) {
                $summary['data'] = array(
                    'count' => count($data['items']),
                    'page' => get_array_value($data, 'page'),
                    'total' => get_array_value($data, 'total'),
                    'keys' => array_keys($data),
                );
            } elseif (isset($data['itens']) && is_array($data['itens'])) {
                $summary['data'] = array(
                    'count' => count($data['itens']),
                    'keys' => array_keys($data),
                );
            }
        }

        if (strlen(json_encode($summary)) > 4000) {
            $summary['data'] = 'response_truncated';
        }

        return $summary;
    }

    private function sanitizeForCache($payload)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $secret_keys = array('token', 'secret', 'password', 'senha', 'api_key', 'apikey', 'authorization');
        $result = array();
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->sanitizeForCache($value);
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

    private function safeJson($payload)
    {
        return json_encode($this->sanitizeForCache($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function decodeJson($json_text)
    {
        if (is_array($json_text)) {
            return $json_text;
        }

        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return array();
        }

        $json_text = preg_replace('/^\xEF\xBB\xBF/', '', $json_text);
        $decoded = json_decode($json_text, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : array();
    }

    private function extractMessage(array $decoded, $status)
    {
        foreach (array('message', 'error', 'detail', 'title') as $key) {
            $value = get_array_value($decoded, $key);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        if ($status === 401) {
            return 'Autenticação negada pela API Belenus.';
        }

        if ($status === 403) {
            return 'Acesso negado pela API Belenus.';
        }

        if ($status === 404) {
            return 'Recurso não encontrado na API Belenus.';
        }

        if ($status >= 500) {
            return 'Falha interna na API Belenus.';
        }

        return $status >= 200 && $status < 300 ? 'OK' : 'Resposta inválida da API Belenus.';
    }

    private function normalizeConfiguration(array $config)
    {
        return array(
            'base_url' => trim((string) get_array_value($config, 'base_url')) ?: 'https://belenus.com.br/api',
            'api_email' => trim((string) get_array_value($config, 'api_email')),
            'api_password' => trim((string) get_array_value($config, 'api_password')),
            'token_ttl_seconds' => (int) get_array_value($config, 'token_ttl_seconds') ?: 3600,
            'products_cache_ttl_seconds' => (int) get_array_value($config, 'products_cache_ttl_seconds') ?: 900,
            'price_cache_ttl_seconds' => (int) get_array_value($config, 'price_cache_ttl_seconds') ?: 300,
            'kits_cache_ttl_seconds' => (int) get_array_value($config, 'kits_cache_ttl_seconds') ?: 900,
            'timeout_seconds' => (int) get_array_value($config, 'timeout_seconds') ?: 20,
            'active' => (int) get_array_value($config, 'active') ? 1 : 0,
        );
    }

    private function errorResult($message, $status = 400, $endpoint = '', $method = 'GET')
    {
        return array(
            'success' => false,
            'http_status' => (int) $status,
            'message' => $message,
            'data' => array(),
            'cache_hit' => false,
            'endpoint' => $endpoint,
            'method' => $method,
        );
    }
}
