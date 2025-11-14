<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\LightModelHelper;

class ServiceDetail extends Model
{
    use HasFactory, LightModelHelper;

    protected $fillable = ['service_id', 'product_id', 'quantity', 'price', 'total'];
    protected $selectable = ['id', 'service_id', 'product_id', 'quantity', 'price', 'total'];
    protected $hidden = [];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

