<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para criar a tabela de distribuidoras (utilities).
 */
class CreateFvUtilities extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_utilities');
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
            'uf' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
                'null' => true
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true
            ]
        ]);

        $this->forge->addField('`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addKey('id', true);
        $this->forge->addKey('uf');
        $this->forge->addKey('code');

        $this->forge->createTable('fv_utilities', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_utilities', true);
    }
}
