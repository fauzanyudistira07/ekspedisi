@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
            <h2 class="cp-section-title">Riwayat Pembayaran</h2>
            <a href="{{ route('customer.payments.create') }}" class="btn btn-primary">+ Buat Payment</a>
        </div>
        <div class="cp-card-body">
            <form method="GET" action="{{ route('customer.payments.index') }}" class="cp-form">
                <div class="row">
                    <div class="col-md-5 mb-2"><input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cari nomor resi"></div>
                    <div class="col-md-4 mb-2">
                        <select name="status" class="custom-select">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ \App\Models\Payment::statusLabel($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex" style="gap:8px;">
                        <button class="btn btn-primary btn-block" type="submit">Filter</button>
                        <a href="{{ route('customer.payments.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="cp-card d-none d-lg-block">
        <div class="cp-card-body p-0">
            <div class="table-responsive">
                <table class="table cp-table mb-0">
                    <thead>
                        <tr>
                            <th>Resi</th>
                            <th>Nominal</th>
                            <th>Metode</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Kedaluwarsa</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->shipment->tracking_number ?? '-' }}</td>
                                <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td>{{ strtoupper($payment->payment_method) }}</td>
                                <td>{{ $payment->reference_number ?: '-' }}</td>
                                <td><span class="cp-badge {{ $payment->payment_status }}">{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</span></td>
                                <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                <td>{{ $payment->expired_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('customer.payments.invoice', $payment) }}" class="btn btn-sm btn-outline-primary">Invoice PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4">Belum ada pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-lg-none">
        @forelse ($payments as $payment)
            <div class="cp-mobile-card">
                <div class="d-flex justify-content-between align-items-start mb-2" style="gap:10px;">
                    <div>
                        <div class="font-weight-bold">{{ $payment->shipment->tracking_number ?? '-' }}</div>
                        <div class="cp-muted-small">{{ $payment->payment_date?->format('d M Y') }}</div>
                    </div>
                    <span class="cp-badge {{ $payment->payment_status }}">{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</span>
                </div>
                <div class="cp-mobile-kv"><span>Nominal</span><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong></div>
                <div class="cp-mobile-kv"><span>Metode</span><strong>{{ strtoupper($payment->payment_method) }}</strong></div>
                <div class="cp-mobile-kv"><span>Reference</span><strong>{{ $payment->reference_number ?: '-' }}</strong></div>
                <div class="cp-mobile-kv"><span>Kedaluwarsa</span><strong>{{ $payment->expired_at?->format('d M Y H:i') ?: '-' }}</strong></div>
                <div class="cp-mobile-kv"><span>Bukti</span>
                    @if ($payment->proof_file)
                        <a href="{{ asset('uploads/payments/' . $payment->proof_file) }}" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2">Lihat</a>
                    @else
                        <strong>-</strong>
                    @endif
                </div>
                <a href="{{ route('customer.payments.invoice', $payment) }}" class="btn btn-sm btn-outline-primary btn-block mt-2">Invoice PDF</a>
            </div>
        @empty
            <div class="cp-card">
                <div class="cp-card-body text-center py-4">Belum ada pembayaran.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $payments->links() }}</div>
</div>
@endsection
