<?php

namespace ProjectAnalizer\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaLaborProfiles extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable("pa_labor_profiles");
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
            "hourly_cost" => array(
                "type" => "DECIMAL",
                "constraint" => "16,2",
                "default" => 0
            ),
            "default_hours_per_day" => array(
                "type" => "DECIMAL",
                "constraint" => "6,2",
                "default" => 8
            ),
            "active" => array(
                "type" => "TINYINT",
                "constraint" => 1,
                "default" => 1
            )
        ));

        $this->forge->addField("`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
        $this->forge->addField("`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        $this->forge->addKey("id", true);
        $this->forge->addKey("name");

        $this->forge->createTable("pa_labor_profiles", true, array(
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4"
        ));
    }

    public function down()
    {
        $this->forge->dropTable("pa_labor_profiles", true);
    }
}
