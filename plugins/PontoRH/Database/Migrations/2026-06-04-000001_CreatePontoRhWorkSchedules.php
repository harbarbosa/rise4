<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhWorkSchedules extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_work_schedules');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'description' => array('type' => 'TEXT', 'null' => true),
            'start_time' => array('type' => 'TIME', 'null' => true, 'default' => null),
            'end_time' => array('type' => 'TIME', 'null' => true, 'default' => null),
            'break_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'tolerance_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'weekly_hours' => array('type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => null),
            'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->addKey('active');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_work_schedules', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_work_schedules', true);
    }
}
