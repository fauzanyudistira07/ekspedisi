<div class="form-group mb-3">
  <label>Origin City</label>
  <input type="text" name="origin_city" class="form-control" value="{{ old('origin_city', $rate->origin_city ?? '') }}" required>
</div>
<div class="form-group mb-3">
  <label>Destination City</label>
  <input type="text" name="destination_city" class="form-control" value="{{ old('destination_city', $rate->destination_city ?? '') }}" required>
</div>
<div class="form-group mb-3">
  <label>Price per Kg</label>
  <input type="number" step="0.01" name="price_per_kg" class="form-control" value="{{ old('price_per_kg', $rate->price_per_kg ?? '') }}" required>
</div>
<div class="form-group mb-3">
  <label>Estimated Days</label>
  <input type="number" name="estimated_days" class="form-control" value="{{ old('estimated_days', $rate->estimated_days ?? '') }}" required>
</div>
