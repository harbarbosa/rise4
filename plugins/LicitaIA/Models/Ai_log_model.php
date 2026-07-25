<?php

namespace LicitaIA\Models;

class Ai_log_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_ai_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log_request($data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = is_array($data) ? $data : array();
        $row = array(
            'opportunity_id' => (int) get_array_value($payload, 'opportunity_id') ?: null,
            'document_id' => (int) get_array_value($payload, 'document_id') ?: null,
            'provider' => trim((string) get_array_value($payload, 'provider')),
            'model_name' => trim((string) get_array_value($payload, 'model_name')),
            'request_type' => trim((string) (get_array_value($payload, 'request_type') ?: 'analysis')),
            'request_json' => $this->normalizePayload(get_array_value($payload, 'request_json', get_array_value($payload, 'request', array()))),
            'response_json' => $this->normalizePayload(get_array_value($payload, 'response_json', get_array_value($payload, 'response', array()))),
            'status' => trim((string) (get_array_value($payload, 'status') ?: 'pending')),
            'error_message' => trim((string) get_array_value($payload, 'error_message')),
            'tokens_input' => (int) get_array_value($payload, 'tokens_input', 0),
            'tokens_output' => (int) get_array_value($payload, 'tokens_output', 0),
            'created_by' => (int) get_array_value($payload, 'created_by', $this->currentUserId()) ?: null,
            'created_at' => get_array_value($payload, 'created_at', get_my_local_time()),
            'updated_at' => get_array_value($payload, 'updated_at', get_my_local_time()),
            'deleted' => 0,
        );

        return $this->ci_save($row, 0);
    }

    private function currentUserId()
    {
        try {
            $users_model = model('App\\Models\\Users_model');
            return (int) $users_model->login_user_id();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function normalizePayload($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
