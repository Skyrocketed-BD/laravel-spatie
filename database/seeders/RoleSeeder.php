<?php

namespace Database\Seeders;

use App\Models\main\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminsitrator = Role::create([
            'name'       => 'Administrator',
            'guard_name' => 'api',
        ]);

        $kontraktor = Role::create([
            'name'       => 'Kontraktor',
            'guard_name' => 'api',
        ]);

        $akunting = Role::create([
            'name'       => 'Akunting',
            'guard_name' => 'api',
        ]);

        $kasir = Role::create([
            'name'       => 'Kasir',
            'guard_name' => 'api',
        ]);

        $data_entry = Role::create([
            'name'       => 'Data Entry',
            'guard_name' => 'api',
        ]);

        $adminsitrator->givePermissionTo([
            'list-kontak',
            'show-kontak',
            'create-kontak',
            'update-kontak',
            'delete-kontak',
        ]);
    }
}
