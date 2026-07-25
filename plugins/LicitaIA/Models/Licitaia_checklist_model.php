<?php

namespace LicitaIA\Models;

class Licitaia_checklist_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_checklist_items';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $checklist_table = $this->db->prefixTable($this->table);
        $opportunities_table = $this->db->prefixTable('licitaia_opportunities');

        $sql = "SELECT c.id, c.opportunity_id, c.item_name, c.is_required, c.status, c.notes, c.created_by, c.created_at, c.updated_at, c.deleted,
                       o.title AS opportunity_title
                FROM {$checklist_table} c
                LEFT JOIN {$opportunities_table} o ON o.id = c.opportunity_id
                WHERE c.deleted = 0
                ORDER BY c.id DESC";

        return $this->queryOrEmpty($sql);
    }
}
