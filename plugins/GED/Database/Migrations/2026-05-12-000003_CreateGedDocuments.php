<?php

namespace GED\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGedDocuments extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable("ged_documents");
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
            "title" => array(
                "type" => "VARCHAR",
                "constraint" => 255
            ),
            "document_type_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true
            ),
            "owner_type" => array(
                "type" => "ENUM",
                "constraint" => array("company", "employee", "supplier"),
                "default" => "company"
            ),
            "owner_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "null" => true
            ),
            "employee_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "null" => true
            ),
            "supplier_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "null" => true
            ),
            "issue_date" => array(
                "type" => "DATE",
                "null" => true
            ),
            "expiration_date" => array(
                "type" => "DATE",
                "null" => true
            ),
            "status" => array(
                "type" => "ENUM",
                "constraint" => array("valid", "expiring", "expired", "pending", "archived"),
                "default" => "pending"
            ),
            "file_path" => array(
                "type" => "VARCHAR",
                "constraint" => 255,
                "null" => true
            ),
            "original_filename" => array(
                "type" => "VARCHAR",
                "constraint" => 255,
                "null" => true
            ),
            "notes" => array(
                "type" => "TEXT",
                "null" => true
            ),
            "created_by" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "null" => true
            ),
            "updated_by" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "null" => true
            )
        ));

        $this->forge->addField("`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $this->forge->addField("`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->forge->addField("`deleted_at` DATETIME NULL DEFAULT NULL");

        $this->forge->addKey("id", true);
        $this->forge->addKey("document_type_id");
        $this->forge->addKey("supplier_id");
        $this->forge->addKey("employee_id");
        $this->forge->addKey("owner_type");
        $this->forge->addKey("owner_id");
        $this->forge->addKey(array("status", "expiration_date"));
        $this->forge->addKey(array("document_type_id", "status"));
        $this->forge->addKey(array("supplier_id", "status"));
        $this->forge->addKey(array("expiration_date", "deleted_at"));
        $this->forge->createTable("ged_documents", true, array(
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
            "COLLATE" => "utf8mb4_unicode_ci"
        ));
    }

    public function down()
    {
        $this->forge->dropTable("ged_documents", true);
    }
}
