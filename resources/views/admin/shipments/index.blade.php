@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="card-title mb-0">Shipments</h4>
@if (in_array('create', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipments', []), true))
<a href="{{ route('shipments.create') }}" class="btn btn-primary">Tambah Shipment</a>
@endif
</div>
<form method="GET" action="{{ route('shipments.index') }}" class="mb-3">
<div class="row">
<div class="col-md-4 mb-2"><input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari resi / pengirim / penerima"></div>
<div class="col-md-3 mb-2"><select name="status" class="form-control"><option value="">Semua Status</option>@foreach($statuses as $status)<option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ \App\Models\Shipment::statusLabel($status) }}</option>@endforeach</select></div>
<div class="col-md-3 mb-2"><select name="courier_id" class="form-control"><option value="">Semua Kurir</option>@foreach($couriers as $courier)<option value="{{ $courier->id }}" {{ (string)($filters['courier_id'] ?? '') === (string)$courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>@endforeach</select></div>
<div class="col-md-2 mb-2 d-flex" style="gap:8px;"><button type="submit" class="btn btn-outline-light btn-block">Filter</button><a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary btn-block">Reset</a></div>
</div>
</form>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Tracking</th><th>Sender</th><th>Receiver</th><th>Courier</th><th>Status</th><th>Total</th><th>Tanggal</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($shipments as $shipment)
<tr>
<td>{{ $shipment->tracking_number }}</td>
<td>{{ $shipment->sender->name ?? '-' }}</td>
<td>{{ $shipment->receiver->name ?? '-' }}</td>
<td>{{ $shipment->courier->name ?? '-' }}</td>
<td>{{ \App\Models\Shipment::statusLabel($shipment->status) }}</td>
<td>{{ number_format($shipment->total_price, 2) }}</td>
<td>{{ $shipment->shipment_date?->format('Y-m-d') }}</td>
<td>
<a href="{{ route('shipments.show', $shipment) }}" class="btn btn-sm btn-info">Detail</a>
@if (in_array('update', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipments', []), true))
<a href="{{ route('shipments.edit', $shipment) }}" class="btn btn-sm btn-warning">Edit</a>
@endif
@if (in_array('delete', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipments', []), true))
<form method="POST" action="{{ route('shipments.destroy', $shipment) }}" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button></form>
@endif
</td>
</tr>
@empty
<tr><td colspan="8" class="text-center">Belum ada data.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $shipments->links() }}</div>
</div></div>
</div></div>
@endsection
