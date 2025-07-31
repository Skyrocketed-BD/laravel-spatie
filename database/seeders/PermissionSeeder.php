<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name'       => 'list-produk',
                'guard_name' => 'api',
            ],
            [
                'name'       => 'show-produk',
                'guard_name' => 'api',
            ],
            [
                'name'       => 'create-produk',
                'guard_name' => 'api',
            ],
            [
                'name'       => 'update-produk',
                'guard_name' => 'api',
            ],
            [
                'name'       => 'delete-produk',
                'guard_name' => 'api',
            ]
        ];

        foreach ($permissions as $key => $value) {
            DB::table('permissions')->insert($value);
        }
    }
}
