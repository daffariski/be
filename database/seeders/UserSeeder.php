<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@email.com',
            'password' => Hash::make('asdasdasd'),
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'Customer User',
            'email' => 'customer@email.com',
            'password' => Hash::make('asdasdasd'),
        ]);

        User::create([
            'name' => 'Mechanic User',
            'email' => 'mechanic@email.com',
            'password' => Hash::make('asdasdasd'),
            'role' => 'mechanic'
        ]);
    }
}
