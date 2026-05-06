@extends('be.master')
@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card mb-4 border-0 shadow-sm page-hero page-hero--green">
      <div class="card-body py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:16px;">
          <div class="page-hero-copy">
            <div class="text-uppercase small mb-2 page-hero-eyebrow">Payment Gateway Desk</div>
            <h4 class="mb-2 page-hero-title">Semua pembayaran diproses via Midtrans</h4>
            <p class="mb-0 page-hero-text">Admin cukup memonitor status sinkronisasi, transaksi tertahan, dan omzet yang benar-benar sudah paid.</p>
          </div>
          <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('payments.index', ['status' => \App\Models\Payment::STATUS_PENDING]) }}" class="btn btn-warning text-dark">Pending Midtrans</a>
            <a href="{{ route('shipments.index') }}" class="btn btn-outline-light">Shipment</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Total Payment</div>
            <h3 class="mb-1">{{ number_format($summary['total'] ?? 0) }}</h3>
            <div class="small text-muted">Seluruh transaksi Midtrans</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Pending</div>
            <h3 class="mb-1 text-warning">{{ number_format($summary['pending'] ?? 0) }}</h3>
            <div class="small text-muted">Menunggu customer/gateway selesai</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Paid</div>
            <h3 class="mb-1 text-success">{{ number_format($summary['paid'] ?? 0) }}</h3>
            <div class="small text-muted">Pembayaran sukses tersinkron</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Revenue Paid</div>
            <h3 class="mb-1 text-primary">Rp {{ number_format((float) ($summary['revenue_paid'] ?? 0), 0, ',', '.') }}</h3>
            <div class="small text-muted">{{ number_format($summary['failed'] ?? 0) }} transaksi gagal</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:12px;">
          <div>
            <h4 class="card-title mb-1">Riwayat Pembayaran</h4>
            <div class="small text-muted">Cari transaksi berdasarkan resi, order ID Midtrans, transaction ID, atau nama customer.</div>
          </div>
          <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('payments.index', ['status' => \App\Models\Payment::STATUS_PENDING]) }}" class="btn btn-sm btn-outline-warning">Pending</a>
            <a href="{{ route('payments.index', ['status' => \App\Models\Payment::STATUS_PAID]) }}" class="btn btn-sm btn-outline-success">Paid</a>
            <a href="{{ route('payments.index', ['status' => \App\Models\Payment::STATUS_FAILED]) }}" class="btn btn-sm btn-outline-danger">Failed</a>
          </div>
        </div>

        <form method="GET" action="{{ route('payments.index') }}" class="mb-4">
          <div class="row">
            <div class="col-md-4 mb-2">
              <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari resi / order id / tx id / customer">
            </div>
            <div class="col-md-3 mb-2">
              <select name="status" class="form-control">
                <option value="">Semua Status</option>
                @foreach($statuses as $status)
                  <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ \App\Models\Payment::statusLabel($status) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3 mb-2">
              <select name="method" class="form-control">
                <option value="">Semua Metode</option>
                @foreach($methods as $method)
                  <option value="{{ $method }}" {{ ($filters['method'] ?? '') === $method ? 'selected' : '' }}>{{ strtoupper($method) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 mb-2 d-flex" style="gap:8px;">
              <button type="submit" class="btn btn-outline-light btn-block">Filter</button>
              <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-dark table-striped align-middle">
            <thead>
              <tr>
                <th>Shipment</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Gateway</th>
                <th>Channel</th>
                <th>Order ID</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Paid At</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($payments as $payment)
                @php
                  $statusTone = match ($payment->payment_status) {
                      \App\Models\Payment::STATUS_PAID => 'success',
                      \App\Models\Payment::STATUS_PENDING => 'warning',
                      \App\Models\Payment::STATUS_FAILED => 'danger',
                      \App\Models\Payment::STATUS_EXPIRED => 'secondary',
                      default => 'primary',
                  };
                @endphp
                <tr>
                  <td>
                    <div class="font-weight-bold">{{ $payment->shipment->tracking_number ?? '-' }}</div>
                    <div class="small text-muted">{{ $payment->reference_number ?? ($payment->midtrans_transaction_status ?? 'Midtrans transaction') }}</div>
                  </td>
                  <td>{{ $payment->shipment->sender->name ?? '-' }}</td>
                  <td>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                  <td>{{ strtoupper($payment->gateway_provider ?? 'midtrans') }}</td>
                  <td>{{ strtoupper($payment->payment_channel ?? '-') }}</td>
                  <td>{{ $payment->gateway_order_id ?? '-' }}</td>
                  <td>{{ $payment->gateway_transaction_id ?? '-' }}</td>
                  <td><span class="badge badge-{{ $statusTone }}">{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</span></td>
                  <td>{{ $payment->paid_at?->format('d M Y H:i') ?: '-' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center">Belum ada data payment.</td>
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
