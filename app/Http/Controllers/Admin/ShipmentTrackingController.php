<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Support\Uploads;
use App\Support\AuditLogger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ShipmentTrackingController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('shipment_trackings', 'read');
        $user = Auth::user();
        $search = request('q');
        $status = request('status');

        $trackings = ShipmentTracking::with('shipment')
            ->whereHas('shipment.payment', fn ($query) => $query->where('payment_status', Payment::STATUS_PAID))
            ->when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
                $query->whereHas('shipment', function ($subQuery) use ($user) {
                    $subQuery->where('courier_id', $user->id);
                });
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('location', 'like', '%' . $search . '%')
                        ->orWhereHas('shipment', fn ($q) => $q->where('tracking_number', 'like', '%' . $search . '%'));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summaryBaseQuery = ShipmentTracking::query()
            ->whereHas('shipment.payment', fn ($query) => $query->where('payment_status', Payment::STATUS_PAID))
            ->when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
                $query->whereHas('shipment', function ($subQuery) use ($user) {
                    $subQuery->where('courier_id', $user->id);
                });
            });

        return view('admin.shipment_trackings.index', [
            'title' => 'Shipment Trackings',
            'trackings' => $trackings,
            'summary' => [
                'total' => (clone $summaryBaseQuery)->count(),
                'today' => (clone $summaryBaseQuery)->whereDate('tracked_at', now()->toDateString())->count(),
                'delivered' => (clone $summaryBaseQuery)->where('status', ShipmentTracking::STATUS_DELIVERED)->count(),
                'with_proof' => (clone $summaryBaseQuery)->whereNotNull('proof_photo')->count(),
                'exception' => (clone $summaryBaseQuery)->whereIn('status', [
                    ShipmentTracking::STATUS_FAILED_DELIVERY,
                    ShipmentTracking::STATUS_EXCEPTION_HOLD,
                    ShipmentTracking::STATUS_RETURNED_TO_SENDER,
                ])->count(),
            ],
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
            'statuses' => ShipmentTracking::statuses(),
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureTablePermission('shipment_trackings', 'create');
        $selectedShipment = null;
        $selectedStatus = $request->input('status');

        if ($request->filled('shipment_id')) {
            $selectedShipment = Shipment::findOrFail((int) $request->input('shipment_id'));
            $this->ensureShipmentVisibility($selectedShipment);
            $this->ensureShipmentPaid($selectedShipment);
        }

        return view('admin.shipment_trackings.create', [
            'title' => 'Create Shipment Tracking',
            'shipments' => $this->visibleShipments(),
            'statuses' => $this->trackingStatusesForCurrentUser($selectedShipment),
            'selectedShipment' => $selectedShipment,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('shipment_trackings', 'create');

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'checkpoint_type' => 'nullable|string|max:50',
            'received_by' => 'nullable|string|max:100',
            'receiver_relation' => 'nullable|string|max:50',
            'proof_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'status' => ['required', Rule::in(ShipmentTracking::statuses())],
            'tracked_at' => 'required|date',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $this->ensureShipmentVisibility($shipment);
        $this->ensureShipmentPaid($shipment);
        $this->validateStatusTransition($shipment, $validated['status']);
        $this->applyDeliveryProofValidation($request, $validated);

        if ($request->hasFile('proof_photo')) {
            $validated['proof_photo'] = Uploads::storePublic($request->file('proof_photo'), 'shipment-trackings', 'proof');
        }

        DB::transaction(function () use ($shipment, $validated): void {
            $tracking = ShipmentTracking::create($validated);
            $shipment->update([
                'status' => $validated['status'],
                'courier_id' => $this->resolveResponsibleCourierId($shipment, $validated['status']),
                'exception_code' => $this->resolveExceptionCode($validated['status']),
                'exception_notes' => $this->resolveExceptionNotes($validated),
                'last_exception_at' => $this->resolveExceptionCode($validated['status']) ? now() : $shipment->last_exception_at,
            ]);

            AuditLogger::log(
                $shipment,
                'tracking.created',
                'Tracking ' . ShipmentTracking::statusLabel($tracking->status) . ' ditambahkan untuk shipment ' . $shipment->tracking_number . '.',
                null,
                [
                    'tracking_id' => $tracking->id,
                    'status' => $tracking->status,
                    'location' => $tracking->location,
                ],
            );
        });

        return redirect()->route('shipment-trackings.index')->with('success', 'Tracking berhasil ditambahkan.');
    }

    public function edit(ShipmentTracking $shipmentTracking)
    {
        $this->ensureTablePermission('shipment_trackings', 'update');
        $this->ensureShipmentVisibility($shipmentTracking->shipment);

        return view('admin.shipment_trackings.edit', [
            'title' => 'Edit Shipment Tracking',
            'shipmentTracking' => $shipmentTracking,
            'shipments' => $this->visibleShipments(),
            'statuses' => $this->trackingStatusesForCurrentUser($shipmentTracking->shipment, $shipmentTracking->status),
            'selectedShipment' => $shipmentTracking->shipment,
            'selectedStatus' => $shipmentTracking->status,
        ]);
    }

    public function update(Request $request, ShipmentTracking $shipmentTracking)
    {
        $this->ensureTablePermission('shipment_trackings', 'update');
        $this->ensureShipmentVisibility($shipmentTracking->shipment);

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'checkpoint_type' => 'nullable|string|max:50',
            'received_by' => 'nullable|string|max:100',
            'receiver_relation' => 'nullable|string|max:50',
            'proof_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'status' => ['required', Rule::in(ShipmentTracking::statuses())],
            'tracked_at' => 'required|date',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $this->ensureShipmentVisibility($shipment);
        $this->ensureShipmentPaid($shipment);
        $this->validateStatusTransition($shipment, $validated['status'], $shipmentTracking->id);
        $this->applyDeliveryProofValidation($request, $validated, $shipmentTracking->proof_photo);

        if ($request->hasFile('proof_photo')) {
            $validated['proof_photo'] = Uploads::storePublic($request->file('proof_photo'), 'shipment-trackings', 'proof');
        } else {
            $validated['proof_photo'] = $shipmentTracking->proof_photo;
        }

        DB::transaction(function () use ($shipment, $shipmentTracking, $validated): void {
            $oldValues = $shipmentTracking->only(['status', 'location', 'description']);
            $shipmentTracking->update($validated);

            $latestStatus = ShipmentTracking::where('shipment_id', $shipment->id)
                ->orderByDesc('tracked_at')
                ->orderByDesc('id')
                ->value('status');

            if ($latestStatus) {
                $shipment->update([
                    'status' => $latestStatus,
                    'courier_id' => $this->resolveResponsibleCourierId($shipment, $latestStatus),
                    'exception_code' => $this->resolveExceptionCode($latestStatus),
                    'exception_notes' => $this->resolveExceptionNotes($validated),
                    'last_exception_at' => $this->resolveExceptionCode($latestStatus) ? now() : $shipment->last_exception_at,
                ]);
            }

            AuditLogger::log(
                $shipment,
                'tracking.updated',
                'Tracking shipment ' . $shipment->tracking_number . ' diperbarui.',
                $oldValues,
                $shipmentTracking->only(['status', 'location', 'description']),
            );
        });

        return redirect()->route('shipment-trackings.index')->with('success', 'Tracking berhasil diperbarui.');
    }

    public function destroy(ShipmentTracking $shipmentTracking)
    {
        $this->ensureTablePermission('shipment_trackings', 'delete');
        $this->ensureShipmentVisibility($shipmentTracking->shipment);

        DB::transaction(function () use ($shipmentTracking): void {
            $shipmentId = $shipmentTracking->shipment_id;
            $summary = 'Tracking #' . $shipmentTracking->id . ' dihapus dari shipment #' . $shipmentId . '.';
            $shipmentTracking->delete();

            $latestStatus = ShipmentTracking::where('shipment_id', $shipmentId)
                ->orderByDesc('tracked_at')
                ->orderByDesc('id')
                ->value('status');

            $shipment = Shipment::find($shipmentId);

            Shipment::where('id', $shipmentId)->update([
                'status' => $latestStatus ?? Shipment::STATUS_PENDING,
                'courier_id' => $this->resolveResponsibleCourierId($shipment ?? Shipment::findOrFail($shipmentId), $latestStatus ?? Shipment::STATUS_PENDING),
                'exception_code' => in_array($latestStatus, [
                    ShipmentTracking::STATUS_FAILED_DELIVERY,
                    ShipmentTracking::STATUS_EXCEPTION_HOLD,
                    ShipmentTracking::STATUS_RETURNED_TO_SENDER,
                ], true) ? $latestStatus : null,
            ]);

            if ($shipment) {
                AuditLogger::log($shipment, 'tracking.deleted', $summary);
            }
        });

        return redirect()->route('shipment-trackings.index')->with('success', 'Tracking berhasil dihapus.');
    }

    private function visibleShipments()
    {
        $user = Auth::user();

        return Shipment::when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
            $query->where('courier_id', $user->id);
        })
            ->whereHas('payment', fn ($query) => $query->where('payment_status', Payment::STATUS_PAID))
            ->orderBy('tracking_number')
            ->get();
    }

    private function ensureShipmentVisibility(Shipment $shipment): void
    {
        $user = Auth::user();

        if ($user && $user->role === User::ROLE_COURIER && $shipment->courier_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke shipment ini.');
        }
    }

    private function ensureShipmentPaid(Shipment $shipment): void
    {
        $shipment->loadMissing('payment');
        if (!$shipment->payment || $shipment->payment->payment_status !== Payment::STATUS_PAID) {
            throw ValidationException::withMessages([
                'shipment_id' => 'Shipment belum lunas sehingga belum bisa diproses tracking pengiriman.',
            ]);
        }
    }

    private function validateStatusTransition(Shipment $shipment, string $status, ?int $ignoreTrackingId = null): void
    {
        $latestTrackingStatus = ShipmentTracking::where('shipment_id', $shipment->id)
            ->when($ignoreTrackingId, fn ($query) => $query->where('id', '!=', $ignoreTrackingId))
            ->orderByDesc('tracked_at')
            ->orderByDesc('id')
            ->value('status');

        $baseStatus = $latestTrackingStatus ?: $shipment->status;
        if (Auth::user()?->role === User::ROLE_COURIER) {
            $shipment->loadMissing('trackings');
            $allowedStatuses = $shipment->nextCourierTaskStatuses();
        } else {
            $allowedStatuses = Shipment::nextTrackingStatuses($baseStatus);
        }

        if (!in_array($status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'Urutan status tracking tidak valid untuk shipment ini.',
            ]);
        }
    }

    private function applyDeliveryProofValidation(Request $request, array &$validated, ?string $existingProof = null): void
    {
        $isCourierProof = Auth::user()?->role === User::ROLE_COURIER
            && Shipment::courierTaskRequiresProof($validated['status']);

        if (!$isCourierProof && $validated['status'] !== ShipmentTracking::STATUS_DELIVERED) {
            $validated['received_by'] = null;
            $validated['receiver_relation'] = null;
            return;
        }

        if (!$isCourierProof) {
            validator($request->all(), [
                'received_by' => 'required|string|max:100',
                'receiver_relation' => 'nullable|string|max:50',
            ])->validate();
        } else {
            $validated['received_by'] = null;
            $validated['receiver_relation'] = null;
        }

        if (!$request->hasFile('proof_photo') && empty($existingProof)) {
            throw ValidationException::withMessages([
                'proof_photo' => $isCourierProof
                    ? ($validated['status'] === ShipmentTracking::STATUS_PICKED_UP
                        ? 'Foto bukti pickup wajib diunggah untuk status sudah dipickup.'
                        : 'Foto bukti pengantaran wajib diunggah untuk status sampai di tujuan.')
                    : 'Foto bukti serah terima wajib diunggah untuk status paket sudah sampai ke rumah penerima.',
            ]);
        }
    }

    private function trackingStatusesForCurrentUser(?Shipment $shipment = null, ?string $currentStatus = null): array
    {
        if (Auth::user()?->role !== User::ROLE_COURIER) {
            return ShipmentTracking::statuses();
        }

        if (!$shipment) {
            return ShipmentTracking::statuses();
        }

        $statuses = $shipment->nextCourierTaskStatuses();

        if ($currentStatus && in_array($currentStatus, ShipmentTracking::statuses(), true) && !in_array($currentStatus, $statuses, true)) {
            array_unshift($statuses, $currentStatus);
        }

        return array_values(array_unique($statuses));
    }

    private function resolveExceptionCode(string $status): ?string
    {
        return in_array($status, [
            ShipmentTracking::STATUS_FAILED_DELIVERY,
            ShipmentTracking::STATUS_EXCEPTION_HOLD,
            ShipmentTracking::STATUS_RETURNED_TO_SENDER,
        ], true) ? $status : null;
    }

    private function resolveExceptionNotes(array $validated): ?string
    {
        return $this->resolveExceptionCode($validated['status'] ?? '') ? ($validated['description'] ?? null) : null;
    }

    private function resolveResponsibleCourierId(Shipment $shipment, string $status): int
    {
        if (in_array($status, [
            ShipmentTracking::STATUS_EXCEPTION_HOLD,
            ShipmentTracking::STATUS_FAILED_DELIVERY,
            ShipmentTracking::STATUS_RETURNED_TO_SENDER,
        ], true)) {
            return (int) $shipment->courier_id;
        }

        $shipment->unsetRelation('trackings');
        $responsibleCourier = $shipment->resolveResponsibleCourierForStatus($status);

        if ($responsibleCourier) {
            return (int) $responsibleCourier->id;
        }

        throw ValidationException::withMessages([
            'status' => match ($status) {
                ShipmentTracking::STATUS_PENDING => 'Belum ada courier pickup aktif di cabang asal.',
                ShipmentTracking::STATUS_PICKED_UP,
                ShipmentTracking::STATUS_IN_TRANSIT => 'Belum ada courier HTH aktif di cabang asal.',
                ShipmentTracking::STATUS_ARRIVED_AT_BRANCH,
                ShipmentTracking::STATUS_OUT_FOR_DELIVERY,
                ShipmentTracking::STATUS_DELIVERED => 'Belum ada courier drop aktif di cabang tujuan.',
                default => 'Belum ada courier yang sesuai untuk status ini.',
            },
        ]);
    }
}
