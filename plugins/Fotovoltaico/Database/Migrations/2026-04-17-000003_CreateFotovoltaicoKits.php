<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFotovoltaicoKits extends Migration
{
    public function up()
    {
        $kits_table = $this->db->prefixTable('fv_kits');
        if (!$this->db->tableExists($kits_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'category_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'distributor_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'title' => array('type' => 'VARCHAR', 'constraint' => 190),
                'code' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'default' => null),
                'description' => array('type' => 'TEXT', 'null' => true),
                'power_kwp' => array('type' => 'DECIMAL', 'constraint' => '16,3', 'default' => 0),
                'notes' => array('type' => 'LONGTEXT', 'null' => true),
                'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'),
                'total_cost' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'total_price' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'margin_value' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'margin_percent' => array('type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0),
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
            $this->forge->addKey('code', false, true);
            $this->forge->addKey('title');
            $this->forge->createTable('fv_kits', true, array('ENGINE' => 'InnoDB'));
        }

        $kit_items_table = $this->db->prefixTable('fv_kit_items');
        if (!$this->db->tableExists($kit_items_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'kit_id' => array('type' => 'INT', 'constraint' => 11),
                'product_id' => array('type' => 'INT', 'constraint' => 11),
                'quantity' => array('type' => 'DECIMAL', 'constraint' => '16,4', 'default' => 1),
                'unit_price' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'unit_cost' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'total_price' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'total_cost' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'notes' => array('type' => 'TEXT', 'null' => true),
                'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('kit_id');
            $this->forge->addKey('product_id');
            $this->forge->addKey(array('kit_id', 'product_id'), false, true, 'kit_product_unique');
            $this->forge->createTable('fv_kit_items', true, array('ENGINE' => 'InnoDB'));
        }
    }

    public function down()
    {
        $this->forge->dropTable('fv_kit_items', true);
        $this->forge->dropTable('fv_kits', true);
    }
}
