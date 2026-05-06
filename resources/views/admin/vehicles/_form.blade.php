<div class="form-group mb-3">
  <label>Plate Number</label>
  <input type="text" name="plate_number" class="form-control" value="{{ old('plate_number', $vehicle->plate_number ?? '') }}" required>
</div>
<div class="form-group mb-3">
  <label>Type</label>
  <select name="type" class="form-control" required>
    @foreach (['motor', 'mobil', 'truck'] as $type)
      <option value="{{ $type }}" {{ old('type', $vehicle->type ?? '') === $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
    @endforeach
  </select>
</div>
<div class="form-group mb-3">
  <label>Courier</label>
  <select name="courier_id" class="form-control" required>
    <option value="">Pilih Courier</option>
    @foreach ($couriers as $courier)
      <option value="{{ $courier->id }}" {{ (string) old('courier_id', $vehicle->courier_id ?? '') === (string) $courier->id ? 'selected' : '' }}>{{ $courier->name }}{{ $courier->branch ? ' - ' . $courier->branch->name : '' }}</option>
    @endforeach
  </select>
</div>
