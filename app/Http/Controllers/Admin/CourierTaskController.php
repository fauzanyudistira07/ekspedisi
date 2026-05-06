<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Support\Uploads;
use App\Support\AuditLogger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CourierTaskController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $this->ensureAccess($user);

        $status = $request->input('status');
        $search = $request->input('q');
        $courierId = $user->role === User::ROLE_COURIER
            ? $user->id
            : ($request->input('courier_id') ?: null);

        $shipments = Shipment::with(['sender', 'receiver', 'originBranch', 'destinationBranch', 'trackings' => function ($query) {
            $query->latest('tracked_at');
        }, 'courier'])
            ->whereHas('payment', fn ($query) => $query->where('payment_status', Payment::STATUS_PAID))
            ->when($courierId, fn ($query) => $query->where('courier_id', $courierId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('tracking_number', 'like', '%' . $search . '%')
                        ->orWhereHas('sender', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('receiver', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('shipment_date')
            ->paginate(10)
            ->withQueryString();

        $taskSummaryQuery = Shipment::query()
            ->whereHas('payment', fn ($query) => $query->where('payment_status', Payment::STATUS_PAID))
            ->when($courierId, fn ($query) => $query->where('courier_id', $courierId));
        $today = now()->toDateString();

        $summary = [
            'assigned_total' => (clone $taskSummaryQuery)->count(),
            'today_assigned' => (clone $taskSummaryQuery)->whereDate('shipment_date', $today)->count(),
            'active' => (clone $taskSummaryQuery)->whereIn('status', [
                Shipment::STATUS_PENDING,
                Shipment::STATUS_IN_TRANSIT,
                Shipment::STATUS_PICKED_UP,
                Shipment::STATUS_ARRIVED_AT_BRANCH,
                Shipment::STATUS_OUT_FOR_DELIVERY,
            ])->count(),
            'completed' => (clone $taskSummaryQuery)->where('status', Shipment::STATUS_DELIVERED)->count(),
        ];

        return view('admin.courier.tasks', [
            'title' => 'Courier Tasks',
            'shipments' => $shipments,
            'summary' => $summary,
            'filters' => [
                'status' => $status,
                'q' => $search,
                'courier_id' => $courierId,
            ],
            'statuses' => Shipment::courierTaskStatuses(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
            'isCourierView' => $user->role === User::ROLE_COURIER,
        ]);
    }

    public function updateStatus(Request $request, Shipment $shipment)
    {
        $user = Auth::user();
        $this->ensureAccess($user);
        $this->ensureShipmentAccess($user, $shipment);
        $this->ensureShipmentPaid($shipment);

        $validated = $request->validate([
            'status' => ['required', Rule::in(ShipmentTracking::statuses())],
            'location' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'received_by' => 'nullable|string|max:100',
            'receiver_relation' => 'nullable|string|max:50',
            'proof_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'tracked_at' => 'nullable|date',
        ]);

        $shipment->loadMissing('trackings');
        $nextStatuses = $shipment->nextCourierTaskStatuses();
        if (!in_array($validated['status'], $nextStatuses, true)) {
            return back()->withErrors([
                'status' => 'Perubahan status tidak valid. Ikuti urutan status pengiriman.',
            ]);
        }

        $this->applyDeliveryProofValidation($request, $validated);

        if ($request->hasFile('proof_photo')) {
            $validated['proof_photo'] = Uploads::storePublic($request->file('proof_photo'), 'shipment-trackings', 'proof');
        }

        ShipmentTracking::create([
            'shipment_id' => $shipment->id,
            'location' => $validated['location'],
            'description' => $validated['description'] ?? 'Update dari dashboard courier.',
            'checkpoint_type' => $shipment->courierTrackingCheckpointType(),
            'received_by' => null,
            'receiver_relation' => null,
            'proof_photo' => $validated['proof_photo'] ?? null,
            'status' => $validated['status'],
            'tracked_at' => $validated['tracked_at'] ?? now(),
        ]);

        $shipment->update([
            'status' => $validated['status'],
            'courier_id' => $this->resolveNextCourierId($shipment, $validated['status']),
            'exception_code' => $this->isExceptionStatus($validated['status']) ? $validated['status'] : null,
            'exception_notes' => $this->isExceptionStatus($validated['status']) ? ($validated['description'] ?? null) : null,
            'last_exception_at' => $this->isExceptionStatus($validated['status']) ? now() : $shipment->last_exception_at,
        ]);

        AuditLogger::log(
            $shipment,
            'courier.status_updated',
            'Kurir memperbarui shipment ' . $shipment->tracking_number . ' ke status ' . ShipmentTracking::statusLabel($validated['status']) . '.',
            null,
            [
                'status' => $validated['status'],
                'location' => $validated['location'],
                'description' => $validated['description'] ?? null,
            ],
        );

        return back()->with('success', 'Status shipment berhasil diperbarui.');
    }

    private function ensureAccess(?User $user): void
    {
        if (!$user || !$user->hasAnyRole([
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_COURIER,
        ])) {
            abort(403, 'Anda tidak memiliki akses ke task courier.');
        }
    }

    private function ensureShipmentAccess(User $user, Shipment $shipment): void
    {
        if ($user->role === User::ROLE_COURIER && $shipment->courier_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke shipment ini.');
        }
    }

    private function applyDeliveryProofValidation(Request $request, array &$validated): void
    {
        if (!Shipment::courierTaskRequiresProof($validated['status'])) {
            $validated['received_by'] = null;
            $validated['receiver_relation'] = null;
            return;
        }

        if (!$request->hasFile('proof_photo')) {
            throw ValidationException::withMessages([
                'proof_photo' => $validated['status'] === ShipmentTracking::STATUS_PICKED_UP
                    ? 'Foto bukti pickup wajib diunggah saat status sudah dipickup.'
                    : 'Foto bukti pengantaran wajib diunggah saat status sampai di tujuan.',
            ]);
        }
    }

    private function isExceptionStatus(string $status): bool
    {
        return in_array($status, [
            ShipmentTracking::STATUS_FAILED_DELIVERY,
            ShipmentTracking::STATUS_EXCEPTION_HOLD,
            ShipmentTracking::STATUS_RETURNED_TO_SENDER,
        ], true);
    }

    private function ensureShipmentPaid(Shipment $shipment): void
    {
        $shipment->loadMissing('payment');
        if (!$shipment->payment || $shipment->payment->payment_status !== Payment::STATUS_PAID) {
            throw ValidationException::withMessages([
                'status' => 'Shipment belum lunas. Status pengiriman tidak bisa diupdate.',
            ]);
        }
    }

    private function resolveNextCourierId(Shipment $shipment, string $nextStatus): int
    {
        if (in_array($nextStatus, [
            ShipmentTracking::STATUS_EXCEPTION_HOLD,
            ShipmentTracking::STATUS_FAILED_DELIVERY,
            ShipmentTracking::STATUS_RETURNED_TO_SENDER,
        ], true)) {
            return (int) $shipment->courier_id;
        }

        $shipment->status = $nextStatus;
        $nextCourier = $shipment->resolveResponsibleCourierForStatus($nextStatus);

        if ($nextCourier) {
            return (int) $nextCourier->id;
        }

        throw ValidationException::withMessages([
            'status' => match ($nextStatus) {
                ShipmentTracking::STATUS_PICKED_UP,
                ShipmentTracking::STATUS_IN_TRANSIT => 'Belum ada courier HTH aktif di cabang asal untuk melanjutkan shipment ini.',
                ShipmentTracking::STATUS_ARRIVED_AT_BRANCH,
                ShipmentTracking::STATUS_OUT_FOR_DELIVERY,
                ShipmentTracking::STATUS_DELIVERED => 'Belum ada courier drop aktif di cabang tujuan untuk melanjutkan shipment ini.',
                default => 'Belum ada courier yang sesuai untuk status ini.',
            },
        ]);
    }
}
