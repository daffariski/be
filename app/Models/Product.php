<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\LightModelHelper;

class Product extends Model
{
    use HasFactory, LightModelHelper;

    protected $fillable = ['name', 'series', 'price', 'stock', 'uom'];
    protected $selectable = ['id', 'series','name', 'price', 'stock', 'uom'];
    protected $searchable =['name', 'series'];
    protected $hidden = [];

    public function serviceDetails()
    {
        return $this->hasMany(ServiceDetail::class);
    }
}
