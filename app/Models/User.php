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
    public const ROLE_CASIER = 'casier';
    public const ROLE_COURIER = 'courier';
    public const ROLE_MANAGER = 'manager';
    public const COURIER_TASK_PICKUP = 'pickup';
    public const COURIER_TASK_DROP = 'drop';
    public const COURIER_TASK_HTH = 'hth';

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

    public function manifests()
    {
        return $this->hasMany(ShipmentManifest::class, 'courier_id');
    }

    public static function internalRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_CASHIER,
            self::ROLE_CASIER,
            self::ROLE_COURIER,
            self::ROLE_MANAGER,
        ];
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function courierTaskType(): ?string
    {
        if ($this->role !== self::ROLE_COURIER) {
            return null;
        }

        $routingKey = mb_strtolower($this->name . ' ' . $this->email);

        return match (true) {
            str_contains($routingKey, self::COURIER_TASK_PICKUP) => self::COURIER_TASK_PICKUP,
            str_contains($routingKey, self::COURIER_TASK_DROP) => self::COURIER_TASK_DROP,
            str_contains($routingKey, self::COURIER_TASK_HTH),
            str_contains($routingKey, 'hub to hub'),
            str_contains($routingKey, 'antar cabang') => self::COURIER_TASK_HTH,
            default => null,
        };
    }

    public function isCourierTaskType(string $type): bool
    {
        return $this->courierTaskType() === $type;
    }

    public static function resolveCourierForTask(int $branchId, string $taskType): ?self
    {
        $couriers = self::query()
            ->where('role', self::ROLE_COURIER)
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        return $couriers->first(fn (self $courier) => $courier->isCourierTaskType($taskType))
            ?? $couriers->first(fn (self $courier) => $courier->courierTaskType() === null)
            ?? null;
    }
}
