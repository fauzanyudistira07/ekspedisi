<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CASHIER = 'cashier';
    public const ROLE_COURIER = 'courier';
    public const ROLE_MANAGER = 'manager';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'courier_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'courier_id');
    }

    public static function internalRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_CASHIER,
            self::ROLE_COURIER,
            self::ROLE_MANAGER,
        ];
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
