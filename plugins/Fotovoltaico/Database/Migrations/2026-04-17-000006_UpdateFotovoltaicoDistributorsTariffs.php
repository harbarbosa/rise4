<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateFotovoltaicoDistributorsTariffs extends Migration
{
    public function up()
    {
        $distributors_table = $this->db->prefixTable('fv_distributors');
        if ($this->db->tableExists($distributors_table)) {
            $fields = $this->db->getFieldNames($distributors_table);
            if (is_array($fields)) {
                if (!in_array('acronym', $fields)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `acronym` VARCHAR(20) NULL AFTER `title`");
                }
                if (!in_array('state_code', $fields)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `state_code` VARCHAR(2) NULL AFTER `city`");
                }
                if (!in_array('notes', $fields)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `notes` TEXT NULL AFTER `state_code`");
                }
                if (!in_array('status', $fields)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notes`");
                }
            }
        }

        $tariffs_table = $this->db->prefixTable('fv_tariffs');
        if ($this->db->tableExists($tariffs_table)) {
            $fields = $this->db->getFieldNames($tariffs_table);
            if (is_array($fields)) {
                if (!in_array('modality', $fields)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `modality` VARCHAR(80) NULL AFTER `distributor_id`");
                }
                if (!in_array('subgroup', $fields)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `subgroup` VARCHAR(80) NULL AFTER `modality`");
                }
                if (!in_array('te', $fields)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `te` DECIMAL(16,6) NOT NULL DEFAULT 0 AFTER `subgroup`");
                }
                if (!in_array('tusd', $fields)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `tusd` DECIMAL(16,6) NOT NULL DEFAULT 0 AFTER `te`");
                }
                if (!in_array('flag_name', $fields)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `flag_name` VARCHAR(80) NULL AFTER `tusd`");
                }
                if (!in_array('flag_value', $fields)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `flag_value` DECIMAL(16,6) NOT NULL DEFAULT 0 AFTER `flag_name`");
                }
                if (!in_array('notes', $fields)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `notes` TEXT NULL AFTER `valid_to`");
                }
                if (!in_array('status', $fields)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notes`");
                }
            }
        }
    }

    public function down()
    {
        // keep upgrade reversible only at schema level if needed
    }
}
