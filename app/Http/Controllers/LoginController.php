<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function index()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard.index');
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('home.index');
        }

        return view('auth.login', [
            'title' => 'Login Akun',
        ]);
    }

    public function authenticate(Request $request)
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard.index');
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('home.index');
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)
            ->whereIn('role', User::internalRoles())
            ->first();
        if ($user && Hash::check($password, $user->password)) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard.index'));
        }

        $customer = Customer::where('email', $email)->first();
        if ($customer && Hash::check($password, $customer->password)) {
            Auth::guard('customer')->login($customer);
            $request->session()->regenerate();

            return redirect()->intended(route('home.index'));
        }

        return back()->withErrors([
            'email' => 'Email atau password tidak valid.',
        ])->withInput();
    }

    /**
     * Logout customer
     */
    public function logoutCustomer(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Logout admin
     */
    public function logoutAdmin(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
