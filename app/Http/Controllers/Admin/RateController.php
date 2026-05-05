<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Rate;
use Illuminate\Http\Request;

class RateController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('rates', 'read');

        return view('admin.rates.index', [
            'title' => 'Rates',
            'rates' => Rate::latest()->paginate(10),
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('rates', 'create');

        return view('admin.rates.create', [
            'title' => 'Create Rate',
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('rates', 'create');

        $validated = $request->validate([
            'origin_city' => 'required|string|max:255',
            'destination_city' => 'required|string|max:255',
            'price_per_kg' => 'required|numeric|min:0',
            'estimated_days' => 'required|integer|min:1',
        ]);

        Rate::create($validated);

        return redirect()->route('rates.index')->with('success', 'Rate berhasil ditambahkan.');
    }

    public function edit(Rate $rate)
    {
        $this->ensureTablePermission('rates', 'update');

        return view('admin.rates.edit', [
            'title' => 'Edit Rate',
            'rate' => $rate,
        ]);
    }

    public function update(Request $request, Rate $rate)
    {
        $this->ensureTablePermission('rates', 'update');

        $validated = $request->validate([
            'origin_city' => 'required|string|max:255',
            'destination_city' => 'required|string|max:255',
            'price_per_kg' => 'required|numeric|min:0',
            'estimated_days' => 'required|integer|min:1',
        ]);

        $rate->update($validated);

        return redirect()->route('rates.index')->with('success', 'Rate berhasil diperbarui.');
    }

    public function destroy(Rate $rate)
    {
        $this->ensureTablePermission('rates', 'delete');

        $rate->delete();

        return redirect()->route('rates.index')->with('success', 'Rate berhasil dihapus.');
    }
}
