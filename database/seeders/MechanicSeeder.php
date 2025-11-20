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
        $mechanicUsers = User::where('role', 'mechanic')->get();

        foreach ($mechanicUsers as $mechanicUser) {
            Mechanic::create([
                'user_id'        => $mechanicUser->id,
                'specialization' => 'Engine Repair',
            ]);
        }
    }
}
