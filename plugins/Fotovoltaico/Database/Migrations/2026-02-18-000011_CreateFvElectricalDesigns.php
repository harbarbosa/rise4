<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para registrar validações elétricas.
 */
class CreateFvElectricalDesigns extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_electrical_designs');
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
            'project_version_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],
            'kit_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],
            'module_product_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],
            'inverter_product_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],
            'module_qty_total' => [
                'type' => 'DECIMAL',
                'constraint' => '12,3',
                'default' => 0
            ],
            'design_json' => [
                'type' => 'LONGTEXT',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('kit_id');
        $this->forge->createTable('fv_electrical_designs', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_electrical_designs', true);
    }
}
