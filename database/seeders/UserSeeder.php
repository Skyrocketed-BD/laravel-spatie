<?php

namespace Database\Seeders;

use App\Models\main\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $users = [
        //     [
        //         'id_users' => 1,
        //         'name'     => fake()->name(),
        //         'username' => 'admin',
        //         'email'    => fake()->unique()->safeEmail(),
        //         'password' => Hash::make('admin123'),
        //     ],
        //     [
        //         'id_users' => 2,
        //         'name'     => fake()->name(),
        //         'username' => 'kontraktor',
        //         'email'    => fake()->unique()->safeEmail(),
        //         'password' => Hash::make('kontraktor123'),
        //     ],
        //     [
        //         'id_users' => 3,
        //         'name'     => fake()->name(),
        //         'username' => 'akunting',
        //         'email'    => fake()->unique()->safeEmail(),
        //         'password' => Hash::make('akunting123'),
        //     ],
        //     [
        //         'id_users' => 4,
        //         'name'     => fake()->name(),
        //         'username' => 'kasir',
        //         'email'    => fake()->unique()->safeEmail(),
        //         'password' => Hash::make('kasir123'),
        //     ],
        // ];
        
        $user = User::create([
            'name'     => fake()->name(),
            'username' => 'admin',
            'email'    => fake()->unique()->safeEmail(),
            'password' => Hash::make('admin123'),
        ]);

        $user->assignRole('Administrator');
    }
}
