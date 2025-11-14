<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Helpers\LightModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LightModelHelper, HasApiTokens;

    // =========================>
    // ## Fillable
    // =========================>
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'role'
    ];

    // =========================>
    // ## Hidden
    // =========================>
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // =========================>
    // ## Searchable
    // =========================>
    public $searchable = [
        'name',
        'email',
    ];

    // =========================>
    // ## Selectable
    // =========================>
    public $selectable = [
        'id',
        'name',
        'email',
        'role',
    ];

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
    public function mechanic()
    {
        return $this->hasOne(Mechanic::class);
    }
    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function services()
    {
        return $this->hasManyThrough(Service::class, Customer::class);
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }
}
