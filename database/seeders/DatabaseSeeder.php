<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class,    // Users, Admins, Customers, Mechanics, Products, Brands
            VehicleSeeder::class,       // Vehicles
            // ServiceSeeder::class,       // Services (done, waiting, process)
            // ServiceQueueSeeder::class,  // Queue numbers for waiting services
            // ServiceDetailSeeder::class, // Products/parts used in completed services
        ]);
    }
}
