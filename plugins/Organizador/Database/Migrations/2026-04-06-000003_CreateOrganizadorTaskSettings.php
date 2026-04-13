<?php

namespace Organizador\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrganizadorTaskSettings extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('my_task_settings');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'setting_name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'setting_value' => array('type' => 'LONGTEXT', 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('setting_name', true);
        $this->forge->createTable('my_task_settings', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4'));
    }

    public function down()
    {
        $this->forge->dropTable('my_task_settings', true);
    }
}
