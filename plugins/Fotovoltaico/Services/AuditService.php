<?php

namespace Fotovoltaico\Services;

use Fotovoltaico\Models\Audit_logs_model;

class AuditService
{
    private $Audit_logs_model;

    public function __construct()
    {
        $this->Audit_logs_model = model(Audit_logs_model::class);
    }

    public function record($entity_type, $entity_id, $action, $old_data = array(), $new_data = array(), $context = array())
    {
        $old_data = $this->_sanitize_payload($old_data);
        $new_data = $this->_sanitize_payload($new_data);
        $changes = $this->_diff_payload($old_data, $new_data);

        return $this->Audit_logs_model->register_audit(array(
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'action' => $action,
            'old_json' => json_encode($old_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_json' => json_encode($new_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'changes_json' => json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip_address' => get_array_value($context, 'ip_address') ?: $this->_request_ip(),
            'user_agent' => get_array_value($context, 'user_agent') ?: $this->_request_agent(),
            'created_by' => (int) get_array_value($context, 'created_by'),
        ));
    }

    public function purge_old_logs($days = 365)
    {
        $days = max(1, (int) $days);
        $table = db_connect()->prefixTable('fv_audit_logs');
        $threshold = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        return db_connect()->table($table)
            ->where('created_at <', $threshold)
            ->delete();
    }

    private function _sanitize_payload($payload)
    {
        if (!is_array($payload)) {
            return array();
        }

        $secret_keys = array('token', 'secret', 'password', 'api_key', 'apikey', 'authorization', 'client_secret', 'access_token');
        $result = array();
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->_sanitize_payload($value);
                continue;
            }

            if (in_array(strtolower((string) $key), $secret_keys, true)) {
                $result[$key] = $this->_mask_value($value);
            } else {
                $result[$key] = is_scalar($value) || $value === null ? $value : json_encode($value);
            }
        }

        return $result;
    }

    private function _diff_payload($old_data, $new_data)
    {
        $changes = array();
        $keys = array_unique(array_merge(array_keys((array) $old_data), array_keys((array) $new_data)));
        foreach ($keys as $key) {
            $old = get_array_value($old_data, $key);
            $new = get_array_value($new_data, $key);
            if ($old !== $new) {
                $changes[$key] = array('old' => $old, 'new' => $new);
            }
        }

        return $changes;
    }

    private function _mask_value($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2) . str_repeat('*', max(0, $length - 4)) . substr($value, -2);
    }

    private function _request_ip()
    {
        return service('request')->getIPAddress() ?: '';
    }

    private function _request_agent()
    {
        return service('request')->getUserAgent() ? (string) service('request')->getUserAgent() : '';
    }
}
