<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products= [
    "E-1 Cylinder Head",
    "E-2 Camshaft Valve",
    "E-3 Cam Chain Tensioner",
    "E-4 Cylinder",
    "E-5 Right Crankcase Cover",
    "E-6 One Way Clutch",
    "E-7 Clutch",
    "E-8 Oil Pump",
    "E-9 Left Crankcase Cover",
    "E-10 Generator",
    "E-11 Starting Clutch",
    "E-12 Starting Motor",
    "E-13 Crankcase",
    "E-14 Crankshaft Piston",
    "E-15 Transmission",
    "E-16 Gearshift Drum",
    "E-17 Kick Starter Spindle",
    "E-18 Carburetor",
    "E-19 Throttle Body",
    "F-1 Headlight",
    "F-2 Speedometer",
    "F-3 Handle Lever Cable Mirror (Tipe Drum Brake)",
    "F-3-1 Cable Mirror (Tipe Disk Brak) ",
    "F-4 Fr. Brake Master Cylinder (Tipe Disk Brake)",
    "F-5 Handle Pipe Handle Cover Switch",
    "F-6 Steering Stem",
    "F-7 Front Fender",
    "F-8 Front Fork",
    "F-9 Front Brake Panel (Tipe Drum Brake)",
    "F-10 Front Brake Caliper (Tipe Disk Brake)",
    "F-11 Front Wheel (Tipe Drum Brake)",
    "F-11-2 Front Wheel (Tipe Disk Brake)",
    "F-11-1 Front Wheel (Tipe Disk Brake)",
    "F-12 Rear Brake Panel (Tipe Drum Brake)",
    "F-13 Rr. Brake Master Cylinder (Tipe Disk Brake)",
    "F-14 Rear Brake Caliper (Tipe Disk Brake)",
    "F-15 Rear Wheel (Tipe Spoke – Drum Brake)",
    "F-15-1 Rear Wheel (Tipe CW – Drum Brake)",
    "F-15-2 Rear Wheel (Tipe Disk Brake)",
    "F-16 Fuel Tank (Tipe Carburetor)",
    "F-16-1 Fuel Tank (Tipe PGM-FI)",
    "F-17 Seat",
    "F-18 Body Cover",
    "F-19 Air Cleaner",
    "F-19-1 Air Cleaner",
    "F-20 Exhaust Mufﬂer",
    "F-21 Pedal Kick Starter Arm",
    "F-22 Step",
    "F-23 Stand",
    "F-24 Swingarm",
    "F-25 Rear Cushion",
    "F-26 Rear Fender",
    "F-27 Front Winker",
    "F-28 Rear Combination Light",
    "F-29 Battery Luggage Box",
    "F-30 Wire Harness (tipe Carburetor)",
    "F-30-1 Wire Harness (tipe PGM-FI)",
    "F-31 Frame Body",
    "F-32 Tools",
    "F-33 Main Pipe Cover Leg Shield",
    "F-34 Caution Label",
    "F-35 Mark Stripe Set"
        ];
       
        foreach ($products as $productName) {
            Product::create([
                'name' => $productName,
                'series' => 'Honda Supra X 125',
                'price' => 0,
                'stock' => 0,
                'uom' => null,
            ]);
        }
    }
}