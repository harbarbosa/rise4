<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaudoTemplates extends Migration
{
    public function up()
    {
        $this->createTemplatesTable();
        $this->addLaudoTemplateSnapshotFields();
    }

    public function down()
    {
        $laudos_table = $this->db->prefixTable('laudos');
        $template_table = $this->db->prefixTable('laudo_templates');

        foreach (array('template_applied_at', 'template_snapshot_json', 'template_version', 'template_name', 'template_code', 'template_key', 'installation_description', 'limitations', 'premises', 'visit_date', 'scheduled_date') as $field) {
            if ($this->db->fieldExists($field, $laudos_table)) {
                $this->forge->dropColumn('laudos', $field);
            }
        }

        if ($this->db->tableExists($template_table)) {
            $this->forge->dropTable('laudo_templates', true);
        }
    }

    private function createTemplatesTable()
    {
        $table = $this->db->prefixTable('laudo_templates');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'template_key' => array('type' => 'VARCHAR', 'constraint' => 120),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'code' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'description' => array('type' => 'TEXT', 'null' => true),
            'type_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'version' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'is_default' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'structure_json' => array('type' => 'LONGTEXT', 'null' => true),
            'published_at' => array('type' => 'DATETIME', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('template_key');
        $this->forge->addKey('code');
        $this->forge->addKey('type_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('status');
        $this->forge->addKey('is_active');
        $this->forge->addKey('is_default');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_templates', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function addLaudoTemplateSnapshotFields()
    {
        $table = $this->db->prefixTable('laudos');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = array();

        if (!$this->db->fieldExists('scheduled_date', $table)) {
            $fields['scheduled_date'] = array('type' => 'DATE', 'null' => true);
        }
        if (!$this->db->fieldExists('visit_date', $table)) {
            $fields['visit_date'] = array('type' => 'DATE', 'null' => true);
        }
        if (!$this->db->fieldExists('premises', $table)) {
            $fields['premises'] = array('type' => 'TEXT', 'null' => true);
        }
        if (!$this->db->fieldExists('limitations', $table)) {
            $fields['limitations'] = array('type' => 'TEXT', 'null' => true);
        }
        if (!$this->db->fieldExists('installation_description', $table)) {
            $fields['installation_description'] = array('type' => 'TEXT', 'null' => true);
        }

        if (!$this->db->fieldExists('template_key', $table)) {
            $fields['template_key'] = array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true);
        }
        if (!$this->db->fieldExists('template_code', $table)) {
            $fields['template_code'] = array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true);
        }
        if (!$this->db->fieldExists('template_name', $table)) {
            $fields['template_name'] = array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true);
        }
        if (!$this->db->fieldExists('template_version', $table)) {
            $fields['template_version'] = array('type' => 'INT', 'constraint' => 11, 'null' => true);
        }
        if (!$this->db->fieldExists('template_snapshot_json', $table)) {
            $fields['template_snapshot_json'] = array('type' => 'LONGTEXT', 'null' => true);
        }
        if (!$this->db->fieldExists('template_applied_at', $table)) {
            $fields['template_applied_at'] = array('type' => 'DATETIME', 'null' => true);
        }

        if ($fields) {
            $this->forge->addColumn('laudos', $fields);
        }
    }
}
