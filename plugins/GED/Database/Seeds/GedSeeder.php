<?php

namespace GED\Database\Seeds;

use CodeIgniter\Database\Seeder;

class GedSeeder extends Seeder
{
    public function run()
    {
        $this->call(GedDocumentTypesSeeder::class);
        $this->call(GedSettingsSeeder::class);
    }
}
