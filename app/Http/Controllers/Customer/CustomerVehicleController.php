<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerVehicleController extends Controller
{
    /**
     * Return vehicles for the currently logged in user, formatted for options.
     */
    public function userVehiclesOption(Request $request)
    {
        $params = $this->getParams($request);

        $user = $request->user();

        return $user->vehicles()
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->get()
            ->map(function ($vehicle) {
                return [
                    'label'   => $vehicle->brand . ' [' . $vehicle->plate_number . ']',
                    'value'   => $vehicle->id,
                    'vehicle' => $vehicle
                ];
            });
    }

    public function customerVehicles(Request $request)
    {
        $vehicles = $this->userVehiclesOption($request);
        return response()->json($vehicles);
    }

    /**
     * Update the specified vehicle by the authenticated user.
     */
    public function updateCustomerVehicle(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();

        if ($vehicle->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized to update this vehicle.'], 403);
        }

        $validated = $request->validate([
            'plate_number' => 'sometimes|required|string|max:255|unique:vehicles,plate_number,' . $vehicle->id,
            'brand'        => 'sometimes|required|string|max:255',
            'series'       => 'sometimes|required|string|max:255',
            'year'         => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'color'        => 'sometimes|required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $vehicle->fill($validated);
            $vehicle->save();
            DB::commit();
            return response()->json(['message' => 'Vehicle updated successfully', 'data' => $vehicle->fresh()]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
