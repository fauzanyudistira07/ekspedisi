<div class="form-group mb-3"><label>Shipment</label>
<select name="shipment_id" class="form-control" required>
<option value="">Pilih Shipment</option>
@foreach($shipments as $shipment)
<option value="{{ $shipment->id }}" {{ (string) old('shipment_id', $shipmentTracking->shipment_id ?? '') === (string) $shipment->id ? 'selected' : '' }}>{{ $shipment->tracking_number }}</option>
@endforeach
</select></div>
<div class="form-group mb-3"><label>Location</label><input type="text" name="location" class="form-control" value="{{ old('location', $shipmentTracking->location ?? '') }}" required></div>
<div class="form-group mb-3"><label>Description</label><textarea name="description" class="form-control">{{ old('description', $shipmentTracking->description ?? '') }}</textarea></div>
<div class="form-group mb-3"><label>Status</label><select name="status" class="form-control" required>@foreach($statuses as $status)<option value="{{ $status }}" {{ old('status', $shipmentTracking->status ?? '') === $status ? 'selected' : '' }}>{{ strtoupper($status) }}</option>@endforeach</select></div>
<div class="form-group mb-3"><label>Tracked At</label><input type="datetime-local" name="tracked_at" class="form-control" value="{{ old('tracked_at', isset($shipmentTracking) && $shipmentTracking->tracked_at ? $shipmentTracking->tracked_at->format('Y-m-d\\TH:i') : now()->format('Y-m-d\\TH:i')) }}" required></div>
