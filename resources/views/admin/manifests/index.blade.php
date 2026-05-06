@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card mb-4 border-0 shadow-sm page-hero page-hero--blue"><div class="card-body py-4">
<div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:16px;">
<div class="page-hero-copy">
<div class="text-uppercase small mb-2 page-hero-eyebrow">Operational Manifest</div>
<h4 class="mb-2 page-hero-title">Batch pengiriman per pickup, linehaul, arrival, dan delivery</h4>
<p class="mb-0 page-hero-text">Manifest dipakai untuk mengelompokkan shipment per kendaraan, cabang, atau perjalanan.</p>
</div>
<a href="{{ route('manifests.create') }}" class="btn btn-warning text-dark">Buat Manifest</a>
</div></div></div>

<div class="card border-0 shadow-sm"><div class="card-body">
<form method="GET" action="{{ route('manifests.index') }}" class="mb-4">
<div class="row">
<div class="col-md-4 mb-2"><select name="type" class="form-control"><option value="">Semua Tipe</option>@foreach($types as $type)<option value="{{ $type }}" {{ ($filters['type'] ?? '') === $type ? 'selected' : '' }}>{{ \App\Models\ShipmentManifest::typeLabel($type) }}</option>@endforeach</select></div>
<div class="col-md-4 mb-2"><select name="status" class="form-control"><option value="">Semua Status</option>@foreach($statuses as $status)<option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ \App\Models\ShipmentManifest::statusLabel($status) }}</option>@endforeach</select></div>
<div class="col-md-4 mb-2 d-flex" style="gap:8px;"><button type="submit" class="btn btn-outline-light btn-block">Filter</button><a href="{{ route('manifests.index') }}" class="btn btn-outline-secondary btn-block">Reset</a></div>
</div>
</form>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Manifest</th><th>Tipe</th><th>Branch</th><th>Kendaraan</th><th>Kurir</th><th>Shipment</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse ($manifests as $manifest)
<tr>
<td>{{ $manifest->manifest_number }}</td>
<td>{{ \App\Models\ShipmentManifest::typeLabel($manifest->manifest_type) }}</td>
<td>{{ $manifest->branch->name ?? '-' }}</td>
<td>{{ $manifest->vehicle->plate_number ?? '-' }}</td>
<td>{{ $manifest->courier->name ?? '-' }}</td>
<td>{{ number_format($manifest->shipments_count) }}</td>
<td>{{ \App\Models\ShipmentManifest::statusLabel($manifest->status) }}</td>
<td><a href="{{ route('manifests.show', $manifest) }}" class="btn btn-sm btn-info">Detail</a></td>
</tr>
@empty
<tr><td colspan="8" class="text-center">Belum ada manifest.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $manifests->links() }}</div>
</div></div>
</div></div>
@endsection
