<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para criar a tabela de kits fotovoltaicos.
 */
class CreateFvKits extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_kits');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 190
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'default_losses' => [
                'type' => 'DECIMAL',
                'constraint' => '6,2',
                'default' => 0
            ]
        ]);

        $this->forge->addField('`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');

        $this->forge->createTable('fv_kits', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_kits', true);
    }
}
