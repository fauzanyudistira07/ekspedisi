@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="card-title mb-0">Users</h4><a href="{{ route('users.create') }}" class="btn btn-primary">Tambah User</a></div>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Branch</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($users as $user)
<tr>
<td>{{ $loop->iteration + ($users->firstItem() ?? 0) - 1 }}</td>
<td>{{ $user->name }}</td>
<td>{{ $user->email }}</td>
<td>{{ strtoupper($user->role) }}</td>
<td>{{ $user->branch->name ?? '-' }}</td>
<td><a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning">Edit</a>
<form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button></form></td>
</tr>
@empty
<tr><td colspan="6" class="text-center">Belum ada data.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $users->links() }}</div>
</div></div>
</div></div>
@endsection
