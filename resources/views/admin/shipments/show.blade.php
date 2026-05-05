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
<p><strong>Status:</strong> {{ strtoupper($shipment->status) }}</p>
</div>
<div class="col-md-6">
<p><strong>Origin:</strong> {{ $shipment->originBranch->name ?? '-' }}</p>
<p><strong>Destination:</strong> {{ $shipment->destinationBranch->name ?? '-' }}</p>
<p><strong>Total Weight:</strong> {{ $shipment->total_weight }}</p>
<p><strong>Total Price:</strong> {{ number_format($shipment->total_price, 2) }}</p>
</div>
</div>
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
<thead><tr><th>Tracked At</th><th>Location</th><th>Status</th><th>Description</th></tr></thead><tbody>
@forelse ($shipment->trackings as $tracking)
<tr><td>{{ $tracking->tracked_at?->format('Y-m-d H:i') }}</td><td>{{ $tracking->location }}</td><td>{{ strtoupper($tracking->status) }}</td><td>{{ $tracking->description }}</td></tr>
@empty
<tr><td colspan="4" class="text-center">Tidak ada tracking.</td></tr>
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
