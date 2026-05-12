<?php

namespace GED\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGedDocumentTypes extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable("ged_document_types");
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
            "description" => array(
                "type" => "TEXT",
                "null" => true
            ),
            "has_expiration" => array(
                "type" => "TINYINT",
                "constraint" => 1,
                "default" => 0
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
        $this->forge->addKey("name", false, true);
        $this->forge->addKey("is_active");
        $this->forge->createTable("ged_document_types", true, array(
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
            "COLLATE" => "utf8mb4_unicode_ci"
        ));
    }

    public function down()
    {
        $this->forge->dropTable("ged_document_types", true);
    }
}
