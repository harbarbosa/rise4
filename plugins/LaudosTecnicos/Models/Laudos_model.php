<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudos_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudos_tecnicos';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $laudo_types_table = $this->db->prefixTable('laudo_types');
        $laudo_categories_table = $this->db->prefixTable('laudo_categories');
        $users_table = $this->db->prefixTable('users');
        $clients_table = $this->db->prefixTable('clients');

        $where = "";
        $join = "LEFT JOIN $laudo_types_table ON $laudo_types_table.id = $table.laudo_type_id ";
        $join .= "LEFT JOIN $laudo_categories_table ON $laudo_categories_table.id = $table.category_id ";
        $join .= "LEFT JOIN $users_table AS technician ON technician.id = $table.technician_id ";
        $join .= "LEFT JOIN $users_table AS reviewer ON reviewer.id = $table.reviewer_id ";
        $join .= "LEFT JOIN $users_table AS approver ON approver.id = $table.approver_id ";
        $join .= "LEFT JOIN $users_table AS created_user ON created_user.id = $table.created_by ";
        $join .= "LEFT JOIN $clients_table ON $clients_table.id = $table.client_id ";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $laudo_type_id = $this->_get_clean_value($options, "laudo_type_id");
        if ($laudo_type_id) {
            $where .= " AND $table.laudo_type_id=$laudo_type_id";
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $table.client_id=$client_id";
        }

        $technician_id = $this->_get_clean_value($options, "technician_id");
        if ($technician_id) {
            $where .= " AND $table.technician_id=$technician_id";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.title LIKE '%$search%' OR $table.description LIKE '%$search%')";
        }

        $company_id = $this->_get_company_id();
        if ($company_id) {
            $where .= " AND ($clients_table.company_id=$company_id OR $table.client_id IS NULL OR $table.client_id=0)";
        }

        $sql = "SELECT $table.*, 
            $laudo_types_table.name as type_name, 
            $laudo_types_table.prefix as type_prefix,
            $laudo_categories_table.name as category_name,
            $laudo_categories_table.color as category_color,
            technician.first_name as technician_name,
            reviewer.first_name as reviewer_name,
            approver.first_name as approver_name,
            created_user.first_name as created_by_name,
            $clients_table.company_name
        FROM $table 
        $join
        WHERE $table.deleted=0 $where
        ORDER BY $table.created_at DESC";

        return $this->db->query($sql);
    }

    public function get_one($id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_counts_by_status($company_id = null)
    {
        $table = $this->db->prefixTable($this->table);
        $clients_table = $this->db->prefixTable('clients');
        
        $where = "WHERE $table.deleted=0";
        if ($company_id) {
            $where .= " AND ($clients_table.company_id=$company_id OR $table.client_id IS NULL OR $table.client_id=0)";
        }

        $sql = "SELECT status, COUNT(*) as count 
            FROM $table 
            LEFT JOIN $clients_table ON $clients_table.id = $table.client_id
            $where
            GROUP BY status";

        $result = $this->db->query($sql)->getResult();
        
        $counts = array(
            'total' => 0,
            'draft' => 0,
            'in_progress' => 0,
            'pending_review' => 0,
            'approved' => 0,
            'issued' => 0,
            'expired' => 0,
            'canceled' => 0
        );

        foreach ($result as $row) {
            $counts[$row->status] = (int)$row->count;
            $counts['total'] += (int)$row->count;
        }

        return $counts;
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
            $data['updated_at'] = get_my_local_time();
        }
        return parent::ci_save($data, $id);
    }

    public function delete($id, $undo = false)
    {
        return parent::delete($id, $undo);
    }
}