<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // ? Initial params
        $params = $this->getParams($request);

        // ? Begin
        $query = Vehicle::query()->with('user:id,name')
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->selectableColumns()
            ->paginate($params["paginate"]);

        $data = $query->all();

        // ? Response
        // $this->responseData($query->all(), $query->total());
        return response()->json([
            'message'   => (count($data) ? 'Success' : 'Empty data'),
            'data'      => $data ?? [],
            'total_row' => null,
            'columns'   => null,
        ], count($data) ? 200 : 206);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ? Validate request
        $this->validation($request->all(), [
            'plate_number' => 'required|string|max:255|unique:vehicles',
            'brand'        => 'required|string|max:255',
            'series'       => 'required|string|max:255',
            'year'         => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color'        => 'required|string|max:255',
            'user_id'      => 'nullable|exists:users,id',
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
            'brand'        => 'sometimes|required|string|max:255',
            'series'       => 'sometimes|required|string|max:255',
            'year'         => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'color'        => 'sometimes|required|string|max:255',
            'user_id'      => 'nullable|exists:users,id',
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
                    'user'  => $vehicle->user
                ];
            });

        return response()->json($vehicles);
    }
}
