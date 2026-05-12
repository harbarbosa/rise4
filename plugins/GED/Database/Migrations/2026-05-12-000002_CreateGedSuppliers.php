<?php

namespace GED\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGedSuppliers extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable("ged_suppliers");
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            "id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "auto_increment" => true
            ),
            "name" => array(
                "type" => "VARCHAR",
                "constraint" => 190
            ),
            "portal_url" => array(
                "type" => "VARCHAR",
                "constraint" => 255,
                "null" => true
            ),
            "contact_name" => array(
                "type" => "VARCHAR",
                "constraint" => 190,
                "null" => true
            ),
            "contact_email" => array(
                "type" => "VARCHAR",
                "constraint" => 190,
                "null" => true
            ),
            "contact_phone" => array(
                "type" => "VARCHAR",
                "constraint" => 50,
                "null" => true
            ),
            "notes" => array(
                "type" => "TEXT",
                "null" => true
            ),
            "is_active" => array(
                "type" => "TINYINT",
                "constraint" => 1,
                "default" => 1
            )
        ));

        $this->forge->addField("`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $this->forge->addField("`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        $this->forge->addKey("id", true);
        $this->forge->addKey("name");
        $this->forge->addKey("is_active");
        $this->forge->createTable("ged_suppliers", true, array(
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
            "COLLATE" => "utf8mb4_unicode_ci"
        ));
    }

    public function down()
    {
        $this->forge->dropTable("ged_suppliers", true);
    }
}
