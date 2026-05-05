<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentTracking extends Model
{
    use HasFactory;

    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_ARRIVED_AT_BRANCH = 'arrived_at_branch';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'shipment_id',
        'location',
        'description',
        'status',
        'tracked_at',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_ARRIVED_AT_BRANCH,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PICKED_UP => 'Sudah Dipickup',
            self::STATUS_IN_TRANSIT => 'Dalam Perjalanan',
            self::STATUS_ARRIVED_AT_BRANCH => 'Tiba di Cabang Tujuan',
            self::STATUS_OUT_FOR_DELIVERY => 'Sedang Diantar',
            self::STATUS_DELIVERED => 'Terkirim',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        $labels = self::statusLabels();

        return $labels[$status] ?? strtoupper(str_replace('_', ' ', (string) $status));
    }
}
