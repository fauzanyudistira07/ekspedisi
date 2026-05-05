<div class="row">
  <div class="col-md-6 form-group mb-3">
    <label>Nama</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
  </div>
  <div class="col-md-6 form-group mb-3">
    <label>Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email ?? '') }}" required>
  </div>
</div>

<div class="row">
  <div class="col-md-8 form-group mb-3">
    <label>Alamat</label>
    <textarea name="address" class="form-control" rows="3">{{ old('address', $customer->address ?? '') }}</textarea>
  </div>
  <div class="col-md-4 form-group mb-3">
    <label>Kota</label>
    <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city ?? '') }}">
  </div>
</div>

<div class="row">
  <div class="col-md-6 form-group mb-3">
    <label>Telepon</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}">
  </div>
  <div class="col-md-6 form-group mb-3">
    <label>Foto</label>
    <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
  </div>
</div>

<div class="row">
  <div class="col-md-6 form-group mb-3">
    <label>Password Baru (Opsional)</label>
    <input type="password" name="password" class="form-control" minlength="8">
  </div>
  <div class="col-md-6 form-group mb-3">
    <label>Konfirmasi Password</label>
    <input type="password" name="password_confirmation" class="form-control" minlength="8">
  </div>
</div>

@if (!empty($customer?->photo))
  <div class="form-group mb-3">
    <label>Foto Saat Ini</label>
    <div><a href="{{ asset('uploads/customers/' . $customer->photo) }}" target="_blank" class="btn btn-sm btn-info">Lihat Foto</a></div>
  </div>
@endif
