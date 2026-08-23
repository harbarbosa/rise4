<?php

namespace OrdemServico\Libraries;

/** Backend-only EuGestor HTTP client. It never exposes credentials or tokens. */
class EuGestorClient
{
    public const API_BASE = 'https://api.eugestor.insidesistemas.com.br/api';
    public const PORTAL_API_BASE = 'https://portal-eugestor.azurewebsites.net/api';
    public const GRAPHQL_URL = 'https://api.eugestor.insidesistemas.com.br/graphql';

    private EuGestorSettings $settings;
    private $transport;

    public function __construct(?EuGestorSettings $settings = null, ?callable $transport = null)
    {
        $this->settings = $settings ?: new EuGestorSettings();
        $this->transport = $transport;
    }

    public function testConnection(): array
    {
        $this->authenticate(true);
        $result = $this->request('GET', '/ordens-servico', ['pagina' => 1, 'tamanhoPagina' => 1, 'status' => 0]);
        return ['success' => $result['status'] >= 200 && $result['status'] < 300, 'status' => $result['status']];
    }

    public function listOpenOrders(): array
    {
        $page = 1;
        $size = 100;
        $all = [];
        $total = null;
        do {
            $result = $this->request('GET', '/ordens-servico', [
                'pagina' => $page,
                'tamanhoPagina' => $size,
                'status' => 0,
                'exibirCanceladas' => 'true',
                'exibirApenasImportadas' => 'false',
            ]);
            $this->assertSuccess($result);
            $items = $this->extractItems($result['data']);
            $all = array_merge($all, $items);
            $total = $this->extractTotal($result['data']);
            $page++;
            if (count($items) === 0 || $page > 1000) { break; }
        } while ($total === null ? count($items) >= $size : count($all) < $total);
        return $all;
    }

    public function getOrderDetails(int $id): array
    {
        $query = 'query getResumoOrdemServico($ordemServicoId: Long!) { ordemServico(query: { ordemServicoId: $ordemServicoId }) { ordemServicoId numeroOrdemServico statusOrdemServico dataAbertura dataPrevisaoFechamento } }';
        $result = $this->requestGraphql([
            'operationName' => 'getResumoOrdemServico',
            'variables' => ['ordemServicoId' => $id],
            'query' => $query,
        ]);
        $this->assertSuccess($result);
        return (array)($result['data']['data']['ordemServico'] ?? []);
    }

    private function authenticate(bool $force = false): string
    {
        $token = $this->settings->getAccessToken();
        if (!$force && $token !== '' && $this->settings->getTokenExpiresAt() > time() + 30) { return $token; }
        $username = $this->settings->getUsername();
        $password = $this->settings->getPassword();
        if ($username === '' || $password === '') { throw new \RuntimeException('Credenciais EuGestor não configuradas.'); }

        // O login observado no portal envia inicialmente apenas estas credenciais.
        $domain = $this->settings->getDomain();
        $result = $this->rawRequest('POST', self::API_BASE . '/auth', [], [
            'email' => $username, 'senha' => $password, 'isLembrarSessao' => true,
        ]);
        $token = $this->extractToken($result['data']);

        // O portal pode devolver a lista de clientes antes de emitir o token.
        if ($token === '' && $result['status'] >= 200 && $result['status'] < 300) {
            $clientId = $this->findClientId($result['data']);
            if ($clientId !== '') {
                $result = $this->rawRequest('POST', self::API_BASE . '/auth', [], [
                    'email' => $username, 'senha' => $password, 'empresaId' => $clientId, 'isLembrarSessao' => true,
                ]);
                $token = $this->extractToken($result['data']);
            }
        }

        // A versão atual do portal usa um endpoint separado para listar os
        // clientes/empresas disponíveis antes de emitir o token.
        // Se /api/auth respondeu, preservamos essa resposta. O endpoint
        // alternativo só deve ser usado quando a rota principal não existe.
        if ($token === '' && (int)$result['status'] === 404) {
            $facilitated = $this->rawRequest('POST', self::PORTAL_API_BASE . '/v2/autenticacao/acesso-facilitado', [], [
                'email' => $username, 'senha' => $password, 'dominio' => $domain,
            ]);
            $clientId = $this->findClientId($facilitated['data']);
            if ($clientId !== '') {
                $result = $this->rawRequest('POST', self::PORTAL_API_BASE . '/v2/autenticacao', [], [
                    'email' => $username, 'senha' => $password, 'empresaId' => $clientId, 'isLembrarSessao' => true,
                ]);
                $token = $this->extractToken($result['data']);
            } else {
                $result = $facilitated;
            }
        }

        // Compatibilidade com instalações que aceitam password em vez de senha.
        if ($token === '') {
            $result = $this->rawRequest('POST', self::API_BASE . '/auth', [], ['email' => $username, 'password' => $password]);
            $token = $this->extractToken($result['data']);
        }
        if ($result['status'] < 200 || $result['status'] >= 300 || $token === '') {
            if ((int)$result['status'] === 401) {
                throw new \RuntimeException('Credenciais inválidas no EuGestor. HTTP 401');
            }
            $apiMessage = $this->findValue($result['data'], ['message', 'error', 'detail']);
            $suffix = $apiMessage !== '' ? ' Mensagem da API: ' . (string)$apiMessage : '';
            throw new \RuntimeException('Não foi possível autenticar no EuGestor. HTTP ' . (int)$result['status'] . '. Verifique e-mail, senha e domínio.' . $suffix);
        }
        $refresh = $this->findValue($result['data'], ['refresh_token', 'refreshToken']);
        $expiresIn = (int)$this->findValue($result['data'], ['expires_in', 'expiresIn']);
        $this->settings->saveSession($token, time() + ($expiresIn > 0 ? $expiresIn : 840), $refresh);
        return $token;
    }

    private function request(string $method, string $path, array $query = []): array
    {
        $token = $this->authenticate();
        $result = $this->rawRequest($method, self::API_BASE . $path, ['Authorization: Bearer ' . $token], $query);
        if ((int)$result['status'] === 401) {
            $this->settings->clearSession();
            $token = $this->authenticate(true);
            $result = $this->rawRequest($method, self::API_BASE . $path, ['Authorization: Bearer ' . $token], $query);
        }
        return $result;
    }

    private function requestGraphql(array $body): array
    {
        $token = $this->authenticate();
        $result = $this->rawRequest('POST', self::GRAPHQL_URL, ['Authorization: Bearer ' . $token], $body);
        if ((int)$result['status'] === 401) {
            $this->settings->clearSession();
            $token = $this->authenticate(true);
            $result = $this->rawRequest('POST', self::GRAPHQL_URL, ['Authorization: Bearer ' . $token], $body);
        }
        return $result;
    }

    private function rawRequest(string $method, string $url, array $headers, array $payload): array
    {
        if ($this->transport) {
            return call_user_func($this->transport, $method, $url, $headers, $payload);
        }
        $ch = curl_init($url);
        $isGet = strtoupper($method) === 'GET';
        if ($isGet && $payload) { curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($payload)); }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method), CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
            CURLOPT_HTTPHEADER => array_merge([
                'Accept: application/json',
                'Origin: https://portal.eugestor.io',
                'Referer: https://portal.eugestor.io/',
            ], $isGet ? [] : ['Content-Type: application/json'], $headers),
        ]);
        if (!$isGet) { curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload)); }
        $raw = curl_exec($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $error = curl_error($ch); curl_close($ch);
        if ($raw === false || $error !== '') { throw new \RuntimeException('EuGestor indisponível.'); }
        $decoded = json_decode((string)$raw, true);
        return ['status' => $status, 'data' => is_array($decoded) ? $decoded : []];
    }

    private function assertSuccess(array $result): void
    {
        if ((int)$result['status'] === 403) { throw new \RuntimeException('Acesso negado pelo EuGestor.'); }
        if ((int)$result['status'] < 200 || (int)$result['status'] >= 300) { throw new \RuntimeException('Erro HTTP do EuGestor: ' . (int)$result['status']); }
    }

    private function extractItems(array $data): array
    {
        if ($this->isList($data)) { return $data; }
        foreach (['lista', 'ordensServico', 'ordensServicoList', 'items', 'content', 'results', 'list', 'rows', 'data'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $items = $this->extractItems($data[$key]);
                if ($items) { return $items; }
            }
        }
        return [];
    }

    private function extractTotal(array $data): ?int
    {
        foreach (['totalRegistros', 'total', 'totalElements', 'quantidadeTotal', 'count'] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) { return (int)$data[$key]; }
        }
        return null;
    }

    private function findValue($data, array $keys)
    {
        if (!is_array($data)) { return ''; }
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && is_scalar($data[$key])) { return $data[$key]; }
        }
        foreach ($data as $value) {
            if (is_array($value)) { $found = $this->findValue($value, $keys); if ($found !== '') { return $found; } }
        }
        return '';
    }

    private function extractToken($data): string
    {
        $token = $this->findValue($data, ['data', 'access_token', 'accessToken', 'token', 'jwt']);
        return is_scalar($token) ? trim((string)$token) : '';
    }

    private function findClientId($data): string
    {
        if (!is_array($data)) { return ''; }
        foreach (['clientes', 'clients', 'contas', 'empresas', 'data'] as $container) {
            if (!isset($data[$container]) || !is_array($data[$container])) { continue; }
            $values = $this->isList($data[$container]) ? $data[$container] : [$data[$container]];
            foreach ($values as $value) {
                if (!is_array($value)) { continue; }
                $id = $this->findValue($value, ['cliente_id', 'clienteId', 'ClienteId']);
                if ($id !== '') { return (string)$id; }
                $id = $this->findValue($value, ['id']);
                if ($id !== '') { return (string)$id; }
            }
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                $id = $this->findClientId($value);
                if ($id !== '') { return $id; }
            }
        }
        return '';
    }

    private function isList(array $data): bool
    {
        if (!$data) { return true; }
        return array_keys($data) === range(0, count($data) - 1);
    }
}
