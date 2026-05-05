<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_E_WALLET = 'e-wallet';
    public const STATUS_PENDING = 'pending';
    public const STATUS_WAITING_VERIFICATION = 'waiting_verification';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'shipment_id',
        'amount',
        'payment_method',
        'reference_number',
        'proof_file',
        'payment_status',
        'payment_date',
        'verified_at',
        'expired_at',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
        'expired_at' => 'datetime',
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
            self::METHOD_CASH,
            self::METHOD_TRANSFER,
            self::METHOD_E_WALLET,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_WAITING_VERIFICATION,
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
            self::STATUS_WAITING_VERIFICATION => 'Menunggu Verifikasi',
            self::STATUS_PAID => 'Lunas',
            self::STATUS_FAILED => 'Pembayaran Ditolak',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
            self::STATUS_REFUNDED => 'Dikembalikan',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        $labels = self::statusLabels();

        return $labels[$status] ?? strtoupper(str_replace('_', ' ', (string) $status));
    }
}
