<?php

namespace OrdemServico\Libraries;

use OrdemServico\Models\OrdemServico_model;

class EuGestorSyncService
{
    private EuGestorClient $client;
    private EuGestorSettings $settings;

    public function __construct(?EuGestorClient $client = null, ?EuGestorSettings $settings = null)
    {
        $this->settings = $settings ?: new EuGestorSettings();
        $this->client = $client ?: new EuGestorClient($this->settings);
    }

    public function testConnection(): array
    {
        return $this->client->testConnection();
    }

    public function syncOpenOrders(): array
    {
        if (!$this->settings->isEnabled()) { throw new \RuntimeException('A integração EuGestor está desabilitada.'); }
        $orders = $this->client->listOpenOrders();
        $db = db_connect('default');
        $syncTable = $db->prefixTable('eugestor_ordens_servico_sync');
        $logsTable = $db->prefixTable('eugestor_sync_logs');
        $osModel = new OrdemServico_model($db);
        $result = ['success' => true, 'found' => count($orders), 'created' => 0, 'updated' => 0, 'errors' => []];

        foreach ($orders as $remote) {
            $externalId = (int)$this->value($remote, ['ordemServicoId', 'id']);
            if (!$externalId) { $result['errors'][] = 'OS sem identificador externo ignorada.'; continue; }
            try {
                $mapped = $this->mapOrder($remote, $externalId);
                $sync = $db->table($syncTable)->where('eugestor_ordem_servico_id', $externalId)->get()->getRow();
                $riseId = (int)($sync->risecrm_ordem_servico_id ?? 0);
                if (!$riseId) {
                    $existing = $db->table($db->prefixTable('os_ordens'))->where('eugestor_ordem_servico_id', $externalId)->where('deleted', 0)->get()->getRow();
                    $riseId = (int)($existing->id ?? 0);
                }
                $clientId = $this->resolveClient($remote, $externalId);
                $mapped['cliente_id'] = $clientId;
                $mapped['tipo_id'] = $this->resolveType();
                $mapped['motivo_id'] = $this->resolveReason($remote);
                $hash = hash('sha256', json_encode($mapped, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $wasExisting = $riseId > 0;
                $saved = $osModel->save_from_post($mapped, $riseId);
                if ($saved === false) { throw new \RuntimeException('Falha ao salvar OS no RiseCRM.'); }
                $riseId = $riseId ?: (int)$db->insertID();
                $this->syncAppointment($riseId, $remote, $mapped);
                $now = get_my_local_time();
                $syncData = [
                    'risecrm_ordem_servico_id' => $riseId,
                    'eugestor_ordem_servico_id' => $externalId,
                    'numero_ordem_servico' => $mapped['eugestor_numero_ordem_servico'],
                    'status_eugestor' => $mapped['eugestor_status'],
                    'data_ultima_sincronizacao' => $now,
                    'hash_dados' => $hash,
                    'updated_at' => $now,
                ];
                if ($sync) { $db->table($syncTable)->where('id', $sync->id)->update($syncData); }
                else { $syncData['created_at'] = $now; $db->table($syncTable)->insert($syncData); }
                $result[$wasExisting ? 'updated' : 'created']++;
            } catch (\Throwable $e) {
                if (count($result['errors']) < 20) { $result['errors'][] = 'EuGestor OS #' . $externalId . ': ' . $this->safeError($e->getMessage()); }
            }
        }
        $result['success'] = count($result['errors']) === 0;
        $now = get_my_local_time();
        $this->settings->saveSyncResult($now, $result);
        $db->table($logsTable)->insert([
            'success' => $result['success'] ? 1 : 0,
            'found' => $result['found'], 'created' => $result['created'], 'updated' => $result['updated'],
            'errors_count' => count($result['errors']), 'message' => $result['success'] ? 'Sincronização concluída.' : 'Sincronização concluída com erros.', 'created_at' => $now,
        ]);
        return $result;
    }

    private function mapOrder(array $remote, int $externalId): array
    {
        $number = (string)$this->value($remote, ['numeroOrdemServico', 'numero', 'orderNumber']);
        $person = $this->value($remote, ['pessoa', 'cliente', 'customer', 'clienteNome', 'nomeCliente', 'nomePrincipal']);
        $name = is_array($person) ? (string)$this->value($person, ['nomePrincipal', 'nome', 'name', 'razaoSocial', 'nomeFantasia']) : (string)$person;
        if ($name === '') { $name = (string)$this->nestedValue($remote, ['clienteNome', 'nomeCliente', 'nomePrincipal', 'razaoSocial', 'nomeFantasia']); }
        $address = $this->value($remote, ['endereco', 'address']);
        $addressText = is_array($address) ? implode(', ', array_filter([(string)$this->value($address, ['logradouro', 'street']), (string)$this->value($address, ['numero', 'number']), (string)$this->value($address, ['cidade', 'city'])])) : (string)$address;
        $defectValue = $this->value($remote, ['defeito', 'description', 'descricao']);
        if ($defectValue === '') { $defectValue = $this->nestedValue($remote, ['defeito', 'description', 'descricao']); }
        if (is_array($defectValue)) { $defectValue = $this->value($defectValue, ['descricao', 'description', 'nome', 'title']); }
        $defect = trim((string)$defectValue);
        $reason = $this->value($remote, ['motivo', 'motivoNome', 'nomeMotivo', 'descricaoMotivo', 'motivoOrdemServico']);
        if ($reason === '') { $reason = $this->nestedValue($remote, ['motivoNome', 'nomeMotivo', 'descricaoMotivo', 'motivoOrdemServico']); }
        if (is_array($reason)) { $reason = $this->value($reason, ['title', 'nome', 'descricao', 'name']); }
        $reason = trim((string)$reason);
        $status = (string)$this->value($remote, ['statusOrdemServico', 'status']);
        $opened = $this->dateValue($this->value($remote, ['dataAbertura', 'openedAt']));
        $subject = trim(implode(' - ', array_filter([$reason, trim($defect)])));
        $title = '#OS' . ($number ?: $externalId);
        if ($subject !== '') { $title .= ' - ' . $subject; }
        return [
            'titulo' => $title,
            'descricao' => trim($subject . ($addressText ? "\n\nEndereço: " . $addressText : '')),
            'status' => 'aberta',
            'data_abertura' => $opened ?: date('Y-m-d'),
            'eugestor_ordem_servico_id' => $externalId,
            'eugestor_numero_ordem_servico' => $number,
            'eugestor_identificador_contrato' => (string)$this->value($remote, ['identificadorContrato', 'contractId']),
            'eugestor_status' => $status,
            'eugestor_cliente_nome' => $name,
        ];
    }

    private function resolveClient(array $remote, int $externalId): int
    {
        $db = db_connect('default'); $table = $db->prefixTable('clients');
        $person = $this->value($remote, ['pessoa', 'cliente', 'customer']);
        $name = trim((string)(is_array($person) ? $this->value($person, ['nomePrincipal', 'nome', 'name']) : $person));
        if ($name === '') { $name = 'Cliente EuGestor #' . $externalId; }
        $normalized = $this->normalizeName($name);
        $candidates = $db->table($table)->select('id, company_name')->where('deleted', 0)->get()->getResult();
        $bestId = 0; $bestScore = 0.0;
        foreach ($candidates as $candidate) {
            $candidateName = $this->normalizeName((string)$candidate->company_name);
            if ($candidateName === '' || $normalized === '') { continue; }
            if ($candidateName === $normalized) { return (int)$candidate->id; }
            $score = 0.0;
            if (strlen($normalized) >= 5 && strlen($candidateName) >= 5 && (strpos($candidateName, $normalized) !== false || strpos($normalized, $candidateName) !== false)) {
                $score = 0.90;
            } else {
                similar_text($normalized, $candidateName, $percent);
                $score = ((float)$percent) / 100;
            }
            if ($score > $bestScore) { $bestScore = $score; $bestId = (int)$candidate->id; }
        }
        if ($bestId && $bestScore >= 0.86) { return $bestId; }
        $db->table($table)->insert(['company_name' => $name, 'type' => 'organization', 'is_lead' => 0, 'created_date' => get_my_local_time(), 'deleted' => 0]);
        return (int)$db->insertID();
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') { return ''; }
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($converted !== false) { $name = $converted; }
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    private function resolveType(): int
    {
        return $this->resolveCatalog('os_tipos', 'title', 'Integração EuGestor');
    }

    private function resolveReason(array $remote = []): int
    {
        $reason = $this->value($remote, ['motivo', 'motivoNome', 'nomeMotivo', 'descricaoMotivo', 'motivoOrdemServico']);
        if (is_array($reason)) { $reason = $this->value($reason, ['title', 'nome', 'descricao', 'name']); }
        $reason = trim((string)$reason);
        return $this->resolveCatalog('os_motivos', 'title', $reason ?: 'Importada do EuGestor');
    }

    private function resolveCatalog(string $logicalTable, string $field, string $name): int
    {
        $db = db_connect('default'); $table = $db->prefixTable($logicalTable);
        $existing = $db->table($table)->where($field, $name)->where('deleted', 0)->get()->getRow();
        if ($existing) { return (int)$existing->id; }
        $db->table($table)->insert([$field => $name, 'deleted' => 0]);
        return (int)$db->insertID();
    }

    private function value($data, array $keys)
    {
        if (!is_array($data)) { return ''; }
        foreach ($keys as $key) { if (array_key_exists($key, $data)) { return $data[$key]; } }
        return '';
    }

    private function nestedValue($data, array $keys)
    {
        $value = $this->value($data, $keys);
        if ($value !== '' && $value !== null) { return $value; }
        if (!is_array($data)) { return ''; }
        foreach ($data as $child) {
            if (is_array($child)) {
                $value = $this->nestedValue($child, $keys);
                if ($value !== '' && $value !== null) { return $value; }
            }
        }
        return '';
    }

    private function syncAppointment(int $riseId, array $remote, array $mapped): void
    {
        $scheduled = $this->nestedValue($remote, [
            'dataHoraAgendamento', 'dataAgendamento', 'dataAgenda', 'dataAtendimento',
            'dataVisita', 'dataPrevisaoAtendimento', 'inicioAgendamento', 'dataHoraInicio',
            'dataInicio', 'inicio', 'scheduledAt',
        ]);
        $start = $this->dateTimeValue($scheduled);
        if ($start === '') { return; }

        $end = $this->dateTimeValue($this->nestedValue($remote, [
            'fimAgendamento', 'dataFimAgendamento', 'fimAtendimento', 'dataHoraFim', 'dataFim', 'fim', 'end_datetime', 'scheduledEnd',
        ]));
        if ($end === '') { $end = date('Y-m-d H:i:s', strtotime($start . ' +30 minutes')); }

        $db = db_connect('default');
        $table = $db->prefixTable('os_atendimentos');
        $existing = $db->table($table)->where('os_id', $riseId)->where('start_datetime', $start)->where('deleted', 0)->get()->getRow();
        $data = [
            'os_id' => $riseId,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'notes' => 'Agendamento importado do EuGestor.' . (!empty($mapped['eugestor_status']) ? ' Status: ' . $mapped['eugestor_status'] : ''),
            'updated_at' => get_my_local_time(),
        ];
        if ($existing) { $db->table($table)->where('id', $existing->id)->update($data); }
        else { $data['created_at'] = get_my_local_time(); $db->table($table)->insert($data); }
    }

    private function dateValue($value): string
    {
        if (!$value) { return ''; }
        $timestamp = strtotime((string)$value);
        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    private function dateTimeValue($value): string
    {
        if (!$value) { return ''; }
        $timestamp = strtotime((string)$value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    private function safeError(string $message): string
    {
        return preg_replace('/(authorization|bearer|token|senha|password)\s*[:=]\s*[^\s,;]+/i', '$1=[redacted]', $message);
    }
}
