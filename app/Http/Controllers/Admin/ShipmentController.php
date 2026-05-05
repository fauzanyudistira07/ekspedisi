<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('shipments', 'read');

        $user = Auth::user();
        $search = request('q');
        $status = request('status');
        $courierId = request('courier_id');

        $shipments = Shipment::with(['sender', 'receiver', 'courier', 'originBranch', 'destinationBranch', 'rate'])
            ->when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
                $query->where('courier_id', $user->id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('tracking_number', 'like', '%' . $search . '%')
                        ->orWhereHas('sender', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('receiver', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($courierId && $user->role !== User::ROLE_COURIER, fn ($query) => $query->where('courier_id', $courierId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.shipments.index', [
            'title' => 'Shipments',
            'shipments' => $shipments,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'courier_id' => $courierId,
            ],
            'statuses' => Shipment::statuses(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('shipments', 'create');

        return view('admin.shipments.create', [
            'title' => 'Create Shipment',
            'customers' => Customer::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
            'rates' => Rate::orderBy('origin_city')->orderBy('destination_city')->get(),
            'statuses' => Shipment::statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('shipments', 'create');

        $validated = $request->validate([
            'tracking_number' => 'required|string|max:255|unique:shipments,tracking_number',
            'sender_id' => 'required|exists:customers,id',
            'receiver_id' => 'required|exists:customers,id',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'courier_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_COURIER)),
            ],
            'rate_id' => 'required|exists:rates,id',
            'total_weight' => 'required|numeric|min:0.01',
            'total_price' => 'required|numeric|min:0',
            'status' => ['required', Rule::in(Shipment::statuses())],
            'shipment_date' => 'required|date',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $photoName = time() . '_' . $request->file('photo')->getClientOriginalName();
            $request->file('photo')->move(public_path('uploads/shipments'), $photoName);
            $validated['photo'] = $photoName;
        }

        Shipment::create($validated);

        return redirect()->route('shipments.index')->with('success', 'Shipment berhasil ditambahkan.');
    }

    public function show(Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'read');
        $this->ensureShipmentVisibility($shipment);

        return view('admin.shipments.show', [
            'title' => 'Shipment Detail',
            'shipment' => $shipment->load(['sender', 'receiver', 'courier', 'originBranch', 'destinationBranch', 'rate', 'items', 'trackings', 'payment']),
        ]);
    }

    public function edit(Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'update');
        $this->ensureShipmentVisibility($shipment);

        return view('admin.shipments.edit', [
            'title' => 'Edit Shipment',
            'shipment' => $shipment,
            'customers' => Customer::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
            'rates' => Rate::orderBy('origin_city')->orderBy('destination_city')->get(),
            'statuses' => Shipment::statuses(),
        ]);
    }

    public function update(Request $request, Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'update');
        $this->ensureShipmentVisibility($shipment);

        $validated = $request->validate([
            'tracking_number' => 'required|string|max:255|unique:shipments,tracking_number,' . $shipment->id,
            'sender_id' => 'required|exists:customers,id',
            'receiver_id' => 'required|exists:customers,id',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'courier_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_COURIER)),
            ],
            'rate_id' => 'required|exists:rates,id',
            'total_weight' => 'required|numeric|min:0.01',
            'total_price' => 'required|numeric|min:0',
            'status' => ['required', Rule::in(Shipment::statuses())],
            'shipment_date' => 'required|date',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $photoName = time() . '_' . $request->file('photo')->getClientOriginalName();
            $request->file('photo')->move(public_path('uploads/shipments'), $photoName);
            $validated['photo'] = $photoName;
        }

        $shipment->update($validated);

        return redirect()->route('shipments.index')->with('success', 'Shipment berhasil diperbarui.');
    }

    public function destroy(Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'delete');

        $shipment->delete();

        return redirect()->route('shipments.index')->with('success', 'Shipment berhasil dihapus.');
    }

    private function ensureShipmentVisibility(Shipment $shipment): void
    {
        $user = Auth::user();

        if ($user && $user->role === User::ROLE_COURIER && $shipment->courier_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke shipment ini.');
        }
    }
}
