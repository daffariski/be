<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\LightControllerHelper;
use Carbon\Carbon;

/**
 * Handles queue management operations:
 * - View today's queues
 * - Next queue (Work button)
 * - Re-queue service to another day
 * - Get customer's queue position
 */
class QueueManagementController extends Controller
{
    use LightControllerHelper;

    // ===============================================>
    // ## Get Today's Queues
    // ===============================================>
    public function getTodayQueues(Request $request)
    {
        $queues = ServiceQueue::today()
            ->with([
                'service.customer.user',
                'service.vehicle',
                'service.mechanic.user',
            ])
            ->byQueueNumber()
            ->get();

        $data = $queues->map(function ($queue) {
            $service = $queue->service;
            return [
                'queue_id' => $queue->id,
                'queue_number' => $queue->queue_number,
                'queue_date' => $queue->queue_date,
                'service_id' => $service->id,
                'customer_name' => $service->customer->user->name ?? $service->customer_name,
                'vehicle' => $service->vehicle->plate_number . ' (' . $service->vehicle->brand . ' ' . $service->vehicle->series . ')',
                'description' => $service->description,
                'status' => $service->status,
                'mechanic' => $service->mechanic ? $service->mechanic->user->name : null,
                'queued_at' => $service->queued_at,
            ];
        });

        return $this->responseData($data->toArray(), $queues->count());
    }

    // ===============================================>
    // ## Work Next Queue - Start next waiting service
    // ===============================================>
    public function workNextQueue(Request $request)
    {
        DB::beginTransaction();
        try {
            // Find the first waiting service in today's queue
            $nextQueue = ServiceQueue::today()
                ->whereHas('service', function ($query) {
                    $query->where('status', 'waiting');
                })
                ->byQueueNumber()
                ->with('service')
                ->first();

            if (!$nextQueue) {
                return response()->json([
                    'message' => 'No waiting services in queue',
                ], 404);
            }

            // This just identifies the next service
            // Admin still needs to click "Start" to assign mechanic
            $service = $nextQueue->service;

            DB::commit();

            return $this->responseData([
                'queue' => $nextQueue,
                'service' => $service->load(['customer.user', 'vehicle', 'queue']),
                'message' => 'Next service ready. Please assign a mechanic to start.',
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Work Next Queue');
        }
    }

    // ===============================================>
    // ## Re-queue Service to Another Day
    // ===============================================>
    public function reQueueService(Request $request, string $serviceId)
    {
        $this->validation($request->all(), [
            'new_date' => 'required|date|after_or_equal:today',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::findOrFail($serviceId);

            // Only waiting services can be re-queued
            if ($service->status !== 'waiting') {
                return response()->json([
                    'message' => 'Only waiting services can be re-queued',
                    'current_status' => $service->status,
                ], 422);
            }

            $newDate = Carbon::parse($request->new_date);
            $oldDate = $service->queue_date;

            // Update service queue date
            $service->update([
                'queue_date' => $newDate,
                'queued_at' => now(), // Update to maintain proper ordering
            ]);

            // Delete old queue entry
            ServiceQueue::where('service_id', $service->id)
                ->where('queue_date', $oldDate)
                ->delete();

            // Create new queue entry for new date
            $nextQueueNumber = ServiceQueue::getNextQueueNumber($newDate);

            ServiceQueue::create([
                'service_id' => $service->id,
                'queue_number' => $nextQueueNumber,
                'queue_date' => $newDate,
            ]);

            DB::commit();

            return $this->responseSaved([
                'service' => $service->fresh(['queue']),
                'old_date' => $oldDate,
                'new_date' => $newDate,
                'new_queue_number' => $nextQueueNumber,
            ], 'Service re-queued successfully');

        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Re-queue Service');
        }
    }

    // ===============================================>
    // ## Get Customer's Queue Position
    // ===============================================>
    public function getCustomerQueuePosition(Request $request)
    {
        $userId = $request->user()->id;
        $customerId = $request->user()->customer->id ?? null;

        if (!$customerId) {
            return response()->json([
                'message' => 'User is not a customer',
            ], 403);
        }

        // Find customer's active service in queue
        $service = Service::where('customer_id', $customerId)
            ->where('status', 'waiting')
            ->where('queue_date', today())
            ->with(['queue', 'vehicle'])
            ->first();

        if (!$service) {
            return $this->responseData([
                'has_active_queue' => false,
                'message' => 'You have no active queue today',
            ]);
        }

        $queue = $service->queue;

        // Count how many queues are ahead
        $queuesAhead = ServiceQueue::today()
            ->where('queue_number', '<', $queue->queue_number)
            ->whereHas('service', function ($q) {
                $q->whereIn('status', ['waiting', 'process']);
            })
            ->count();

        return $this->responseData([
            'has_active_queue' => true,
            'queue_number' => $queue->queue_number,
            'queues_ahead' => $queuesAhead,
            'estimated_wait' => $queuesAhead * 30, // Assume 30 mins per service
            'service' => [
                'id' => $service->id,
                'description' => $service->description,
                'vehicle' => $service->vehicle->plate_number . ' (' . $service->vehicle->brand . ')',
                'status' => $service->status,
                'queued_at' => $service->queued_at,
            ],
        ]);
    }

    // ===============================================>
    // ## Get Queue Statistics
    // ===============================================>
    public function getQueueStatistics()
    {
        $totalQueues = ServiceQueue::today()->count();
        $waitingCount = Service::where('status', 'waiting')
            ->where('queue_date', today())
            ->count();
        $processCount = Service::where('status', 'process')
            ->where('queue_date', today())
            ->count();
        $completedCount = Service::where('status', 'done')
            ->where('queue_date', today())
            ->count();
        $cancelledCount = Service::where('status', 'cancelled')
            ->where('queue_date', today())
            ->count();

        // Get current queue being worked on
        $currentQueue = ServiceQueue::today()
            ->whereHas('service', function ($q) {
                $q->where('status', 'process');
            })
            ->with('service.mechanic.user', 'service.vehicle')
            ->first();

        return $this->responseData([
            'total_queues' => $totalQueues,
            'waiting' => $waitingCount,
            'in_process' => $processCount,
            'completed' => $completedCount,
            'cancelled' => $cancelledCount,
            'current_queue' => $currentQueue ? [
                'queue_number' => $currentQueue->queue_number,
                'vehicle' => $currentQueue->service->vehicle->plate_number,
                'mechanic' => $currentQueue->service->mechanic->user->name ?? null,
            ] : null,
        ]);
    }
}
