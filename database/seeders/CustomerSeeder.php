<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerUser = User::where('email', 'customer@example.com')->first();

        if ($customerUser) {
            Customer::create([
                'user_id' => $customerUser->id,
                'phone' => '081234567890',
                'address' => 'Jl. Contoh No. 123',
            ]);
        }
    }
}