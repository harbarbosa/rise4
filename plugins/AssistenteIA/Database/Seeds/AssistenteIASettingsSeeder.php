<?php

namespace AssistenteIA\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AssistenteIASettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = model('App\\Models\\Settings_model');
        if (!$settings->get_setting('assistente_ia_model')) {
            $settings->save_setting('assistente_ia_model', 'openai/gpt-4o-mini');
        }
    }
}
