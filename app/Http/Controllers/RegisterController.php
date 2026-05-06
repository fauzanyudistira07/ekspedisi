<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard.index');
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('home.index');
        }

        return view('auth.register');
    }

    public function store(Request $request)
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard.index');
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('home.index');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:customers,email',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $photoName = null;

        if ($request->hasFile('photo')) {
            $photoName = Uploads::storePublic($request->file('photo'), 'customers');
        }

        Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'address' => $validated['address'],
            'city' => $validated['city'],
            'phone' => $validated['phone'],
            'photo' => $photoName,
        ]);

        return redirect()->route('login')->with('success', 'Register berhasil, silakan login.');
    }
}
