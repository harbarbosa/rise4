<?php

namespace Frota\Controllers;

use App\Controllers\Security_Controller;

class Fipe extends Security_Controller
{
    private array $allowedTypes = ['carros', 'motos', 'caminhoes'];

    public function __construct()
    {
        parent::__construct();
        helper('frota');
    }

    private function ensureAccess()
    {
        if (!frota_can_access($this->login_user ?? null)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Acesso negado.'
            ]);
        }

        return null;
    }

    private function type(): string
    {
        $type = strtolower(trim((string)$this->request->getGet('tipo')));
        return in_array($type, $this->allowedTypes, true) ? $type : 'carros';
    }

    private function requestJson(string $url, string $cacheKey): array
    {
        $cache = service('cache');
        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $client = service('curlrequest');
            $response = $client->get($url, [
                'timeout' => 10,
                'connect_timeout' => 5,
                'http_errors' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'AlfaHP-Rise-Frota/1.0'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('BrasilAPI HTTP ' . $response->getStatusCode());
            }

            $data = json_decode((string)$response->getBody(), true);
            if (!is_array($data)) {
                throw new \RuntimeException('Resposta inválida da BrasilAPI');
            }

            $cache->save($cacheKey, $data, 43200);
            return $data;
        } catch (\Throwable $e) {
            log_message('error', '[Frota/FIPE] ' . $e->getMessage());
            throw $e;
        }
    }

    public function marcas()
    {
        if ($response = $this->ensureAccess()) {
            return $response;
        }

        $type = $this->type();

        try {
            $rows = $this->requestJson(
                'https://brasilapi.com.br/api/fipe/marcas/v1/' . $type,
                'frota_fipe_marcas_' . $type
            );

            $data = [];
            foreach ($rows as $row) {
                $name = trim((string)($row['nome'] ?? $row['name'] ?? ''));
                $code = trim((string)($row['valor'] ?? $row['value'] ?? ''));
                if ($name !== '' && $code !== '') {
                    $data[] = ['id' => $code, 'text' => $name];
                }
            }

            return $this->response->setJSON(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(503)->setJSON([
                'success' => false,
                'message' => 'Não foi possível consultar as marcas agora. Digite a marca manualmente.'
            ]);
        }
    }

    public function modelos()
    {
        if ($response = $this->ensureAccess()) {
            return $response;
        }

        $type = $this->type();
        $brandCode = preg_replace('/\D+/', '', (string)$this->request->getGet('marca'));
        if (!$brandCode) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Marca não informada.'
            ]);
        }

        try {
            $rows = $this->requestJson(
                'https://brasilapi.com.br/api/fipe/veiculos/v1/' . $type . '/' . $brandCode,
                'frota_fipe_modelos_' . $type . '_' . $brandCode
            );

            $data = [];
            foreach ($rows as $row) {
                $model = trim((string)($row['modelo'] ?? $row['model'] ?? ''));
                if ($model !== '') {
                    $data[] = ['id' => $model, 'text' => $model];
                }
            }

            return $this->response->setJSON(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(503)->setJSON([
                'success' => false,
                'message' => 'Não foi possível consultar os modelos agora. Digite o modelo manualmente.'
            ]);
        }
    }
}
