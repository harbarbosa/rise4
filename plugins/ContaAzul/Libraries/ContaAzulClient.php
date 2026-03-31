<?php

namespace ContaAzul\Libraries;

class ContaAzulClient
{
    const AUTH_URL = 'https://auth.contaazul.com/login';
    const TOKEN_URL = 'https://auth.contaazul.com/oauth2/token';
    const API_BASE = 'https://api-v2.contaazul.com';

    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $scope;
    private $accessToken;
    private $refreshToken;
    private $expiresAt;

    public function __construct($clientId, $clientSecret, $redirectUri, $scope = 'openid profile aws.cognito.signin.user.admin', $accessToken = '', $refreshToken = '', $expiresAt = '')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->scope = $scope;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
    }

    public function getAuthorizationUrl($state)
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => $this->scope
        ]);
        return self::AUTH_URL . '?' . $params;
    }

    public function exchangeCode($code)
    {
        $body = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri
        ]);

        return $this->tokenRequest($body);
    }

    public function refreshAccessToken($refreshToken = null)
    {
        $refresh = $refreshToken ?: $this->refreshToken;
        if (!$refresh) {
            return ["ok" => false, "status" => 0, "data" => null, "body" => "Refresh token vazio"];
        }

        $body = http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh
        ]);

        return $this->tokenRequest($body);
    }

    public function listPeople($page = 0, $size = 50)
    {
        return $this->queryEndpoint('/v1/pessoas', [
            'pagina' => max(0, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ]);
    }

    public function getPerson($id)
    {
        return $this->queryEndpoint('/v1/pessoas/{id}', [], ['id' => $id]);
    }

    public function getLegacyPerson($id)
    {
        return $this->queryEndpoint('/v1/pessoas/legado/{id}', [], ['id' => $id]);
    }

    public function listProducts($page = 0, $size = 50)
    {
        return $this->queryEndpoint('/v1/produtos', [
            'pagina' => max(0, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ]);
    }

    public function listProductCategories()
    {
        return $this->queryEndpoint('/v1/produtos/categorias');
    }

    public function listProductCests($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/produtos/cest', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function listProductNcms($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/produtos/ncm', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function listProductUnits($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/produtos/unidades-medida', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function listProductEcommerceCategories($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/produtos/ecommerce-categorias', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function listProductEcommerceBrands($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/produtos/ecommerce-marcas', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function listServices($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/servicos', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query), [], ['/v1/servico']);
    }

    public function getService($id)
    {
        return $this->queryEndpoint('/v1/servicos/{id}', [], ['id' => $id], ['/v1/servico/{id}']);
    }

    
    public function listCostCenters($page = 1, $size = 50)
    {
        $page = max(1, (int) $page);
        $size = max(1, (int) $size);
        $query = http_build_query([
            'pagina' => $page,
            'tamanho_pagina' => $size
        ]);

        $endpoints = [
            self::API_BASE . '/v1/centro-de-custo?' . $query,
            self::API_BASE . '/v1/centro-de-custo',
            self::API_BASE . '/v1/centros-de-custo?' . $query,
            self::API_BASE . '/v1/centros-de-custo'
        ];

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        $last = ["ok" => false, "status" => 0, "data" => null, "body" => ""];
        foreach ($endpoints as $url) {
            $resp = $this->getRequest($url, $headers);
            if ($resp["ok"]) {
                return $resp;
            }
            $last = $resp;
        }

        return $last;
    }

    public function createCostCenter($title, $code = null, $isActive = true)
    {
        $title = trim((string) $title);
        if ($title === '') {
            return ["ok" => false, "status" => 0, "data" => null, "body" => "Titulo vazio"];
        }

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        $payloads = [
            [
                'descricao' => $title,
                'codigo' => $code,
                'ativo' => $isActive ? true : false
            ],
            [
                'name' => $title,
                'code' => $code,
                'active' => $isActive ? true : false
            ],
            [
                'nome' => $title,
                'codigo' => $code,
                'ativo' => $isActive ? true : false
            ]
        ];

        $endpoints = [
            self::API_BASE . '/v1/centro-de-custo',
            self::API_BASE . '/v1/centros-de-custo'
        ];

        $last = ["ok" => false, "status" => 0, "data" => null, "body" => ""];
        foreach ($endpoints as $url) {
            foreach ($payloads as $payload) {
                $cleanPayload = array_filter($payload, function ($value) {
                    return $value !== null && $value !== '';
                });
                $resp = $this->postJsonRequest($url, $headers, $cleanPayload);
                if ($resp["ok"]) {
                    return $resp;
                }
                $last = $resp;
            }
        }

        return $last;
    }

    public function getProduct($id)
    {
        return $this->queryEndpoint('/v1/produtos/{id}', [], ['id' => $id]);
    }

    public function listPayables($costCenterId = null, $page = 1, $size = 100)
    {
        $page = max(1, (int)$page);
        $size = max(1, (int)$size);

        $paramSets = $this->buildPayableReceivableParamSets($costCenterId, $page, $size);
        $endpoints = [
            self::API_BASE . '/v1/financeiro/eventos-financeiros/contas-a-pagar/buscar',
            self::API_BASE . '/v1/financeiro/contas-a-pagar',
            self::API_BASE . '/v1/contas-a-pagar'
        ];

        return $this->getWithFallback($endpoints, $paramSets);
    }

    public function listReceivables($costCenterId = null, $page = 1, $size = 100)
    {
        $page = max(1, (int)$page);
        $size = max(1, (int)$size);

        $paramSets = $this->buildPayableReceivableParamSets($costCenterId, $page, $size);
        $endpoints = [
            self::API_BASE . '/v1/financeiro/eventos-financeiros/contas-a-receber/buscar',
            self::API_BASE . '/v1/financeiro/contas-a-receber',
            self::API_BASE . '/v1/contas-a-receber'
        ];

        return $this->getWithFallback($endpoints, $paramSets);
    }

    public function isExpired()
    {
        if (!$this->expiresAt) {
            return true;
        }
        $ts = is_numeric($this->expiresAt) ? (int) $this->expiresAt : strtotime($this->expiresAt);
        return $ts <= (time() + 300);
    }

    public function setTokensFromResponse($data)
    {
        $this->accessToken = $data['access_token'] ?? '';
        $this->refreshToken = $data['refresh_token'] ?? $this->refreshToken;
        $this->expiresAt = isset($data['expires_in']) ? time() + (int)$data['expires_in'] : $this->expiresAt;
    }

    public function getTokens()
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt
        ];
    }

    public function listCategories($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/categorias', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function listDreCategories($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/financeiro/categorias-dre', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function listFinancialAccounts($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/conta-financeira', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function getFinancialAccountBalance($id)
    {
        return $this->queryEndpoint('/v1/conta-financeira/{id_conta_financeira}/saldo-atual', [], [
            'id_conta_financeira' => $id
        ]);
    }

    public function listEventInstallments($eventId, $page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/financeiro/eventos-financeiros/{id_evento}/parcelas', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query), ['id_evento' => $eventId]);
    }

    public function listSales($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/venda/busca', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function getSale($id)
    {
        return $this->queryEndpoint('/v1/venda/{id}', [], ['id' => $id]);
    }

    public function listSaleItems($saleId, $page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/venda/{id_venda}/itens', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query), ['id_venda' => $saleId]);
    }

    public function listSellers()
    {
        return $this->queryEndpoint('/v1/venda/vendedores');
    }

    public function getNextSaleNumber()
    {
        return $this->queryEndpoint('/v1/venda/proximo-numero');
    }

    public function listContracts($page = 1, $size = 50, $query = [])
    {
        return $this->queryEndpoint('/v1/contratos', array_merge([
            'pagina' => max(1, (int) $page),
            'tamanho_pagina' => max(1, (int) $size)
        ], $query));
    }

    public function getNextContractNumber()
    {
        return $this->queryEndpoint('/v1/contratos/proximo-numero');
    }

    public function listInvoices(array $query)
    {
        return $this->queryEndpoint('/v1/notas-fiscais', $query);
    }

    public function listServiceInvoices(array $query)
    {
        return $this->queryEndpoint('/v1/notas-fiscais-servico', $query);
    }

    public function getInvoice($key)
    {
        return $this->queryEndpoint('/v1/notas-fiscais/{chave}', [], ['chave' => $key]);
    }

    public function listTransfers(array $query = [])
    {
        return $this->queryEndpoint('/v1/financeiro/transferencias', $query);
    }

    public function queryEndpoint($path, array $query = [], array $pathParams = [], array $fallbackPaths = [])
    {
        $paths = array_merge([$path], $fallbackPaths);
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        $last = ["ok" => false, "status" => 0, "data" => null, "body" => ""];
        foreach ($paths as $candidatePath) {
            $resolved = $this->resolvePath($candidatePath, $pathParams);
            if (!$resolved['ok']) {
                return $resolved;
            }

            $url = self::API_BASE . $resolved['path'];
            $filteredQuery = array_filter($query, function ($value) {
                return $value !== null && $value !== '';
            });

            if ($filteredQuery) {
                $url .= '?' . http_build_query($filteredQuery);
            }

            $response = $this->getRequest($url, $headers);
            if ($response['ok']) {
                return $response;
            }

            $last = $response;
        }

        return $last;
    }

    private function tokenRequest($body)
    {
        $headers = [
            "Authorization: Basic " . base64_encode($this->clientId . ":" . $this->clientSecret),
            "Content-Type: application/x-www-form-urlencoded"
        ];

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $this->applyCaInfo($ch);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ["ok" => false, "status" => 0, "data" => null, "body" => $error];
        }

        $decoded = json_decode($response, true);
        $ok = $httpCode >= 200 && $httpCode < 300 && isset($decoded['access_token']);
        if ($ok) {
            $this->setTokensFromResponse($decoded);
        }

        return ["ok" => $ok, "status" => $httpCode, "data" => $decoded, "body" => $response];
    }

    private function getRequest($url, $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $this->applyCaInfo($ch);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ["ok" => false, "status" => 0, "data" => null, "body" => $error];
        }

        $decoded = json_decode($response, true);

       

        
       
        $ok = $httpCode >= 200 && $httpCode < 300;
        return ["ok" => $ok, "status" => $httpCode, "data" => $decoded, "body" => $response];
    }

    private function postJsonRequest($url, $headers, $payload)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $this->applyCaInfo($ch);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ["ok" => false, "status" => 0, "data" => null, "body" => $error];
        }

        $decoded = json_decode($response, true);
        $ok = $httpCode >= 200 && $httpCode < 300;
        return ["ok" => $ok, "status" => $httpCode, "data" => $decoded, "body" => $response];
    }

    private function buildCostCenterParamSets($costCenterId, $page, $size)
    {
        $params = [];
        if ($costCenterId) {
            $params[] = ['pagina' => $page, 'tamanho_pagina' => $size, 'centroDeCustoId' => $costCenterId];
            $params[] = ['pagina' => $page, 'tamanho_pagina' => $size, 'centro_de_custo_id' => $costCenterId];
            $params[] = ['pagina' => $page, 'tamanho_pagina' => $size, 'centroDeCusto' => $costCenterId];
            $params[] = ['pagina' => $page, 'tamanho_pagina' => $size, 'centro_de_custo' => $costCenterId];
            $params[] = ['page' => $page, 'size' => $size, 'cost_center_id' => $costCenterId];
        } else {
            $params[] = ['pagina' => $page, 'tamanho_pagina' => $size];
            $params[] = ['page' => $page, 'size' => $size];
        }

        return $params;
    }

    private function buildPayableReceivableParamSets($costCenterId, $page, $size)
    {
        $params = [];
        $dateFrom = '2000-01-01';
        $dateTo = '2099-12-31';

        if ($costCenterId) {
            $params[] = [
                'pagina' => $page,
                'tamanho_pagina' => $size,
                'data_vencimento_de' => $dateFrom,
                'data_vencimento_ate' => $dateTo,
                'ids_centros_de_custo' => $costCenterId
            ];
            $params[] = [
                'page' => $page,
                'size' => $size,
                'data_vencimento_de' => $dateFrom,
                'data_vencimento_ate' => $dateTo,
                'ids_centros_de_custo' => $costCenterId
            ];
            $params[] = [
                'pagina' => $page,
                'tamanho_pagina' => $size,
                'data_vencimento_de' => $dateFrom,
                'data_vencimento_ate' => $dateTo,
                'centro_de_custo_id' => $costCenterId
            ];
            $params[] = [
                'pagina' => $page,
                'tamanho_pagina' => $size,
                'data_vencimento_de' => $dateFrom,
                'data_vencimento_ate' => $dateTo,
                'centroDeCustoId' => $costCenterId
            ];
        } else {
            $params[] = [
                'pagina' => $page,
                'tamanho_pagina' => $size,
                'data_vencimento_de' => $dateFrom,
                'data_vencimento_ate' => $dateTo
            ];
            $params[] = [
                'page' => $page,
                'size' => $size,
                'data_vencimento_de' => $dateFrom,
                'data_vencimento_ate' => $dateTo
            ];
        }

        return $params;
    }

    private function getWithFallback($endpoints, $paramSets)
    {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        $last = ["ok" => false, "status" => 0, "data" => null, "body" => ""];
        foreach ($endpoints as $baseUrl) {
            foreach ($paramSets as $params) {
                $url = $baseUrl;
                if (!empty($params)) {
                    $url .= '?' . http_build_query($params);
                }
                $resp = $this->getRequest($url, $headers);
                if ($resp["ok"]) {
                    return $resp;
                }
                $last = $resp;
            }
        }

        return $last;
    }

    private function resolvePath($path, array $pathParams)
    {
        $resolvedPath = $path;

        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $matches);
        foreach ($matches[1] ?? [] as $placeholder) {
            $value = trim((string) ($pathParams[$placeholder] ?? ''));
            if ($value === '') {
                return ["ok" => false, "status" => 0, "data" => null, "body" => "Parametro de caminho ausente: {$placeholder}"];
            }

            $resolvedPath = str_replace('{' . $placeholder . '}', rawurlencode($value), $resolvedPath);
        }

        return [
            "ok" => true,
            "status" => 200,
            "data" => null,
            "body" => '',
            "path" => $resolvedPath
        ];
    }

    /**
     * Cria pessoa/cliente no Conta Azul.
     */
    public function createPerson($data)
    {
        $url = self::API_BASE . '/v1/pessoas';
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        return $this->postJson($url, $data, $headers);
    }

    /**
     * Atualiza pessoa/cliente no Conta Azul.
     */
    public function updatePerson($id, $data)
    {
        $url = self::API_BASE . '/v1/pessoas/' . $id;
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        return $this->putJson($url, $data, $headers);
    }

    /**
     * Exclui pessoa/cliente no Conta Azul.
     */
    public function deletePerson($id)
    {
        $url = self::API_BASE . '/v1/pessoas/' . $id;
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        return $this->deleteRequest($url, $headers);
    }

    private function postJson($url, $data, $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $this->applyCaInfo($ch);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ["ok" => false, "status" => 0, "data" => null, "body" => $error];
        }

        $decoded = json_decode($response, true);
        $ok = $httpCode >= 200 && $httpCode < 300;
        return ["ok" => $ok, "status" => $httpCode, "data" => $decoded, "body" => $response];
    }

    private function putJson($url, $data, $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $this->applyCaInfo($ch);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ["ok" => false, "status" => 0, "data" => null, "body" => $error];
        }

        $decoded = json_decode($response, true);
        $ok = $httpCode >= 200 && $httpCode < 300;
        return ["ok" => $ok, "status" => $httpCode, "data" => $decoded, "body" => $response];
    }

    private function deleteRequest($url, $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $this->applyCaInfo($ch);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ["ok" => false, "status" => 0, "data" => null, "body" => $error];
        }

        $decoded = json_decode($response, true);
        $ok = $httpCode >= 200 && $httpCode < 300;
        return ["ok" => $ok, "status" => $httpCode, "data" => $decoded, "body" => $response];
    }

    private function applyCaInfo($ch)
    {
        $ca = ini_get('curl.cainfo');
        if (!$ca) {
            $ca = ini_get('openssl.cafile');
        }
        if ($ca && is_file($ca)) {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
            return;
        }

        $fallbacks = [
            'C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\extras\\ssl\\cacert.pem',
            'C:\\laragon\\bin\\php\\php-8.2.12-Win32-vs16-x64\\extras\\ssl\\cacert.pem',
            'C:\\laragon\\bin\\php\\php-8.3.0-Win32-vs16-x64\\extras\\ssl\\cacert.pem',
            'C:\\laragon\\etc\\ssl\\cacert.pem',
            'D:\\Projects\\Laragon-installer\\8.0-W64\\etc\\ssl\\cacert.pem',
            'D:\\Projects\\Laragon-installer\\8.0-W64\\extras\\ssl\\cacert.pem',
        ];

        foreach ($fallbacks as $path) {
            if (is_file($path)) {
                curl_setopt($ch, CURLOPT_CAINFO, $path);
                return;
            }
        }
    }
}

