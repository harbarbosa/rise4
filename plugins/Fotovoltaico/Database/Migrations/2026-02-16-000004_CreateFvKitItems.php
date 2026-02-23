<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para criar a tabela de itens de kits fotovoltaicos.
 */
class CreateFvKitItems extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_kit_items');
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
            'kit_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false
            ],
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1
            ],
            'is_optional' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('kit_id');
        $this->forge->addKey('product_id');

        $this->forge->createTable('fv_kit_items', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_kit_items', true);
    }
}
