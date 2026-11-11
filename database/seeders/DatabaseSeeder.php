<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            IncidentCategorySeeder::class,
            RoleUserSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(ReportTrackingSeeder::class);
        }
    }
}
