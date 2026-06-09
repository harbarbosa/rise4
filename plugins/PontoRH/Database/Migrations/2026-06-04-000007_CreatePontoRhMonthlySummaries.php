<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhMonthlySummaries extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_monthly_summaries');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11),
            'user_id' => array('type' => 'INT', 'constraint' => 11),
            'date' => array('type' => 'DATE'),
            'summary_year' => array('type' => 'SMALLINT', 'constraint' => 4),
            'summary_month' => array('type' => 'TINYINT', 'constraint' => 2),
            'expected_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'worked_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'overtime_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'absence_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'late_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'adjustment_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'),
            'hash' => array('type' => 'CHAR', 'constraint' => 64),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('date');
        $this->forge->addKey('status');
        $this->forge->addKey('hash', false, true);
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_monthly_summaries', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_monthly_summaries', true);
    }
}
