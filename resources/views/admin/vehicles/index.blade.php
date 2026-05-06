@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card"><div class="card-body">
<div class="cp-info-box mb-3">Cabang kendaraan mengikuti cabang courier yang dipilih. Data ini dipakai admin untuk penugasan manifest dan operasional antar cabang.</div>
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="card-title mb-0">Vehicles</h4><a href="{{ route('vehicles.create') }}" class="btn btn-primary">Tambah Vehicle</a></div>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>#</th><th>Plate</th><th>Type</th><th>Courier</th><th>Branch</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($vehicles as $vehicle)
<tr>
<td>{{ $loop->iteration + ($vehicles->firstItem() ?? 0) - 1 }}</td>
<td>{{ $vehicle->plate_number }}</td>
<td>{{ $vehicle->type }}</td>
<td>{{ $vehicle->courier->name ?? '-' }}</td>
<td>{{ $vehicle->courier->branch->name ?? '-' }}</td>
<td><a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-warning">Edit</a>
<form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button></form></td>
</tr>
@empty
<tr><td colspan="6" class="text-center">Belum ada data.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $vehicles->links() }}</div>
</div></div>
</div></div>
@endsection
