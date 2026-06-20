<?php

namespace App\Http\Controllers\Customer;

use App\Models\Service;
use App\Models\ServiceQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\LightControllerHelper;
use App\Http\Controllers\Controller;

class CustomerServiceController extends Controller
{
    use LightControllerHelper;

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

        return $this->responseData($query->all(), $query->total());
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
            $rules['vehicle_brand']        .= '|required';
            $rules['vehicle_series']       .= '|required';
            $rules['vehicle_year']         .= '|required';
            $rules['vehicle_color']        .= '|required';
        }

        $this->validation($request->all(), $rules);

        DB::beginTransaction();
        try {
            $vehicleId = $request->vehicle_id;

            // Handle vehicle creation if vehicle_id is not provided or is null 
            if (!$request->has('vehicle_id') || $request->vehicle_id === null) {
                $vehicle = \App\Models\Vehicle::create([
                    'user_id'      => $user->id,                        // Associate with the authenticated user
                    'plate_number' => $request->vehicle_plate_number,
                    'brand'        => $request->vehicle_brand,
                    'series'       => $request->vehicle_series,
                    'year'         => $request->vehicle_year,
                    'color'        => $request->vehicle_color,
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
            $service->vehicle_id  = $vehicleId;
            $service->status      = 'waiting';
            $service->queue_date  = today();        // Set queue for today
            $service->queued_at   = now();
            $service->save();

            // Create queue entry
            $lastQueue = ServiceQueue::whereDate('queue_date', today())
                ->orderBy('queue_number', 'desc')
                ->first();

            $nextQueueNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;

            ServiceQueue::create([
                'service_id'   => $service->id,
                'queue_number' => $nextQueueNumber,
                'queue_date'   => today(),
                'status'       => 'waiting',
            ]);

            DB::commit();
            return $this->responseSaved($service->load(['customer.user', 'vehicle', 'queue'])->toArray());
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Create Customer Service');
        }
    }
}
