<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'queue_number',
        'queue_date',
        'status',
    ];

    protected $selectable = [
        'id',
        'service_id',
        'queue_number',
        'queue_date',
        'created_at',
        'updated_at',
        'status',
    ];

    protected $searchable = ['queue_number'];

    protected $hidden = [];

    protected $casts = [
        'queue_date' => 'date',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Get queues for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('queue_date', $date);
    }

    /**
     * Get today's queues
     */
    public function scopeToday($query)
    {
        return $query->where('queue_date', today());
    }

    /**
     * Order by queue number
     */
    public function scopeByQueueNumber($query)
    {
        return $query->orderBy('queue_number', 'asc');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Get next available queue number for a date
     */
    public static function getNextQueueNumber($date)
    {
        $lastQueue = static::where('queue_date', $date)
            ->orderBy('queue_number', 'desc')
            ->first();

        return $lastQueue ? $lastQueue->queue_number + 1 : 1;
    }
}
