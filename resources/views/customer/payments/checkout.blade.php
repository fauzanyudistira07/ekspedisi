@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex justify-content-between align-items-center">
            <h2 class="cp-section-title">Checkout Midtrans</h2>
            <a href="{{ route('customer.payments.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="cp-card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="cp-muted-small">Tracking Number</div>
                    <strong>{{ $payment->shipment->tracking_number }}</strong>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="cp-muted-small">Order ID Midtrans</div>
                    <strong>{{ $payment->gateway_order_id }}</strong>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="cp-muted-small">Nominal</div>
                    <strong>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</strong>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="cp-muted-small">Status Saat Ini</div>
                    <span class="cp-badge {{ $payment->payment_status }}">{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</span>
                </div>
            </div>

            <div class="cp-info-box mb-3">
                Klik tombol di bawah untuk membuka popup Midtrans. Jika popup diblokir browser, gunakan tombol cadangan redirect.
            </div>

            <div class="d-flex flex-wrap" style="gap:10px;">
                <button type="button" id="pay-button" class="btn btn-primary">Bayar Sekarang</button>
                <a href="{{ $payment->snap_redirect_url }}" class="btn btn-outline-primary" target="_blank">Buka Halaman Midtrans</a>
                <a href="{{ route('customer.payments.result', $payment) }}" class="btn btn-outline-secondary">Cek Status Lagi</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ $midtransSnapUrl }}" data-client-key="{{ $midtransClientKey }}"></script>
<script>
(function () {
    const payButton = document.getElementById('pay-button');
    const resultUrl = @json(route('customer.payments.result', $payment));
    const snapToken = @json($payment->snap_token);

    function goToResult() {
        window.location.href = resultUrl;
    }

    payButton.addEventListener('click', function () {
        window.snap.pay(snapToken, {
            onSuccess: goToResult,
            onPending: goToResult,
            onError: goToResult,
            onClose: function () {
                window.location.href = resultUrl;
            }
        });
    });

    setTimeout(function () {
        payButton.click();
    }, 300);
})();
</script>
@endpush
