<div class="form-group mb-3">
  <label>Name</label>
  <input type="text" name="name" class="form-control" value="{{ old('name', $branch->name ?? '') }}" required>
</div>
<div class="form-group mb-3">
  <label>City</label>
  <input type="text" name="city" class="form-control" value="{{ old('city', $branch->city ?? '') }}" required>
</div>
<div class="form-group mb-3">
  <label>Address</label>
  <input type="text" name="address" class="form-control" value="{{ old('address', $branch->address ?? '') }}" required>
</div>
<div class="form-group mb-3">
  <label>Phone</label>
  <input type="text" name="phone" class="form-control" value="{{ old('phone', $branch->phone ?? '') }}" required>
</div>
