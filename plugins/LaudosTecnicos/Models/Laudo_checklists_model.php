<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_checklists_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudo_checklists';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('laudo_types');
        
        $where = "";
        $join = "LEFT JOIN $types_table ON $types_table.id = $table.laudo_type_id ";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $laudo_type_id = $this->_get_clean_value($options, "laudo_type_id");
        if ($laudo_type_id) {
            $where .= " AND $table.laudo_type_id=$laudo_type_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.name LIKE '%$search%' OR $table.code LIKE '%$search%')";
        }

        $sql = "SELECT $table.*, $types_table.name as type_name
            FROM $table $join
            WHERE $table.deleted=0 $where
            ORDER BY $table.name ASC";

        return $this->db->query($sql);
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_items($checklist_id)
    {
        $table = $this->db->prefixTable('laudo_checklist_items');
        $sql = "SELECT * FROM $table WHERE checklist_id=$checklist_id AND deleted=0 ORDER BY sort_order ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_item($item_id)
    {
        $table = $this->db->prefixTable('laudo_checklist_items');
        $sql = "SELECT * FROM $table WHERE id=$item_id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        return parent::ci_save($data, $id);
    }

    public function save_item($data, $id = 0)
    {
        $table = $this->db->prefixTable('laudo_checklist_items');
        $id = (int)$id;
        
        if ($id) {
            $this->db->where('id', $id);
            $this->db->update($table, $data);
            return $id;
        } else {
            $data['created_at'] = get_my_local_time();
            $this->db->insert($table, $data);
            return $this->db->insertID();
        }
    }

    public function delete_item($id)
    {
        $table = $this->db->prefixTable('laudo_checklist_items');
        return $this->db->query("UPDATE $table SET deleted=1 WHERE id=$id");
    }

    public function clone_checklist($id, $new_name = null)
    {
        $checklist = $this->get_one($id);
        if (!$checklist) return false;

        $new_code = $checklist->code . '_CLONE_' . time();
        
        $data = array(
            'name' => $new_name ?: $checklist->name . ' (Cópia)',
            'code' => $new_code,
            'category' => $checklist->category,
            'laudo_type_id' => $checklist->laudo_type_id,
            'description' => $checklist->description,
            'version' => 1,
            'status' => 'draft',
            'created_by' => $checklist->created_by
        );
        
        $new_id = $this->save($data, 0);
        
        // Copiar itens
        $items = $this->get_items($id);
        foreach ($items as $item) {
            $item_data = array(
                'checklist_id' => $new_id,
                'group_name' => $item->group_name,
                'code' => $item->code,
                'question' => $item->question,
                'guidance' => $item->guidance,
                'response_type' => $item->response_type,
                'expected_answer' => $item->expected_answer,
                'severity' => $item->severity,
                'weight' => $item->weight,
                'is_required' => $item->is_required,
                'evidence_required' => $item->evidence_required,
                'photo_required' => $item->photo_required,
                'measurement_required' => $item->measurement_required,
                'observation_required' => $item->observation_required,
                'standard_code' => $item->standard_code,
                'generates_nc' => $item->generates_nc,
                'sort_order' => $item->sort_order
            );
            $this->save_item($item_data, 0);
        }
        
        return $new_id;
    }

    public function publish($id)
    {
        return $this->save(array(
            'status' => 'published',
            'published_at' => get_my_local_time()
        ), $id);
    }

    public function get_categories()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT DISTINCT category FROM $table WHERE deleted=0 AND category IS NOT NULL ORDER BY category";
        return $this->db->query($sql)->getResult();
    }
}

class Laudo_checklist_answers_model extends Crud_model
{
    protected $table = 'laudo_checklist_answers';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $items_table = $this->db->prefixTable('laudo_checklist_items');
        
        $sql = "SELECT a.*, i.question, i.group_name, i.response_type, i.severity, i.expected_answer
            FROM $table a
            LEFT JOIN $items_table i ON i.id = a.item_id
            WHERE a.laudo_id=$laudo_id
            ORDER BY i.sort_order ASC";
        
        return $this->db->query($sql)->getResult();
    }

    public function get_stats($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $items_table = $this->db->prefixTable('laudo_checklist_items');
        
        // Total
        $total = $this->db->query("SELECT COUNT(*) as count FROM $table WHERE laudo_id=$laudo_id")->getRow()->count;
        
        // Por tipo de resposta
        $sql = "SELECT a.response, COUNT(*) as count 
            FROM $table a 
            WHERE a.laudo_id=$laudo_id 
            GROUP BY a.response";
        $by_response = $this->db->query($sql)->getResult();
        
        // Por severidade
        $sql = "SELECT i.severity, COUNT(*) as count 
            FROM $table a 
            LEFT JOIN $items_table i ON i.id = a.item_id 
            WHERE a.laudo_id=$laudo_id 
            GROUP BY i.severity";
        $by_severity = $this->db->query($sql)->getResult();
        
        return array(
            'total' => $total,
            'by_response' => $by_response,
            'by_severity' => $by_severity
        );
    }

    public function save_answer($data, $id = 0)
    {
        $id = (int)$id;
        $table = $this->db->prefixTable($this->table);
        
        if ($id) {
            $this->db->where('id', $id);
            $this->db->update($table, $data);
            return $id;
        } else {
            $data['created_at'] = get_my_local_time();
            $this->db->insert($table, $data);
            return $this->db->insertID();
        }
    }
}