@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="card-title mb-0">Shipment Trackings</h4>
@if (in_array('create', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_trackings', []), true))
<a href="{{ route('shipment-trackings.create') }}" class="btn btn-primary">Tambah Tracking</a>
@endif
</div>
<form method="GET" action="{{ route('shipment-trackings.index') }}" class="mb-3">
<div class="row">
<div class="col-md-5 mb-2"><input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari resi / lokasi"></div>
<div class="col-md-4 mb-2"><select name="status" class="form-control"><option value="">Semua Status</option>@foreach($statuses as $status)<option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ \App\Models\ShipmentTracking::statusLabel($status) }}</option>@endforeach</select></div>
<div class="col-md-3 mb-2 d-flex" style="gap:8px;"><button type="submit" class="btn btn-outline-light btn-block">Filter</button><a href="{{ route('shipment-trackings.index') }}" class="btn btn-outline-secondary btn-block">Reset</a></div>
</div>
</form>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Shipment</th><th>Location</th><th>Status</th><th>Tracked At</th><th>Aksi</th></tr></thead><tbody>
@forelse ($trackings as $tracking)
<tr>
<td>{{ $tracking->shipment->tracking_number ?? '-' }}</td>
<td>{{ $tracking->location }}</td>
<td>{{ \App\Models\ShipmentTracking::statusLabel($tracking->status) }}</td>
<td>{{ $tracking->tracked_at?->format('Y-m-d H:i') }}</td>
<td>
@if (in_array('update', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_trackings', []), true))
<a href="{{ route('shipment-trackings.edit', $tracking) }}" class="btn btn-sm btn-warning">Edit</a>
@endif
@if (in_array('delete', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_trackings', []), true))
<form method="POST" action="{{ route('shipment-trackings.destroy', $tracking) }}" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button></form>
@endif
</td>
</tr>
@empty
<tr><td colspan="5" class="text-center">Belum ada data.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $trackings->links() }}</div>
</div></div>
</div></div>
@endsection
