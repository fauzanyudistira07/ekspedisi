<div class="row">
  <div class="col-md-6 form-group mb-3"><label>Tracking Number</label><input type="text" name="tracking_number" class="form-control" value="{{ old('tracking_number', $shipment->tracking_number ?? '') }}" required></div>
  <div class="col-md-6 form-group mb-3"><label>Shipment Date</label><input type="date" name="shipment_date" class="form-control" value="{{ old('shipment_date', isset($shipment) ? $shipment->shipment_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required></div>
</div>
<div class="row">
  <div class="col-md-6 form-group mb-3"><label>Sender</label><select name="sender_id" class="form-control" required><option value="">Pilih Sender</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" {{ (string) old('sender_id', $shipment->sender_id ?? '') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>@endforeach</select></div>
  <div class="col-md-6 form-group mb-3"><label>Receiver</label><select name="receiver_id" class="form-control" required><option value="">Pilih Receiver</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" {{ (string) old('receiver_id', $shipment->receiver_id ?? '') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>@endforeach</select></div>
</div>
<div class="row">
  <div class="col-md-6 form-group mb-3"><label>Origin Branch</label><select name="origin_branch_id" id="origin_branch_id" class="form-control" required><option value="">Pilih Branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" {{ (string) old('origin_branch_id', $shipment->origin_branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>@endforeach</select></div>
  <div class="col-md-6 form-group mb-3"><label>Destination Branch</label><select name="destination_branch_id" id="destination_branch_id" class="form-control" required><option value="">Pilih Branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" {{ (string) old('destination_branch_id', $shipment->destination_branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>@endforeach</select></div>
</div>
<div class="row">
  <div class="col-md-6 form-group mb-3"><label>Courier</label><select name="courier_id" class="form-control" required><option value="">Pilih Courier</option>@foreach($couriers as $courier)<option value="{{ $courier->id }}" {{ (string) old('courier_id', $shipment->courier_id ?? '') === (string) $courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>@endforeach</select></div>
  <div class="col-md-6 form-group mb-3"><label>Rate</label><select name="rate_id" id="rate_id" class="form-control" required><option value="">Pilih Rate</option>@foreach($rates as $rate)<option value="{{ $rate->id }}" data-price="{{ $rate->price_per_kg }}" data-origin="{{ $rate->origin_city }}" data-destination="{{ $rate->destination_city }}" {{ (string) old('rate_id', $shipment->rate_id ?? '') === (string) $rate->id ? 'selected' : '' }}>{{ $rate->origin_city }} -> {{ $rate->destination_city }} ({{ number_format($rate->price_per_kg,2) }}/kg)</option>@endforeach</select></div>
</div>
<div class="row">
  <div class="col-md-3 form-group mb-3"><label>Total Weight</label><input type="number" step="0.01" name="total_weight" class="form-control" value="{{ old('total_weight', $shipment->total_weight ?? '') }}" required></div>
  <div class="col-md-3 form-group mb-3"><label>Total Price</label><input type="number" step="0.01" name="total_price" id="total_price" class="form-control" value="{{ old('total_price', $shipment->total_price ?? '') }}" readonly></div>
  <div class="col-md-3 form-group mb-3"><label>Status</label><select name="status" class="form-control" required>@foreach($statuses as $status)<option value="{{ $status }}" {{ old('status', $shipment->status ?? 'pending') === $status ? 'selected' : '' }}>{{ strtoupper($status) }}</option>@endforeach</select></div>
  <div class="col-md-3 form-group mb-3"><label>Photo</label><input type="file" name="photo" class="form-control"></div>
</div>
<small class="text-muted d-block mb-3">Total price dihitung otomatis dari rate dan total weight.</small>

@push('scripts')
<script>
(function () {
  const rateInput = document.getElementById('rate_id');
  const weightInput = document.querySelector('input[name="total_weight"]');
  const priceInput = document.getElementById('total_price');

  function updatePrice() {
    const selected = rateInput.options[rateInput.selectedIndex];
    const pricePerKg = selected ? parseFloat(selected.dataset.price || 0) : 0;
    const totalWeight = parseFloat(weightInput.value || 0);

    if (!pricePerKg || !totalWeight) {
      priceInput.value = totalWeight ? totalWeight.toFixed(2) : '';
      if (!pricePerKg) {
        priceInput.value = '';
      }
      return;
    }

    priceInput.value = (pricePerKg * totalWeight).toFixed(2);
  }

  rateInput.addEventListener('change', updatePrice);
  weightInput.addEventListener('input', updatePrice);
  updatePrice();
})();
</script>
@endpush
