<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            MenuModuleSeeder::class,
            MenuCategorySeeder::class,
            MenuBodySeeder::class,
            RoleAccessSeeder::class,
            TransactionNameSeeder::class,
            CogSeeder::class,
            CoaClasificationSeeder::class,
            ArrangementsSeeder::class
        ]);
    }
}
