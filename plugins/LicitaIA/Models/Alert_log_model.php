<?php

namespace LicitaIA\Models;

class Alert_log_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_alert_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function has_sent_today($alert_key, $opportunity_id, $recipient_user_id, $alert_date = null)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $alert_key = trim((string) $alert_key);
        $opportunity_id = (int) $opportunity_id;
        $recipient_user_id = (int) $recipient_user_id;
        $alert_date = trim((string) ($alert_date ?: get_today_date()));

        if ($alert_key === '' || !$opportunity_id || !$recipient_user_id) {
            return false;
        }

        return (bool) $this->get_one_where(array(
            'alert_key' => $alert_key,
            'opportunity_id' => $opportunity_id,
            'recipient_user_id' => $recipient_user_id,
            'alert_date' => $alert_date,
            'deleted' => 0,
        ))->id;
    }

    public function log_alert($data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = is_array($data) ? $data : array();
        $row = array(
            'alert_type' => trim((string) get_array_value($payload, 'alert_type')),
            'alert_key' => trim((string) get_array_value($payload, 'alert_key')),
            'opportunity_id' => (int) get_array_value($payload, 'opportunity_id') ?: null,
            'recipient_user_id' => (int) get_array_value($payload, 'recipient_user_id') ?: null,
            'notification_id' => (int) get_array_value($payload, 'notification_id') ?: null,
            'alert_date' => trim((string) (get_array_value($payload, 'alert_date') ?: get_today_date())),
            'channel_web' => (int) get_array_value($payload, 'channel_web', 0),
            'channel_email' => (int) get_array_value($payload, 'channel_email', 0),
            'channel_whatsapp' => (int) get_array_value($payload, 'channel_whatsapp', 0),
            'status' => trim((string) (get_array_value($payload, 'status') ?: 'completed')),
            'subject' => trim((string) get_array_value($payload, 'subject')),
            'message' => trim((string) get_array_value($payload, 'message')),
            'payload_json' => $this->normalizePayload(get_array_value($payload, 'payload_json', get_array_value($payload, 'payload', array()))),
            'sent_at' => get_array_value($payload, 'sent_at', get_my_local_time()),
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
