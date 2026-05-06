<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Shipment;
use App\Models\ShipmentManifest;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                'shipments.originBranch',
                'shipments.destinationBranch',
                'shipments.courier',
                'auditLogs.actor',
            ]),
        ]);
    }

    private function ensureAccess(): void
    {
        $role = Auth::user()?->role;

        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_MANAGER], true)) {
            abort(403, 'Anda tidak memiliki akses ke manifest.');
        }
    }
}
