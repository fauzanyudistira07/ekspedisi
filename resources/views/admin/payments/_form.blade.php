<div class="form-group mb-3"><label>Shipment</label>
<select name="shipment_id" class="form-control" required>
<option value="">Pilih Shipment</option>
@foreach($shipments as $shipment)
<option value="{{ $shipment->id }}" {{ (string) old('shipment_id', $payment->shipment_id ?? '') === (string) $shipment->id ? 'selected' : '' }}>{{ $shipment->tracking_number }}</option>
@endforeach
</select></div>
<div class="form-group mb-3"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $payment->amount ?? '') }}" required></div>
<div class="form-group mb-3"><label>Payment Method</label><select name="payment_method" class="form-control" required>@foreach($methods as $method)<option value="{{ $method }}" {{ old('payment_method', $payment->payment_method ?? '') === $method ? 'selected' : '' }}>{{ strtoupper($method) }}</option>@endforeach</select></div>
<div class="form-group mb-3"><label>Reference Number</label><input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $payment->reference_number ?? '') }}" placeholder="TRX/REF pembayaran"></div>
<div class="form-group mb-3"><label>Bukti Pembayaran</label><input type="file" name="proof_file" class="form-control" accept=".jpg,.jpeg,.png"></div>
<div class="form-group mb-3"><label>Payment Status</label><select name="payment_status" class="form-control" required>@foreach($statuses as $status)<option value="{{ $status }}" {{ old('payment_status', $payment->payment_status ?? 'pending') === $status ? 'selected' : '' }}>{{ strtoupper($status) }}</option>@endforeach</select></div>
<div class="form-group mb-3"><label>Payment Date</label><input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', isset($payment) ? $payment->payment_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required></div>
<div class="form-group mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $payment->notes ?? '') }}</textarea></div>
@if (!empty($payment?->proof_file))
  <div class="form-group mb-3">
    <label>Bukti Pembayaran</label>
    <div><a href="{{ asset('uploads/payments/' . $payment->proof_file) }}" target="_blank" class="btn btn-sm btn-info">Lihat Bukti</a></div>
  </div>
@endif
