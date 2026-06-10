<?php

namespace PontoRH\install;

function pontorh_uninstall()
{
    try {
        $db = db_connect('default');
        $tables = array(
            $db->prefixTable('pontorh_treatment_history'),
            $db->prefixTable('pontorh_treatment_cases'),
            $db->prefixTable('pontorh_work_schedule_members'),
            $db->prefixTable('pontorh_location_assignments'),
            $db->prefixTable('pontorh_audit_logs'),
            $db->prefixTable('pontorh_monthly_summaries'),
            $db->prefixTable('pontorh_adjustment_requests'),
            $db->prefixTable('pontorh_schedule_days'),
            $db->prefixTable('pontorh_devices'),
            $db->prefixTable('pontorh_locations'),
            $db->prefixTable('pontorh_records'),
            $db->prefixTable('pontorh_work_schedules'),
            $db->prefixTable('pontorh_settings'),
        );

        foreach ($tables as $table) {
            if ($db->tableExists($table)) {
                $db->query("DROP TABLE `{$table}`");
            }
        }
    } catch (\Throwable $e) {
        log_message('error', '[PontoRH] Uninstall hook error: ' . $e->getMessage());
    }

    return true;
}
