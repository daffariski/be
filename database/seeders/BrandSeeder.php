<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        # Insert default brands
        $brands = [
            ['name' => 'Honda'],
            ['name' => 'Suzuki'],
            ['name' => 'Yamaha'],
        ];

        DB::table('brands')->insert($brands);
    }
}
