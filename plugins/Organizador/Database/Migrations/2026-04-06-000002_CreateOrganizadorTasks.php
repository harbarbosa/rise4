<?php

namespace Organizador\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrganizadorTasks extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('my_tasks');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'title' => array('type' => 'VARCHAR', 'constraint' => 255),
            'description' => array('type' => 'TEXT', 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'),
            'priority' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'medium'),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'assigned_to' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'start_date' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'due_date' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'reminder_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'position' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'is_favorite' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'labels' => array('type' => 'TEXT', 'null' => true),
            'notify_assigned_to' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'notify_creator' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'email_notification' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'reminder_sent_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'completed_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('priority');
        $this->forge->addKey('assigned_to');
        $this->forge->addKey('created_by');
        $this->forge->addKey('category_id');
        $this->forge->createTable('my_tasks', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4'));
    }

    public function down()
    {
        $this->forge->dropTable('my_tasks', true);
    }
}
