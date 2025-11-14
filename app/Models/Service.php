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
        'queue_id',
        'vehicle_id',
        'description',
        'status',
        'approved_at'
    ];

    protected $selectable = [
        'id',
        'customer_id',
        'customer_name',
        'description',
        'mechanic_id',
        'admin_id',
        'queue_id',
        'vehicle_id',
        'status',
        'approved_at',
        'created_at',
        'queue.queue_number'
    ];

    protected $hidden = [];

    protected $searchable=['customer_name','descriptions'];

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
        return $this->belongsTo(Queue::class);
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function details()
    {
        return $this->hasMany(ServiceDetail::class);
    }
}