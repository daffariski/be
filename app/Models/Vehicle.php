<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plate_number',
        'brand',
        'series',
        'year',
        'color',
        'last_serviced_at',
    ];
    protected $selectable = [
        'id',
        'user_id',
        'plate_number',
        'brand',
        'series',
        'year',
        'color',
        'last_serviced_at',
    ];

    public function scopeSelectableColumns($query)
    {
        return $query->select($this->selectable);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function queue()
    {
        return $this->hasOne(Queue::class);
    }

    // Add search scope - only searches plate_number
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('plate_number', 'like', '%' . $search . '%');
        }
        return $query;
    }

    // Add filter scope
    public function scopeFilter($query, $filters)
    {
        if ($filters) {
            foreach ($filters as $key => $value) {
                if ($value !== null) {
                    $query->where($key, $value);
                }
            }
        }
        return $query;
    }
}
