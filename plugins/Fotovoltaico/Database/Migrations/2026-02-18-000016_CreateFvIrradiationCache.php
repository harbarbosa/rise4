<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cache de irradiação mensal por lat/lon.
 */
class CreateFvIrradiationCache extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_irradiation_cache');
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
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false
            ],
            'lat' => [
                'type' => 'DECIMAL',
                'constraint' => '10,6',
                'null' => false
            ],
            'lon' => [
                'type' => 'DECIMAL',
                'constraint' => '10,6',
                'null' => false
            ],
            'monthly_json' => [
                'type' => 'LONGTEXT',
                'null' => false
            ],
            'annual_value' => [
                'type' => 'DECIMAL',
                'constraint' => '12,4',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['provider', 'lat', 'lon']);
        $this->forge->createTable('fv_irradiation_cache', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_irradiation_cache', true);
    }
}
