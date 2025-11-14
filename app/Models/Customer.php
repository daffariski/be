<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'phone', 'address'];
    protected $selectable = ['id', 'user_id', 'phone', 'address'];
    protected $searchable = ['phone', 'address'];
    protected $hidden = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function services()
    {
        return $this->hasMany(Service::class);
    }
}

