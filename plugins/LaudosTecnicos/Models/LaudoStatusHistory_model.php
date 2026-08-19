<?php

namespace LaudosTecnicos\Models;

class LaudoStatusHistory_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_status_history';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log_change(array $data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = array(
            'laudo_id' => (int) (get_array_value($data, 'laudo_id') ?: 0),
            'from_status_code' => get_array_value($data, 'from_status_code') ?: null,
            'to_status_code' => get_array_value($data, 'to_status_code') ?: '',
            'user_id' => (int) (get_array_value($data, 'user_id') ?: 0),
            'comment' => get_array_value($data, 'comment') ?: '',
            'source' => get_array_value($data, 'source') ?: 'web',
            'ip_address' => get_array_value($data, 'ip_address') ?: '',
        );

        return $this->db->table($this->db->prefixTable($this->table))->insert($payload);
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $history_table = $this->db->prefixTable($this->table);
        $laudos_table = $this->db->prefixTable('laudos');
        $users_table = $this->db->prefixTable('users');
        $statuses_table = $this->db->prefixTable('laudo_statuses');
        $where = " WHERE 1=1";

        $laudo_id = (int) get_array_value($options, 'laudo_id');
        if ($laudo_id) {
            $where .= " AND $history_table.laudo_id=" . $laudo_id;
        }

        return $this->queryOrEmpty("SELECT $history_table.*,
                $laudos_table.title AS laudo_title,
                CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) AS user_name,
                fs.name AS from_status_name,
                ts.name AS to_status_name
            FROM $history_table
            LEFT JOIN $laudos_table ON $laudos_table.id = $history_table.laudo_id AND $laudos_table.deleted = 0
            LEFT JOIN $users_table ON $users_table.id = $history_table.user_id AND $users_table.deleted = 0
            LEFT JOIN $statuses_table fs ON fs.code = $history_table.from_status_code AND fs.deleted = 0
            LEFT JOIN $statuses_table ts ON ts.code = $history_table.to_status_code AND ts.deleted = 0
            $where
            ORDER BY $history_table.created_at DESC");
    }
}
