<?php

namespace RestApi\Controllers;

use App\Models\Settings_model;
use ContaAzul\Config\QueryEndpoints;
use ContaAzul\Libraries\ContaAzulClient;

class ContaAzulController extends Rest_api_Controller
{
    protected QueryEndpoints $queryConfig;
    protected Settings_model $settingsModel;

    public function __construct()
    {
        parent::__construct();
        $this->queryConfig = new QueryEndpoints();
        $this->settingsModel = new Settings_model();
    }

    public function endpoints()
    {
        $data = [];

        foreach ($this->queryConfig->endpoints as $key => $endpoint) {
            $data[] = [
                'key' => $key,
                'label' => $endpoint['label'] ?? $key,
                'path' => $endpoint['path'] ?? '',
                'path_params' => array_values($endpoint['path_params'] ?? []),
                'query_params' => array_values($endpoint['query_params'] ?? []),
                'docs_url' => $endpoint['docs_url'] ?? null,
                'route' => get_uri('api/contaazul/query/' . $key),
            ];
        }

        return $this->respond([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function query(string $key)
    {
        $definition = $this->queryConfig->endpoints[$key] ?? null;
        if (!$definition) {
            return $this->failNotFound('Conta Azul endpoint not found.');
        }

        $client = $this->makeClient();
        $refreshError = $this->refreshClientIfNeeded($client);
        if ($refreshError) {
            return $this->failUnauthorized($refreshError['message'] ?? 'Conta Azul token refresh failed.');
        }

        $query = $this->request->getGet();
        $pathParams = [];

        foreach ($definition['path_params'] ?? [] as $param) {
            $value = trim((string) ($query[$param] ?? ''));
            if ($value === '') {
                return $this->failValidationErrors("Missing required path parameter: {$param}");
            }

            $pathParams[$param] = $value;
            unset($query[$param]);
        }

        $response = $client->queryEndpoint(
            $definition['path'],
            $query,
            $pathParams,
            $definition['fallback_paths'] ?? []
        );

        $payload = [
            'status' => $response['ok'],
            'endpoint' => $key,
            'request' => [
                'path' => $definition['path'],
                'path_params' => $pathParams,
                'query' => $query,
            ],
            'supported_query_params' => array_values($definition['query_params'] ?? []),
            'data' => $response['data'],
        ];

        if (!$response['ok']) {
            $payload['message'] = 'Conta Azul query failed.';
            $payload['http_status'] = $response['status'];
            $payload['raw_body'] = $response['body'];

            return $this->respond($payload, $response['status'] ?: 500);
        }

        return $this->respond($payload, $response['status'] ?: 200);
    }

    protected function makeClient(): ContaAzulClient
    {
        $redirectUri = $this->settingsModel->get_setting('contaazul_redirect_uri') ?: get_uri('contaazul/callback');
        $scope = $this->settingsModel->get_setting('contaazul_scope') ?: 'openid profile aws.cognito.signin.user.admin';

        return new ContaAzulClient(
            $this->settingsModel->get_setting('contaazul_client_id'),
            $this->settingsModel->get_setting('contaazul_client_secret'),
            $redirectUri,
            $scope,
            $this->settingsModel->get_setting('contaazul_access_token'),
            $this->settingsModel->get_setting('contaazul_refresh_token'),
            $this->settingsModel->get_setting('contaazul_token_expires_at')
        );
    }

    protected function refreshClientIfNeeded(ContaAzulClient $client): ?array
    {
        if (!$client->isExpired()) {
            return null;
        }

        $refreshToken = $this->settingsModel->get_setting('contaazul_refresh_token');
        if (!$refreshToken) {
            return ['message' => 'Conta Azul refresh token is not configured.'];
        }

        $refresh = $client->refreshAccessToken($refreshToken);
        if (!$refresh['ok']) {
            return ['message' => 'Conta Azul token refresh failed: ' . ($refresh['body'] ?: 'unknown error')];
        }

        $tokens = $client->getTokens();
        $this->settingsModel->save_setting('contaazul_access_token', $tokens['access_token'] ?? '');
        $this->settingsModel->save_setting('contaazul_refresh_token', $tokens['refresh_token'] ?? '');
        $this->settingsModel->save_setting('contaazul_token_expires_at', $tokens['expires_at'] ?? '');

        return null;
    }
}
