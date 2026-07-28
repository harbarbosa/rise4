<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_inspections_model extends Crud_model
{
    protected $table = 'laudo_inspections';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $laudos_table = $this->db->prefixTable('laudos_tecnicos');
        $clients_table = $this->db->prefixTable('clients');
        $users_table = $this->db->prefixTable('users');
        
        $where = "";
        $join = "LEFT JOIN $laudos_table ON $laudos_table.id = $table.laudo_id ";
        $join .= "LEFT JOIN $clients_table ON $clients_table.id = $table.client_id ";
        $join .= "LEFT JOIN $users_table AS resp ON resp.id = $table.responsible_id ";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $laudo_id = $this->_get_clean_value($options, "laudo_id");
        if ($laudo_id) {
            $where .= " AND $table.laudo_id=$laudo_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $responsible_id = $this->_get_clean_value($options, "responsible_id");
        if ($responsible_id) {
            $where .= " AND $table.responsible_id=$responsible_id";
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND $table.scheduled_date >= '$start_date'";
        }
        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND $table.scheduled_date <= '$end_date'";
        }

        $sql = "SELECT $table.*, 
            $laudos_table.title as laudo_title, $laudos_table.laudo_number,
            $clients_table.company_name,
            resp.first_name as responsible_name
        FROM $table $join
        WHERE $table.deleted=0 $where
        ORDER BY $table.scheduled_date ASC, $table.scheduled_time ASC";

        return $this->db->query($sql);
    }

    public function get_one($id)
    {
        $table = $this->db->prefixTable($this->table);
        $laudos_table = $this->db->prefixTable('laudos_tecnicos');
        
        $sql = "SELECT i.*, l.title as laudo_title, l.laudo_number, l.address as laudo_address
            FROM $table i
            LEFT JOIN $laudos_table l ON l.id = i.laudo_id
            WHERE i.id=$id AND i.deleted=0";
        
        return $this->db->query($sql)->getRow();
    }

    public function get_for_calendar($start_date, $end_date, $responsible_id = null)
    {
        $table = $this->db->prefixTable($this->table);
        
        $sql = "SELECT i.*, l.title as laudo_title, l.company_name as client_name
            FROM $table i
            LEFT JOIN {$this->db->prefixTable('laudos_tecnicos')} l ON l.id = i.laudo_id
            WHERE i.deleted=0 
            AND i.scheduled_date >= '$start_date' 
            AND i.scheduled_date <= '$end_date'";
        
        if ($responsible_id) {
            $sql .= " AND i.responsible_id = $responsible_id";
        }
        
        $sql .= " ORDER BY i.scheduled_date ASC, i.scheduled_time ASC";
        
        return $this->db->query($sql)->getResult();
    }

    public function check_conflicts($date, $time, $responsible_id, $exclude_id = null)
    {
        $table = $this->db->prefixTable($this->table);
        
        $sql = "SELECT i.*, l.title as laudo_title
            FROM $table i
            LEFT JOIN {$this->db->prefixTable('laudos_tecnicos')} l ON l.id = i.laudo_id
            WHERE i.deleted=0 
            AND i.status NOT IN ('completed', 'canceled', 'reagendada')
            AND i.scheduled_date = '$date'
            AND i.responsible_id = $responsible_id
            AND i.scheduled_time = '$time'";
        
        if ($exclude_id) {
            $sql .= " AND i.id != $exclude_id";
        }
        
        return $this->db->query($sql)->getResult();
    }

    public function check_equipment_conflicts($date, $equipment_ids, $exclude_id = null)
    {
        if (!$equipment_ids || !is_array($equipment_ids)) return array();
        
        $table = $this->db->prefixTable($this->table);
        $equipment_list = implode(',', array_map('intval', $equipment_ids));
        
        $sql = "SELECT i.*, l.title as laudo_title
            FROM $table i
            LEFT JOIN {$this->db->prefixTable('laudos_tecnicos')} l ON l.id = i.laudo_id
            WHERE i.deleted=0 
            AND i.status NOT IN ('completed', 'canceled', 'reagendada')
            AND i.scheduled_date = '$date'
            AND FIND_IN_SET(equipment_ids, '$equipment_list')";
        
        if ($exclude_id) {
            $sql .= " AND i.id != $exclude_id";
        }
        
        return $this->db->query($sql)->getResult();
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
            // Gerar código se não existir
            if (empty($data['code'])) {
                $data['code'] = 'INS-' . date('Ymd') . '-' . str_pad($this->get_next_number(), 4, '0', STR_PAD_LEFT);
            }
        }
        return parent::ci_save($data, $id);
    }

    private function get_next_number()
    {
        $table = $this->db->prefixTable($this->table);
        $result = $this->db->query("SELECT MAX(CAST(SUBSTRING(code, 9) AS UNSIGNED)) as max_num FROM $table WHERE code LIKE 'INS-" . date('Ymd') . "%'")->getRow();
        return ($result && $result->max_num) ? $result->max_num + 1 : 1;
    }

    public function update_status($id, $status, $extra_data = array())
    {
        $data = array('status' => $status);
        $now = get_my_local_time();
        
        switch ($status) {
            case 'iniciada':
                $data['started_at'] = $now;
                break;
            case 'pausada':
                $data['paused_at'] = $now;
                break;
            case 'resumed':
                $data['resumed_at'] = $now;
                $data['status'] = 'iniciada';
                break;
            case 'completed':
                $data['completed_at'] = $now;
                break;
        }
        
        $data = array_merge($data, $extra_data);
        
        return $this->save($data, $id);
    }

    public function get_stats($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        
        $sql = "SELECT status, COUNT(*) as count 
            FROM $table 
            WHERE laudo_id=$laudo_id AND deleted=0 
            GROUP BY status";
        
        return $this->db->query($sql)->getResult();
    }
}

class Laudo_photos_model extends Crud_model
{
    protected $table = 'laudo_photos';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND deleted=0 ORDER BY sort_order ASC, photo_number ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_for_inspection($inspection_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE inspection_id=$inspection_id AND deleted=0 ORDER BY taken_at DESC";
        return $this->db->query($sql)->getResult();
    }

    public function get_next_number($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $result = $this->db->query("SELECT MAX(photo_number) as max_num FROM $table WHERE laudo_id=$laudo_id AND deleted=0")->getRow();
        return ($result && $result->max_num) ? $result->max_num + 1 : 1;
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
            if (empty($data['photo_number'])) {
                $data['photo_number'] = $this->get_next_number($data['laudo_id']);
            }
        }
        return parent::ci_save($data, $id);
    }

    public function generate_hash($file_path)
    {
        return hash_file('sha256', $file_path);
    }
}

class Laudo_inspection_pendencies_model extends Crud_model
{
    protected $table = 'laudo_inspection_pendencies';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_inspection($inspection_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE inspection_id=$inspection_id ORDER BY created_at DESC";
        return $this->db->query($sql)->getResult();
    }

    public function get_blocking($inspection_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE inspection_id=$inspection_id AND is_blocking=1 AND resolved=0";
        return $this->db->query($sql)->getResult();
    }

    public function add_pendency($inspection_id, $type, $description, $is_blocking = false)
    {
        $data = array(
            'inspection_id' => $inspection_id,
            'type' => $type,
            'description' => $description,
            'is_blocking' => $is_blocking ? 1 : 0
        );
        
        return parent::ci_save($data, 0);
    }

    public function resolve($id, $user_id)
    {
        $data = array(
            'resolved' => 1,
            'resolved_at' => get_my_local_time(),
            'resolved_by' => $user_id
        );
        
        $table = $this->db->prefixTable($this->table);
        $this->db->where('id', $id);
        return $this->db->update($table, $data);
    }
}