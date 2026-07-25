<?php

namespace LicitaIA\Models;

class Licitaia_settings_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_settings';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_setting($key)
    {
        $key = trim((string) $key);
        if ($key === '' || !$this->hasTable()) {
            return '';
        }

        $row = $this->get_one_where(array('setting_name' => $key, 'deleted' => 0));
        if (!($row && !empty($row->id))) {
            return '';
        }

        $value = (string) ($row->setting_value ?? '');
        if (str_starts_with($value, 'enc:')) {
            return $this->decryptValue(substr($value, 4));
        }

        return $value;
    }

    public function save_setting($key, $value, $encrypted = false)
    {
        $key = trim((string) $key);
        if ($key === '' || !$this->hasTable()) {
            return false;
        }

        $stored_value = $this->normalizeValue($value);
        if ($encrypted) {
            $stored_value = 'enc:' . $this->encryptValue((string) $stored_value);
        }

        $existing = $this->get_one_where(array('setting_name' => $key, 'deleted' => 0));
        $data = array(
            'setting_name' => $key,
            'setting_value' => $stored_value,
            'deleted' => 0,
            'updated_at' => get_my_local_time(),
        );

        if (!empty($existing->id)) {
            return $this->ci_save($data, $existing->id);
        }

        $data['created_at'] = get_my_local_time();
        return $this->ci_save($data, 0);
    }

    public function get_ai_settings()
    {
        if (!$this->hasTable()) {
            return array();
        }

        return array(
            'ai_provider' => $this->get_setting('ai_provider'),
            'ai_model' => $this->get_setting('ai_model'),
            'ai_api_base_url' => $this->get_setting('ai_api_base_url'),
            'ai_api_key' => $this->get_setting('ai_api_key'),
            'ai_enabled' => $this->get_setting('ai_enabled'),
            'reports_enabled' => $this->get_setting('reports_enabled'),
            'checklist_enabled' => $this->get_setting('checklist_enabled'),
            'opportunities_default_status' => $this->get_setting('opportunities_default_status'),
        );
    }

    public function get_alert_settings()
    {
        if (!$this->hasTable()) {
            return array();
        }

        return array(
            'alerts_enabled' => $this->get_setting('alerts_enabled') ?: '1',
            'alerts_days_before_opening' => $this->get_setting('alerts_days_before_opening') ?: '7,3,1',
            'alerts_days_before_submission' => $this->get_setting('alerts_days_before_submission') ?: '7,3,1',
            'alerts_recipient_user_ids' => $this->get_setting('alerts_recipient_user_ids') ?: '',
            'alerts_email_enabled' => $this->get_setting('alerts_email_enabled') ?: '1',
            'alerts_whatsapp_enabled' => $this->get_setting('alerts_whatsapp_enabled') ?: '0',
        );
    }

    public function get_all_settings()
    {
        if (!$this->hasTable()) {
            return array();
        }

        return $this->get_all_where(array('deleted' => 0), 1000, 0, 'setting_name', 'id, setting_name, setting_value, deleted')->getResult();
    }

    private function normalizeValue($value)
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function encryptValue($value)
    {
        try {
            $encrypter = get_encrypter();
            return bin2hex($encrypter->encrypt((string) $value));
        } catch (\Throwable $e) {
            return base64_encode((string) $value);
        }
    }

    private function decryptValue($value)
    {
        try {
            if (ctype_xdigit($value) && strlen($value) % 2 === 0) {
                $encrypter = get_encrypter();
                return (string) $encrypter->decrypt(hex2bin($value));
            }
        } catch (\Throwable $e) {
            // Fall through to the original payload if decryption fails.
        }

        $decoded = base64_decode($value, true);
        return $decoded !== false ? (string) $decoded : (string) $value;
    }
}
