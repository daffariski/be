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

        for ($i = 0; $i < 5; $i++) {
            Vehicle::create([
                'user_id'      => $users->random()->id ?? null,
                'plate_number' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}[A-Z]{2}'),
                'brand'        => fake()->randomElement(['Yamaha', 'Honda', 'Suzuki']),
                'series'       => fake()->word(),
                'year'         => fake()->numberBetween(2000, 2023),
                'color'        => fake()->colorName(),
            ]);
        }
    }
}
