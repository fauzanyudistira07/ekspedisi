<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'address',
        'city',
        'phone',
        'photo',
        'last_tracking_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_tracking_seen_at' => 'datetime',
    ];

    public function sentShipments()
    {
        return $this->hasMany(Shipment::class, 'sender_id');
    }

    public function receivedShipments()
    {
        return $this->hasMany(Shipment::class, 'receiver_id');
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }
}
