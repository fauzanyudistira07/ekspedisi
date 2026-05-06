@php
  $formShipment = $selectedShipment ?? null;
  $isCourierTrackingForm = (Auth::user()->role ?? null) === \App\Models\User::ROLE_COURIER;
@endphp

@if ($isCourierTrackingForm && $formShipment)
<input type="hidden" name="shipment_id" value="{{ $formShipment->id }}">
<div class="form-group mb-3">
  <label>Shipment</label>
  <div class="cp-inline-info">
    <div class="font-weight-bold">{{ $formShipment->tracking_number }}</div>
    <div class="small text-muted">{{ $formShipment->originBranch->city ?? '-' }} ke {{ $formShipment->destinationBranch->city ?? '-' }}</div>
    <div class="small text-muted">Penerima: {{ $formShipment->receiver->name ?? '-' }}</div>
  </div>
</div>
@else
<div class="form-group mb-3"><label>Shipment</label>
<select name="shipment_id" class="form-control" required>
<option value="">Pilih Shipment</option>
@foreach($shipments as $shipment)
<option value="{{ $shipment->id }}" {{ (string) old('shipment_id', $shipmentTracking->shipment_id ?? $formShipment?->id ?? '') === (string) $shipment->id ? 'selected' : '' }}>{{ $shipment->tracking_number }}</option>
@endforeach
</select></div>
@endif
<div class="form-group mb-3"><label>Location</label><input type="text" name="location" class="form-control" value="{{ old('location', $shipmentTracking->location ?? '') }}" required></div>
<div class="form-group mb-3"><label>Description</label><textarea name="description" class="form-control">{{ old('description', $shipmentTracking->description ?? '') }}</textarea></div>
<div class="form-group mb-3"><label>Status</label><select name="status" id="tracking_status" class="form-control" required>@foreach($statuses as $status)<option value="{{ $status }}" {{ old('status', $shipmentTracking->status ?? $selectedStatus ?? '') === $status ? 'selected' : '' }}>{{ $isCourierTrackingForm ? ($formShipment ? $formShipment->courierTrackingStatusSelectionLabel($status) : \App\Models\Shipment::courierTaskActionStatusLabel($status)) : \App\Models\ShipmentTracking::statusLabel($status) }}</option>@endforeach</select></div>
<div id="delivery_proof_fields" style="display:none;">
@if (!$isCourierTrackingForm)
<div class="form-group mb-3"><label>Received By</label><input type="text" name="received_by" class="form-control js-delivery-proof-input" value="{{ old('received_by', $shipmentTracking->received_by ?? '') }}"></div>
<div class="form-group mb-3"><label>Receiver Relation</label><input type="text" name="receiver_relation" class="form-control" value="{{ old('receiver_relation', $shipmentTracking->receiver_relation ?? '') }}" placeholder="Keluarga / Security / Resepsionis"></div>
@endif
<div class="form-group mb-3"><label>Proof Photo</label><input type="file" name="proof_photo" class="form-control js-delivery-proof-input" accept="image/*" capture="environment"></div>
@if ($shipmentTracking?->proofPhotoExists())
<div class="form-group mb-3">
    <a href="{{ $shipmentTracking->proofPhotoUrl() }}" target="_blank" class="btn btn-sm btn-info">Lihat Bukti Saat Ini</a>
</div>
@elseif (!empty($shipmentTracking?->proof_photo))
<div class="form-group mb-3">
    <div class="small text-warning">File bukti saat ini tidak ditemukan. Unggah ulang foto jika diperlukan.</div>
</div>
@endif
</div>
<div class="cp-info-box mb-3">
  @if ($isCourierTrackingForm)
  Flow courier:
  Pickup = <strong>Pending -> Menuju Pickup -> Sudah Dipickup -> Sampai di Cabang</strong>.
  HTH = <strong>Proses -> Menuju Cabang Tujuan -> Sampai di Cabang Tujuan</strong>.
  Drop = <strong>Pickup dari Cabang -> Sedang Diantar ke Alamat -> Sampai di Tujuan</strong>.
  Bukti foto wajib saat <strong>Sudah Dipickup</strong> dan <strong>Sampai di Tujuan</strong>.
  @else
  Bukti paket sampai dipakai hanya saat status <strong>sampai ke rumah penerima</strong>.
  Isinya: nama penerima, hubungan penerima bila perlu, dan foto proof of delivery.
  @endif
</div>
<div class="form-group mb-3"><label>Tracked At</label><input type="datetime-local" name="tracked_at" class="form-control" value="{{ old('tracked_at', isset($shipmentTracking) && $shipmentTracking->tracked_at ? $shipmentTracking->tracked_at->format('Y-m-d\\TH:i') : now()->format('Y-m-d\\TH:i')) }}" required></div>

@push('scripts')
<script>
(function () {
  const statusInput = document.getElementById('tracking_status');
  const proofFields = document.getElementById('delivery_proof_fields');
  const isCourierTrackingForm = @json($isCourierTrackingForm);

  if (!statusInput || !proofFields) {
    return;
  }

  function toggleProofFields() {
    const proofStatuses = @json($isCourierTrackingForm ? ['picked_up', 'delivered'] : ['delivered']);
    const showProofFields = proofStatuses.includes(statusInput.value);
    proofFields.style.display = showProofFields ? '' : 'none';

    proofFields.querySelectorAll('.js-delivery-proof-input, input[name="received_by"]').forEach(function (input) {
      if (input.name === 'received_by' && isCourierTrackingForm) {
        input.required = false;
        return;
      }

      input.required = showProofFields;
    });
  }

  statusInput.addEventListener('change', toggleProofFields);
  toggleProofFields();
})();
</script>
@endpush
