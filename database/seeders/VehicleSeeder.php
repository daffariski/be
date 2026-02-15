<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $series = [
            'Honda'  => ['Beat', 'Vario', 'Scoopy', 'Genio', 'PCX'],
            'Suzuki' => ['Nex', 'Satria', 'Smash', 'Shogun', 'Bravo'],
            'Yamaha' => ['Mio', 'Aerox', 'Fazzio', 'Vixion', 'MX King'],
        ];

        for ($i = 0; $i < 5; $i++) {
            $brand = fake()->randomElement(['Yamaha', 'Honda', 'Suzuki']);
            Vehicle::create([
                'user_id'      => $users->random()->id ?? null,
                'plate_number' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}[A-Z]{2}'),
                'brand'        => $brand,
                'series'       => fake()->randomElement($series[$brand]),
                'year'         => fake()->numberBetween(2000, 2023),
                'color'        => fake()->colorName(),
            ]);
        }
    }
}
