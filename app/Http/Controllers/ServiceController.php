<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\LightControllerHelper;
use Carbon\Carbon;

class ServiceController extends Controller
{
    use LightControllerHelper;

    // ========================================>
    // ## Display a listing of the resource.
    // ========================================>
    public function index(Request $request)
    {
        // ? Initial params
        $params = $this->getParams($request);

        // ? Begin
        $query = Service::query()->with(['customer', 'mechanic.user', 'admin', 'queue', 'details', 'vehicle'])
            ->search($params["search"] || '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->selectableColumns()
            ->paginate($params["paginate"]);

        // ? Response
        $this->responseData($query->all(), $query->total());
    }

    // =============================================>
    // ## Store a newly created resource in storage.
    // =============================================>
    public function store(Request $request)
    {
        // Define validation rules
        $rules = [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',

            'vehicle_id' => 'nullable|exists:vehicles,id',
            'vehicle_plate_number' => 'nullable|string|max:255|unique:vehicles,plate_number',
            'vehicle_brand' => 'nullable|string|max:255',
            'vehicle_series' => 'nullable|string|max:255',
            'vehicle_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'vehicle_color' => 'nullable|string|max:255',

            'mechanic_id' => 'nullable|exists:mechanics,id',
            'admin_id' => 'nullable|exists:admins,id',
            'queue_id' => 'nullable|exists:queues,id',
            'description' => 'nullable|string',
            'status' => 'required|in:waiting,process,done,cancelled',
            'approved_at' => 'nullable|date',
        ];

        // Conditionally make fields required
        if (!$request->has('customer_id')) {
            $rules['customer_name'] .= '|required';
        }

        if (!$request->has('vehicle_id')) {
            $rules['vehicle_plate_number'] .= '|required';
            $rules['vehicle_brand'] .= '|required';
            $rules['vehicle_series'] .= '|required';
            $rules['vehicle_year'] .= '|required';
            $rules['vehicle_color'] .= '|required';
        }

        $this->validation($request->all(), $rules);

        DB::beginTransaction();
        try {
            $customerId = $request->customer_id;
            $vehicleId = $request->vehicle_id;
            $customerName = $request->customer_name;

            // Handle vehicle creation if vehicle_id is not provided
            if (!$request->has('vehicle_id')) {
                $vehicle = \App\Models\Vehicle::create([
                    'user_id' => null,
                    'plate_number' => $request->vehicle_plate_number,
                    'brand' => $request->vehicle_brand,
                    'series' => $request->vehicle_series,
                    'year' => $request->vehicle_year,
                    'color' => $request->vehicle_color,
                ]);
                $vehicleId = $vehicle->id;
            }

            // Create the Service
            $service = new \App\Models\Service();
            $service->fill($request->only('description', 'status', 'admin_id',));
            $service->customer_id = $customerId;
            $service->vehicle_id = $vehicleId;

            // If customer_id was not provided, store the customer_name from the request
            if (!$request->has('customer_id')) {
                $service->customer_name = $customerName;
            }

            $service->save();

            // Update last_serviced_at for the vehicle
            $vehicle = \App\Models\Vehicle::findOrFail($vehicleId);
            $vehicle->last_serviced_at = now();
            $vehicle->save();

            DB::commit();
            $this->responseSaved($service->load(['customer.user', 'vehicle'])->toArray());
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['err' => $th], 201);;
        }
    }

    // ============================================>
    // ## Display the specified resource.
    // ============================================>
    public function show(string $id)
    {
        // ? Initial
        $service = Service::query()->with(['customer', 'mechanic', 'admin', 'queue', 'details'])
            ->selectableColumns()
            ->findOrFail($id);

        // ? Response
        $this->responseData($service->toArray());
    }

    // ============================================>
    // ## Update the specified resource in storage.
    // ============================================>
    public function update(Request $request, string $id)
    {
        // ? Initial
        DB::beginTransaction();
        $service = Service::findOrFail($id);

        // ? Validate request
        $this->validation($request->all(), [
            'customer_id' => 'sometimes|required|exists:customers,id',
            'mechanic_id' => 'nullable|exists:mechanics,id',
            'admin_id' => 'nullable|exists:admins,id',
            'queue_id' => 'nullable|exists:queues,id',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:waiting,process,done,cancelled',
            'approved_at' => 'nullable|date',
        ]);

        // ? Dump data
        $service->fill($request->all());

        // ? Executing
        try {
            $service->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Update Service');
        }

        // ? final
        DB::commit();
        $this->responseSaved($service->toArray());
    }

    // ===============================================>
    // ## Remove the specified resource from storage.
    // ===============================================>
    public function destroy(string $id)
    {
        // ? Initial
        $service = Service::findOrFail($id);

        // ? Executing
        try {
            $service->delete();
        } catch (\Throwable $th) {
            $this->responseError($th, 'Delete Service');
        }

        // ? final
        $this->responseData(['message' => 'Service deleted successfully']);
    }

    // ===============================================>
    // ## Change the status of the specified service.
    // ===============================================>
    public function changeStatus(Request $request, string $id)
    {
        // ? Initial
        DB::beginTransaction();
        $service = Service::findOrFail($id);

        // ? Validate request
        $this->validation($request->all(), [
            'status' => 'required|in:waiting,process,done,cancelled',
        ]);

        // ? Dump data
        $service->status = $request->status;
        if ($request->status === 'done' && is_null($service->approved_at)) {
            $service->approved_at = now();
        }

        // ? Executing
        try {
            $service->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Change Service Status');
        }

        // ? final
        DB::commit();
        $this->responseSaved($service->toArray());
    }

    /**
     * Return services for the currently logged in user.
     */
    public function customerServices(Request $request)
    {
        $params = $this->getParams($request);

        $user = $request->user();

        $query = $user->services()->with(['customer', 'mechanic.user', 'admin', 'queue', 'details', 'vehicle'])
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->paginate($params["paginate"]);

        $this->responseData($query->all(), $query->total());
    }

    /**
     * Store a newly created service by an authenticated customer.
     */
    public function storeCustomerService(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer profile not found for this user.'], 404);
        }

        // Define validation rules
        $rules = [
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'vehicle_plate_number' => 'nullable|string|max:255|unique:vehicles,plate_number',
            'vehicle_brand' => 'nullable|string|max:255',
            'vehicle_series' => 'nullable|string|max:255',
            'vehicle_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'vehicle_color' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'preferred_datetime' => 'required|date|after:now',
        ];

        // Conditionally make fields required if vehicle_id is not provided
        if (!$request->has('vehicle_id')) {
            $rules['vehicle_plate_number'] .= '|required';
            $rules['vehicle_brand'] .= '|required';
            $rules['vehicle_series'] .= '|required';
            $rules['vehicle_year'] .= '|required';
            $rules['vehicle_color'] .= '|required';
        }

        $this->validation($request->all(), $rules);

        DB::beginTransaction();
        try {
            $vehicleId = $request->vehicle_id;

            // Handle vehicle creation if vehicle_id is not provided
            if (!$request->has('vehicle_id')) {
                $vehicle = \App\Models\Vehicle::create([
                    'user_id' => $user->id, // Associate with the authenticated user
                    'plate_number' => $request->vehicle_plate_number,
                    'brand' => $request->vehicle_brand,
                    'series' => $request->vehicle_series,
                    'year' => $request->vehicle_year,
                    'color' => $request->vehicle_color,
                ]);
                $vehicleId = $vehicle->id;
            }

            // Create the Service
            $service = new \App\Models\Service();
            $service->fill($request->only('description'));
            $service->customer_id = $customer->id;
            $service->vehicle_id = $vehicleId;
            $service->status = 'waiting';
            $service->preferred_datetime = Carbon::parse($request->preferred_datetime)->setTimezone('UTC');
            $service->save();

            // Update last_serviced_at for the vehicle
            $vehicle = \App\Models\Vehicle::findOrFail($vehicleId);
            $vehicle->last_serviced_at = now();
            $vehicle->save();

            DB::commit();
            $this->responseSaved($service->load(['customer.user', 'vehicle'])->toArray());
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Create Customer Service');
        }
    }

    public function approve(Request $request, Service $service)
    {
        $admin = $request->user()->admin;

        if (!$admin) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        if ($service->status !== 'waiting') {
            return response()->json(['message' => 'This service has already been approved or processed.'], 400);
        }

        $this->validation($request->all(), [
            'mechanic_id' => 'required|exists:mechanics,id',
        ]);

        DB::beginTransaction();

        try {
            // Generate queue number for today
            $today = now()->toDateString();
            $lastQueue = \App\Models\Queue::whereDate('date', $today)
                ->orderByDesc('queue_number')
                ->first();

            $nextNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;

            // Create new queue entry
            $queue = \App\Models\Queue::create([
                'queue_number' => $nextNumber,
                'date' => $today,
                'status' => 'waiting',
            ]);

            // Update service data
            $service->update([
                'admin_id' => $admin->id,
                'mechanic_id' => $request->mechanic_id,
                'queue_id' => $queue->id,
                'status' => 'process',
                'approved_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Service approved successfully',
                'service' => $service->load(['queue', 'mechanic.user', 'customer.user']),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Approve Service');
        }
    }
    public function cancel(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $service = Service::findOrFail($id);

            if (!in_array($service->status, ['pending', 'waiting'])) {
                return response()->json([
                    'message' => 'This service cannot be cancelled.'
                ], 400);
            }


            // $validated = $request->validate([
            //     'reason' => 'nullable|string|max:255',
            // ]);

            $service->status = 'cancelled';
            $service->cancel_reason = $validated['reason'] ?? null;
            $service->cancelled_at = now();
            $service->save();

            // ServiceStatusLog::create([
            //     'service_id' => $service->id,
            //     'status' => 'cancelled',
            //     'note' => $validated['reason'] ?? null,
            // ]);

            DB::commit();

            return response()->json([
                'message' => 'Service has been successfully cancelled.',
                'data' => $service,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to cancel service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
