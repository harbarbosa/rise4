<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;
use GuzzleHttp\Client;

class Laudo_devices_model extends Crud_model
{
    protected $table = 'laudo_devices';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function register_device($user_id, $device_uuid, $access_token, $refresh_token)
    {
        // Verificar se dispositivo já existe
        $existing = $this->get_by_uuid($device_uuid, $user_id);
        
        if ($existing) {
            // Atualizar tokens
            return parent::ci_save([
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'refresh_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'last_access_at' => get_my_local_time(),
                'is_active' => 1
            ], $existing->id);
        }
        
        // Criar novo
        $data = [
            'user_id' => $user_id,
            'device_uuid' => $device_uuid,
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'refresh_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'is_active' => 1,
            'created_at' => get_my_local_time()
        ];
        
        return parent::ci_save($data, 0);
    }

    public function get_by_uuid($device_uuid, $user_id = null)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE device_uuid='$device_uuid'";
        if ($user_id) {
            $sql .= " AND user_id=$user_id";
        }
        $sql .= " LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function validate_token($token)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE access_token='$token' AND is_active=1 AND is_revoked=0 LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function validate_refresh($refresh_token)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE refresh_token='$refresh_token' AND is_active=1 AND is_revoked=0 
            AND (refresh_expires_at IS NULL OR refresh_expires_at > NOW()) LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function refresh_tokens($id, $access_token, $refresh_token)
    {
        $data = [
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'refresh_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ];
        return parent::ci_save($data, $id) ? true : false;
    }

    public function update_last_access($id)
    {
        $table = $this->db->prefixTable($this->table);
        $this->db->query("UPDATE $table SET last_access_at=NOW() WHERE id=$id");
    }

    public function revoke_device($device_uuid)
    {
        $table = $this->db->prefixTable($this->table);
        $this->db->query("UPDATE $table SET is_revoked=1, is_active=0 WHERE device_uuid='$device_uuid'");
    }

    public function revoke_all_user($user_id)
    {
        $table = $this->db->prefixTable($this->table);
        $this->db->query("UPDATE $table SET is_revoked=1, is_active=0 WHERE user_id=$user_id");
    }
}

class Laudo_offline_model extends Crud_model
{
    protected $table = 'laudo_offline_records';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function register_change($device_uuid, $user_id, $table_name, $record_id, $operation, $data)
    {
        $uuid = $this->_generate_uuid();
        
        $record_data = [
            'device_uuid' => $device_uuid,
            'user_id' => $user_id,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'record_uuid' => $uuid,
            'operation' => $operation,
            'local_data' => json_encode($data),
            'local_created_at' => get_my_local_time(),
            'local_updated_at' => get_my_local_time(),
            'version' => 1,
            'sync_status' => 'pending',
            'hash' => hash('sha256', json_encode($data)),
            'created_at' => get_my_local_time()
        ];
        
        return parent::ci_save($record_data, 0);
    }

    public function get_changes($device_uuid, $since = null)
    {
        $table = $this->db->prefixTable($this->table);
        
        $sql = "SELECT * FROM $table WHERE device_uuid='$device_uuid' AND sync_status='pending'";
        
        if ($since) {
            $sql .= " AND local_updated_at > '$since'";
        }
        
        $sql .= " ORDER BY local_updated_at ASC";
        
        return $this->db->query($sql)->getResult();
    }

    public function process_changes($device_uuid, $user_id, $changes)
    {
        $results = [];
        
        foreach ($changes as $change) {
            try {
                $result = $this->_process_single_change($user_id, $change);
                $results[] = [
                    'local_id' => $change['local_id'] ?? null,
                    'success' => true,
                    'server_id' => $result
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'local_id' => $change['local_id'] ?? null,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    private function _process_single_change($user_id, $change)
    {
        $table_name = $change['table'];
        $operation = $change['operation'];
        $data = $change['data'];
        
        // Obter model adequado
        $model = $this->_get_model($table_name);
        
        if (!$model) {
            throw new \Exception("Tabela não suportada: $table_name");
        }
        
        // Verificar conflitos
        if ($operation === 'update') {
            $existing = $model->get_one($data['id']);
            if ($existing && $existing->updated_at > $change['client_updated_at']) {
                // Conflito - marcar para resolução
                $this->_mark_conflict($change, $existing);
                throw new \Exception('Conflito de sincronização');
            }
        }
        
        // Executar operação
        if ($operation === 'insert') {
            unset($data['id']); // Remover ID local
            $data['created_by'] = $user_id;
            return $model->save($data, 0);
        } elseif ($operation === 'update') {
            return $model->save($data, $data['id']);
        } elseif ($operation === 'delete') {
            return $model->delete($data['id']);
        }
        
        return null;
    }

    private function _get_model($table_name)
    {
        $models = [
            'laudos_tecnicos' => 'LaudosTecnicos\Models\Laudos_model',
            'laudo_sections' => 'LaudosTecnicos\Models\Laudo_sections_model',
            'laudo_inspections' => 'LaudosTecnicos\Models\Laudo_inspections_model',
            'laudo_checklist_answers' => 'LaudosTecnicos\Models\Laudo_checklist_answers_model',
            'laudo_non_conformities' => 'LaudosTecnicos\Models\Laudo_non_conformities_model'
        ];
        
        if (isset($models[$table_name])) {
            return model($models[$table_name]);
        }
        
        return null;
    }

    private function _mark_conflict($change, $existing)
    {
        $table = $this->db->prefixTable($this->table);
        
        $this->db->query("UPDATE $table SET 
            sync_status='conflict', 
            conflict_data='" . json_encode($existing) . "' 
            WHERE record_id={$change['data']['id']} 
            AND table_name='{$change['table']}'");
    }

    private function _generate_uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

class Laudo_api_logs_model extends Crud_model
{
    protected $table = 'laudo_api_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log($user_id, $method, $endpoint, $status, $extra = [])
    {
        $data = [
            'user_id' => $user_id,
            'method' => $method,
            'endpoint' => $endpoint,
            'response_status' => $status,
            'ip_address' => $extra['ip'] ?? '',
            'user_agent' => $extra['user_agent'] ?? '',
            'created_at' => get_my_local_time()
        ];
        
        // Inserir de forma assíncrona em produção
        if (rand(1, 10) === 1) { // 10% de chance de logar para performance
            return parent::ci_save($data, 0);
        }
        return true;
    }
}

class Laudo_ai_model extends Crud_model
{
    protected $table = 'laudo_ai_config';
    protected $usage_table = 'laudo_ai_usage';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_active_config()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE is_active=1 LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function generate($feature, $prompt, $user_id)
    {
        $config = $this->get_active_config();
        
        if (!$config) {
            throw new \Exception('IA não configurada');
        }
        
        // Verificar limite
        if ($config->current_usage >= $config->monthly_limit) {
            throw new \Exception('Limite de uso mensal excedido');
        }
        
        // Construir prompt completo
        $full_prompt = $this->_build_prompt($feature, $prompt, $config);
        
        // Chamar API
        $response = $this->_call_api($config, $full_prompt);
        
        // Registrar uso
        $this->_log_usage($config->id, $user_id, $feature, $prompt, $response, $config->model);
        
        // Atualizar contador
        $this->update_usage($config->id);
        
        return $response;
    }

    private function _build_prompt($feature, $prompt, $config)
    {
        $system_prompt = $config->system_prompt ?? 'Você é um assistente técnico especializado em laudos técnicos e normas regulamentadoras.';
        
        // Adicionar contexto do recurso
        $feature_prompts = [
            'organize_notes' => 'Organize as anotações abaixo de forma clara e profissional:',
            'improve_text' => 'Melhore o texto técnico mantendo a precisão:',
            'create_objective' => 'Elabore um objetivo claro e objetivo:',
            'create_scope' => 'Defina o escopo da inspeção:',
            'create_methodology' => 'Descreva a metodologia aplicada:',
            'create_diagnosis' => 'Elabore o diagnóstico técnico:',
            'create_conclusion' => 'Redija a conclusão técnica:',
            'create_recommendations' => 'Sugira recomendações técnicas:',
            'executive_summary' => 'Crie um resumo executivo:',
            'describe_photo' => 'Descreva a imagem de forma técnica:',
            'check_gaps' => 'Identifique possíveis lacunas no laudo:',
            'check_inconsistencies' => 'Verifique inconsistências:',
            'suggest_action_plan' => 'Sugira plano de ação:'
        ];
        
        $instruction = $feature_prompts[$feature] ?? 'Analise e responda:';
        
        return [
            'system' => $system_prompt,
            'user' => $instruction . "\n\n" . $prompt
        ];
    }

    private function _call_api($config, $prompt)
    {
        $client = new Client([
            'timeout' => $config->timeout
        ]);
        
        $headers = [
            'Authorization' => 'Bearer ' . $config->api_key,
            'Content-Type' => 'application/json'
        ];
        
        $body = [
            'model' => $config->model,
            'messages' => [
                ['role' => 'system', 'content' => $prompt['system']],
                ['role' => 'user', 'content' => $prompt['user']]
            ],
            'temperature' => (float)$config->temperature,
            'max_tokens' => (int)$config->max_tokens
        ];
        
        try {
            $response = $client->post($config->api_url, [
                'headers' => $headers,
                'json' => $body
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            return $data['choices'][0]['message']['content'] ?? '';
        } catch (\Exception $e) {
            throw new \Exception('Erro na chamada da IA: ' . $e->getMessage());
        }
    }

    private function _log_usage($config_id, $user_id, $feature, $prompt, $response, $model)
    {
        $usage_table = $this->db->prefixTable($this->usage_table);
        
        $sql = "INSERT INTO $usage_table (config_id, user_id, feature, prompt, response, model, created_at) 
            VALUES ($config_id, $user_id, '$feature', '" . esc($prompt) . "', '" . esc($response) . "', '$model', NOW())";
        
        $this->db->query($sql);
    }

    public function update_usage($config_id)
    {
        $table = $this->db->prefixTable($this->table);
        $this->db->query("UPDATE $table SET current_usage = current_usage + 1 WHERE id=$config_id");
    }
}