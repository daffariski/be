<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceQueue;
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
        $query = Service::query()->with(['customer.user', 'mechanic.user', 'admin', 'queue', 'details', 'vehicle'])
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->selectableColumns()
            ->paginate($params["paginate"]);

        // ? Response
        return $this->responseData($query->all(), $query->total());
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
            'status' => 'nullable|in:waiting,process,done,cancelled',
            'add_to_queue' => 'nullable|boolean', // Whether to add to queue (true for fresh, false for old service)
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
            $service = new Service();
            $service->fill($request->only('description', 'admin_id'));
            $service->customer_id = $customerId;
            $service->vehicle_id = $vehicleId;
            $service->status = $request->input('status', 'waiting');

            // If customer_id was not provided, store the customer_name from the request
            if (!$request->has('customer_id')) {
                $service->customer_name = $customerName;
            }

            // Check if customer already has active service today (prevent monopolizing queue)
            if ($customerId && Service::customerHasActiveServiceToday($customerId)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Customer already has an active service today. Please complete current service first.',
                ], 422);
            }

            // Determine if service should be added to queue
            $addToQueue = $request->input('add_to_queue', true); // Default true for fresh services

            if ($addToQueue && $service->status === 'waiting') {
                // Set queue fields
                $service->queue_date = $request->input('queue_date', today());
                $service->queue_priority = $request->input('queue_priority', 999);
                $service->queued_at = now();

                $service->save();

                // Create queue entry
                $queueNumber = ServiceQueue::getNextQueueNumber($service->queue_date);
                ServiceQueue::create([
                    'service_id' => $service->id,
                    'queue_number' => $queueNumber,
                    'queue_date' => $service->queue_date,
                ]);
            } else {
                // Old service or non-queued service
                $service->save();
            }

            // Update last_serviced_at for the vehicle (only if service is done)
            if ($service->status === 'done') {
                $vehicle = \App\Models\Vehicle::findOrFail($vehicleId);
                $vehicle->last_serviced_at = now();
                $vehicle->save();
            }

            DB::commit();
            return $this->responseSaved($service->load(['customer.user', 'vehicle', 'queue'])->toArray());
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Create Service');
        }
    }

    // ============================================>
    // ## Display the specified resource.
    // ============================================>
    public function show(string $id)
    {
        // ? Initial
        $service = Service::query()->with(['customer.user', 'mechanic.user', 'admin', 'queue', 'details', 'vehicle'])
            ->selectableColumns()
            ->findOrFail($id);

        // ? Response - wrap single resource in an array to match list endpoints
        return $this->responseData([$service->toArray()]);
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

    // ===============================================>
    // ## Start Service (assign mechanic + set to process)
    // ===============================================>
    public function start(Request $request, Service $service)
    {
        $this->validation($request->all(), [
            'mechanic_id' => 'required|exists:mechanics,id',
        ]);

        if ($service->status !== 'waiting') {
            return response()->json([
                'message' => 'Only waiting services can be started',
                'current_status' => $service->status,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $service->mechanic_id = $request->mechanic_id;
            $service->status = 'process';
            $service->started_at = now();
            $service->save();

            DB::commit();
            return $this->responseSaved($service->fresh(['mechanic.user', 'customer.user', 'vehicle', 'queue'])->toArray());
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Start Service');
        }
    }

    /**
     * Return services for the currently logged in user.
     */
    public function customerServices(Request $request)
    {
        $params = $this->getParams($request);

        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer profile not found.'], 404);
        }

        $query = Service::where('customer_id', $customer->id)
            ->with(['customer.user', 'mechanic.user', 'admin', 'queue', 'details', 'vehicle'])
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
            'vehicle_id'           => 'nullable|exists:vehicles,id',
            'vehicle_plate_number' => 'nullable|string|max:255|unique:vehicles,plate_number',
            'vehicle_brand'        => 'nullable|string|max:255',
            'vehicle_series'       => 'nullable|string|max:255',
            'vehicle_year'         => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'vehicle_color'        => 'nullable|string|max:255',
            'description'          => 'required|string',
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

            // Check if customer already has active service today
            if (Service::where('customer_id', $customer->id)
                ->where('queue_date', today())
                ->whereIn('status', ['waiting', 'process'])
                ->exists()
            ) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Anda sudah memiliki servis aktif hari ini. Harap tunggu hingga selesai.',
                ], 422);
            }

            // Create the Service
            $service = new Service();
            $service->fill($request->only('description'));
            $service->customer_id = $customer->id;
            $service->vehicle_id = $vehicleId;
            $service->status = 'waiting';
            $service->queue_date = today(); // Set queue for today
            $service->queued_at = now();
            $service->save();

            // Create queue entry
            $lastQueue = ServiceQueue::whereDate('queue_date', today())
                ->orderBy('queue_number', 'desc')
                ->first();

            $nextQueueNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;

            ServiceQueue::create([
                'service_id' => $service->id,
                'queue_number' => $nextQueueNumber,
                'queue_date' => today(),
                'status' => 'waiting',
            ]);

            DB::commit();
            return $this->responseSaved($service->load(['customer.user', 'vehicle', 'queue'])->toArray());
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Create Customer Service');
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
            $lastQueue = ServiceQueue::whereDate('date', $today)
                ->orderByDesc('queue_number')
                ->first();

            $nextNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;

            // Create new queue entry
            $queue = ServiceQueue::create([
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
            // $service->cancel_reason = $validated['reason'] ?? null;
            $service->cancelled_at = now();
            $service->save();

            // update shop session stats
            $session = ShopSession::getTodaySession();
            if ($session) {
                $session->services_cancelled += 1;
                $session->save();
            }

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
