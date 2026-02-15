<?php

namespace App\Http\Controllers;

use App\Models\ShopSession;
use App\Models\Service;
use App\Models\ServiceQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\LightControllerHelper;
use Carbon\Carbon;

/**
 * Handles shop session operations:
 * - Open shop (Buka Toko)
 * - Close shop (Tutup Toko)
 * - Get current session status
 */
class ShopSessionController extends Controller
{
    use LightControllerHelper;

    // ===============================================>
    // ## Open Shop - "Buka Toko"
    // ===============================================>
    public function openShop(Request $request)
    {
        DB::beginTransaction();
        try {
            // Check if shop is already open
            if (ShopSession::isShopOpen()) {
                return response()->json([
                    'message' => 'Shop is already open today',
                ], 422);
            }

            // Get or create today's session
            $session = ShopSession::getTodaySession();

            // Get admin ID from authenticated user
            $adminId = $request->user()->admin->id ?? null;

            if (!$adminId) {
                return response()->json([
                    'message' => 'Only admins can open the shop',
                ], 403);
            }

            // Open the shop
            $session->openShop($adminId);

            // Calculate queue numbers for today's waiting services
            $this->calculateTodayQueues();

            // Handle unfinished services from yesterday
            $this->handleYesterdayServices();

            DB::commit();

            return $this->responseSaved([
                'session' => $session->fresh(['openedByAdmin.user']),
                'queues_calculated' => ServiceQueue::today()->count(),
                'waiting_services' => Service::where('status', 'waiting')
                    ->where('queue_date', today())
                    ->count(),
            ], 'Toko Berhasil Dibuka!');

        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Open Shop');
        }
    }

    // ===============================================>
    // ## Close Shop - "Tutup Toko"
    // ===============================================>
    public function closeShop(Request $request)
    {
        DB::beginTransaction();
        try {
            // Check if shop is open
            if (!ShopSession::isShopOpen()) {
                return response()->json([
                    'message' => 'Shop is not open',
                ], 422);
            }

            $session = ShopSession::today()->open()->first();

            // Get admin ID
            $adminId = $request->user()->admin->id ?? null;

            if (!$adminId) {
                return response()->json([
                    'message' => 'Only admins can close the shop',
                ], 403);
            }

            // Close the shop
            $session->closeShop($adminId, $request->input('auto_close', false));

            DB::commit();

            return $this->responseSaved([
                'session' => $session->fresh(['closedByAdmin.user']),
                'statistics' => [
                    'services_completed' => $session->services_completed,
                    'services_cancelled' => $session->services_cancelled,
                    'gross_revenue' => $session->gross_revenue,
                    'open_duration' => $session->opened_at->diffForHumans($session->closed_at, true),
                ],
            ], 'Toko Berhasil Ditutup!');

        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Close Shop');
        }
    }

    // ===============================================>
    // ## Get Shop Status
    // ===============================================>
    public function getStatus()
    {
        $session = ShopSession::getTodaySession();

        $data = [
            'is_open' => $session->status === 'open',
            'session' => $session->load(['openedByAdmin.user', 'closedByAdmin.user']),
            'today_statistics' => [
                'services_completed' => $session->services_completed,
                'services_cancelled' => $session->services_cancelled,
                'gross_revenue' => $session->gross_revenue,
                'waiting_services' => Service::where('status', 'waiting')
                    ->where('queue_date', today())
                    ->count(),
                'in_process_services' => Service::where('status', 'process')
                    ->where('queue_date', today())
                    ->count(),
            ],
        ];

        return $this->responseData([$data]);
    }

    // ===============================================>
    // ## List Shop Sessions (History)
    // ===============================================>
    public function listSessions(Request $request)
    {
        $sessions = ShopSession::orderByDesc('date')->get();
        return $this->responseData($sessions->toArray());
    }

    // ===============================================>
    // ## PRIVATE: Calculate Today's Queue Numbers
    // ===============================================>
    private function calculateTodayQueues()
    {
        // Get all waiting services for today, ordered by priority and time
        $waitingServices = Service::where('status', 'waiting')
            ->where('queue_date', today())
            ->byQueueOrder()
            ->get();

        if ($waitingServices->isEmpty()) {
            return;
        }

        $queueNumber = 1;

        foreach ($waitingServices as $service) {
            // Create or update queue entry
            ServiceQueue::updateOrCreate(
                [
                    'service_id' => $service->id,
                    'queue_date' => today(),
                ],
                [
                    'queue_number' => $queueNumber,
                ]
            );

            $queueNumber++;
        }
    }

    // ===============================================>
    // ## PRIVATE: Handle Yesterday's Unfinished Services
    // ===============================================>
    private function handleYesterdayServices()
    {
        $yesterday = Carbon::yesterday();

        // Get services from yesterday that are still waiting
        $waitingServices = Service::where('status', 'waiting')
            ->where('queue_date', $yesterday)
            ->get();

        // Auto re-queue to today
        foreach ($waitingServices as $service) {
            $service->update([
                'queue_date' => today(),
                'queued_at' => now(), // Update queued time to maintain order
            ]);

            // Delete old queue entry
            ServiceQueue::where('service_id', $service->id)
                ->where('queue_date', $yesterday)
                ->delete();
        }

        // Services in "process" status stay as-is (admin will manually handle)
    }
}
