<div class="form-group mb-3"><label>Name</label><input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required></div>
<div class="small text-muted mb-3">Untuk role courier, nama atau email bisa mengandung kata <strong>pickup</strong>, <strong>drop</strong>, atau <strong>hth</strong> agar task dibagi otomatis sesuai cabang dan tahap pengiriman.</div>
<div class="form-group mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required></div>
<div class="form-group mb-3"><label>Role</label>
<select name="role" class="form-control" required>
@foreach ($roles as $role)
<option value="{{ $role }}" {{ old('role', $user->role ?? '') === $role ? 'selected' : '' }}>{{ strtoupper($role) }}</option>
@endforeach
</select></div>
<div class="form-group mb-3"><label>Branch</label>
<select name="branch_id" class="form-control">
<option value="">- Pilih Branch -</option>
@foreach ($branches as $branch)
<option value="{{ $branch->id }}" {{ (string) old('branch_id', $user->branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
@endforeach
</select></div>
<div class="form-group mb-3"><label>Password {{ isset($user) ? '(kosongkan jika tidak diubah)' : '' }}</label><input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}></div>
<div class="form-group mb-3"><label>Confirm Password</label><input type="password" name="password_confirmation" class="form-control" {{ isset($user) ? '' : 'required' }}></div>
