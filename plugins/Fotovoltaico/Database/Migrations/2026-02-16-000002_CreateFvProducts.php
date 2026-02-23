<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para criar a tabela de produtos fotovoltaicos.
 */
class CreateFvProducts extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_product');
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
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['module', 'inverter', 'service', 'other'],
                'default' => 'module'
            ],
            'brand' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true
            ],
            'sku' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true
            ],
            'cost' => [
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'default' => 0
            ],
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'default' => 0
            ],
            'specs' => [
                'type' => 'JSON',
                'null' => true
            ],
            'datasheet_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ]
        ]);

        $this->forge->addField('`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addKey('id', true);
        $this->forge->addKey('type');
        $this->forge->addKey('sku');

        $this->forge->createTable('fv_product', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_product', true);
    }
}
