<?php

namespace ProjectAnalizer\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaTaskLaborProfiles extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable("pa_task_labor_profiles");
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
            "project_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true
            ),
            "task_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true
            ),
            "labor_profile_id" => array(
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true
            ),
            "qty_people" => array(
                "type" => "DECIMAL",
                "constraint" => "8,2",
                "default" => 1
            ),
            "hours_per_day" => array(
                "type" => "DECIMAL",
                "constraint" => "6,2",
                "null" => true
            ),
            "notes" => array(
                "type" => "TEXT",
                "null" => true
            )
        ));

        $this->forge->addField("`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
        $this->forge->addField("`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        $this->forge->addKey("id", true);
        $this->forge->addKey("project_id");
        $this->forge->addKey("task_id");
        $this->forge->addKey("labor_profile_id");

        $this->forge->createTable("pa_task_labor_profiles", true, array(
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4"
        ));
    }

    public function down()
    {
        $this->forge->dropTable("pa_task_labor_profiles", true);
    }
}
