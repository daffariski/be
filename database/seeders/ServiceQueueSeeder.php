<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceQueue;
use Illuminate\Database\Seeder;

class ServiceQueueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates queue entries for all services with 'waiting' status
     */
    public function run(): void
    {
        // Get all waiting services ordered by queued_at time (first come, first served)
        $waitingServices = Service::where('status', 'waiting')
            ->whereNotNull('queue_date')
            ->whereNotNull('queued_at')
            ->orderBy('queue_date', 'asc')
            ->orderBy('queued_at', 'asc')
            ->get();

        if ($waitingServices->isEmpty()) {
            $this->command->warn('No waiting services found with queue_date. Please run ServiceSeeder first!');
            return;
        }

        // Group by queue_date and create queue numbers
        $servicesByDate = $waitingServices->groupBy('queue_date');
        $totalQueues = 0;

        foreach ($servicesByDate as $date => $services) {
            $queueNumber = 1;

            foreach ($services as $service) {
                ServiceQueue::create([
                    'service_id'   => $service->id,
                    'queue_number' => $queueNumber,
                    'queue_date'   => $date,
                    'created_at'   => $service->created_at,
                    'updated_at'   => $service->updated_at,
                ]);

                $queueNumber++;
                $totalQueues++;
            }

            $this->command->info("✓ Created " . ($queueNumber - 1) . " queues for $date");
        }

        $this->command->info("✓ Total queue entries created: $totalQueues");
    }
}
