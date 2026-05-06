@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card mb-3"><div class="card-body">
<h4 class="card-title">Detail Shipment {{ $shipment->tracking_number }}</h4>
<div class="row">
<div class="col-md-6">
<p><strong>Sender:</strong> {{ $shipment->sender->name ?? '-' }}</p>
<p><strong>Receiver:</strong> {{ $shipment->receiver->name ?? '-' }}</p>
<p><strong>Courier:</strong> {{ $shipment->courier->name ?? '-' }}</p>
<p><strong>Status:</strong> {{ \App\Models\Shipment::statusLabel($shipment->status) }}</p>
</div>
<div class="col-md-6">
<p><strong>Origin:</strong> {{ $shipment->originBranch->name ?? '-' }}</p>
<p><strong>Destination:</strong> {{ $shipment->destinationBranch->name ?? '-' }}</p>
<p><strong>Total Weight:</strong> {{ $shipment->total_weight }}</p>
<p><strong>Total Price:</strong> {{ number_format($shipment->total_price, 2) }}</p>
</div>
</div>
@if ($shipment->exception_code)
<div class="alert alert-warning mt-3 mb-0">
  <strong>Exception Aktif:</strong> {{ \App\Models\Shipment::statusLabel($shipment->exception_code) }}
  <div class="small mt-1">{{ $shipment->exception_notes ?: 'Belum ada catatan exception.' }}</div>
</div>
@endif
<a href="{{ route('shipments.index') }}" class="btn btn-secondary">Kembali</a>
</div></div>

<div class="card mb-3"><div class="card-body"><h5 class="mb-3">Items</h5>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Item</th><th>Qty</th><th>Weight</th></tr></thead><tbody>
@forelse ($shipment->items as $item)
<tr><td>{{ $item->item_name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->weight }}</td></tr>
@empty
<tr><td colspan="3" class="text-center">Tidak ada item.</td></tr>
@endforelse
</tbody></table></div></div></div>

<div class="card mb-3"><div class="card-body"><h5 class="mb-3">Trackings</h5>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Tracked At</th><th>Location</th><th>Status</th><th>Description</th><th>Checkpoint</th><th>POD</th></tr></thead><tbody>
@forelse ($shipment->trackings as $tracking)
<tr>
<td>{{ $tracking->tracked_at?->format('Y-m-d H:i') }}</td>
<td>{{ $tracking->location }}</td>
<td>{{ \App\Models\ShipmentTracking::statusLabel($tracking->status) }}</td>
<td>
  <div>{{ $tracking->description ?: '-' }}</div>
  @if ($tracking->received_by)
    <small class="text-info d-block">Diterima oleh: {{ $tracking->received_by }}{{ $tracking->receiver_relation ? ' (' . $tracking->receiver_relation . ')' : '' }}</small>
  @endif
</td>
<td>{{ $tracking->checkpoint_type ?: '-' }}</td>
<td>
  @if ($tracking->proof_photo)
    <a href="{{ asset('uploads/shipment-trackings/' . $tracking->proof_photo) }}" target="_blank" class="btn btn-sm btn-info">Lihat Bukti</a>
  @else
    <span class="text-muted">-</span>
  @endif
</td>
</tr>
@empty
<tr><td colspan="6" class="text-center">Tidak ada tracking.</td></tr>
@endforelse
</tbody></table></div></div></div>

<div class="card mb-3"><div class="card-body"><h5 class="mb-3">Manifest</h5>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Manifest</th><th>Tipe</th><th>Branch</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse ($shipment->manifests as $manifest)
<tr>
<td>{{ $manifest->manifest_number }}</td>
<td>{{ \App\Models\ShipmentManifest::typeLabel($manifest->manifest_type) }}</td>
<td>{{ $manifest->branch->name ?? '-' }}</td>
<td>{{ \App\Models\ShipmentManifest::statusLabel($manifest->status) }}</td>
<td><a href="{{ route('manifests.show', $manifest) }}" class="btn btn-sm btn-info">Detail</a></td>
</tr>
@empty
<tr><td colspan="5" class="text-center">Shipment ini belum masuk manifest.</td></tr>
@endforelse
</tbody></table></div></div></div>

<div class="card mb-3"><div class="card-body"><h5 class="mb-3">Audit Log</h5>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Waktu</th><th>Aktor</th><th>Event</th><th>Ringkasan</th></tr></thead><tbody>
@forelse ($shipment->auditLogs as $log)
<tr>
<td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
<td>{{ $log->actor->name ?? 'System' }}</td>
<td>{{ $log->event }}</td>
<td>{{ $log->summary }}</td>
</tr>
@empty
<tr><td colspan="4" class="text-center">Belum ada audit log.</td></tr>
@endforelse
</tbody></table></div></div></div>

<div class="card"><div class="card-body"><h5 class="mb-3">Payment</h5>
@if ($shipment->payment)
<p><strong>Amount:</strong> {{ number_format($shipment->payment->amount, 2) }}</p>
<p><strong>Method:</strong> {{ strtoupper($shipment->payment->payment_method) }}</p>
<p><strong>Status:</strong> {{ strtoupper($shipment->payment->payment_status) }}</p>
<p><strong>Date:</strong> {{ $shipment->payment->payment_date?->format('Y-m-d') }}</p>
@else
<p class="mb-0">Belum ada pembayaran.</p>
@endif
</div></div>

</div></div>
@endsection
