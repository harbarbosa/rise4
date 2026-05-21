<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Proposal_snapshots_model extends Crud_model
{
    protected $table = 'fv_proposal_snapshots';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('proposal_id', 'proposal_version_id', 'snapshot_json', 'snapshot_hash', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_proposal_snapshots');
        $where = "WHERE $table.deleted=0";

        $proposal_id = (int) get_array_value($options, 'proposal_id');
        if ($proposal_id) {
            $where .= " AND $table.proposal_id=$proposal_id";
        }

        $proposal_version_id = (int) get_array_value($options, 'proposal_version_id');
        if ($proposal_version_id) {
            $where .= " AND $table.proposal_version_id=$proposal_version_id";
        }

        try {
            return $this->db->query("SELECT * FROM $table $where ORDER BY $table.id DESC");
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Proposal snapshots query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_latest_snapshot($proposal_id)
    {
        return $this->get_details(array('proposal_id' => (int) $proposal_id))->getRow();
    }

    public function get_snapshot_by_version($proposal_version_id)
    {
        return $this->get_details(array('proposal_version_id' => (int) $proposal_version_id))->getRow();
    }

    public function store_snapshot($proposal_id, $proposal_version_id, $snapshot_json, $created_by = 0)
    {
        $snapshot_json = is_string($snapshot_json) ? $snapshot_json : json_encode($snapshot_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $data = array(
            'proposal_id' => (int) $proposal_id,
            'proposal_version_id' => (int) $proposal_version_id,
            'snapshot_json' => $snapshot_json,
            'snapshot_hash' => hash('sha256', $snapshot_json),
            'created_by' => (int) $created_by,
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
        );

        return $this->ci_save($data);
    }
}
