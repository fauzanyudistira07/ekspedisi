@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card mb-3"><div class="card-body">
<h4 class="card-title">Manifest {{ $manifest->manifest_number }}</h4>
<div class="row">
<div class="col-md-6">
<p><strong>Tipe:</strong> {{ \App\Models\ShipmentManifest::typeLabel($manifest->manifest_type) }}</p>
<p><strong>Status:</strong> {{ \App\Models\ShipmentManifest::statusLabel($manifest->status) }}</p>
<p><strong>Branch:</strong> {{ $manifest->branch->name ?? '-' }}</p>
<p><strong>Kurir:</strong> {{ $manifest->courier->name ?? '-' }}</p>
</div>
<div class="col-md-6">
<p><strong>Kendaraan:</strong> {{ $manifest->vehicle->plate_number ?? '-' }}</p>
<p><strong>Departed At:</strong> {{ $manifest->departed_at?->format('Y-m-d H:i') ?: '-' }}</p>
<p><strong>Arrived At:</strong> {{ $manifest->arrived_at?->format('Y-m-d H:i') ?: '-' }}</p>
<p><strong>Notes:</strong> {{ $manifest->notes ?: '-' }}</p>
</div>
</div>
<a href="{{ route('manifests.index') }}" class="btn btn-secondary">Kembali</a>
</div></div>

<div class="card mb-3"><div class="card-body"><h5 class="mb-3">Shipment dalam Manifest</h5>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Resi</th><th>Rute</th><th>Kurir</th><th>Status</th></tr></thead><tbody>
@forelse ($manifest->shipments as $shipment)
<tr>
<td>{{ $shipment->tracking_number }}</td>
<td>{{ $shipment->originBranch->city ?? '-' }} ke {{ $shipment->destinationBranch->city ?? '-' }}</td>
<td>{{ $shipment->courier->name ?? '-' }}</td>
<td>{{ \App\Models\Shipment::statusLabel($shipment->status) }}</td>
</tr>
@empty
<tr><td colspan="4" class="text-center">Belum ada shipment.</td></tr>
@endforelse
</tbody></table></div></div></div>

<div class="card"><div class="card-body"><h5 class="mb-3">Audit Log</h5>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Waktu</th><th>Aktor</th><th>Event</th><th>Ringkasan</th></tr></thead><tbody>
@forelse ($manifest->auditLogs as $log)
<tr><td>{{ $log->created_at?->format('Y-m-d H:i') }}</td><td>{{ $log->actor->name ?? 'System' }}</td><td>{{ $log->event }}</td><td>{{ $log->summary }}</td></tr>
@empty
<tr><td colspan="4" class="text-center">Belum ada audit log.</td></tr>
@endforelse
</tbody></table></div></div></div>
</div></div>
@endsection
