<?php

namespace ContaAzul\Controllers;

use App\Controllers\Security_Controller;
use App\Models\Settings_model;
use ContaAzul\Config\QueryEndpoints;
use ContaAzul\Libraries\ContaAzulClient;

class ContaAzulQuery extends Security_Controller
{
    public $Settings_model;

    protected QueryEndpoints $queryConfig;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_admin();
        $this->Settings_model = new Settings_model();
        $this->queryConfig = new QueryEndpoints();
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
                'docs_url' => $endpoint['docs_url'] ?? null,
                'route' => get_uri('contaazul/api/query/' . $key),
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function query(string $key)
    {
        $definition = $this->queryConfig->endpoints[$key] ?? null;
        if (!$definition) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Endpoint de consulta não encontrado.',
            ]);
        }

        $client = $this->makeClient();
        $refreshError = $this->refreshClientIfNeeded($client);
        if ($refreshError) {
            return $this->response->setStatusCode(401)->setJSON($refreshError);
        }

        $query = $this->request->getGet();
        $pathParams = [];
        foreach ($definition['path_params'] ?? [] as $param) {
            $value = trim((string) ($query[$param] ?? ''));
            if ($value === '') {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => "Parâmetro obrigatório ausente: {$param}",
                ]);
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

        return $this->response->setStatusCode($response['status'] ?: 500)->setJSON([
            'success' => $response['ok'],
            'endpoint' => $key,
            'request' => [
                'path' => $definition['path'],
                'path_params' => $pathParams,
                'query' => $query,
            ],
            'status' => $response['status'],
            'data' => $response['data'],
            'raw_body' => $response['body'],
        ]);
    }

    protected function makeClient(): ContaAzulClient
    {
        $redirectUri = get_setting('contaazul_redirect_uri') ?: get_uri('contaazul/callback');
        $scope = get_setting('contaazul_scope') ?: 'openid profile aws.cognito.signin.user.admin';

        return new ContaAzulClient(
            get_setting('contaazul_client_id'),
            get_setting('contaazul_client_secret'),
            $redirectUri,
            $scope,
            get_setting('contaazul_access_token'),
            get_setting('contaazul_refresh_token'),
            get_setting('contaazul_token_expires_at')
        );
    }

    protected function refreshClientIfNeeded(ContaAzulClient $client): ?array
    {
        if (!$client->isExpired()) {
            return null;
        }

        $refreshToken = get_setting('contaazul_refresh_token');
        if (!$refreshToken) {
            return [
                'success' => false,
                'message' => 'Refresh token do Conta Azul não configurado.',
            ];
        }

        $refresh = $client->refreshAccessToken($refreshToken);
        if (!$refresh['ok']) {
            return [
                'success' => false,
                'message' => 'Falha ao renovar token do Conta Azul.',
                'details' => $refresh['body'],
            ];
        }

        $this->persistTokens($client->getTokens());
        return null;
    }

    protected function persistTokens(array $tokens): void
    {
        $this->Settings_model->save_setting('contaazul_access_token', $tokens['access_token'] ?? '');
        $this->Settings_model->save_setting('contaazul_refresh_token', $tokens['refresh_token'] ?? '');
        $this->Settings_model->save_setting('contaazul_token_expires_at', $tokens['expires_at'] ?? '');
    }
}
