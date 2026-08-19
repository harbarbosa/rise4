<?php

namespace LaudosTecnicos\Libraries;

class AuditService
{
    public function log(array $data = array())
    {
        $db = db_connect('default');
        $table = $db->prefixTable('laudo_audit_logs');

        if (!$db->tableExists($table)) {
            return false;
        }

        $payload = array(
            'entity_type' => get_array_value($data, 'entity_type') ?: '',
            'entity_id' => (int) (get_array_value($data, 'entity_id') ?: 0),
            'action' => get_array_value($data, 'action') ?: '',
            'user_id' => (int) (get_array_value($data, 'user_id') ?: 0),
            'ip_address' => get_array_value($data, 'ip_address') ?: '',
            'source' => get_array_value($data, 'source') ?: 'web',
            'old_values_json' => get_array_value($data, 'old_values_json') ?: '',
            'new_values_json' => get_array_value($data, 'new_values_json') ?: '',
            'description' => get_array_value($data, 'description') ?: '',
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'created_at' => get_array_value($data, 'created_at') ?: get_current_utc_time(),
        );

        return (bool) $db->table($table)->insert($payload);
    }
}
