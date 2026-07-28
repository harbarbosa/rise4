<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_status_history_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudo_status_history';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $status_table = $this->db->prefixTable('laudo_status');
        $users_table = $this->db->prefixTable('users');
        $where = "";

        $laudo_id = $this->_get_clean_value($options, "laudo_id");
        if ($laudo_id) {
            $where .= " AND $table.laudo_id=$laudo_id";
        }

        $sql = "SELECT $table.*, 
            from_status.name as from_status_name, from_status.color as from_status_color,
            to_status.name as to_status_name, to_status.color as to_status_color,
            user.first_name as user_name
        FROM $table 
        LEFT JOIN $status_table as from_status ON from_status.id = $table.from_status_id
        LEFT JOIN $status_table as to_status ON to_status.id = $table.to_status_id
        LEFT JOIN $users_table as user ON user.id = $table.user_id
        WHERE $table.deleted=0 $where
        ORDER BY $table.created_at DESC";

        return $this->db->query($sql);
    }

    public function add_history($laudo_id, $from_status_code, $to_status_code, $user_id, $comment = '', $origin = 'web')
    {
        $status_model = model(Laudo_status_model::class);
        
        $from_status = $from_status_code ? $status_model->get_by_code($from_status_code) : null;
        $to_status = $status_model->get_by_code($to_status_code);
        
        if (!$to_status) {
            return false;
        }

        $data = array(
            'laudo_id' => $laudo_id,
            'from_status_id' => $from_status ? $from_status->id : null,
            'to_status_id' => $to_status->id,
            'user_id' => $user_id,
            'comment' => $comment,
            'ip_address' => $this->get_client_ip(),
            'origin' => $origin,
            'created_at' => get_my_local_time()
        );

        return parent::ci_save($data, 0);
    }

    public function get_latest($laudo_id, $limit = 1)
    {
        $table = $this->db->prefixTable($this->table);
        $status_table = $this->db->prefixTable('laudo_status');
        $users_table = $this->db->prefixTable('users');
        
        $sql = "SELECT $table.*, 
            to_status.name as to_status_name, to_status.color as to_status_color,
            user.first_name as user_name
        FROM $table 
        LEFT JOIN $status_table as to_status ON to_status.id = $table.to_status_id
        LEFT JOIN $users_table as user ON user.id = $table.user_id
        WHERE $table.laudo_id=$laudo_id AND $table.deleted=0
        ORDER BY $table.created_at DESC
        LIMIT $limit";

        return $this->db->query($sql)->getResult();
    }

    private function get_client_ip()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return $ip;
    }
}