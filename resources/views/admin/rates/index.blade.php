@extends('be.master')

@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card"><div class="card-body">
<div class="cp-info-box mb-3">Tarif ini dipakai untuk menghitung ongkir otomatis pada form shipment customer dan admin berdasarkan rute cabang asal ke cabang tujuan.</div>
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="card-title mb-0">Rates</h4>
<a href="{{ route('rates.create') }}" class="btn btn-primary">Tambah Rate</a>
</div>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>#</th><th>Origin</th><th>Destination</th><th>Price/Kg</th><th>Est. Days</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($rates as $rate)
<tr>
<td>{{ $loop->iteration + ($rates->firstItem() ?? 0) - 1 }}</td>
<td>{{ $rate->origin_city }}</td>
<td>{{ $rate->destination_city }}</td>
<td>{{ number_format($rate->price_per_kg, 2) }}</td>
<td>{{ $rate->estimated_days }}</td>
<td>
<a href="{{ route('rates.edit', $rate) }}" class="btn btn-sm btn-warning">Edit</a>
<form method="POST" action="{{ route('rates.destroy', $rate) }}" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button></form>
</td>
</tr>
@empty
<tr><td colspan="6" class="text-center">Belum ada data.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $rates->links() }}</div>
</div></div>
</div></div>
@endsection
