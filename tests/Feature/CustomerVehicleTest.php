<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerVehicleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * A basic feature test example.
     */
    public function test_customer_can_update_their_own_vehicle(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $updatedData = [
            'brand' => 'Honda',
            'series' => 'Civic',
            'year' => 2023,
        ];

        $response = $this->patchJson("/api/customer/vehicles/{$vehicle->id}", $updatedData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'brand' => 'Honda',
            'series' => 'Civic',
            'year' => 2023,
        ]);
    }
}
