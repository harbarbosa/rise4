<?php

namespace Organizador\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReminderBeforeFieldsToOrganizadorTasks extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('my_tasks');
        if (!$this->db->tableExists($table)) {
            return;
        }

        if (!$this->db->fieldExists('reminder_before_value', $table)) {
            $this->forge->addColumn($table, array(
                'reminder_before_value' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'default' => null,
                    'after' => 'reminder_at',
                ),
            ));
        }

        if (!$this->db->fieldExists('reminder_before_unit', $table)) {
            $this->forge->addColumn($table, array(
                'reminder_before_unit' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'default' => null,
                    'after' => 'reminder_before_value',
                ),
            ));
        }
    }

    public function down()
    {
        $table = $this->db->prefixTable('my_tasks');
        if (!$this->db->tableExists($table)) {
            return;
        }

        if ($this->db->fieldExists('reminder_before_unit', $table)) {
            $this->forge->dropColumn($table, 'reminder_before_unit');
        }

        if ($this->db->fieldExists('reminder_before_value', $table)) {
            $this->forge->dropColumn($table, 'reminder_before_value');
        }
    }
}
