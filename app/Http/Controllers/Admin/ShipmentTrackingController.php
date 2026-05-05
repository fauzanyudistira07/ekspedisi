<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        return view('admin.shipment_trackings.index', [
            'title' => 'Shipment Trackings',
            'trackings' => $trackings,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
            'statuses' => ShipmentTracking::statuses(),
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('shipment_trackings', 'create');

        return view('admin.shipment_trackings.create', [
            'title' => 'Create Shipment Tracking',
            'shipments' => $this->visibleShipments(),
            'statuses' => ShipmentTracking::statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('shipment_trackings', 'create');

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(ShipmentTracking::statuses())],
            'tracked_at' => 'required|date',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $this->ensureShipmentVisibility($shipment);

        DB::transaction(function () use ($shipment, $validated): void {
            ShipmentTracking::create($validated);
            $shipment->update([
                'status' => $validated['status'],
            ]);
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
            'status' => ['required', Rule::in(ShipmentTracking::statuses())],
            'tracked_at' => 'required|date',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $this->ensureShipmentVisibility($shipment);

        DB::transaction(function () use ($shipment, $shipmentTracking, $validated): void {
            $shipmentTracking->update($validated);

            $latestStatus = ShipmentTracking::where('shipment_id', $shipment->id)
                ->orderByDesc('tracked_at')
                ->orderByDesc('id')
                ->value('status');

            if ($latestStatus) {
                $shipment->update([
                    'status' => $latestStatus,
                ]);
            }
        });

        return redirect()->route('shipment-trackings.index')->with('success', 'Tracking berhasil diperbarui.');
    }

    public function destroy(ShipmentTracking $shipmentTracking)
    {
        $this->ensureTablePermission('shipment_trackings', 'delete');
        $this->ensureShipmentVisibility($shipmentTracking->shipment);

        DB::transaction(function () use ($shipmentTracking): void {
            $shipmentId = $shipmentTracking->shipment_id;
            $shipmentTracking->delete();

            $latestStatus = ShipmentTracking::where('shipment_id', $shipmentId)
                ->orderByDesc('tracked_at')
                ->orderByDesc('id')
                ->value('status');

            Shipment::where('id', $shipmentId)->update([
                'status' => $latestStatus ?? Shipment::STATUS_PENDING,
            ]);
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
}
