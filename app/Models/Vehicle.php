<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'owner_name',
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
        'owner_name',
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
        return $this->hasMany(ServiceQueue::class);
    }

    // Add search scope - only searches plate_number
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('plate_number', 'like', '%' . $search . '%')
                ->orWhere('brand', 'like', '%' . $search . '%')
                ->orWhere('series', 'like', '%' . $search . '%')
                ->orWhere('color', 'like', '%' . $search . '%')
                ->orWhere('year', 'like', '%' . $search . '%')
                ->orWhereHas('user', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                });
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
