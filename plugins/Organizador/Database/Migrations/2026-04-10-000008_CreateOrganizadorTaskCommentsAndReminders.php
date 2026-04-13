<?php

namespace Organizador\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrganizadorTaskCommentsAndReminders extends Migration
{
    public function up()
    {
        $comments_table = $this->db->prefixTable('my_task_comments');
        if (!$this->db->tableExists($comments_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'task_id' => array('type' => 'INT', 'constraint' => 11),
                'description' => array('type' => 'LONGTEXT', 'null' => true),
                'files' => array('type' => 'LONGTEXT', 'null' => true),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));
            $this->forge->addKey('id', true);
            $this->forge->addKey('task_id');
            $this->forge->addKey('created_by');
            $this->forge->createTable('my_task_comments', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4'));
        }

        $reminders_table = $this->db->prefixTable('my_task_reminders');
        if (!$this->db->tableExists($reminders_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'task_id' => array('type' => 'INT', 'constraint' => 11),
                'title' => array('type' => 'VARCHAR', 'constraint' => 255),
                'description' => array('type' => 'TEXT', 'null' => true),
                'remind_at' => array('type' => 'DATETIME'),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'is_done' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));
            $this->forge->addKey('id', true);
            $this->forge->addKey('task_id');
            $this->forge->addKey('created_by');
            $this->forge->addKey('remind_at');
            $this->forge->createTable('my_task_reminders', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4'));
        }
    }

    public function down()
    {
        $this->forge->dropTable('my_task_reminders', true);
        $this->forge->dropTable('my_task_comments', true);
    }
}
