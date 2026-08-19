<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLaudoTemplateFields extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('laudos');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = array();
        if (!$this->db->fieldExists('template_id', $table)) {
            $fields['template_id'] = array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true);
        }
        if (!$this->db->fieldExists('template_snapshot_json', $table)) {
            $fields['template_snapshot_json'] = array('type' => 'LONGTEXT', 'null' => true);
        }

        if ($fields) {
            $this->forge->addColumn('laudos', $fields);
        }
    }

    public function down()
    {
        $table = $this->db->prefixTable('laudos');
        if (!$this->db->tableExists($table)) {
            return;
        }

        foreach (array('template_id', 'template_snapshot_json') as $field) {
            if ($this->db->fieldExists($field, $table)) {
                $this->forge->dropColumn('laudos', $field);
            }
        }
    }
}
