<?php

namespace GED\Database\Seeds;

use CodeIgniter\Database\Seeder;

class GedSettingsSeeder extends Seeder
{
    public function run()
    {
        $table = $this->db->prefixTable('ged_settings');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $settings = array(
            'alert_days' => '30,15,7,0',
            'enable_native_notifications' => '1',
            'notify_admins' => '1',
            'notify_document_creator' => '1',
            'upload_max_size_mb' => '20',
            'allowed_file_extensions' => 'pdf,jpg,jpeg,png,doc,docx',
            'default_document_status' => 'pending',
            'default_submission_status' => 'pending'
        );

        foreach ($settings as $name => $value) {
            $existing = $this->db->table($table)->select('id')->where('setting_name', $name)->get()->getRowArray();
            $now = date('Y-m-d H:i:s');
            $payload = array(
                'setting_name' => $name,
                'setting_value' => $value,
                'updated_at' => $now
            );

            if ($existing) {
                $this->db->table($table)->where('id', (int) $existing['id'])->update($payload);
            } else {
                $payload['created_at'] = $now;
                $this->db->table($table)->insert($payload);
            }
        }
    }
}
