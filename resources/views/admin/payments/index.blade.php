@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="card-title mb-0">Payments</h4>
<div class="d-flex" style="gap:8px;">
@if (in_array(Auth::user()->role, ['admin', 'manager', 'cashier'], true))
<a href="{{ route('payments.verification') }}" class="btn btn-outline-light">Verifikasi</a>
@endif
@if (in_array('create', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.payments', []), true))
<a href="{{ route('payments.create') }}" class="btn btn-primary">Tambah Payment</a>
@endif
</div>
</div>
<form method="GET" action="{{ route('payments.index') }}" class="mb-3">
<div class="row">
<div class="col-md-4 mb-2"><input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari resi / reference"></div>
<div class="col-md-3 mb-2"><select name="status" class="form-control"><option value="">Semua Status</option>@foreach($statuses as $status)<option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ \App\Models\Payment::statusLabel($status) }}</option>@endforeach</select></div>
<div class="col-md-3 mb-2"><select name="method" class="form-control"><option value="">Semua Metode</option>@foreach($methods as $method)<option value="{{ $method }}" {{ ($filters['method'] ?? '') === $method ? 'selected' : '' }}>{{ strtoupper($method) }}</option>@endforeach</select></div>
<div class="col-md-2 mb-2 d-flex" style="gap:8px;"><button type="submit" class="btn btn-outline-light btn-block">Filter</button><a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-block">Reset</a></div>
</div>
</form>
<div class="table-responsive"><table class="table table-dark table-striped">
<thead><tr><th>Shipment</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Date</th><th>Bukti</th><th>Aksi</th></tr></thead><tbody>
@forelse ($payments as $payment)
<tr>
<td>{{ $payment->shipment->tracking_number ?? '-' }}</td>
<td>{{ number_format($payment->amount, 2) }}</td>
<td>{{ strtoupper($payment->payment_method) }}</td>
<td>{{ $payment->reference_number ?? '-' }}</td>
<td>{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</td>
<td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
<td>
@if ($payment->proof_file)
<a href="{{ asset('uploads/payments/' . $payment->proof_file) }}" target="_blank" class="btn btn-sm btn-info">Lihat</a>
@else
-
@endif
</td>
<td>
@if (in_array('update', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.payments', []), true))
<a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-warning">Edit</a>
@endif
@if (in_array('delete', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.payments', []), true))
<form method="POST" action="{{ route('payments.destroy', $payment) }}" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button></form>
@endif
</td>
</tr>
@empty
<tr><td colspan="8" class="text-center">Belum ada data.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $payments->links() }}</div>
</div></div>
</div></div>
@endsection
