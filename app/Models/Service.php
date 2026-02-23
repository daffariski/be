<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\LightModelHelper;

class Service extends Model
{
    use HasFactory, LightModelHelper;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'mechanic_id',
        'admin_id',
        'vehicle_id',
        'description',
        'status',
        'queue_date',
        'queue_priority',
        'queued_at',
        'service_fee',
        'total_price',
        'payment_method',
        'payment_proof',
        'payment_status',
    ];

    protected $selectable = [
        'id',
        'customer_id',
        'customer_name',
        'description',
        'mechanic_id',
        'admin_id',
        'vehicle_id',
        'status',
        'queue_date',
        'queue_priority',
        'queued_at',
        'service_fee',
        'total_price',
        'payment_method',
        'payment_status',
        'created_at',
        'updated_at',
        // 'service_queues.queue_number',
        // 'service_queues.queue_date',
    ];

    protected $hidden = [];

    protected $searchable = ['customer_name', 'description'];

    protected $casts = [
        'queue_date' => 'date',
        'queued_at'  => 'datetime',
        'service_fee' => 'integer',
        'total_price' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function mechanic()
    {
        return $this->belongsTo(Mechanic::class);
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
    public function queue()
    {
        return $this->hasOne(ServiceQueue::class);
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function details()
    {
        return $this->hasMany(ServiceDetail::class);
    }

    // ==========================================
    // Queue Management Scopes
    // ==========================================

    /**
     * Get services for a specific queue date
     */
    public function scopeForQueueDate($query, $date)
    {
        return $query->where('queue_date', $date);
    }

    /**
     * Get services that are in queue (waiting status)
     */
    public function scopeInQueue($query)
    {
        return $query->where('status', 'waiting')
            ->whereNotNull('queue_date')
            ->whereNotNull('queued_at');
    }

    /**
     * Order by queue priority
     * Lower queue_priority number = higher priority
     * Then by queued_at (first come first served)
     */
    public function scopeByQueueOrder($query)
    {
        return $query->orderBy('queue_date', 'asc')
            ->orderBy('queue_priority', 'asc')
            ->orderBy('queued_at', 'asc')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Check if customer already has an active service today
     */
    public static function customerHasActiveServiceToday($customerId)
    {
        return static::where('customer_id', $customerId)
            ->where('queue_date', today())
            ->whereIn('status', ['waiting', 'process'])
            ->exists();
    }
}
