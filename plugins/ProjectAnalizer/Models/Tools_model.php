<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Tools_model extends Crud_model
{
    protected $table = "pa_tools";
    public function __construct() { parent::__construct($this->table); }
    public function get_active_tools() { return $this->db->query("SELECT * FROM " . $this->db->prefixTable($this->table_without_prefix) . " WHERE active=1 AND deleted=0 ORDER BY name ASC"); }
    public function find_or_create($name)
    {
        $name = trim((string) $name);
        if (!$name) { return 0; }
        $row = $this->db->table($this->db->prefixTable($this->table_without_prefix))->where("name", $name)->where("deleted", 0)->get()->getRow();
        return $row ? (int) $row->id : (int) $this->ci_save(array("name" => clean_data($name), "active" => 1, "deleted" => 0), 0);
    }
}
