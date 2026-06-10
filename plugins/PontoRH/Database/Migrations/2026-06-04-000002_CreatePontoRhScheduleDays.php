<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhScheduleDays extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_schedule_days');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'work_schedule_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'day_of_week' => array('type' => 'TINYINT', 'constraint' => 1),
            'start_time' => array('type' => 'TIME', 'null' => true, 'default' => null),
            'end_time' => array('type' => 'TIME', 'null' => true, 'default' => null),
            'break_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('work_schedule_id');
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('day_of_week');
        $this->forge->addKey('active');
        $this->forge->addForeignKey('work_schedule_id', 'pontorh_work_schedules', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_schedule_days', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_schedule_days', true);
    }
}
