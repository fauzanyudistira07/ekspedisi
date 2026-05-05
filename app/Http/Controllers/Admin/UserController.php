<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('users', 'read');

        return view('admin.users.index', [
            'title' => 'Users',
            'users' => User::with('branch')->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('users', 'create');

        return view('admin.users.create', [
            'title' => 'Create User',
            'roles' => User::internalRoles(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('users', 'create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::internalRoles())],
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->ensureTablePermission('users', 'update');

        return view('admin.users.edit', [
            'title' => 'Edit User',
            'user' => $user,
            'roles' => User::internalRoles(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureTablePermission('users', 'update');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::internalRoles())],
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->ensureTablePermission('users', 'delete');

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
