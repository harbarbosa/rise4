<?php

namespace GED\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGedNotificationLogs extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable("ged_notification_logs");
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
            "submission_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "null" => true
            ),
            "user_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true
            ),
            "notification_type" => array(
                "type" => "VARCHAR",
                "constraint" => 100
            ),
            "days_before" => array(
                "type" => "INT",
                "constraint" => 11,
                "null" => true
            ),
            "sent_at" => array(
                "type" => "DATETIME",
                "null" => false
            )
        ));

        $this->forge->addField("`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->forge->addKey("id", true);
        $this->forge->addKey("document_id");
        $this->forge->addKey("submission_id");
        $this->forge->addKey("user_id");
        $this->forge->addKey("notification_type");
        $this->forge->addKey(array("document_id", "notification_type", "days_before"));
        $this->forge->addKey(array("sent_at", "notification_type"));
        $this->forge->createTable("ged_notification_logs", true, array(
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
            "COLLATE" => "utf8mb4_unicode_ci"
        ));
    }

    public function down()
    {
        $this->forge->dropTable("ged_notification_logs", true);
    }
}
