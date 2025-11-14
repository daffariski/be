<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Helpers\LightControllerHelper;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    use LightControllerHelper;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // ? Initial params
        $params = $this->getParams($request);

        // ? Begin
        $query = Vehicle::query()
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->selectableColumns()
            ->paginate($params["paginate"]);

        // ? Response
        $this->responseData($query->all(), $query->total());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ? Validate request
        $this->validation($request->all(), [
            'plate_number' => 'required|string|max:255|unique:vehicles',
            'brand' => 'required|string|max:255',
            'series' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // ? Initial
        DB::beginTransaction();
        $vehicle = new Vehicle();

        // ? Dump data
        $vehicle->fill($request->all());

        // ? Executing
        try {
            $vehicle->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Create Vehicle');
        }

        // ? final
        DB::commit();
        $this->responseSaved($vehicle->toArray());
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        // ? Response
        $this->responseData($vehicle->toArray());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        // ? Validate request
        $this->validation($request->all(), [
            'plate_number' => 'sometimes|required|string|max:255|unique:vehicles,plate_number,' . $vehicle->id,
            'brand' => 'sometimes|required|string|max:255',
            'series' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'sometimes|required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // ? Initial
        DB::beginTransaction();

        // ? Dump data
        $vehicle->fill($request->all());

        // ? Executing
        try {
            $vehicle->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Update Vehicle');
        }

        // ? final
        DB::commit();
        $this->responseSaved($vehicle->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        // ? Executing
        try {
            $vehicle->delete();
        } catch (\Throwable $th) {
            $this->responseError($th, 'Delete Vehicle');
        }

        // ? final
        $this->responseData(['message' => 'Vehicle deleted successfully']);
    }

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
                    'label' => $vehicle->brand . ' [' . $vehicle->plate_number . ']',
                    'value' => $vehicle->id,
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
        'brand' => 'sometimes|required|string|max:255',
        'series' => 'sometimes|required|string|max:255',
        'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
        'color' => 'sometimes|required|string|max:255',
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



    /**
     * Return all vehicles, formatted for options.
     */
    public function allVehiclesOption(Request $request)
    {
        $params = $this->getParams($request);

        $vehicles = Vehicle::query()
            ->with('user')
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->get()
            ->map(function ($vehicle) {
                return [
                    'label' => $vehicle->series . ' [' . $vehicle->plate_number . ']',
                    'value' => $vehicle->id,
                    'user' => $vehicle->user
                ];
            });

        return response()->json($vehicles);
    }
}