<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\LightModelHelper;

class Admin extends Model
{
    use HasFactory, LightModelHelper;

    protected $fillable = ['user_id'];
    protected $selectable = ['id', 'user_id'];
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
