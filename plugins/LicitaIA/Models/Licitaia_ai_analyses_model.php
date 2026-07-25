<?php

namespace LicitaIA\Models;

class Licitaia_ai_analyses_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_ai_analyses';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $analyses_table = $this->db->prefixTable($this->table);
        $opportunities_table = $this->db->prefixTable('licitaia_opportunities');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT a.id, a.opportunity_id, a.model_name, a.prompt_version, a.summary, a.recommendation, a.status,
                       a.created_by, a.created_at, a.updated_at, a.deleted,
                       o.title AS opportunity_title,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name
                FROM {$analyses_table} a
                LEFT JOIN {$opportunities_table} o ON o.id = a.opportunity_id
                LEFT JOIN {$users_table} u ON u.id = a.created_by
                WHERE a.deleted = 0
                ORDER BY a.id DESC";

        return $this->queryOrEmpty($sql);
    }
}
