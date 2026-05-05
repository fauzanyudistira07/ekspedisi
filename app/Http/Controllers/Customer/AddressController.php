<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AddressController extends Controller
{
    public function index()
    {
        $customerId = Auth::guard('customer')->id();

        $addresses = CustomerAddress::where('customer_id', $customerId)
            ->latest()
            ->paginate(10);

        return view('customer.addresses.index', [
            'title' => 'Address Book',
            'addresses' => $addresses,
        ]);
    }

    public function create()
    {
        return view('customer.addresses.create', [
            'title' => 'Tambah Alamat Penerima',
        ]);
    }

    public function store(Request $request)
    {
        $customerId = Auth::guard('customer')->id();
        $validated = $this->validateAddress($request);

        $receiverCustomer = $this->findOrCreateReceiverCustomer($validated);

        if (!empty($validated['is_default'])) {
            CustomerAddress::where('customer_id', $customerId)->update(['is_default' => false]);
        }

        CustomerAddress::create([
            'customer_id' => $customerId,
            'receiver_customer_id' => $receiverCustomer->id,
            'label' => $validated['label'],
            'receiver_name' => $validated['receiver_name'],
            'receiver_email' => $validated['receiver_email'] ?? null,
            'receiver_phone' => $validated['receiver_phone'],
            'city' => $validated['city'],
            'address' => $validated['address'],
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ]);

        return redirect()->route('customer.addresses.index')->with('success', 'Alamat penerima berhasil disimpan.');
    }

    public function edit(CustomerAddress $customerAddress)
    {
        $this->ensureOwnership($customerAddress);

        return view('customer.addresses.edit', [
            'title' => 'Edit Alamat Penerima',
            'address' => $customerAddress,
        ]);
    }

    public function update(Request $request, CustomerAddress $customerAddress)
    {
        $this->ensureOwnership($customerAddress);
        $validated = $this->validateAddress($request);

        $receiverCustomer = $this->findOrCreateReceiverCustomer($validated);
        $customerAddress->receiverCustomer()->associate($receiverCustomer);

        if (!empty($validated['is_default'])) {
            CustomerAddress::where('customer_id', $customerAddress->customer_id)
                ->where('id', '!=', $customerAddress->id)
                ->update(['is_default' => false]);
        }

        $customerAddress->update([
            'label' => $validated['label'],
            'receiver_name' => $validated['receiver_name'],
            'receiver_email' => $validated['receiver_email'] ?? null,
            'receiver_phone' => $validated['receiver_phone'],
            'city' => $validated['city'],
            'address' => $validated['address'],
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ]);

        return redirect()->route('customer.addresses.index')->with('success', 'Alamat penerima berhasil diperbarui.');
    }

    public function destroy(CustomerAddress $customerAddress)
    {
        $this->ensureOwnership($customerAddress);
        $customerAddress->delete();

        return redirect()->route('customer.addresses.index')->with('success', 'Alamat penerima berhasil dihapus.');
    }

    private function ensureOwnership(CustomerAddress $address): void
    {
        if ($address->customer_id !== Auth::guard('customer')->id()) {
            abort(403, 'Anda tidak memiliki akses ke alamat ini.');
        }
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:80',
            'receiver_name' => 'required|string|max:80',
            'receiver_email' => 'nullable|email|max:255',
            'receiver_phone' => 'required|string|max:20',
            'city' => 'required|string|max:120',
            'address' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);
    }

    private function findOrCreateReceiverCustomer(array $addressData): Customer
    {
        $receiverEmail = $addressData['receiver_email'] ?? null;

        if ($receiverEmail) {
            $existing = Customer::where('email', $receiverEmail)->first();
            if ($existing) {
                $existing->update([
                    'name' => $addressData['receiver_name'],
                    'address' => $addressData['address'],
                    'city' => $addressData['city'],
                    'phone' => $addressData['receiver_phone'],
                ]);

                return $existing;
            }
        }

        $generatedEmail = $receiverEmail ?: 'receiver+' . Str::lower(Str::random(10)) . '@ekspedisi.local';

        return Customer::create([
            'name' => $addressData['receiver_name'],
            'email' => $generatedEmail,
            'password' => Hash::make(Str::random(16)),
            'address' => $addressData['address'],
            'city' => $addressData['city'],
            'phone' => $addressData['receiver_phone'],
        ]);
    }
}
