<?php

namespace LicitaIA\Models;

class Licitaia_keywords_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_keywords';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        return $this->queryOrEmpty("SELECT k.id, k.keyword, k.category, k.active, k.created_by, k.created_at, k.updated_at, k.deleted
            FROM " . $this->db->prefixTable($this->table) . " k
            WHERE k.deleted = 0
            ORDER BY k.keyword ASC");
    }
}
