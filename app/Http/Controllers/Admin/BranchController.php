<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('branches', 'read');

        return view('admin.branches.index', [
            'title' => 'Branches',
            'branches' => Branch::latest()->paginate(10),
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('branches', 'create');

        return view('admin.branches.create', [
            'title' => 'Create Branch',
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('branches', 'create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:25',
        ]);

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch berhasil ditambahkan.');
    }

    public function edit(Branch $branch)
    {
        $this->ensureTablePermission('branches', 'update');

        return view('admin.branches.edit', [
            'title' => 'Edit Branch',
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->ensureTablePermission('branches', 'update');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:25',
        ]);

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        $this->ensureTablePermission('branches', 'delete');

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch berhasil dihapus.');
    }
}
