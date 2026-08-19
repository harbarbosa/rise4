<?php

namespace Engenharia\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandEngenhariaChecklists extends Migration
{
    public function up()
    {
        $this->addMissingColumns('eng_checklists', array(
            'is_enabled' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
        ));
        $this->addMissingColumns('eng_checklist_groups', array(
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
        ));
        $this->addMissingColumns('eng_checklist_items', array(
            'title' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'inspector_instruction' => array('type' => 'TEXT', 'null' => true),
            'allow_observation' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'requires_photo' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'requires_measurement' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'measurement_unit' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true),
            'criticality' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'medium'),
            'default_recommendation' => array('type' => 'TEXT', 'null' => true),
            'response_options_json' => array('type' => 'TEXT', 'null' => true),
            'not_verified_allowed' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
        ));
    }

    public function down() { }

    private function addMissingColumns(string $table, array $columns)
    {
        $full = $this->db->prefixTable($table);
        if (!$this->db->tableExists($full)) { return; }
        $existing = $this->db->getFieldNames($full);
        foreach ($columns as $name => $definition) {
            if (!in_array($name, $existing, true)) { $this->forge->addColumn($table, array($name => $definition)); }
        }
    }
}
