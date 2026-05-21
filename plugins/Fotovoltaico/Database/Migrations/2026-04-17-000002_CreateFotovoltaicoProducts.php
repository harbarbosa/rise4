<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFotovoltaicoProducts extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_products');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'distributor_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'product_type' => array('type' => 'VARCHAR', 'constraint' => 20),
            'sku' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'default' => null),
            'title' => array('type' => 'VARCHAR', 'constraint' => 190),
            'description' => array('type' => 'TEXT', 'null' => true),
            'brand' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
            'model' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
            'unit' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => 'un'),
            'warranty' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
            'power_rating' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
            'efficiency' => array('type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0),
            'voltage' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'default' => null),
            'cost_price' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
            'sale_price' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
            'tax_rate' => array('type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0),
            'technical_specs_json' => array('type' => 'LONGTEXT', 'null' => true),
            'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('category_id');
        $this->forge->addKey('distributor_id');
        $this->forge->addKey('product_type');
        $this->forge->addKey('sku', false, true);
        $this->forge->addKey('title');
        $this->forge->createTable('fv_products', true, array('ENGINE' => 'InnoDB'));
    }

    public function down()
    {
        $this->forge->dropTable('fv_products', true);
    }
}
