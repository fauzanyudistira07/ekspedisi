<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\Uploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('customer.profile.edit', [
            'title' => 'Profile',
            'customer' => Auth::guard('customer')->user(),
        ]);
    }

    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:customers,email,' . $customer->id,
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
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

        return redirect()->route('customer.profile.edit')->with('success', 'Profile berhasil diperbarui.');
    }
}
