<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceQueue;
use App\Models\ServiceDetail;
use App\Models\ShopSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\LightControllerHelper;
use App\Models\Product;

/**
 * Handles the service workflow operations:
 * - Start service (assign mechanic, change to process)
 * - Add service details (parts/products used)
 * - Complete service (payment, mark as done)
 */
class ServiceWorkflowController extends Controller
{
    use LightControllerHelper;

    // ===============================================>
    // ## Start Service - Assign mechanic and begin work
    // ===============================================>
    public function startService(Request $request, string $serviceId)
    {
        // Validate
        $this->validation($request->all(), [
            'mechanic_id' => 'required|exists:mechanics,id',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::findOrFail($serviceId);

            // Check if service is in waiting status
            if ($service->status !== 'waiting') {
                return response()->json([
                    'message' => 'Service must be in waiting status to start',
                    'current_status' => $service->status,
                ], 422);
            }

            // Assign mechanic and change status to process
            $service->update([
                'mechanic_id' => $request->mechanic_id,
                'status' => 'process',
            ]);

            DB::commit();

            return $this->responseSaved(
                $service->load(['mechanic.user', 'vehicle', 'customer', 'queue'])->toArray(),
                'Service started successfully'
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Start Service');
        }
    }

    // ===============================================>
    // ## Add Service Detail - Mechanic adds parts used
    // ===============================================>
    public function addServiceDetail(Request $request, string $serviceId)
    {
        // Validate
        $this->validation($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            // 'price'      => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::findOrFail($serviceId);

            // Check if service is in process
            if ($service->status !== 'process') {
                return response()->json([
                    'message' => 'Can only add details to services in process',
                    'current_status' => $service->status,
                ], 422);
            }

            // Get Product price
            $product = Product::findOrFail($request->product_id);

            // Calculate total
            $total = $request->quantity * $product->price;

            // Create service detail
            $detail = ServiceDetail::create([
                'service_id' => $service->id,
                'product_id' => $request->product_id,
                'quantity'   => $request->quantity,
                'price'      => $product->price,
                'total'      => $total,
            ]);

            // Update service price (sum of all details)
            $service->total_price = $service->details()->sum('total');
            $service->save();

            DB::commit();

            return $this->responseSaved(
                $detail->load('product')->toArray(),
                'Service detail added successfully'
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Add Service Detail');
        }
    }

    // ===============================================>
    // ## Complete Service - Mark as done with payment
    // ===============================================>
    public function completeService(Request $request, string $serviceId)
    {
        // Validate
        $this->validation($request->all(), [
            'payment_method' => 'required|in:cash,qris,transfer,debit,credit',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::findOrFail($serviceId);

            // Check if service is in process
            if ($service->status !== 'process') {
                return response()->json([
                    'message' => 'Can only complete services in process',
                    'current_status' => $service->status,
                ], 422);
            }

            // Handle payment proof upload
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = $this->uploadFile(
                    $request->file('payment_proof'),
                    'payment_proofs'
                );
            }

            // Update service
            $service->update([
                'status' => 'done',
                'payment_method' => $request->payment_method,
                'payment_proof' => $paymentProofPath,
                'payment_status' => 'paid',
            ]);

            // Update vehicle last serviced
            $service->vehicle->update([
                'last_serviced_at' => now(),
            ]);

            DB::commit();

            return $this->responseSaved(
                $service->load(['mechanic.user', 'vehicle', 'customer', 'details.product', 'queue'])->toArray(),
                'Service completed successfully'
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Complete Service');
        }
    }

    // ===============================================>
    // ## Get Mechanic's Active Services
    // ===============================================>
    public function getMechanicActiveServices(Request $request)
    {
        $mechanic = $request->user()->mechanic;

        if (!$mechanic) {
            return response()->json(['message' => 'User is not a mechanic'], 403);
        }

        $services = Service::where('mechanic_id', $mechanic->id)
            ->where('status', 'process')
            ->with(['vehicle', 'customer.user', 'queue', 'details.product'])
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->responseData($services->toArray(), $services->count());
    }

    // ===============================================>
    // ## Get Mechanic's Completed Services (history)
    // ===============================================>
    public function getMechanicCompletedServices(Request $request)
    {
        $mechanic = $request->user()->mechanic;

        if (!$mechanic) {
            return response()->json(['message' => 'User is not a mechanic'], 403);
        }

        $services = Service::where('mechanic_id', $mechanic->id)
            ->where('status', 'done')
            ->with(['vehicle', 'customer.user', 'queue', 'details.product'])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return $this->responseData($services->toArray(), $services->count());
    }

    // ===============================================>
    // ## Finish Service - Mechanic marks work as done
    // ===============================================>
    public function finishService(Request $request, Service $service)
    {
        $mechanic = $request->user()->mechanic;

        if (!$mechanic || $service->mechanic_id !== $mechanic->id) {
            return response()->json(['message' => 'Tidak diizinkan menyelesaikan servis ini'], 403);
        }

        if ($service->status !== 'process') {
            return response()->json([
                'message' => 'Hanya servis yang sedang dikerjakan yang bisa diselesaikan',
                'current_status' => $service->status,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $service->status = 'done';
            $service->save();

            // Update vehicle last serviced
            if ($service->vehicle) {
                $service->vehicle->last_serviced_at = now();
                $service->vehicle->save();
            }

            DB::commit();

            return $this->responseSaved(
                $service->fresh(['mechanic.user', 'vehicle', 'customer.user', 'details.product', 'queue'])->toArray(),
                'Servis berhasil diselesaikan'
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Finish Service');
        }
    }

    // ===============================================>
    // ## Remove Service Detail - Mechanic can remove if mistake
    // ===============================================>
    public function removeServiceDetail(string $serviceId, string $detailId)
    {
        DB::beginTransaction();
        try {
            $service = Service::findOrFail($serviceId);
            $detail = ServiceDetail::where('service_id', $serviceId)
                ->where('id', $detailId)
                ->firstOrFail();

            // Check if service is still in process
            if ($service->status !== 'process') {
                return response()->json([
                    'message' => 'Can only remove details from services in process',
                ], 422);
            }

            $detail->delete();

            // Recalculate service price
            $service->total_price = $service->details()->sum('total');
            $service->save();

            DB::commit();

            return $this->responseData([], null, 'Service detail removed successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Remove Service Detail');
        }
    }
}
