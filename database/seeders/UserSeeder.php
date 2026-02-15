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
        User::factory()->count(1)->create([
            'role'  => 'admin',
            'email' => 'admin@email.com'
        ]);
        User::factory()->count(1)->create([
            'role'  => 'customer',
            'email' => 'customer@email.com'
        ]);
        User::factory()->count(1)->create([
            'role'  => 'mechanic',
            'email' => 'mechanic@email.com'
        ]);
    }
}
