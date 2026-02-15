<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ShopSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'opened_at',
        'closed_at',
        'status',
        'services_completed',
        'services_cancelled',
        'gross_revenue',
        'opened_by',
        'closed_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'services_completed' => 'integer',
        'services_cancelled' => 'integer',
        'gross_revenue' => 'integer',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function openedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'opened_by');
    }

    public function closedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'closed_by');
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Get today's session
     */
    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    /**
     * Get open sessions
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Get closed sessions
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Check if shop is currently open
     */
    public static function isShopOpen()
    {
        return static::today()->open()->exists();
    }

    /**
     * Get today's session or create if not exists
     */
    public static function getTodaySession()
    {
        return static::firstOrCreate(
            ['date' => today()],
            ['status' => 'closed']
        );
    }

    /**
     * Open the shop for today
     */
    public function openShop($adminId)
    {
        $this->update([
            'opened_at' => now(),
            'status' => 'open',
            'opened_by' => $adminId,
        ]);
    }

    /**
     * Close the shop
     */
    public function closeShop($adminId, $autoClose = false)
    {
        // Calculate statistics before closing
        $this->calculateDailyStatistics();

        $this->update([
            'closed_at' => now(),
            'status' => 'closed',
            'closed_by' => $adminId,
            'notes' => $autoClose ? 'Auto-closed at 5 PM' : $this->notes,
        ]);
    }

    /**
     * Calculate daily statistics
     */
    public function calculateDailyStatistics()
    {
        $completedServices = Service::where('queue_date', $this->date)
            ->where('status', 'done')
            ->get();

        $cancelledServices = Service::where('queue_date', $this->date)
            ->where('status', 'cancelled')
            ->count();

        $this->update([
            'services_completed' => $completedServices->count(),
            'services_cancelled' => $cancelledServices,
            'gross_revenue' => $completedServices->sum('price'),
        ]);
    }

    /**
     * Check if it's time to auto-close (5 PM)
     */
    public static function shouldAutoClose()
    {
        return Carbon::now()->hour >= 17 && static::isShopOpen();
    }
}

