<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
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
        }

        return view('admin.shipment_trackings.create', [
            'title' => 'Create Shipment Tracking',
            'shipments' => $this->visibleShipments(),
            'statuses' => ShipmentTracking::statuses(),
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
        $this->validateStatusTransition($shipment, $validated['status']);
        $this->applyDeliveryProofValidation($request, $validated);

        if ($request->hasFile('proof_photo')) {
            $validated['proof_photo'] = Uploads::storePublic($request->file('proof_photo'), 'shipment-trackings', 'proof');
        }

        DB::transaction(function () use ($shipment, $validated): void {
            $tracking = ShipmentTracking::create($validated);
            $shipment->update([
                'status' => $validated['status'],
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
            'statuses' => ShipmentTracking::statuses(),
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
        })->orderBy('tracking_number')->get();
    }

    private function ensureShipmentVisibility(Shipment $shipment): void
    {
        $user = Auth::user();

        if ($user && $user->role === User::ROLE_COURIER && $shipment->courier_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke shipment ini.');
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
        $allowedStatuses = Shipment::nextTrackingStatuses($baseStatus);

        if (!in_array($status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'Urutan status tracking tidak valid untuk shipment ini.',
            ]);
        }
    }

    private function applyDeliveryProofValidation(Request $request, array &$validated, ?string $existingProof = null): void
    {
        if ($validated['status'] !== ShipmentTracking::STATUS_DELIVERED) {
            $validated['received_by'] = null;
            $validated['receiver_relation'] = null;
            return;
        }

        validator($request->all(), [
            'received_by' => 'required|string|max:100',
            'receiver_relation' => 'nullable|string|max:50',
        ])->validate();

        if (!$request->hasFile('proof_photo') && empty($existingProof)) {
            throw ValidationException::withMessages([
                'proof_photo' => 'Foto bukti serah terima wajib diunggah untuk status paket sudah sampai ke rumah penerima.',
            ]);
        }
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
}
