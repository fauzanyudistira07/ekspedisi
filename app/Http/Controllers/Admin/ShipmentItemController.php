<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Support\Uploads;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipmentItemController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('shipment_items', 'read');
        $user = Auth::user();

        $items = ShipmentItem::with('shipment')
            ->when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
                $query->whereHas('shipment', function ($subQuery) use ($user) {
                    $subQuery->where('courier_id', $user->id);
                });
            })
            ->latest()
            ->paginate(10);

        return view('admin.shipment_items.index', [
            'title' => 'Shipment Items',
            'items' => $items,
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('shipment_items', 'create');

        return view('admin.shipment_items.create', [
            'title' => 'Create Shipment Item',
            'shipments' => $this->visibleShipments(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('shipment_items', 'create');

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'weight' => 'required|numeric|min:0.01',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $this->ensureShipmentVisibility($shipment);

        if ($request->hasFile('photo')) {
            $validated['photo'] = Uploads::storePublic($request->file('photo'), 'shipment-items');
        }

        ShipmentItem::create($validated);
        $this->syncShipmentTotals($shipment);

        return redirect()->route('shipment-items.index')->with('success', 'Shipment item berhasil ditambahkan.');
    }

    public function edit(ShipmentItem $shipmentItem)
    {
        $this->ensureTablePermission('shipment_items', 'update');
        $this->ensureShipmentVisibility($shipmentItem->shipment);

        return view('admin.shipment_items.edit', [
            'title' => 'Edit Shipment Item',
            'shipmentItem' => $shipmentItem,
            'shipments' => $this->visibleShipments(),
        ]);
    }

    public function update(Request $request, ShipmentItem $shipmentItem)
    {
        $this->ensureTablePermission('shipment_items', 'update');
        $originalShipment = $shipmentItem->shipment;

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'weight' => 'required|numeric|min:0.01',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $this->ensureShipmentVisibility($shipment);

        if ($request->hasFile('photo')) {
            $validated['photo'] = Uploads::storePublic($request->file('photo'), 'shipment-items');
        }

        $shipmentItem->update($validated);
        if ($originalShipment->id !== $shipment->id) {
            $this->syncShipmentTotals($originalShipment->fresh(['rate', 'items']));
        }
        $this->syncShipmentTotals($shipment);

        return redirect()->route('shipment-items.index')->with('success', 'Shipment item berhasil diperbarui.');
    }

    public function destroy(ShipmentItem $shipmentItem)
    {
        $this->ensureTablePermission('shipment_items', 'delete');
        $this->ensureShipmentVisibility($shipmentItem->shipment);

        $shipment = $shipmentItem->shipment;
        $shipmentItem->delete();
        $this->syncShipmentTotals($shipment);

        return redirect()->route('shipment-items.index')->with('success', 'Shipment item berhasil dihapus.');
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

    private function syncShipmentTotals(Shipment $shipment): void
    {
        $shipment->loadMissing('rate', 'items');

        $totalWeight = $shipment->items->sum(function (ShipmentItem $item) {
            return (float) $item->weight * (int) $item->quantity;
        });

        $shipment->update([
            'total_weight' => round($totalWeight, 2),
            'total_price' => round($totalWeight * (float) $shipment->rate->price_per_kg, 2),
        ]);
    }
}
