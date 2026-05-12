<?php

namespace GED\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGedDocumentSubmissions extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable("ged_document_submissions");
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
            "document_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true
            ),
            "supplier_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "null" => true
            ),
            "submitted_at" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "portal_status" => array(
                "type" => "ENUM",
                "constraint" => array("pending", "submitted", "approved", "rejected", "expired"),
                "default" => "pending"
            ),
            "portal_reference" => array(
                "type" => "VARCHAR",
                "constraint" => 190,
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
        $this->forge->addKey("document_id");
        $this->forge->addKey("supplier_id");
        $this->forge->addKey("portal_status");
        $this->forge->addKey(array("portal_status", "submitted_at"));
        $this->forge->addKey(array("document_id", "portal_status"));
        $this->forge->addKey(array("supplier_id", "portal_status"));
        $this->forge->addKey(array("deleted_at", "portal_status"));
        $this->forge->createTable("ged_document_submissions", true, array(
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
            "COLLATE" => "utf8mb4_unicode_ci"
        ));
    }

    public function down()
    {
        $this->forge->dropTable("ged_document_submissions", true);
    }
}
