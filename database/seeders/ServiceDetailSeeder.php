<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceDetail;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ServiceDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Adds product/parts used in completed services
     */
    public function run(): void
    {
        $completedServices = Service::where('status', 'done')->get();
        $products = Product::all();

        if ($completedServices->isEmpty()) {
            $this->command->warn('No completed services found. Please run ServiceSeeder first!');
            return;
        }

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please run ProductSeeder first!');
            return;
        }

        $totalDetailsCreated = 0;

        foreach ($completedServices as $service) {
            // Each service uses 1-5 different products/parts
            $numberOfProducts = rand(1, 5);
            $usedProducts = $products->random($numberOfProducts);

            foreach ($usedProducts as $product) {
                $quantity = rand(1, 3);
                $price = $product->price;
                $total = $quantity * $price;

                ServiceDetail::create([
                    'service_id' => $service->id,
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'price'      => $price,
                    'total'      => $total,
                    'created_at' => $service->updated_at, // Use service completion time
                    'updated_at' => $service->updated_at,
                ]);

                $totalDetailsCreated++;
            }
        }

        $this->command->info("✓ Created {$totalDetailsCreated} service detail records!");
        $this->command->info("  Products/parts linked to {$completedServices->count()} completed services");
    }
}
