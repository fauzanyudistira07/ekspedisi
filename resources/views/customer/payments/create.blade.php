@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex justify-content-between align-items-center">
            <h2 class="cp-section-title">Bayar Dengan Midtrans</h2>
            <a href="{{ route('customer.payments.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="cp-card-body">
            <div class="cp-stepper mb-3">
                <div class="cp-step">1. Pilih Shipment</div>
                <div class="cp-step">2. Generate Snap</div>
                <div class="cp-step">3. Bayar di Midtrans</div>
                <div class="cp-step">4. Status Sinkron Otomatis</div>
            </div>
            <p class="cp-info-box">Semua pembayaran sekarang diproses hanya melalui Midtrans. Setelah memilih shipment, sistem akan membuat checkout Midtrans otomatis.</p>

            <form method="POST" action="{{ route('customer.payments.store') }}" class="cp-form mt-3">
                @csrf

                <div class="form-group mb-3">
                    <label>Pilih Shipment</label>
                    <select name="shipment_id" id="shipment_id" class="custom-select" required>
                        <option value="">- Pilih shipment -</option>
                        @foreach ($shipments as $shipment)
                            <option value="{{ $shipment->id }}" data-amount="{{ $shipment->total_price }}" {{ (string) old('shipment_id') === (string) $shipment->id ? 'selected' : '' }}>
                                {{ $shipment->tracking_number }} - Rp {{ number_format($shipment->total_price, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($shipments->isEmpty())
                    <div class="alert alert-warning border-0">
                        Belum ada shipment yang siap dibayar. Buat shipment dulu dari menu <strong>Shipment</strong>.
                    </div>
                @endif

                <div class="cp-info-box mb-3" id="amount_preview">Nominal pembayaran akan tampil setelah memilih shipment.</div>
                <div class="cp-info-box mb-3">Metode yang tersedia mengikuti kanal aktif di akun Midtrans Anda: VA, e-wallet, QRIS, kartu, dan metode lain yang Anda aktifkan di dashboard Midtrans.</div>

                <button type="submit" class="btn btn-primary" {{ $shipments->isEmpty() ? 'disabled' : '' }}>Lanjut Ke Midtrans</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const shipmentSelect = document.getElementById('shipment_id');
    const amountPreview = document.getElementById('amount_preview');

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
    }

    function updateAmountPreview() {
        const selected = shipmentSelect.options[shipmentSelect.selectedIndex];
        const amount = selected ? parseFloat(selected.dataset.amount || 0) : 0;

        if (!amount) {
            amountPreview.textContent = 'Nominal pembayaran akan tampil setelah memilih shipment.';
            return;
        }

        amountPreview.innerHTML = 'Nominal yang akan diproses di Midtrans: <strong>' + formatRupiah(amount) + '</strong>';
    }

    shipmentSelect.addEventListener('change', updateAmountPreview);
    updateAmountPreview();
})();
</script>
@endpush
