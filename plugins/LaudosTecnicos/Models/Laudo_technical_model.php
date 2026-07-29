<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_measurements_model extends Crud_model
{
    protected $table = 'laudo_measurements';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('laudo_measurement_types');
        
        $sql = "SELECT m.*, t.name as type_name, t.magnitude, t.reference_value, t.tolerance
            FROM $table m
            LEFT JOIN $types_table t ON t.id = m.measurement_type_id
            WHERE m.laudo_id=$laudo_id
            ORDER BY m.measured_at DESC";
        
        return $this->db->query($sql)->getResult();
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        // Classificar automaticamente se enabled
        if (!isset($data['result']) || empty($data['result'])) {
            $data['result'] = $this->_classifyMeasurement($data);
        }
        
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        
        return parent::ci_save($data, $id) ? true : false;
    }

    private function _classifyMeasurement($data)
    {
        if (!isset($data['measurement_type_id']) || !isset($data['value'])) {
            return 'normal';
        }

        $types_table = $this->db->prefixTable('laudo_measurement_types');
        $type = $this->db->query("SELECT * FROM $types_table WHERE id={$data['measurement_type_id']}")->getRow();
        
        if (!$type || !$type->auto_classify) {
            return 'normal';
        }

        $value = (float)$data['value'];
        $reference = (float)$type->reference_value;
        $tolerance = (float)$type->tolerance;

        // Verificar limites
        if ($type->min_value && $value < $type->min_value) {
            return 'critical';
        }
        if ($type->max_value && $value > $type->max_value) {
            return 'critical';
        }

        // Verificar tolerância
        if ($value >= ($reference - $tolerance) && $value <= ($reference + $tolerance)) {
            return 'conform';
        } elseif ($value >= ($reference - $tolerance * 2) && $value <= ($reference + $tolerance * 2)) {
            return 'attention';
        } else {
            return 'non_conform';
        }
    }
}

class Laudo_measurement_types_model extends Crud_model
{
    protected $table = 'laudo_measurement_types';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $where = "";
        
        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.name LIKE '%$search%' OR $table.magnitude LIKE '%$search%')";
        }

        $active = $this->_get_clean_value($options, "active");
        if ($active !== null) {
            $where .= " AND $table.active=" . ($active ? 1 : 0);
        }

        $sql = "SELECT * FROM $table WHERE deleted=0 $where ORDER BY $table.name ASC";
        return $this->db->query($sql);
    }

    public function get_dropdown()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT id, name, unit FROM $table WHERE deleted=0 AND active=1 ORDER BY name ASC";
        $result = $this->db->query($sql)->getResult();
        
        $dropdown = array();
        foreach ($result as $row) {
            $dropdown[$row->id] = $row->name . ' (' . $row->unit . ')';
        }
        return $dropdown;
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_by_magnitude($magnitude)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE magnitude='$magnitude' AND deleted=0 AND active=1";
        return $this->db->query($sql)->getResult();
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        return parent::ci_save($data, $id) ? true : false;
    }

    public function get_magnitudes()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT DISTINCT magnitude FROM $table WHERE deleted=0 AND active=1 ORDER BY magnitude";
        return $this->db->query($sql)->getResult();
    }
}

class Laudo_equipment_model extends Crud_model
{
    protected $table = 'laudo_equipment';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $where = "";

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.name LIKE '%$search%' OR $table.serial_number LIKE '%$search%' OR $table.patrimony LIKE '%$search%')";
        }

        $type = $this->_get_clean_value($options, "type");
        if ($type) {
            $where .= " AND $table.type='$type'";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $sql = "SELECT * FROM $table WHERE deleted=0 $where ORDER BY $table.name ASC";
        return $this->db->query($sql);
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_dropdown($active_only = true)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT id, name, serial_number, patrimony FROM $table WHERE deleted=0";
        if ($active_only) {
            $sql .= " AND status='active' AND (next_calibration IS NULL OR next_calibration >= CURDATE())";
        }
        $sql .= " ORDER BY name ASC";
        
        $result = $this->db->query($sql)->getResult();
        
        $dropdown = array();
        foreach ($result as $row) {
            $label = $row->name;
            if ($row->patrimony) $label .= ' (' . $row->patrimony . ')';
            $dropdown[$row->id] = $label;
        }
        return $dropdown;
    }

    public function get_for_calibration_alert($days = 30)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table 
            WHERE deleted=0 AND status='active' 
            AND next_calibration IS NOT NULL 
            AND next_calibration <= DATE_ADD(CURDATE(), INTERVAL $days DAY)
            ORDER BY next_calibration ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_expired()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table 
            WHERE deleted=0 AND status='active' 
            AND next_calibration IS NOT NULL 
            AND next_calibration < CURDATE()
            ORDER BY next_calibration ASC";
        return $this->db->query($sql)->getResult();
    }

    public function is_valid($id)
    {
        $equipment = $this->get_one($id);
        if (!$equipment) return false;
        if ($equipment->status !== 'active') return false;
        if ($equipment->next_calibration && $equipment->next_calibration < date('Y-m-d')) return false;
        return true;
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        return parent::ci_save($data, $id) ? true : false;
    }

    public function get_types()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT DISTINCT type FROM $table WHERE deleted=0 ORDER BY type";
        return $this->db->query($sql)->getResult();
    }
}

class Laudo_standards_model extends Crud_model
{
    protected $table = 'laudo_standards';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $where = "";

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.code LIKE '%$search%' OR $table.title LIKE '%$search%')";
        }

        $institution = $this->_get_clean_value($options, "institution");
        if ($institution) {
            $where .= " AND $table.institution='$institution'";
        }

        $category = $this->_get_clean_value($options, "category");
        if ($category) {
            $where .= " AND $table.category='$category'";
        }

        $sql = "SELECT * FROM $table WHERE deleted=0 $where ORDER BY $table.code ASC";
        return $this->db->query($sql);
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_by_code($code)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE code='$code' AND deleted=0 LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function get_dropdown()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT id, code, title FROM $table WHERE deleted=0 AND status='active' ORDER BY code ASC";
        $result = $this->db->query($sql)->getResult();
        
        $dropdown = array();
        foreach ($result as $row) {
            $dropdown[$row->id] = $row->code . ' - ' . substr($row->title, 0, 60);
        }
        return $dropdown;
    }

    public function get_for_type($laudo_type_id)
    {
        $table = $this->db->prefixTable($this->table);
        $link_table = $this->db->prefixTable('laudo_standard_types');
        
        $sql = "SELECT s.* FROM $table s
            LEFT JOIN $link_table st ON st.standard_id = s.id
            WHERE st.laudo_type_id = $laudo_type_id AND s.deleted=0 AND s.status='active'";
        
        return $this->db->query($sql)->getResult();
    }

    public function link_to_type($standard_id, $laudo_type_id)
    {
        $table = $this->db->prefixTable('laudo_standard_types');
        
        // Verificar se já existe
        $exists = $this->db->query("SELECT id FROM $table WHERE standard_id=$standard_id AND laudo_type_id=$laudo_type_id")->getRow();
        if ($exists) return true;
        
        return $this->db->insert($table, array(
            'standard_id' => $standard_id,
            'laudo_type_id' => $laudo_type_id,
            'created_at' => get_my_local_time()
        ));
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        return parent::ci_save($data, $id) ? true : false;
    }

    public function get_institutions()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT DISTINCT institution FROM $table WHERE deleted=0 ORDER BY institution";
        return $this->db->query($sql)->getResult();
    }

    public function get_categories()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT DISTINCT category FROM $table WHERE deleted=0 AND category IS NOT NULL ORDER BY category";
        return $this->db->query($sql)->getResult();
    }
}