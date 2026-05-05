@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="card-title mb-0">Shipment Items</h4>
@if (in_array('create', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_items', []), true))
<a href="{{ route('shipment-items.create') }}" class="btn btn-primary">Tambah Item</a>
@endif
</div>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Shipment</th><th>Item</th><th>Qty</th><th>Weight</th><th>Aksi</th></tr></thead><tbody>
@forelse ($items as $item)
<tr>
<td>{{ $item->shipment->tracking_number ?? '-' }}</td>
<td>{{ $item->item_name }}</td>
<td>{{ $item->quantity }}</td>
<td>{{ $item->weight }}</td>
<td>
@if (in_array('update', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_items', []), true))
<a href="{{ route('shipment-items.edit', $item) }}" class="btn btn-sm btn-warning">Edit</a>
@endif
@if (in_array('delete', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_items', []), true))
<form method="POST" action="{{ route('shipment-items.destroy', $item) }}" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button></form>
@endif
</td>
</tr>
@empty
<tr><td colspan="5" class="text-center">Belum ada data.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $items->links() }}</div>
</div></div>
</div></div>
@endsection
