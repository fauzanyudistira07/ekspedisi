<div class="form-group mb-3">
    <label>Label Alamat</label>
    <input type="text" name="label" class="form-control" value="{{ old('label', $address->label ?? '') }}" placeholder="Contoh: Rumah Ibu / Kantor Cabang" required>
</div>

<div class="row">
    <div class="col-md-6 form-group mb-3"><label>Nama Penerima</label><input type="text" name="receiver_name" class="form-control" value="{{ old('receiver_name', $address->receiver_name ?? '') }}" required></div>
    <div class="col-md-6 form-group mb-3"><label>Email Penerima (opsional)</label><input type="email" name="receiver_email" class="form-control" value="{{ old('receiver_email', $address->receiver_email ?? '') }}"></div>
</div>

<div class="row">
    <div class="col-md-8 form-group mb-3"><label>Alamat Lengkap</label><input type="text" name="address" class="form-control" value="{{ old('address', $address->address ?? '') }}" required></div>
    <div class="col-md-2 form-group mb-3"><label>Kota</label><input type="text" name="city" class="form-control" value="{{ old('city', $address->city ?? '') }}" required></div>
    <div class="col-md-2 form-group mb-3"><label>Telepon</label><input type="text" name="receiver_phone" class="form-control" value="{{ old('receiver_phone', $address->receiver_phone ?? '') }}" required></div>
</div>

<div class="form-group form-check mb-3">
    <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default" {{ old('is_default', $address->is_default ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_default">Jadikan alamat default</label>
</div>
