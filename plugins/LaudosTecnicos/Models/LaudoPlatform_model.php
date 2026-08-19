<?php

namespace LaudosTecnicos\Models;

class LaudoPlatform_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_api_devices';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function upsert_device(array $data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $table = $this->db->prefixTable('laudo_api_devices');
        $uuid = trim((string) get_array_value($data, 'device_uuid'));
        if ($uuid === '') {
            $uuid = laudostecnicos_generate_token(32);
        }

        $payload = array(
            'device_uuid' => $uuid,
            'user_id' => (int) (get_array_value($data, 'user_id') ?: 0),
            'device_name' => trim((string) get_array_value($data, 'device_name')),
            'platform' => trim((string) get_array_value($data, 'platform')),
            'app_version' => trim((string) get_array_value($data, 'app_version')),
            'push_token' => trim((string) get_array_value($data, 'push_token')),
            'last_ip_address' => trim((string) get_array_value($data, 'last_ip_address')),
            'expires_at' => trim((string) get_array_value($data, 'expires_at')),
            'last_seen_at' => trim((string) get_array_value($data, 'last_seen_at')) ?: get_current_utc_time(),
            'last_sync_at' => trim((string) get_array_value($data, 'last_sync_at')),
            'sync_cursor' => trim((string) get_array_value($data, 'sync_cursor')),
            'is_active' => !empty(get_array_value($data, 'is_active')) ? 1 : 1,
            'revoked_at' => trim((string) get_array_value($data, 'revoked_at')),
            'revoked_reason' => trim((string) get_array_value($data, 'revoked_reason')),
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'updated_by' => (int) (get_array_value($data, 'updated_by') ?: 0),
            'deleted' => 0,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
        );

        $existing = $this->db->table($table)->where('device_uuid', $uuid)->where('deleted', 0)->get()->getRow();
        if ($existing && $existing->id) {
            return $this->db->table($table)->where('id', $existing->id)->update($payload) ? (int) $existing->id : false;
        }

        return $this->db->table($table)->insert($payload) ? $this->db->insertID() : false;
    }

    public function get_device_by_uuid(string $uuid)
    {
        $table = $this->db->prefixTable('laudo_api_devices');
        if (!$this->db->tableExists($table) || trim($uuid) === '') {
            return null;
        }

        return $this->db->table($table)->where('device_uuid', trim($uuid))->where('deleted', 0)->get()->getRow();
    }

    public function get_devices_for_user(int $user_id)
    {
        $table = $this->db->prefixTable('laudo_api_devices');
        if (!$this->db->tableExists($table) || !$user_id) {
            return array();
        }

        return $this->db->table($table)
            ->where('user_id', $user_id)
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult() ?: array();
    }

    public function revoke_device(int $device_id, string $reason = '')
    {
        $table = $this->db->prefixTable('laudo_api_devices');
        if (!$this->db->tableExists($table) || !$device_id) {
            return false;
        }

        return $this->db->table($table)->where('id', $device_id)->update(array(
            'is_active' => 0,
            'revoked_at' => get_current_utc_time(),
            'revoked_reason' => trim($reason),
            'updated_at' => get_current_utc_time(),
        ));
    }

    public function create_token(array $data)
    {
        $table = $this->db->prefixTable('laudo_api_tokens');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        $plain_token = trim((string) (get_array_value($data, 'plain_token') ?: laudostecnicos_generate_token(48)));
        $payload = array(
            'user_id' => (int) (get_array_value($data, 'user_id') ?: 0),
            'device_id' => (int) (get_array_value($data, 'device_id') ?: 0),
            'token_type' => trim((string) (get_array_value($data, 'token_type') ?: 'access')),
            'token_hash' => password_hash($plain_token, PASSWORD_DEFAULT),
            'refresh_token_hash' => trim((string) (get_array_value($data, 'refresh_token_hash') ?: '')),
            'expires_at' => trim((string) (get_array_value($data, 'expires_at') ?: '')),
            'refresh_expires_at' => trim((string) (get_array_value($data, 'refresh_expires_at') ?: '')),
            'revoked_at' => trim((string) (get_array_value($data, 'revoked_at') ?: '')),
            'last_used_at' => trim((string) (get_array_value($data, 'last_used_at') ?: '')),
            'scope_json' => laudostecnicos_safe_json(get_array_value($data, 'scope_json', array())),
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'updated_by' => (int) (get_array_value($data, 'updated_by') ?: 0),
            'deleted' => 0,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
        );

        $this->db->table($table)->insert($payload);
        return array('id' => $this->db->insertID(), 'plain_token' => $plain_token);
    }

    public function find_access_token(string $token)
    {
        $table = $this->db->prefixTable('laudo_api_tokens');
        if (!$this->db->tableExists($table) || trim($token) === '') {
            return null;
        }

        $rows = $this->db->table($table)->where('deleted', 0)->where('token_type', 'access')->where('revoked_at', null)->get()->getResult();
        foreach ($rows as $row) {
            if (!empty($row->token_hash) && password_verify($token, (string) $row->token_hash)) {
                return $row;
            }
        }

        return null;
    }

    public function find_refresh_token(string $token)
    {
        $table = $this->db->prefixTable('laudo_api_tokens');
        if (!$this->db->tableExists($table) || trim($token) === '') {
            return null;
        }

        $rows = $this->db->table($table)->where('deleted', 0)->where('token_type', 'refresh')->where('revoked_at', null)->get()->getResult();
        foreach ($rows as $row) {
            if (!empty($row->token_hash) && password_verify($token, (string) $row->token_hash)) {
                return $row;
            }
        }

        return null;
    }

    public function revoke_token(int $token_id, string $reason = '')
    {
        $table = $this->db->prefixTable('laudo_api_tokens');
        if (!$this->db->tableExists($table) || !$token_id) {
            return false;
        }

        return $this->db->table($table)->where('id', $token_id)->update(array(
            'revoked_at' => get_current_utc_time(),
            'revoked_reason' => trim($reason),
            'updated_at' => get_current_utc_time(),
        ));
    }

    public function revoke_user_tokens(int $user_id)
    {
        $table = $this->db->prefixTable('laudo_api_tokens');
        if (!$this->db->tableExists($table) || !$user_id) {
            return false;
        }

        return $this->db->table($table)->where('user_id', $user_id)->update(array(
            'revoked_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
        ));
    }

    public function log_request(array $data)
    {
        $table = $this->db->prefixTable('laudo_api_request_logs');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        return $this->db->table($table)->insert(array(
            'user_id' => (int) (get_array_value($data, 'user_id') ?: 0),
            'device_id' => (int) (get_array_value($data, 'device_id') ?: 0),
            'method' => trim((string) (get_array_value($data, 'method') ?: 'GET')),
            'endpoint' => trim((string) (get_array_value($data, 'endpoint') ?: '')),
            'status_code' => (int) (get_array_value($data, 'status_code') ?: 200),
            'request_json' => get_array_value($data, 'request_json') ?: '',
            'response_json' => get_array_value($data, 'response_json') ?: '',
            'ip_address' => trim((string) (get_array_value($data, 'ip_address') ?: '')),
            'user_agent' => trim((string) (get_array_value($data, 'user_agent') ?: '')),
            'created_at' => get_current_utc_time(),
        ));
    }

    public function save_sync_record(array $data)
    {
        $table = $this->db->prefixTable('laudo_record_syncs');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        $payload = array(
            'entity_type' => trim((string) (get_array_value($data, 'entity_type') ?: '')),
            'entity_id' => (int) (get_array_value($data, 'entity_id') ?: 0),
            'uuid' => trim((string) (get_array_value($data, 'uuid') ?: laudostecnicos_generate_token(32))),
            'local_created_at' => trim((string) (get_array_value($data, 'local_created_at') ?: get_current_utc_time())),
            'local_updated_at' => trim((string) (get_array_value($data, 'local_updated_at') ?: get_current_utc_time())),
            'server_updated_at' => trim((string) (get_array_value($data, 'server_updated_at') ?: get_current_utc_time())),
            'version' => (int) (get_array_value($data, 'version') ?: 1),
            'device_uuid' => trim((string) (get_array_value($data, 'device_uuid') ?: '')),
            'user_id' => (int) (get_array_value($data, 'user_id') ?: 0),
            'sync_status' => trim((string) (get_array_value($data, 'sync_status') ?: 'synced')),
            'record_hash' => trim((string) (get_array_value($data, 'record_hash') ?: '')),
            'payload_json' => get_array_value($data, 'payload_json') ?: '',
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        return $this->db->table($table)->insert($payload) ? $this->db->insertID() : false;
    }

    public function log_ai_usage(array $data)
    {
        $table = $this->db->prefixTable('laudo_ai_usage_logs');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        return $this->db->table($table)->insert(array(
            'prompt_id' => (int) (get_array_value($data, 'prompt_id') ?: 0),
            'user_id' => (int) (get_array_value($data, 'user_id') ?: 0),
            'resource_type' => trim((string) (get_array_value($data, 'resource_type') ?: '')),
            'resource_id' => (int) (get_array_value($data, 'resource_id') ?: 0),
            'provider' => trim((string) (get_array_value($data, 'provider') ?: '')),
            'model' => trim((string) (get_array_value($data, 'model') ?: '')),
            'prompt_text' => trim((string) (get_array_value($data, 'prompt_text') ?: '')),
            'response_text' => trim((string) (get_array_value($data, 'response_text') ?: '')),
            'tokens_input' => (int) (get_array_value($data, 'tokens_input') ?: 0),
            'tokens_output' => (int) (get_array_value($data, 'tokens_output') ?: 0),
            'temperature' => (float) (get_array_value($data, 'temperature') ?: 0),
            'meta_json' => get_array_value($data, 'meta_json') ?: '',
            'created_at' => get_current_utc_time(),
        ));
    }

    public function save_prompt(array $data)
    {
        $table = $this->db->prefixTable('laudo_ai_prompts');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        $payload = array(
            'name' => trim((string) get_array_value($data, 'name')),
            'code' => trim((string) get_array_value($data, 'code')),
            'description' => trim((string) get_array_value($data, 'description')),
            'template_text' => trim((string) get_array_value($data, 'template_text')),
            'category' => trim((string) get_array_value($data, 'category')),
            'version' => (int) (get_array_value($data, 'version') ?: 1),
            'is_active' => !empty(get_array_value($data, 'is_active')) ? 1 : 0,
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'updated_by' => (int) (get_array_value($data, 'updated_by') ?: 0),
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        $id = (int) (get_array_value($data, 'id') ?: 0);
        if ($id) {
            return $this->db->table($table)->where('id', $id)->update($payload);
        }

        return $this->db->table($table)->insert($payload) ? $this->db->insertID() : false;
    }

    public function get_prompts()
    {
        $table = $this->db->prefixTable('laudo_ai_prompts');
        if (!$this->db->tableExists($table)) {
            return array();
        }

        return $this->db->table($table)->where('deleted', 0)->orderBy('sort', 'ASC')->orderBy('id', 'DESC')->get()->getResult() ?: array();
    }

    public function get_report_summary(): array
    {
        $summary = array(
            'api_requests' => 0,
            'ai_requests' => 0,
            'synced_records' => 0,
            'devices' => 0,
        );

        $table = $this->db->prefixTable('laudo_api_request_logs');
        if ($this->db->tableExists($table)) {
            $summary['api_requests'] = (int) $this->db->table($table)->countAllResults();
        }

        $table = $this->db->prefixTable('laudo_ai_usage_logs');
        if ($this->db->tableExists($table)) {
            $summary['ai_requests'] = (int) $this->db->table($table)->countAllResults();
        }

        $table = $this->db->prefixTable('laudo_record_syncs');
        if ($this->db->tableExists($table)) {
            $summary['synced_records'] = (int) $this->db->table($table)->countAllResults();
        }

        $table = $this->db->prefixTable('laudo_api_devices');
        if ($this->db->tableExists($table)) {
            $summary['devices'] = (int) $this->db->table($table)->countAllResults();
        }

        return $summary;
    }
}
