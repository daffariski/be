<?php

namespace Database\Seeders;

use App\Models\Mechanic;
use App\Models\User;
use Illuminate\Database\Seeder;

class MechanicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mechanicUser = User::where('email', 'mechanic@example.com')->first();

        if ($mechanicUser) {
            Mechanic::create([
                'user_id' => $mechanicUser->id,
                'specialization' => 'Engine Repair',
            ]);
        }
    }
}