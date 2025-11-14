<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = ['queue_number', 'date', 'status', 'vehicle_id'];
    protected $selectable = ['id', 'queue_number', 'date', 'status', 'vehicle_id'];
    protected $searchable = ['queue_number', 'status'];
    protected $hidden = [];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}
