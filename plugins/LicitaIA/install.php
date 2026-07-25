<?php

namespace LicitaIA\install;

function licitaia_install()
{
    \LicitaIA\Plugin::runMigrations();

    try {
        $db = db_connect('default');
        $table = $db->prefixTable('licitaia_settings');

        if (!$db->tableExists($table)) {
            return true;
        }

        $defaults = array(
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4.1-mini',
            'ai_api_base_url' => '',
            'ai_api_key' => '',
            'ai_enabled' => '1',
            'reports_enabled' => '1',
            'checklist_enabled' => '1',
            'opportunities_default_status' => 'new',
        );

        foreach ($defaults as $key => $value) {
            $existing = $db->table($table)->where('setting_name', $key)->where('deleted', 0)->get()->getRow();
            if (!$existing) {
                $db->table($table)->insert(array(
                    'setting_name' => $key,
                    'setting_value' => $value,
                    'created_at' => get_current_utc_time(),
                    'updated_at' => get_current_utc_time(),
                    'deleted' => 0,
                ));
            }
        }

        try {
            $checklist_model = model(\LicitaIA\Models\Checklist_item_model::class);
            $checklist_model->seed_default_items();
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA] Checklist seed error: ' . $e->getMessage());
        }
    } catch (\Throwable $e) {
        log_message('error', '[LicitaIA] Install hook error: ' . $e->getMessage());
    }

    return true;
}
