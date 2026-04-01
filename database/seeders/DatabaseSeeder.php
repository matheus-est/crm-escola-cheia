<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MenuGroupSeeder::class,
            ModuleSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            CrmPermissionSeeder::class,
            TermVersionSeeder::class,
            SystemSettingSeeder::class,
            SegmentSeeder::class,
            OutcomeSeeder::class,
        ]);
    }
}
