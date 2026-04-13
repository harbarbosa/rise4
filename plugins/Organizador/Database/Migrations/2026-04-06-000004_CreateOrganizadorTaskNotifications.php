<?php

namespace Organizador\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrganizadorTaskNotifications extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('my_task_notifications');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'task_id' => array('type' => 'INT', 'constraint' => 11),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'event' => array('type' => 'VARCHAR', 'constraint' => 80),
            'channel' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'system'),
            'is_sent' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'sent_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('event');
        $this->forge->createTable('my_task_notifications', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4'));
    }

    public function down()
    {
        $this->forge->dropTable('my_task_notifications', true);
    }
}
