<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceQueue;
use App\Models\ShopSession;
use App\Models\Product;
use App\Models\Mechanic;
use Illuminate\Http\Request;
use App\Helpers\LightControllerHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard statistics and overview for admin
 */
class DashboardController extends Controller
{
    use LightControllerHelper;

    // ===============================================>
    // ## Get Dashboard Overview
    // ===============================================>
    public function getOverview()
    {
        $session = ShopSession::getTodaySession();

        $data = [
            // Shop status
            'shop_status' => [
                'is_open' => $session->status === 'open',
                'opened_at' => $session->opened_at,
                'closed_at' => $session->closed_at,
            ],

            // Today's statistics
            'today' => [
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

            // Queue overview
            'queue' => [
                'total_queues' => ServiceQueue::today()->count(),
                'current_queue_number' => $this->getCurrentQueueNumber(),
                'next_queue' => $this->getNextQueueInfo(),
            ],

            // Active services (in process)
            'active_services' => $this->getActiveServices(),

            // This week statistics
            'this_week' => $this->getWeeklyStatistics(),

            // Quick stats
            'quick_stats' => [
                'total_customers_today' => Service::where('queue_date', today())
                    ->distinct('customer_id')
                    ->count('customer_id'),
                'active_mechanics' => Mechanic::whereHas('services', function ($q) {
                    $q->where('status', 'process');
                })->count(),
                'low_stock_products' => Product::where('stock', '<', 10)->count(),
            ],
        ];

        return $this->responseData([$data]);
    }

    // ===============================================>
    // ## Get Revenue Statistics
    // ===============================================>
    public function getRevenueStatistics(Request $request)
    {
        $period = $request->get('period', 'week'); // day, week, month, year

        $data = match ($period) {
            'day' => $this->getDailyRevenue(),
            'week' => $this->getWeeklyRevenue(),
            'month' => $this->getMonthlyRevenue(),
            'year' => $this->getYearlyRevenue(),
            default => $this->getWeeklyRevenue(),
        };

        return $this->responseData($data);
    }

    // ===============================================>
    // ## Get Service Statistics
    // ===============================================>
    public function getServiceStatistics(Request $request)
    {
        $period = $request->get('period', 'week');

        $startDate = match ($period) {
            'day' => today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfWeek(),
        };

        $services = Service::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        $data = $services->groupBy('date')->map(function ($dayServices, $date) {
            return [
                'date' => $date,
                'done' => $dayServices->where('status', 'done')->sum('count'),
                'cancelled' => $dayServices->where('status', 'cancelled')->sum('count'),
                'waiting' => $dayServices->where('status', 'waiting')->sum('count'),
                'process' => $dayServices->where('status', 'process')->sum('count'),
                'total' => $dayServices->sum('count'),
            ];
        })->values();

        return $this->responseData($data->toArray(), $data->count());
    }

    // ===============================================>
    // ## Get Mechanic Performance
    // ===============================================>
    public function getMechanicPerformance()
    {
        $mechanics = Mechanic::with('user')
            ->withCount([
                'services as completed_today' => function ($q) {
                    $q->where('status', 'done')
                        ->where('queue_date', today());
                },
                'services as completed_this_week' => function ($q) {
                    $q->where('status', 'done')
                        ->where('created_at', '>=', Carbon::now()->startOfWeek());
                },
            ])
            ->withSum(['services as revenue_today' => function ($q) {
                $q->where('status', 'done')
                    ->where('queue_date', today());
            }], 'total_price')
            ->get();

        $data = $mechanics->map(function ($mechanic) {
            return [
                'mechanic_id' => $mechanic->id,
                'name' => $mechanic->user->name,
                'specialization' => $mechanic->specialization,
                'completed_today' => $mechanic->completed_today ?? 0,
                'completed_this_week' => $mechanic->completed_this_week ?? 0,
                'revenue_today' => $mechanic->revenue_today ?? 0,
                'currently_working' => $mechanic->services()
                    ->where('status', 'process')
                    ->exists(),
            ];
        });

        return $this->responseData($data->toArray(), $mechanics->count());
    }

    // ===============================================>
    // PRIVATE: Helper Methods
    // ===============================================>

    private function getCurrentQueueNumber()
    {
        $currentQueue = ServiceQueue::today()
            ->whereHas('service', function ($q) {
                $q->where('status', 'process');
            })
            ->first();

        return $currentQueue ? $currentQueue->queue_number : null;
    }

    private function getNextQueueInfo()
    {
        $nextQueue = ServiceQueue::today()
            ->whereHas('service', function ($q) {
                $q->where('status', 'waiting');
            })
            ->byQueueNumber()
            ->with('service.vehicle', 'service.customer.user')
            ->first();

        if (!$nextQueue) {
            return null;
        }

        return [
            'queue_number' => $nextQueue->queue_number,
            'customer_name' => $nextQueue->service->customer->user->name ?? $nextQueue->service->customer_name,
            'vehicle' => $nextQueue->service->vehicle->plate_number,
            'description' => $nextQueue->service->description,
        ];
    }

    private function getActiveServices()
    {
        return Service::where('status', 'process')
            ->with(['mechanic.user', 'vehicle', 'queue'])
            ->get()
            ->map(function ($service) {
                return [
                    'service_id' => $service->id,
                    'queue_number' => $service->queue->queue_number ?? null,
                    'vehicle' => $service->vehicle->plate_number,
                    'mechanic' => $service->mechanic->user->name ?? null,
                    'started_at' => $service->updated_at,
                ];
            })
            ->toArray();
    }

    private function getWeeklyStatistics()
    {
        $weekStart = Carbon::now()->startOfWeek();

        return [
            'services_completed' => Service::where('status', 'done')
                ->where('created_at', '>=', $weekStart)
                ->count(),
            'services_cancelled' => Service::where('status', 'cancelled')
                ->where('created_at', '>=', $weekStart)
                ->count(),
            'gross_revenue' => Service::where('status', 'done')
                ->where('created_at', '>=', $weekStart)
                ->sum('total_price'),
        ];
    }

    private function getDailyRevenue()
    {
        return Service::where('status', 'done')
            ->where('queue_date', today())
            ->selectRaw('SUM(total_price) as total, COUNT(*) as count')
            ->first()
            ->toArray();
    }

    private function getWeeklyRevenue()
    {
        $weekStart = Carbon::now()->startOfWeek();

        return Service::where('status', 'done')
            ->where('created_at', '>=', $weekStart)
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getMonthlyRevenue()
    {
        $monthStart = Carbon::now()->startOfMonth();

        return Service::where('status', 'done')
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getYearlyRevenue()
    {
        $yearStart = Carbon::now()->startOfYear();

        return Service::where('status', 'done')
            ->where('created_at', '>=', $yearStart)
            ->selectRaw('MONTH(created_at) as month, SUM(total_price) as total, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}
