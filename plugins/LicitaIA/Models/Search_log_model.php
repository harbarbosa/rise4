<?php

namespace LicitaIA\Models;

class Search_log_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_search_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log_search($data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = is_array($data) ? $data : array();
        $row = array(
            'source_id' => (int) get_array_value($payload, 'source_id') ?: null,
            'opportunity_id' => (int) get_array_value($payload, 'opportunity_id') ?: null,
            'query_text' => trim((string) get_array_value($payload, 'query_text')),
            'filters_json' => $this->normalizePayload(get_array_value($payload, 'filters_json', get_array_value($payload, 'filters', array()))),
            'results_count' => (int) get_array_value($payload, 'results_count', 0),
            'status' => trim((string) (get_array_value($payload, 'status') ?: 'completed')),
            'response_json' => $this->normalizePayload(get_array_value($payload, 'response_json', get_array_value($payload, 'response', array()))),
            'error_message' => trim((string) get_array_value($payload, 'error_message')),
            'started_at' => get_array_value($payload, 'started_at', get_my_local_time()),
            'finished_at' => get_array_value($payload, 'finished_at', get_my_local_time()),
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
