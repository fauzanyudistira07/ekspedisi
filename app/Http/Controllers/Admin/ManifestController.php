<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Models\ShipmentManifest;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManifestController extends Controller
{
    public function index()
    {
        $this->ensureAccess();

        $status = request('status');
        $type = request('type');

        $manifests = ShipmentManifest::with(['branch', 'vehicle', 'courier'])
            ->withCount('shipments')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($type, fn ($query) => $query->where('manifest_type', $type))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.manifests.index', [
            'title' => 'Manifests',
            'manifests' => $manifests,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
            'statuses' => ShipmentManifest::statuses(),
            'types' => ShipmentManifest::types(),
        ]);
    }

    public function create()
    {
        $this->ensureAccess();

        return view('admin.manifests.create', [
            'title' => 'Create Manifest',
            'branches' => Branch::orderBy('name')->get(),
            'vehicles' => Vehicle::with('courier')->orderBy('plate_number')->get(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
            'shipments' => Shipment::with(['originBranch', 'destinationBranch', 'courier'])
                ->whereIn('status', Shipment::manifestEligibleStatuses())
                ->whereHas('payment', fn ($query) => $query->where('payment_status', Payment::STATUS_PAID))
                ->orderBy('tracking_number')
                ->get(),
            'statuses' => ShipmentManifest::statuses(),
            'types' => ShipmentManifest::types(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAccess();

        $validated = $request->validate([
            'manifest_number' => 'nullable|string|max:255|unique:shipment_manifests,manifest_number',
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'courier_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_COURIER)),
            ],
            'manifest_type' => ['required', Rule::in(ShipmentManifest::types())],
            'status' => ['required', Rule::in(ShipmentManifest::statuses())],
            'departed_at' => 'nullable|date',
            'arrived_at' => 'nullable|date|after_or_equal:departed_at',
            'notes' => 'nullable|string',
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'exists:shipments,id',
        ]);

        if (empty($validated['manifest_number'])) {
            $validated['manifest_number'] = 'MAN-' . now()->format('YmdHis');
        }
        $this->ensurePaidShipmentIds($validated['shipment_ids']);

        DB::transaction(function () use ($validated): void {
            $manifest = ShipmentManifest::create(collect($validated)->except('shipment_ids')->all());
            $manifest->shipments()->sync($validated['shipment_ids']);

            AuditLogger::log(
                $manifest,
                'manifest.created',
                'Manifest ' . $manifest->manifest_number . ' dibuat dengan ' . count($validated['shipment_ids']) . ' shipment.',
                null,
                [
                    'manifest_type' => $manifest->manifest_type,
                    'status' => $manifest->status,
                    'shipment_ids' => $validated['shipment_ids'],
                ],
            );
        });

        return redirect()->route('manifests.index')->with('success', 'Manifest berhasil dibuat.');
    }

    public function show(ShipmentManifest $manifest)
    {
        $this->ensureAccess();

        return view('admin.manifests.show', [
            'title' => 'Manifest Detail',
            'manifest' => $manifest->load([
                'branch',
                'vehicle.courier',
                'courier',
                'shipments' => fn ($query) => $query->with(['originBranch', 'destinationBranch', 'courier'])->orderBy('tracking_number'),
                'auditLogs.actor',
            ]),
            'checkpointStatuses' => ShipmentManifest::checkpointStatuses(),
        ]);
    }

    public function edit(ShipmentManifest $manifest)
    {
        $this->ensureAccess();

        return view('admin.manifests.edit', [
            'title' => 'Edit Manifest',
            'manifest' => $manifest,
            'branches' => Branch::orderBy('name')->get(),
            'vehicles' => Vehicle::with('courier')->orderBy('plate_number')->get(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
            'shipments' => Shipment::with(['originBranch', 'destinationBranch', 'courier'])
                ->where(function ($query) use ($manifest) {
                    $query->where(function ($eligibleQuery) {
                        $eligibleQuery
                            ->whereIn('status', Shipment::manifestEligibleStatuses())
                            ->whereHas('payment', fn ($paymentQuery) => $paymentQuery->where('payment_status', Payment::STATUS_PAID));
                    })->orWhereIn('id', $manifest->shipments()->pluck('shipments.id'));
                })
                ->orderBy('tracking_number')
                ->get(),
            'statuses' => ShipmentManifest::statuses(),
            'types' => ShipmentManifest::types(),
        ]);
    }

    public function update(Request $request, ShipmentManifest $manifest)
    {
        $this->ensureAccess();

        $validated = $request->validate([
            'manifest_number' => 'required|string|max:255|unique:shipment_manifests,manifest_number,' . $manifest->id,
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'courier_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_COURIER)),
            ],
            'manifest_type' => ['required', Rule::in(ShipmentManifest::types())],
            'status' => ['required', Rule::in(ShipmentManifest::statuses())],
            'departed_at' => 'nullable|date',
            'arrived_at' => 'nullable|date|after_or_equal:departed_at',
            'notes' => 'nullable|string',
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'exists:shipments,id',
        ]);
        $this->ensurePaidShipmentIds($validated['shipment_ids']);

        DB::transaction(function () use ($manifest, $validated): void {
            $oldValues = $manifest->only(['manifest_type', 'status', 'branch_id', 'vehicle_id', 'courier_id']);
            $manifest->update(collect($validated)->except('shipment_ids')->all());
            $manifest->shipments()->sync($validated['shipment_ids']);

            AuditLogger::log(
                $manifest,
                'manifest.updated',
                'Manifest ' . $manifest->manifest_number . ' diperbarui.',
                $oldValues,
                [
                    'manifest_type' => $manifest->manifest_type,
                    'status' => $manifest->status,
                    'branch_id' => $manifest->branch_id,
                    'vehicle_id' => $manifest->vehicle_id,
                    'courier_id' => $manifest->courier_id,
                    'shipment_ids' => $validated['shipment_ids'],
                ],
            );
        });

        return redirect()->route('manifests.show', $manifest)->with('success', 'Manifest berhasil diperbarui.');
    }

    public function checkpointUpdate(Request $request, ShipmentManifest $manifest, Shipment $shipment)
    {
        $this->ensureAccess();
        $this->ensurePaidShipmentIds([$shipment->id]);

        if (!$manifest->shipments()->where('shipments.id', $shipment->id)->exists()) {
            abort(404, 'Shipment tidak ada di manifest ini.');
        }

        $validated = $request->validate([
            'checkpoint_status' => ['required', Rule::in(ShipmentManifest::checkpointStatuses())],
            'checkpoint_notes' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($manifest, $shipment, $validated): void {
            $pivotData = [
                'checkpoint_status' => $validated['checkpoint_status'],
                'checkpoint_notes' => $validated['checkpoint_notes'] ?? null,
            ];

            if (in_array($validated['checkpoint_status'], ['loaded', 'departed'], true)) {
                $pivotData['loaded_at'] = now();
            }

            if (in_array($validated['checkpoint_status'], ['arrived', 'unloaded'], true)) {
                $pivotData['unloaded_at'] = now();
            }

            $manifest->shipments()->updateExistingPivot($shipment->id, $pivotData);

            $trackingStatus = $this->resolveTrackingStatusFromManifest($manifest, $validated['checkpoint_status']);
            if ($trackingStatus) {
                ShipmentTracking::create([
                    'shipment_id' => $shipment->id,
                    'branch_id' => $manifest->branch_id,
                    'location' => $validated['location'] ?: ($manifest->branch->name ?? 'Manifest ' . $manifest->manifest_number),
                    'description' => $validated['checkpoint_notes'] ?: ('Update manifest ' . $manifest->manifest_number),
                    'checkpoint_type' => 'manifest_' . $manifest->manifest_type,
                    'status' => $trackingStatus,
                    'tracked_at' => now(),
                ]);

                $shipment->update([
                    'status' => $trackingStatus,
                    'exception_code' => in_array($trackingStatus, [
                        Shipment::STATUS_EXCEPTION_HOLD,
                        Shipment::STATUS_FAILED_DELIVERY,
                        Shipment::STATUS_RETURNED_TO_SENDER,
                    ], true) ? $trackingStatus : null,
                    'exception_notes' => in_array($trackingStatus, [
                        Shipment::STATUS_EXCEPTION_HOLD,
                        Shipment::STATUS_FAILED_DELIVERY,
                        Shipment::STATUS_RETURNED_TO_SENDER,
                    ], true) ? ($validated['checkpoint_notes'] ?? null) : null,
                    'last_exception_at' => in_array($trackingStatus, [
                        Shipment::STATUS_EXCEPTION_HOLD,
                        Shipment::STATUS_FAILED_DELIVERY,
                        Shipment::STATUS_RETURNED_TO_SENDER,
                    ], true) ? now() : $shipment->last_exception_at,
                ]);
            }

            AuditLogger::log(
                $manifest,
                'manifest.checkpoint_updated',
                'Checkpoint shipment ' . $shipment->tracking_number . ' di manifest ' . $manifest->manifest_number . ' diubah menjadi ' . ShipmentManifest::checkpointStatusLabel($validated['checkpoint_status']) . '.',
                null,
                [
                    'shipment_id' => $shipment->id,
                    'checkpoint_status' => $validated['checkpoint_status'],
                    'checkpoint_notes' => $validated['checkpoint_notes'] ?? null,
                ],
            );
        });

        return redirect()->route('manifests.show', $manifest)->with('success', 'Checkpoint manifest berhasil diperbarui.');
    }

    private function ensureAccess(): void
    {
        $role = Auth::user()?->role;

        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_MANAGER], true)) {
            abort(403, 'Anda tidak memiliki akses ke manifest.');
        }
    }

    private function resolveTrackingStatusFromManifest(ShipmentManifest $manifest, string $checkpointStatus): ?string
    {
        if ($checkpointStatus === 'exception_hold') {
            return ShipmentTracking::STATUS_EXCEPTION_HOLD;
        }

        return match ($manifest->manifest_type) {
            ShipmentManifest::TYPE_PICKUP => in_array($checkpointStatus, ['loaded', 'departed'], true)
                ? ShipmentTracking::STATUS_PICKED_UP
                : null,
            ShipmentManifest::TYPE_LINEHAUL => in_array($checkpointStatus, ['loaded', 'departed', 'arrived'], true)
                ? ShipmentTracking::STATUS_IN_TRANSIT
                : null,
            ShipmentManifest::TYPE_ARRIVAL => in_array($checkpointStatus, ['arrived', 'unloaded'], true)
                ? ShipmentTracking::STATUS_ARRIVED_AT_BRANCH
                : null,
            ShipmentManifest::TYPE_DELIVERY => in_array($checkpointStatus, ['loaded', 'departed'], true)
                ? ShipmentTracking::STATUS_OUT_FOR_DELIVERY
                : null,
            default => null,
        };
    }

    private function ensurePaidShipmentIds(array $shipmentIds): void
    {
        $shipmentIds = array_values(array_unique(array_map('intval', $shipmentIds)));

        $paidShipmentIds = Shipment::query()
            ->whereIn('id', $shipmentIds)
            ->whereHas('payment', fn ($query) => $query->where('payment_status', Payment::STATUS_PAID))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $unpaidIds = array_values(array_diff($shipmentIds, $paidShipmentIds));
        if (!empty($unpaidIds)) {
            throw ValidationException::withMessages([
                'shipment_ids' => 'Hanya shipment dengan pembayaran lunas yang boleh dimasukkan ke manifest.',
            ]);
        }
    }
}
