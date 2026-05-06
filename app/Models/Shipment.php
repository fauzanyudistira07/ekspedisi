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
        return $this->belongsToMany(ShipmentManifest::class, 'manifest_shipments', 'shipment_id', 'manifest_id')
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
            self::STATUS_DELIVERED => 'Sampai ke Rumah Penerima',
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

    public static function courierPickupStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_TRANSIT,
            self::STATUS_PICKED_UP,
            self::STATUS_ARRIVED_AT_BRANCH,
        ];
    }

    public static function courierTaskStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_TRANSIT,
            self::STATUS_PICKED_UP,
            self::STATUS_ARRIVED_AT_BRANCH,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_EXCEPTION_HOLD,
            self::STATUS_DELIVERED,
        ];
    }

    public static function courierTaskFilterStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_TRANSIT => 'Menuju Pickup / Menuju Cabang Tujuan',
            self::STATUS_PICKED_UP => 'Sudah Dipickup / Proses Antar Cabang',
            self::STATUS_ARRIVED_AT_BRANCH => 'Sampai di Cabang Tujuan / Pickup dari Cabang',
            self::STATUS_OUT_FOR_DELIVERY => 'Sedang Diantar ke Alamat',
            self::STATUS_EXCEPTION_HOLD => 'Exception / Hold',
            self::STATUS_DELIVERED => 'Sampai di Tujuan',
        ];
    }

    public static function courierPickupStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_TRANSIT => 'Menuju Pickup',
            self::STATUS_PICKED_UP => 'Sudah Dipickup',
            self::STATUS_ARRIVED_AT_BRANCH => 'Sampai di Cabang',
        ];
    }

    public static function courierPickupStatusLabel(?string $status): string
    {
        $labels = self::courierPickupStatusLabels();

        return $labels[$status] ?? self::statusLabel($status);
    }

    public static function courierTaskFilterStatusLabel(?string $status): string
    {
        $labels = self::courierTaskFilterStatusLabels();

        return $labels[$status] ?? self::statusLabel($status);
    }

    public static function courierTaskActionStatusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_TRANSIT => 'Menuju Pickup / Menuju Cabang Tujuan',
            self::STATUS_PICKED_UP => 'Sudah Dipickup / Proses Antar Cabang',
            self::STATUS_ARRIVED_AT_BRANCH => 'Sampai di Cabang Tujuan / Pickup dari Cabang',
            self::STATUS_OUT_FOR_DELIVERY => 'Sedang Diantar ke Alamat',
            self::STATUS_EXCEPTION_HOLD => 'Exception / Hold',
            self::STATUS_DELIVERED => 'Sampai di Tujuan',
            default => self::statusLabel($status),
        };
    }

    public function hasTrackingStatus(string $status): bool
    {
        if ($this->relationLoaded('trackings')) {
            return $this->trackings->contains(fn ($tracking) => $tracking->status === $status);
        }

        return $this->trackings()->where('status', $status)->exists();
    }

    public function courierTaskCurrentStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_TRANSIT => $this->hasTrackingStatus(self::STATUS_PICKED_UP)
                ? 'Menuju Cabang Tujuan'
                : 'Menuju Pickup',
            self::STATUS_PICKED_UP => 'Proses Antar Cabang',
            self::STATUS_ARRIVED_AT_BRANCH => 'Pickup dari Cabang',
            self::STATUS_OUT_FOR_DELIVERY => 'Sedang Diantar ke Alamat',
            self::STATUS_EXCEPTION_HOLD => 'Exception / Hold',
            self::STATUS_DELIVERED => 'Sampai di Tujuan',
            default => self::statusLabel($this->status),
        };
    }

    public function courierTaskLatestTrackingLabel(?string $trackingStatus): string
    {
        if ($trackingStatus === null) {
            return '-';
        }

        if ($trackingStatus === self::STATUS_ARRIVED_AT_BRANCH) {
            return 'Sampai di Cabang Tujuan';
        }

        if ($trackingStatus === self::STATUS_PICKED_UP) {
            return 'Sudah Dipickup';
        }

        if ($trackingStatus === self::STATUS_IN_TRANSIT) {
            return $this->hasTrackingStatus(self::STATUS_PICKED_UP)
                ? 'Menuju Cabang Tujuan'
                : 'Menuju Pickup';
        }

        if ($trackingStatus === self::STATUS_DELIVERED) {
            return 'Sampai di Tujuan';
        }

        if ($trackingStatus === self::STATUS_OUT_FOR_DELIVERY) {
            return 'Sedang Diantar ke Alamat';
        }

        return self::statusLabel($trackingStatus);
    }

    public function courierTaskStageLabel(): string
    {
        return match ($this->courierTaskType()) {
            'pickup' => 'Task Pickup',
            'hth' => 'Task HTH',
            'drop' => 'Task Drop',
            default => 'Task Courier',
        };
    }

    public function courierTaskType(): string
    {
        $stageStatus = $this->status === self::STATUS_EXCEPTION_HOLD
            ? $this->latestOperationalCourierStatus()
            : $this->status;

        return match (true) {
            $stageStatus === self::STATUS_PENDING => 'pickup',
            $stageStatus === self::STATUS_IN_TRANSIT && !$this->hasTrackingStatus(self::STATUS_PICKED_UP) => 'pickup',
            $stageStatus === self::STATUS_PICKED_UP => 'hth',
            $stageStatus === self::STATUS_IN_TRANSIT && $this->hasTrackingStatus(self::STATUS_PICKED_UP) => 'hth',
            in_array($stageStatus, [self::STATUS_ARRIVED_AT_BRANCH, self::STATUS_OUT_FOR_DELIVERY, self::STATUS_DELIVERED], true) => 'drop',
            default => 'general',
        };
    }

    public function courierTaskTypeLabel(): string
    {
        return match ($this->courierTaskType()) {
            'pickup' => 'Pickup',
            'hth' => 'HTH',
            'drop' => 'Drop',
            default => 'Courier',
        };
    }

    public function courierTaskStageFlow(): string
    {
        return match ($this->courierTaskType()) {
            'pickup' => 'Pending -> Menuju Pickup -> Sudah Dipickup -> Sampai di Cabang',
            'hth' => 'Proses -> Menuju Cabang Tujuan -> Sampai di Cabang Tujuan',
            'drop' => 'Pickup dari Cabang -> Sedang Diantar ke Alamat -> Sampai di Tujuan',
            default => 'Ikuti urutan status courier.',
        };
    }

    public function courierTrackingCheckpointType(): string
    {
        return match ($this->courierTaskType()) {
            'pickup' => 'courier_pickup',
            'hth' => 'courier_hth',
            'drop' => 'courier_drop',
            default => 'courier_task',
        };
    }

    public function courierTrackingStatusSelectionLabel(string $status): string
    {
        if ($this->status === self::STATUS_EXCEPTION_HOLD && $status === $this->courierTaskResumeStatusFromHold()) {
            return 'Memproses Kembali';
        }

        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_TRANSIT => $this->hasTrackingStatus(self::STATUS_PICKED_UP)
                ? 'Menuju Cabang Tujuan'
                : 'Menuju Pickup',
            self::STATUS_PICKED_UP => 'Sudah Dipickup',
            self::STATUS_ARRIVED_AT_BRANCH => 'Sampai di Cabang Tujuan',
            self::STATUS_OUT_FOR_DELIVERY => 'Sedang Diantar ke Alamat',
            self::STATUS_DELIVERED => 'Sampai di Tujuan',
            default => self::statusLabel($status),
        };
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

    public static function nextCourierPickupStatuses(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::STATUS_PENDING => [self::STATUS_IN_TRANSIT],
            self::STATUS_IN_TRANSIT => [self::STATUS_PICKED_UP],
            self::STATUS_PICKED_UP => [self::STATUS_ARRIVED_AT_BRANCH],
            default => [],
        };
    }

    public function nextCourierTaskStatuses(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING => [self::STATUS_IN_TRANSIT],
            self::STATUS_IN_TRANSIT => $this->hasTrackingStatus(self::STATUS_PICKED_UP)
                ? [self::STATUS_ARRIVED_AT_BRANCH]
                : [self::STATUS_PICKED_UP],
            self::STATUS_PICKED_UP => [self::STATUS_IN_TRANSIT],
            self::STATUS_ARRIVED_AT_BRANCH => [self::STATUS_OUT_FOR_DELIVERY],
            self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED],
            self::STATUS_EXCEPTION_HOLD => [$this->courierTaskResumeStatusFromHold()],
            default => [],
        };
    }

    public function courierTaskNextStatusLabel(string $nextStatus): string
    {
        if ($this->status === self::STATUS_EXCEPTION_HOLD && $nextStatus === $this->courierTaskResumeStatusFromHold()) {
            return 'Memproses Kembali';
        }

        if ($this->status === self::STATUS_PENDING && $nextStatus === self::STATUS_IN_TRANSIT) {
            return 'Menuju Pickup';
        }

        if ($this->status === self::STATUS_IN_TRANSIT && !$this->hasTrackingStatus(self::STATUS_PICKED_UP) && $nextStatus === self::STATUS_PICKED_UP) {
            return 'Sudah Dipickup';
        }

        if ($this->status === self::STATUS_PICKED_UP && $nextStatus === self::STATUS_IN_TRANSIT) {
            return 'Menuju Cabang Tujuan';
        }

        if ($this->status === self::STATUS_IN_TRANSIT && $this->hasTrackingStatus(self::STATUS_PICKED_UP) && $nextStatus === self::STATUS_ARRIVED_AT_BRANCH) {
            return 'Sampai di Cabang Tujuan';
        }

        if ($this->status === self::STATUS_ARRIVED_AT_BRANCH && $nextStatus === self::STATUS_OUT_FOR_DELIVERY) {
            return 'Sedang Diantar ke Alamat';
        }

        if ($this->status === self::STATUS_OUT_FOR_DELIVERY && $nextStatus === self::STATUS_DELIVERED) {
            return 'Sampai di Tujuan';
        }

        return self::statusLabel($nextStatus);
    }

    public static function courierTaskRequiresProof(string $status): bool
    {
        return in_array($status, [
            self::STATUS_PICKED_UP,
            self::STATUS_DELIVERED,
        ], true);
    }

    public function resolveResponsibleCourierForStatus(?string $status = null): ?User
    {
        $status ??= $this->status;

        return match ($status) {
            self::STATUS_PENDING => User::resolveCourierForTask((int) $this->origin_branch_id, User::COURIER_TASK_PICKUP),
            self::STATUS_IN_TRANSIT => $this->hasTrackingStatus(self::STATUS_PICKED_UP)
                ? User::resolveCourierForTask((int) $this->origin_branch_id, User::COURIER_TASK_HTH)
                : User::resolveCourierForTask((int) $this->origin_branch_id, User::COURIER_TASK_PICKUP),
            self::STATUS_PICKED_UP => User::resolveCourierForTask((int) $this->origin_branch_id, User::COURIER_TASK_HTH),
            self::STATUS_ARRIVED_AT_BRANCH,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED => User::resolveCourierForTask((int) $this->destination_branch_id, User::COURIER_TASK_DROP),
            default => $this->courier,
        };
    }

    public function courierTaskResumeStatusFromHold(): string
    {
        $previousStatus = $this->latestOperationalCourierStatus();

        if (in_array($previousStatus, [self::STATUS_ARRIVED_AT_BRANCH, self::STATUS_OUT_FOR_DELIVERY], true)) {
            return self::STATUS_OUT_FOR_DELIVERY;
        }

        return self::STATUS_IN_TRANSIT;
    }

    private function latestOperationalCourierStatus(): string
    {
        $excludedStatuses = [
            self::STATUS_EXCEPTION_HOLD,
            self::STATUS_FAILED_DELIVERY,
            self::STATUS_RETURNED_TO_SENDER,
        ];

        if ($this->relationLoaded('trackings')) {
            $trackingStatus = $this->trackings
                ->first(fn ($tracking) => !in_array($tracking->status, $excludedStatuses, true))
                ?->status;

            return $trackingStatus ?: self::STATUS_PENDING;
        }

        $trackingStatus = $this->trackings()
            ->whereNotIn('status', $excludedStatuses)
            ->orderByDesc('tracked_at')
            ->orderByDesc('id')
            ->value('status');

        return $trackingStatus ?: self::STATUS_PENDING;
    }
}
