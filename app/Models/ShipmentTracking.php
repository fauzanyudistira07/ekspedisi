<?php

namespace App\Models;

use App\Support\Uploads;
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
    public const STATUS_FAILED_DELIVERY = 'failed_delivery';
    public const STATUS_EXCEPTION_HOLD = 'exception_hold';
    public const STATUS_RETURNED_TO_SENDER = 'returned_to_sender';

    protected $fillable = [
        'shipment_id',
        'branch_id',
        'location',
        'description',
        'checkpoint_type',
        'received_by',
        'receiver_relation',
        'proof_photo',
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

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_ARRIVED_AT_BRANCH,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED_DELIVERY,
            self::STATUS_EXCEPTION_HOLD,
            self::STATUS_RETURNED_TO_SENDER,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PICKED_UP => 'Sudah Dipickup',
            self::STATUS_IN_TRANSIT => 'Dalam Perjalanan',
            self::STATUS_ARRIVED_AT_BRANCH => 'Tiba di Cabang Tujuan',
            self::STATUS_OUT_FOR_DELIVERY => 'Sedang Diantar',
            self::STATUS_DELIVERED => 'Sampai ke Rumah Penerima',
            self::STATUS_FAILED_DELIVERY => 'Gagal Antar',
            self::STATUS_EXCEPTION_HOLD => 'Exception / Hold',
            self::STATUS_RETURNED_TO_SENDER => 'Retur ke Pengirim',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        $labels = self::statusLabels();

        return $labels[$status] ?? strtoupper(str_replace('_', ' ', (string) $status));
    }

    public function requiresDeliveryProof(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function proofPhotoExists(): bool
    {
        return Uploads::exists('shipment-trackings', $this->proof_photo);
    }

    public function proofPhotoUrl(): ?string
    {
        if (!$this->proofPhotoExists()) {
            return null;
        }

        return Uploads::publicUrl('shipment-trackings', $this->proof_photo);
    }
}
