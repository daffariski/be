<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mechanic extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'specialization', 'on_duty_at'];
    protected $selectable = ['id', 'user_id', 'specialization'];
    protected $searchable = ['specialization'];
    protected $hidden = [];

    public function user() { return $this->belongsTo(User::class); }
    public function services() { return $this->hasMany(Service::class); }
}

