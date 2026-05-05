<div class="form-group mb-3"><label>Shipment</label>
<select name="shipment_id" class="form-control" required>
<option value="">Pilih Shipment</option>
@foreach($shipments as $shipment)
<option value="{{ $shipment->id }}" {{ (string) old('shipment_id', $shipmentItem->shipment_id ?? '') === (string) $shipment->id ? 'selected' : '' }}>{{ $shipment->tracking_number }}</option>
@endforeach
</select></div>
<div class="form-group mb-3"><label>Item Name</label><input type="text" name="item_name" class="form-control" value="{{ old('item_name', $shipmentItem->item_name ?? '') }}" required></div>
<div class="form-group mb-3"><label>Quantity</label><input type="number" name="quantity" class="form-control" value="{{ old('quantity', $shipmentItem->quantity ?? 1) }}" required></div>
<div class="form-group mb-3"><label>Weight</label><input type="number" step="0.01" name="weight" class="form-control" value="{{ old('weight', $shipmentItem->weight ?? '') }}" required></div>
<div class="form-group mb-3"><label>Photo</label><input type="file" name="photo" class="form-control"></div>
