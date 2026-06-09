<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTeamMemberAndScheduleTypeToWorkSchedules extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_work_schedules');
        if (!$this->db->tableExists($table)) {
            return;
        }

        if (!$this->db->fieldExists('team_member_id', $table)) {
            $this->forge->addColumn('pontorh_work_schedules', array(
                'team_member_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null, 'after' => 'name'),
            ));
        }

        if (!$this->db->fieldExists('schedule_type', $table)) {
            $this->forge->addColumn('pontorh_work_schedules', array(
                'schedule_type' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'comercial', 'after' => 'description'),
            ));
        }

        if (!$this->db->fieldExists('bank_hours', $table)) {
            $this->forge->addColumn('pontorh_work_schedules', array(
                'bank_hours' => array('type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00', 'after' => 'weekly_hours'),
            ));
        }

        if (!$this->db->fieldExists('extra_tolerance_minutes', $table)) {
            $this->forge->addColumn('pontorh_work_schedules', array(
                'extra_tolerance_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'tolerance_minutes'),
            ));
        }

        $fields = $this->db->getFieldData($table);
        $fk_exists = false;
        foreach ($fields as $field) {
            if ($field->name === 'team_member_id') {
                $fk_exists = true;
                break;
            }
        }

        if ($fk_exists) {
            try {
                $this->db->query("ALTER TABLE {$table} ADD CONSTRAINT `fk_pontorh_work_schedules_team_member_id` FOREIGN KEY (`team_member_id`) REFERENCES `" . $this->db->prefixTable('users') . "`(`id`) ON DELETE SET NULL ON UPDATE CASCADE");
            } catch (\Throwable $e) {
                log_message('error', '[PontoRH] Migration FK error: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        $table = $this->db->prefixTable('pontorh_work_schedules');
        if (!$this->db->tableExists($table)) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE {$table} DROP FOREIGN KEY `fk_pontorh_work_schedules_team_member_id`");
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Migration FK rollback error: ' . $e->getMessage());
        }

        if ($this->db->fieldExists('team_member_id', $table)) {
            $this->forge->dropColumn('pontorh_work_schedules', 'team_member_id');
        }
        if ($this->db->fieldExists('schedule_type', $table)) {
            $this->forge->dropColumn('pontorh_work_schedules', 'schedule_type');
        }
        if ($this->db->fieldExists('bank_hours', $table)) {
            $this->forge->dropColumn('pontorh_work_schedules', 'bank_hours');
        }
        if ($this->db->fieldExists('extra_tolerance_minutes', $table)) {
            $this->forge->dropColumn('pontorh_work_schedules', 'extra_tolerance_minutes');
        }
    }
}
