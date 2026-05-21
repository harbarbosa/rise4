<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFotovoltaicoCatalog extends Migration
{
    public function up()
    {
        $categories_table = $this->db->prefixTable('fv_product_categories');
        if (!$this->db->tableExists($categories_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'title' => array('type' => 'VARCHAR', 'constraint' => 190),
                'slug' => array('type' => 'VARCHAR', 'constraint' => 190),
                'description' => array('type' => 'TEXT', 'null' => true),
                'color' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null),
                'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('slug', false, true);
            $this->forge->addKey('title');
            $this->forge->createTable('fv_product_categories', true, array('ENGINE' => 'InnoDB'));
        }

        $distributors_table = $this->db->prefixTable('fv_distributors');
        if (!$this->db->tableExists($distributors_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'title' => array('type' => 'VARCHAR', 'constraint' => 190),
                'legal_name' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'default' => null),
                'document' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'default' => null),
                'email' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'default' => null),
                'phone' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'default' => null),
                'website' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                'address' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                'city' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                'state' => array('type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'default' => null),
                'country' => array('type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'default' => null),
                'note' => array('type' => 'TEXT', 'null' => true),
                'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('document', false, true);
            $this->forge->addKey('title');
            $this->forge->createTable('fv_distributors', true, array('ENGINE' => 'InnoDB'));
        }
    }

    public function down()
    {
        $this->forge->dropTable('fv_distributors', true);
        $this->forge->dropTable('fv_product_categories', true);
    }
}
