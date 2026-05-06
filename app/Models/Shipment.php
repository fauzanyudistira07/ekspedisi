<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_ARRIVED_AT_BRANCH = 'arrived_at_branch';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED_DELIVERY = 'failed_delivery';
    public const STATUS_EXCEPTION_HOLD = 'exception_hold';
    public const STATUS_RETURNED_TO_SENDER = 'returned_to_sender';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tracking_number',
        'sender_id',
        'receiver_id',
        'origin_branch_id',
        'destination_branch_id',
        'courier_id',
        'rate_id',
        'total_weight',
        'total_price',
        'status',
        'exception_code',
        'exception_notes',
        'last_exception_at',
        'shipment_date',
        'photo',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'total_weight' => 'decimal:2',
        'total_price' => 'decimal:2',
        'last_exception_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(Customer::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Customer::class, 'receiver_id');
    }

    public function originBranch()
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function rate()
    {
        return $this->belongsTo(Rate::class);
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function trackings()
    {
        return $this->hasMany(ShipmentTracking::class);
    }

    public function manifests()
    {
        return $this->belongsToMany(ShipmentManifest::class, 'manifest_shipments')
            ->withPivot(['loaded_at', 'unloaded_at', 'checkpoint_status', 'checkpoint_notes'])
            ->withTimestamps();
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_ARRIVED_AT_BRANCH,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED_DELIVERY,
            self::STATUS_EXCEPTION_HOLD,
            self::STATUS_RETURNED_TO_SENDER,
            self::STATUS_CANCELLED,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Diproses',
            self::STATUS_PICKED_UP => 'Sudah Dipickup',
            self::STATUS_IN_TRANSIT => 'Dalam Perjalanan',
            self::STATUS_ARRIVED_AT_BRANCH => 'Tiba di Cabang Tujuan',
            self::STATUS_OUT_FOR_DELIVERY => 'Sedang Diantar',
            self::STATUS_DELIVERED => 'Terkirim',
            self::STATUS_FAILED_DELIVERY => 'Gagal Antar',
            self::STATUS_EXCEPTION_HOLD => 'Exception / Hold',
            self::STATUS_RETURNED_TO_SENDER => 'Retur ke Pengirim',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        $labels = self::statusLabels();

        return $labels[$status] ?? strtoupper(str_replace('_', ' ', (string) $status));
    }

    public static function deliveryFlow(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_ARRIVED_AT_BRANCH,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
        ];
    }

    public static function manifestEligibleStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_ARRIVED_AT_BRANCH,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_FAILED_DELIVERY,
            self::STATUS_EXCEPTION_HOLD,
        ];
    }

    public function statusStep(): int
    {
        $index = array_search($this->status, self::deliveryFlow(), true);

        return $index === false ? 0 : $index + 1;
    }

    public static function nextTrackingStatuses(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::STATUS_PENDING => [self::STATUS_PICKED_UP, self::STATUS_EXCEPTION_HOLD],
            self::STATUS_PICKED_UP => [self::STATUS_IN_TRANSIT, self::STATUS_EXCEPTION_HOLD],
            self::STATUS_IN_TRANSIT => [self::STATUS_IN_TRANSIT, self::STATUS_ARRIVED_AT_BRANCH, self::STATUS_EXCEPTION_HOLD],
            self::STATUS_ARRIVED_AT_BRANCH => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_EXCEPTION_HOLD],
            self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_DELIVERED, self::STATUS_FAILED_DELIVERY, self::STATUS_RETURNED_TO_SENDER, self::STATUS_EXCEPTION_HOLD],
            self::STATUS_FAILED_DELIVERY => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_RETURNED_TO_SENDER, self::STATUS_EXCEPTION_HOLD],
            self::STATUS_EXCEPTION_HOLD => [self::STATUS_IN_TRANSIT, self::STATUS_ARRIVED_AT_BRANCH, self::STATUS_OUT_FOR_DELIVERY, self::STATUS_RETURNED_TO_SENDER],
            default => [],
        };
    }
}
