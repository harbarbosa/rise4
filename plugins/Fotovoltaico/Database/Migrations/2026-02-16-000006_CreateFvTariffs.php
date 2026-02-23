<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para criar a tabela de tarifas por distribuidora.
 */
class CreateFvTariffs extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_tariffs');
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
            'utility_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false
            ],
            'group_type' => [
                'type' => 'ENUM',
                'constraint' => ['A', 'B'],
                'default' => 'B'
            ],
            'modality' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true
            ],
            'te' => [
                'type' => 'DECIMAL',
                'constraint' => '16,6',
                'default' => 0
            ],
            'tusd' => [
                'type' => 'DECIMAL',
                'constraint' => '16,6',
                'default' => 0
            ],
            'other' => [
                'type' => 'JSON',
                'null' => true
            ],
            'valid_from' => [
                'type' => 'DATE',
                'null' => true
            ],
            'valid_to' => [
                'type' => 'DATE',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('utility_id');
        $this->forge->addKey('group_type');

        $this->forge->createTable('fv_tariffs', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_tariffs', true);
    }
}
