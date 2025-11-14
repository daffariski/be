<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Feature::insert([
            ['feature_group_id' => 1, 'code' => 001, 'name' => 'Dashboard', 'description' => 'Halaman Dashboard'],
            ['feature_group_id' => 2, 'code' => 002, 'name' => 'Manajemen Pengguna', 'description' => 'Halaman Manajemen Pengguna'],
            ['feature_group_id' => 2, 'code' => 003, 'name' => 'Manajemen Role', 'description' => 'Halaman Manajemen Role']
        ]);
    }
}
