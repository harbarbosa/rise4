<?php

namespace Organizador\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrganizadorTaskTags extends Migration
{
    public function up()
    {
        $db = db_connect('default');
        $prefix = $db->getPrefix();

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}my_task_tags` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(190) NOT NULL,
            `color` VARCHAR(20) NULL DEFAULT NULL,
            `sort` INT(11) NOT NULL DEFAULT 0,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `title` (`title`),
            KEY `sort` (`sort`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    public function down()
    {
        $db = db_connect('default');
        $prefix = $db->getPrefix();
        $this->db->query("DROP TABLE IF EXISTS `{$prefix}my_task_tags`");
    }
}
