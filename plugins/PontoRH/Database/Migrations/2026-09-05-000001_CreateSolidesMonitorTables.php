<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSolidesMonitorTables extends Migration
{
    public function up()
    {
        $prefix = $this->db->DBPrefix;
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}pontorh_solides_employees` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `team_member_id` INT NULL, `solides_employee_id` VARCHAR(100) NOT NULL, `employee_name` VARCHAR(255) NULL, `active` TINYINT(1) NOT NULL DEFAULT 1, `last_sync_at` DATETIME NULL, PRIMARY KEY (`id`), UNIQUE KEY `solides_employee_id` (`solides_employee_id`), KEY `team_member_id` (`team_member_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}pontorh_solides_punches` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `solides_record_id` VARCHAR(120) NOT NULL, `solides_employee_id` VARCHAR(100) NOT NULL, `team_member_id` INT NULL, `punch_time` DATETIME NOT NULL, `raw_payload` LONGTEXT NULL, `synced_at` DATETIME NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `solides_record_id` (`solides_record_id`), KEY `employee_date` (`solides_employee_id`,`punch_time`), KEY `team_member_id` (`team_member_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}pontorh_solides_alerts` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `team_member_id` INT NULL, `solides_employee_id` VARCHAR(100) NOT NULL, `work_date` DATE NOT NULL, `punch_count` INT NOT NULL DEFAULT 0, `expected_count` INT NOT NULL DEFAULT 4, `status` VARCHAR(30) NOT NULL DEFAULT 'pending', `employee_notified_at` DATETIME NULL, `dp_seen_at` DATETIME NULL, `resolved_at` DATETIME NULL, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `employee_work_date` (`solides_employee_id`,`work_date`), KEY `status` (`status`), KEY `team_member_id` (`team_member_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_solides_alerts', true);
        $this->forge->dropTable('pontorh_solides_punches', true);
        $this->forge->dropTable('pontorh_solides_employees', true);
    }
}
