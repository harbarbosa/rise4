<?php

namespace GED\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGedSettings extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable("ged_settings");
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
            "setting_name" => array(
                "type" => "VARCHAR",
                "constraint" => 190
            ),
            "setting_value" => array(
                "type" => "TEXT",
                "null" => true
            )
        ));

        $this->forge->addField("`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $this->forge->addField("`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        $this->forge->addKey("id", true);
        $this->forge->addKey("setting_name", false, true);
        $this->forge->createTable("ged_settings", true, array(
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
            "COLLATE" => "utf8mb4_unicode_ci"
        ));
    }

    public function down()
    {
        $this->forge->dropTable("ged_settings", true);
    }
}
