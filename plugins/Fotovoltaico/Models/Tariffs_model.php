<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Tariffs_model extends Crud_model
{
    protected $table = 'fv_tariffs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('distributor_id', 'modality', 'subgroup', 'tariff_class', 'tariff_subclass', 'group_name', 'time_slot', 'unit', 'resolution', 'tariff_detail', 'tariff_base', 'te', 'tusd', 'flag_name', 'flag_value', 'source', 'origin_hash', 'valid_from', 'valid_to', 'notes', 'sync_notes', 'active', 'is_current', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_tariffs');
        $distributors_table = $this->db->prefixTable('fv_distributors');
        if (!$this->db->tableExists($table)) {
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
        $where = "WHERE $table.deleted=0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $distributor_id = (int) get_array_value($options, 'distributor_id');
        if ($distributor_id) {
            $where .= " AND $table.distributor_id=$distributor_id";
        }

        $modality = trim((string) get_array_value($options, 'modality'));
        if ($modality !== '') {
            $where .= " AND $table.modality=" . $this->db->escape($modality);
        }

        $subgroup = trim((string) get_array_value($options, 'subgroup'));
        if ($subgroup !== '') {
            $where .= " AND $table.subgroup=" . $this->db->escape($subgroup);
        }

        $group_name = trim((string) get_array_value($options, 'group_name'));
        if ($group_name !== '') {
            $where .= " AND $table.group_name=" . $this->db->escape($group_name);
        }

        $source = trim((string) get_array_value($options, 'source'));
        if ($source !== '') {
            $where .= " AND $table.source=" . $this->db->escape($source);
        }

        $current_only = get_array_value($options, 'current_only');
        if ($current_only) {
            $where .= " AND $table.is_current=1";
        }

        $active_only = get_array_value($options, 'active_only');
        if ($active_only !== null && $active_only !== '') {
            $where .= " AND $table.active=" . (int) $active_only;
        }

        $reference_date = trim((string) get_array_value($options, 'reference_date'));
        if ($reference_date !== '') {
            $reference_date = $this->db->escape($reference_date);
            $where .= " AND ($table.valid_from IS NULL OR $table.valid_from <= $reference_date) AND ($table.valid_to IS NULL OR $table.valid_to >= $reference_date)";
        }

        $vigency_status = trim((string) get_array_value($options, 'vigency_status'));
        if ($vigency_status !== '') {
            $comparison_date = $reference_date !== '' ? $reference_date : $this->db->escape(date('Y-m-d'));
            if ($vigency_status === 'current') {
                $where .= " AND ($table.valid_from IS NULL OR $table.valid_from <= $comparison_date) AND ($table.valid_to IS NULL OR $table.valid_to >= $comparison_date)";
            } elseif ($vigency_status === 'expired') {
                $where .= " AND $table.valid_to IS NOT NULL AND $table.valid_to < $comparison_date";
            } elseif ($vigency_status === 'future') {
                $where .= " AND $table.valid_from IS NOT NULL AND $table.valid_from > $comparison_date";
            }
        }

        $sql = "SELECT $table.*, $distributors_table.title AS distributor_title,
        CASE
            WHEN ($table.valid_from IS NULL OR $table.valid_from <= CURDATE()) AND ($table.valid_to IS NULL OR $table.valid_to >= CURDATE()) THEN 1
            ELSE 0
        END AS current_status
        FROM $table
        LEFT JOIN $distributors_table ON $distributors_table.id=$table.distributor_id AND $distributors_table.deleted=0
        $where
        ORDER BY $table.valid_from DESC, $table.id DESC";

        try {
            return $this->db->query($sql);
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Tariffs query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_current_tariff($distributor_id = 0, $modality = '', $subgroup = '', $reference_date = null)
    {
        $options = array(
            'active_only' => 1
        );

        if ($distributor_id) {
            $options['distributor_id'] = (int) $distributor_id;
        }
        if ($modality !== '') {
            $options['modality'] = $modality;
        }
        if ($subgroup !== '') {
            $options['subgroup'] = $subgroup;
        }
        if ($reference_date) {
            $options['reference_date'] = $reference_date;
        }

        return $this->get_details($options)->getRow();
    }

    public function get_tariff_for_proposal($distributor_id = 0, $modality = '', $subgroup = '', $reference_date = null)
    {
        return $this->get_current_tariff($distributor_id, $modality, $subgroup, $reference_date);
    }

    public function get_current_tariffs_by_distributor($distributor_id = 0)
    {
        if (!$distributor_id) {
            return array();
        }

        $query = $this->get_details(array(
            'distributor_id' => (int) $distributor_id,
            'active_only' => 1,
            'current_only' => 1,
        ));

        return $query ? $query->getResult() : array();
    }

    public function get_tariff_history_by_distributor($distributor_id = 0)
    {
        if (!$distributor_id) {
            return array();
        }

        $query = $this->get_details(array(
            'distributor_id' => (int) $distributor_id,
        ));

        return $query ? $query->getResult() : array();
    }

    public function get_latest_current_flag($reference_date = null)
    {
        $reference_date = $reference_date ?: date('Y-m-d');
        return $this->get_details(array(
            'reference_date' => $reference_date,
            'vigency_status' => 'current',
            'active_only' => 1,
        ))->getRow();
    }

    public function close_previous_vigency($distributor_id, $modality, $subgroup, $valid_from, $exclude_id = 0)
    {
        $table = $this->db->prefixTable('fv_tariffs');
        $builder = $this->db->table($table);
        $builder->where('deleted', 0);
        $builder->where('distributor_id', (int) $distributor_id);
        $builder->where('modality', $modality);
        $builder->where('subgroup', $subgroup);
        if ($exclude_id) {
            $builder->where('id !=', (int) $exclude_id);
        }

        $rows = $builder->get()->getResult();
        $close_date = $valid_from ? date('Y-m-d', strtotime($valid_from . ' -1 day')) : null;
        foreach ($rows as $row) {
            $update = array('active' => 0, 'updated_at' => get_my_local_time());
            if ($close_date) {
                $update['valid_to'] = $close_date;
            }
            $this->ci_save($update, $row->id);
        }

        $this->sync_current_flags((int) $distributor_id);
    }

    public function sync_current_flags($distributor_id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        if (!$this->db->tableExists($table)) {
            return false;
        }
        if (!$this->db->fieldExists('is_current', $table)) {
            return false;
        }

        $builder = $this->db->table($table)->where('deleted', 0);
        if ($distributor_id) {
            $builder->where('distributor_id', (int) $distributor_id);
        }
        $builder->update(array(
            'is_current' => 0,
            'updated_at' => get_my_local_time(),
        ));

        $sql = "UPDATE {$table}
            SET is_current = 1,
                updated_at = " . $this->db->escape(get_my_local_time()) . "
            WHERE deleted = 0
            AND active = 1
            AND (valid_from IS NULL OR valid_from <= CURDATE())
            AND (valid_to IS NULL OR valid_to >= CURDATE())";

        if ($distributor_id) {
            $sql .= " AND distributor_id = " . (int) $distributor_id;
        }

        return $this->db->query($sql);
    }
}
