<?php

namespace Database\Seeders;

use App\Models\Role as ModelsRole;
use Illuminate\Database\Seeder;

class Role extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = ModelsRole::create([
            'name' => 'owner',
        ]);

        $admin = ModelsRole::create([
            'name' => 'admin',
        ]);

        $owner->givePermissionTo([
            'list-produk',
            'show-produk',
            'create-produk',
            'update-produk',
            'delete-produk',
        ]);

        $admin->givePermissionTo([
            'list-produk',
            'show-produk',
        ]);
    }
}
