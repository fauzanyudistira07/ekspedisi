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
<div class="d-flex flex-wrap" style="gap:8px;">
<a href="{{ route('manifests.edit', $manifest) }}" class="btn btn-warning">Edit Manifest</a>
<a href="{{ route('manifests.index') }}" class="btn btn-secondary">Kembali</a>
</div>
</div></div>

<div class="card mb-3"><div class="card-body"><h5 class="mb-3">Shipment dalam Manifest</h5>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Resi</th><th>Rute</th><th>Kurir</th><th>Status</th><th>Checkpoint Manifest</th><th>Waktu</th><th>Update</th></tr></thead><tbody>
@forelse ($manifest->shipments as $shipment)
<tr>
<td>{{ $shipment->tracking_number }}</td>
<td>{{ $shipment->originBranch->city ?? '-' }} ke {{ $shipment->destinationBranch->city ?? '-' }}</td>
<td>{{ $shipment->courier->name ?? '-' }}</td>
<td>{{ \App\Models\Shipment::statusLabel($shipment->status) }}</td>
<td>
  <div>{{ \App\Models\ShipmentManifest::checkpointStatusLabel($shipment->pivot->checkpoint_status) ?: '-' }}</div>
  <small class="text-muted">{{ $shipment->pivot->checkpoint_notes ?: '-' }}</small>
</td>
<td>
  <div><small>Loaded: {{ $shipment->pivot->loaded_at ? \Illuminate\Support\Carbon::parse($shipment->pivot->loaded_at)->format('Y-m-d H:i') : '-' }}</small></div>
  <div><small>Unloaded: {{ $shipment->pivot->unloaded_at ? \Illuminate\Support\Carbon::parse($shipment->pivot->unloaded_at)->format('Y-m-d H:i') : '-' }}</small></div>
</td>
<td style="min-width:260px;">
  <form method="POST" action="{{ route('manifests.shipments.checkpoint', [$manifest, $shipment]) }}">
    @csrf
    @method('PATCH')
    <div class="mb-2">
      <select name="checkpoint_status" class="form-control form-control-sm" required>
        @foreach($checkpointStatuses as $checkpointStatus)
          <option value="{{ $checkpointStatus }}" {{ $shipment->pivot->checkpoint_status === $checkpointStatus ? 'selected' : '' }}>{{ \App\Models\ShipmentManifest::checkpointStatusLabel($checkpointStatus) }}</option>
        @endforeach
      </select>
    </div>
    <div class="mb-2">
      <input type="text" name="location" class="form-control form-control-sm" placeholder="Lokasi checkpoint">
    </div>
    <div class="mb-2">
      <input type="text" name="checkpoint_notes" class="form-control form-control-sm" value="{{ $shipment->pivot->checkpoint_notes }}" placeholder="Catatan checkpoint">
    </div>
    <button type="submit" class="btn btn-sm btn-primary btn-block">Update Checkpoint</button>
  </form>
</td>
</tr>
@empty
<tr><td colspan="7" class="text-center">Belum ada shipment.</td></tr>
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
