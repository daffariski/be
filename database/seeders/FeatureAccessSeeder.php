<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FeatureAccessSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\FeatureAccess::insert([
            ['feature_id' => 1, 'code' => 1, 'name' => 'Melihat'],
            ['feature_id' => 2, 'code' => 1, 'name' => 'Melihat'],
            ['feature_id' => 2, 'code' => 2, 'name' => 'Membuat'],
            ['feature_id' => 2, 'code' => 3, 'name' => 'Mengubah'],
            ['feature_id' => 2, 'code' => 4, 'name' => 'Menghapus'],
            ['feature_id' => 3, 'code' => 1, 'name' => 'Melihat'],
            ['feature_id' => 3, 'code' => 2, 'name' => 'Mengubah']
        ]);
    }
}
