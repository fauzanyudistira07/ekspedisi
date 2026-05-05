@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex justify-content-between align-items-center">
            <h2 class="cp-section-title">Buat Pembayaran</h2>
            <a href="{{ route('customer.payments.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="cp-card-body">
            <div class="cp-stepper mb-3">
                <div class="cp-step">1. Pilih Shipment</div>
                <div class="cp-step">2. Isi Pembayaran</div>
                <div class="cp-step">3. Upload Bukti</div>
                <div class="cp-step">4. Tunggu Verifikasi</div>
            </div>
            <p class="cp-info-box">Alur pembayaran profesional: pilih shipment -> pilih metode -> isi referensi dan upload bukti (untuk transfer/e-wallet) -> tunggu verifikasi kasir maksimal 1x24 jam.</p>

            <form method="POST" action="{{ route('customer.payments.store') }}" enctype="multipart/form-data" class="cp-form mt-3">
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
                        Belum ada shipment yang bisa dibayar. Buat shipment dulu dari menu <strong>Shipment</strong>.
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-4 form-group mb-3">
                        <label>Metode Pembayaran</label>
                        <select name="payment_method" id="payment_method" class="custom-select" required>
                            @foreach ($methods as $method)
                                <option value="{{ $method }}" {{ old('payment_method') === $method ? 'selected' : '' }}>{{ strtoupper($method) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label>Tanggal Pembayaran</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label>Nomor Referensi (opsional)</label>
                        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" placeholder="Contoh: TRX-2026-0001">
                    </div>
                </div>

                <div class="form-group mb-3" id="proof_wrap">
                    <label>Upload Bukti Pembayaran</label>
                    <input type="file" name="proof_file" class="form-control">
                    <div class="cp-muted-small mt-1">Wajib untuk metode <strong>transfer</strong> dan <strong>e-wallet</strong>.</div>
                </div>

                <div class="cp-info-box mb-3" id="method_hint">
                    Metode cash: bayar langsung ke kasir cabang, status akan diperbarui oleh petugas.
                </div>

                <div class="form-group mb-3">
                    <label>Catatan (opsional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan jika perlu">{{ old('notes') }}</textarea>
                </div>

                <div class="cp-info-box mb-3" id="amount_preview">Nominal pembayaran akan tampil setelah memilih shipment.</div>

                <button type="submit" class="btn btn-primary" {{ $shipments->isEmpty() ? 'disabled' : '' }}>Submit Pembayaran</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const shipmentSelect = document.getElementById('shipment_id');
    const paymentMethod = document.getElementById('payment_method');
    const amountPreview = document.getElementById('amount_preview');
    const proofWrap = document.getElementById('proof_wrap');
    const methodHint = document.getElementById('method_hint');

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

        amountPreview.innerHTML = 'Nominal yang harus dibayar: <strong>' + formatRupiah(amount) + '</strong>';
    }

    function toggleProofField() {
        const method = paymentMethod.value;
        proofWrap.style.display = (method === 'cash') ? 'none' : '';

        if (method === 'cash') {
            methodHint.textContent = 'Metode cash: bayar langsung ke kasir cabang, status akan diperbarui oleh petugas.';
            return;
        }

        if (method === 'transfer') {
            methodHint.textContent = 'Metode transfer: pastikan nominal sesuai, isi nomor referensi transfer, lalu upload bukti yang jelas.';
            return;
        }

        methodHint.textContent = 'Metode e-wallet: kirim sesuai nominal, isi ID transaksi/e-wallet reference, lalu upload bukti pembayaran.';
    }

    shipmentSelect.addEventListener('change', updateAmountPreview);
    paymentMethod.addEventListener('change', toggleProofField);

    updateAmountPreview();
    toggleProofField();
})();
</script>
@endpush
