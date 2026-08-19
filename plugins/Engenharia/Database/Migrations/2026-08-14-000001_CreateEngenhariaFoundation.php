<?php

namespace Engenharia\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEngenhariaFoundation extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists($this->db->prefixTable('eng_settings'))) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'setting_name' => array('type' => 'VARCHAR', 'constraint' => 100),
                'setting_value' => array('type' => 'TEXT', 'null' => true),
                'created_at' => array('type' => 'DATETIME', 'null' => true),
                'updated_at' => array('type' => 'DATETIME', 'null' => true),
            ));
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('setting_name');
            $this->forge->createTable('eng_settings', true, array('ENGINE' => 'InnoDB'));
        }

        if (!$this->db->tableExists($this->db->prefixTable('eng_types'))) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'name' => array('type' => 'VARCHAR', 'constraint' => 150),
                'code' => array('type' => 'VARCHAR', 'constraint' => 50),
                'is_enabled' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                'created_at' => array('type' => 'DATETIME', 'null' => true),
                'updated_at' => array('type' => 'DATETIME', 'null' => true),
            ));
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('eng_types', true, array('ENGINE' => 'InnoDB'));
        }
    }

    public function down()
    {
        $this->forge->dropTable('eng_types', true);
        $this->forge->dropTable('eng_settings', true);
    }
}
