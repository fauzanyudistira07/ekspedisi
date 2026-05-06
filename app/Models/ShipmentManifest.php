<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentManifest extends Model
{
    use HasFactory;

    public const TYPE_PICKUP = 'pickup';
    public const TYPE_LINEHAUL = 'linehaul';
    public const TYPE_ARRIVAL = 'arrival';
    public const TYPE_DELIVERY = 'delivery';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'manifest_number',
        'branch_id',
        'vehicle_id',
        'courier_id',
        'manifest_type',
        'status',
        'departed_at',
        'arrived_at',
        'notes',
    ];

    protected $casts = [
        'departed_at' => 'datetime',
        'arrived_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function shipments()
    {
        return $this->belongsToMany(Shipment::class, 'manifest_shipments', 'manifest_id', 'shipment_id')
            ->withPivot(['loaded_at', 'unloaded_at', 'checkpoint_status', 'checkpoint_notes'])
            ->withTimestamps();
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    public static function checkpointStatuses(): array
    {
        return [
            'loaded',
            'departed',
            'arrived',
            'unloaded',
            'exception_hold',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_PICKUP,
            self::TYPE_LINEHAUL,
            self::TYPE_ARRIVAL,
            self::TYPE_DELIVERY,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_IN_PROGRESS,
            self::STATUS_CLOSED,
        ];
    }

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            self::TYPE_PICKUP => 'Manifest Pickup',
            self::TYPE_LINEHAUL => 'Manifest Linehaul',
            self::TYPE_ARRIVAL => 'Manifest Tiba Cabang',
            self::TYPE_DELIVERY => 'Manifest Delivery',
            default => strtoupper((string) $type),
        };
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_PROGRESS => 'Berjalan',
            self::STATUS_CLOSED => 'Ditutup',
            default => strtoupper((string) $status),
        };
    }

    public static function checkpointStatusLabel(?string $status): string
    {
        return match ($status) {
            'loaded' => 'Sudah Dimuat',
            'departed' => 'Sudah Berangkat',
            'arrived' => 'Sudah Tiba',
            'unloaded' => 'Sudah Diturunkan',
            'exception_hold' => 'Hold / Exception',
            default => strtoupper((string) $status),
        };
    }
}
