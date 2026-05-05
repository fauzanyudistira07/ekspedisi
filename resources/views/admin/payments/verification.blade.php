@extends('be.master')

@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="card-title mb-0">Payment Verification Queue</h4>
          <a href="{{ route('payments.index') }}" class="btn btn-outline-light btn-sm">Lihat Semua Payment</a>
        </div>

        <form method="GET" action="{{ route('payments.verification') }}" class="mb-3">
          <div class="row">
            <div class="col-md-5 mb-2">
              <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari resi / reference / customer">
            </div>
            <div class="col-md-4 mb-2">
              <select name="method" class="form-control">
                <option value="">Semua Metode</option>
                @foreach($methods as $method)
                  <option value="{{ $method }}" {{ ($filters['method'] ?? '') === $method ? 'selected' : '' }}>{{ strtoupper($method) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3 mb-2 d-flex" style="gap:8px;">
              <button type="submit" class="btn btn-outline-light btn-block">Filter</button>
              <a href="{{ route('payments.verification') }}" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-dark table-striped">
            <thead>
              <tr>
                <th>Tracking</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Bukti</th>
                <th>Status</th>
                <th>Catatan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($payments as $payment)
                <tr>
                  <td>{{ $payment->shipment->tracking_number ?? '-' }}</td>
                  <td>{{ $payment->shipment->sender->name ?? '-' }}</td>
                  <td>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                  <td>{{ strtoupper($payment->payment_method) }}</td>
                  <td>{{ $payment->reference_number ?: '-' }}</td>
                  <td>
                    @if ($payment->proof_file)
                      <a href="{{ asset('uploads/payments/' . $payment->proof_file) }}" target="_blank" class="btn btn-sm btn-info">Lihat Bukti</a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if ($payment->payment_status === 'waiting_verification')
                      <span class="badge badge-warning">{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</span>
                    @elseif ($payment->payment_status === 'pending')
                      <span class="badge badge-secondary">{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</span>
                    @else
                      <span class="badge badge-dark">{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</span>
                    @endif
                  </td>
                  <td style="max-width: 260px;">{{ $payment->notes ?: '-' }}</td>
                  <td style="min-width: 240px;">
                    @if (in_array(Auth::user()->role, ['admin', 'cashier'], true))
                      <form method="POST" action="{{ route('payments.verify', $payment) }}" class="mb-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="notes" class="form-control form-control-sm mb-1" placeholder="Catatan verifikasi (opsional)">
                        <button type="submit" class="btn btn-sm btn-success btn-block" onclick="return confirm('Verifikasi pembayaran ini sebagai PAID?')">Approve</button>
                      </form>
                      <form method="POST" action="{{ route('payments.reject', $payment) }}">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="notes" class="form-control form-control-sm mb-1" placeholder="Alasan penolakan" required>
                        <button type="submit" class="btn btn-sm btn-danger btn-block" onclick="return confirm('Tolak pembayaran ini?')">Reject</button>
                      </form>
                    @else
                      <span class="text-muted">Read Only</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center">Tidak ada pembayaran yang menunggu verifikasi.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">{{ $payments->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
