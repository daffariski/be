<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Admin;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $mechanics = Mechanic::all();
        $admins    = Admin::all();
        $vehicles  = Vehicle::with('user.customer')->get();

        if ($customers->isEmpty() || $mechanics->isEmpty() || $admins->isEmpty() || $vehicles->isEmpty()) {
            $this->command->warn('Please ensure Customers, Mechanics, Admins, and Vehicles are seeded first!');
            return;
        }

        $serviceDescriptions = [
            'Service rutin bulanan',
            'Ganti oli mesin',
            'Tune up lengkap',
            'Ganti kampas rem depan belakang',
            'Service CVT dan ganti roller',
            'Perbaikan mesin',
            'Ganti ban depan',
            'Perbaikan sistem kelistrikan',
            'Ganti rantai dan gear',
            'Service karburator',
            'Ganti busi dan filter udara',
            'Cek dan service rem',
        ];

        // =============================================
        // COMPLETED SERVICES (status: done)
        // Past 30 days with random dates
        // =============================================
        for ($i = 0; $i < 15; $i++) {
            $daysAgo = rand(1, 30);
            $vehicle = $vehicles->random();

            Service::create([
                'customer_id'   => $vehicle->user?->customer?->id ?? $customers->random()->id,
                'customer_name' => null,
                'mechanic_id'   => $mechanics->random()->id,
                'admin_id'      => $admins->random()->id,
                'vehicle_id'    => $vehicle->id,
                'description'   => $serviceDescriptions[array_rand($serviceDescriptions)],
                'status'        => 'done',
                'created_at'    => Carbon::now()->subDays($daysAgo)->subHours(rand(1, 8)),
                'updated_at'    => Carbon::now()->subDays($daysAgo)->addHours(rand(1, 4)),
            ]);
        }

        // =============================================
        // WAITING SERVICES (status: waiting)
        // Today's services that will have queue numbers
        // =============================================
        $waitingServicesData = [];

        // Create 8 waiting services for today
        for ($i = 0; $i < 8; $i++) {
            $vehicle = $vehicles->random();
            $queuedAt = Carbon::now()->subHours($i)->subMinutes(rand(0, 59));

            $waitingServicesData[] = [
                'customer_id'    => $vehicle->user?->customer?->id ?? $customers->random()->id,
                'customer_name'  => null,
                'mechanic_id'    => null, // Not assigned yet
                'admin_id'       => $admins->random()->id,
                'vehicle_id'     => $vehicle->id,
                'description'    => $serviceDescriptions[array_rand($serviceDescriptions)],
                'status'         => 'waiting',
                'queue_date'     => Carbon::today(), // Queued for today
                'queue_priority' => 999, // Default priority
                'queued_at'      => $queuedAt, // When customer queued
                'payment_status' => 'unpaid',
                'created_at'     => $queuedAt,
                'updated_at'     => $queuedAt,
            ];
        }

        Service::insert($waitingServicesData);

        // =============================================
        // IN PROCESS SERVICES (status: process)
        // Currently being worked on
        // =============================================
        for ($i = 0; $i < 3; $i++) {
            $vehicle = $vehicles->random();

            Service::create([
                'customer_id'   => $vehicle->user?->customer?->id ?? $customers->random()->id,
                'customer_name' => null,
                'mechanic_id'   => $mechanics->random()->id,
                'admin_id'      => $admins->random()->id,
                'vehicle_id'    => $vehicle->id,
                'description'   => $serviceDescriptions[array_rand($serviceDescriptions)],
                'status'        => 'process',
                'created_at'    => Carbon::now()->subHours(rand(2, 5)),
                'updated_at'    => Carbon::now()->subMinutes(rand(10, 60)),
            ]);
        }

        // =============================================
        // WALK-IN CUSTOMERS (no customer_id)
        // Services from customers not in the system
        // =============================================
        $walkInNames = [
            'Budi Santoso',
            'Siti Rahayu',
            'Ahmad Hidayat',
            'Dewi Lestari',
            'Eko Prasetyo',
        ];

        for ($i = 0; $i < 5; $i++) {
            $vehicle = $vehicles->whereNull('user_id')->first() ?? $vehicles->random();
            $status = $i < 3 ? 'done' : 'waiting';
            $daysAgo = $status === 'done' ? rand(1, 15) : 0;
            $createdAt = Carbon::now()->subDays($daysAgo)->subHours(rand(0, 8));

            Service::create([
                'customer_id'    => null,
                'customer_name'  => $walkInNames[$i],
                'mechanic_id'    => $status === 'done' ? $mechanics->random()->id : null,
                'admin_id'       => $admins->random()->id,
                'vehicle_id'     => $vehicle->id,
                'description'    => $serviceDescriptions[array_rand($serviceDescriptions)],
                'status'         => $status,
                'queue_date'     => $status === 'waiting' ? Carbon::today() : null,
                'queue_priority' => 999,
                'queued_at'      => $status === 'waiting' ? $createdAt : null,
                'payment_status' => $status === 'done' ? 'paid' : 'unpaid',
                'price'          => $status === 'done' ? rand(50, 500) * 1000 : 0,
                'payment_method' => $status === 'done' ? (['cash', 'qris', 'transfer'])[array_rand(['cash', 'qris', 'transfer'])] : null,
                'created_at'     => $createdAt,
                'updated_at'     => Carbon::now()->subDays($daysAgo)->addHours($status === 'done' ? rand(1, 3) : 0),
            ]);
        }

        $this->command->info('✓ Services seeded successfully!');
        $this->command->info('  - Completed services: 15');
        $this->command->info('  - Waiting services: 8 (will get queue numbers)');
        $this->command->info('  - In-process services: 3');
        $this->command->info('  - Walk-in customer services: 5');
    }
}
