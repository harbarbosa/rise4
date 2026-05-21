<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Proposal_versions_model extends Crud_model
{
    protected $table = 'fv_proposal_versions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('proposal_id', 'version_number', 'status', 'subtotal', 'discount_total', 'tax_total', 'total', 'result_json', 'payload_json', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_proposal_versions');
        $users_table = $this->db->prefixTable('users');
        $where = "WHERE $table.deleted=0";

        $proposal_id = (int) get_array_value($options, 'proposal_id');
        if ($proposal_id) {
            $where .= " AND $table.proposal_id=$proposal_id";
        }

        $version_number = (int) get_array_value($options, 'version_number');
        if ($version_number) {
            $where .= " AND $table.version_number=$version_number";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status=" . $this->db->escape($status);
        }

        $sql = "SELECT $table.*, CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) AS created_by_name
            FROM $table
            LEFT JOIN $users_table ON $users_table.id=$table.created_by AND $users_table.deleted=0
            $where
            ORDER BY $table.version_number DESC, $table.id DESC";

        try {
            return $this->db->query($sql);
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Proposal versions query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_last_version($proposal_id)
    {
        return $this->get_details(array('proposal_id' => (int) $proposal_id))->getRow();
    }

    public function get_version($proposal_id, $version_number)
    {
        return $this->get_details(array(
            'proposal_id' => (int) $proposal_id,
            'version_number' => (int) $version_number
        ))->getRow();
    }

    public function get_versions($proposal_id)
    {
        return $this->get_details(array('proposal_id' => (int) $proposal_id));
    }
}
