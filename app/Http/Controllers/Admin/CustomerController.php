<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index(Request $request)
    {
        $this->ensureTablePermission('customers', 'read');

        $search = $request->input('q');
        $city = $request->input('city');

        $customers = Customer::query()
            ->withCount(['sentShipments', 'receivedShipments'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->when($city, fn ($query) => $query->where('city', $city))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.index', [
            'title' => 'Customers',
            'customers' => $customers,
            'cities' => Customer::query()
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->distinct()
                ->orderBy('city')
                ->pluck('city'),
            'filters' => [
                'q' => $search,
                'city' => $city,
            ],
        ]);
    }

    public function edit(Customer $customer)
    {
        $this->ensureTablePermission('customers', 'update');

        return view('admin.customers.edit', [
            'title' => 'Edit Customer',
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $this->ensureTablePermission('customers', 'update');

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:customers,email,' . $customer->id,
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
            'password' => 'nullable|string|min:8|confirmed',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->hasFile('photo')) {
            $validated['photo'] = Uploads::storePublic($request->file('photo'), 'customers');
        }

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Data customer berhasil diperbarui.');
    }
}
