<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const METHOD_MIDTRANS = 'midtrans';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'shipment_id',
        'amount',
        'gateway_provider',
        'gateway_order_id',
        'gateway_transaction_id',
        'payment_method',
        'payment_channel',
        'snap_token',
        'snap_redirect_url',
        'reference_number',
        'proof_file',
        'payment_status',
        'midtrans_transaction_status',
        'payment_date',
        'paid_at',
        'verified_at',
        'expired_at',
        'verified_by',
        'notes',
        'gateway_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'expired_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function methods(): array
    {
        return [
            self::METHOD_MIDTRANS,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_EXPIRED,
            self::STATUS_REFUNDED,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Pembayaran',
            self::STATUS_PAID => 'Lunas',
            self::STATUS_FAILED => 'Pembayaran Gagal',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
            self::STATUS_REFUNDED => 'Dikembalikan',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        $labels = self::statusLabels();

        return $labels[$status] ?? strtoupper(str_replace('_', ' ', (string) $status));
    }

    public function isFinal(): bool
    {
        return in_array($this->payment_status, [
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_EXPIRED,
            self::STATUS_REFUNDED,
        ], true);
    }
}
