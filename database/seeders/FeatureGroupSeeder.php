<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FeatureGroupSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\FeatureGroup::insert([
            ['name' => 'Dashboard'],
            ['name' => 'Pengguna']
        ]);
    }
}
