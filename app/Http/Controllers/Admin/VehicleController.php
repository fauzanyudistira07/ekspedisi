<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('vehicles', 'read');

        return view('admin.vehicles.index', [
            'title' => 'Vehicles',
            'vehicles' => Vehicle::with('courier.branch')->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('vehicles', 'create');

        return view('admin.vehicles.create', [
            'title' => 'Create Vehicle',
            'couriers' => User::with('branch')->where('role', User::ROLE_COURIER)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('vehicles', 'create');

        $validated = $request->validate([
            'plate_number' => 'required|string|max:255|unique:vehicles,plate_number',
            'type' => 'required|in:motor,mobil,truck',
            'courier_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_COURIER)),
            ],
        ]);

        Vehicle::create($validated);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle berhasil ditambahkan.');
    }

    public function edit(Vehicle $vehicle)
    {
        $this->ensureTablePermission('vehicles', 'update');

        return view('admin.vehicles.edit', [
            'title' => 'Edit Vehicle',
            'vehicle' => $vehicle,
            'couriers' => User::with('branch')->where('role', User::ROLE_COURIER)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->ensureTablePermission('vehicles', 'update');

        $validated = $request->validate([
            'plate_number' => 'required|string|max:255|unique:vehicles,plate_number,' . $vehicle->id,
            'type' => 'required|in:motor,mobil,truck',
            'courier_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_COURIER)),
            ],
        ]);

        $vehicle->update($validated);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle berhasil diperbarui.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->ensureTablePermission('vehicles', 'delete');

        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('success', 'Vehicle berhasil dihapus.');
    }
}
