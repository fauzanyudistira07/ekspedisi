@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header"><h2 class="cp-section-title">Profil Customer</h2></div>
        <div class="cp-card-body">
            <div class="row align-items-center mb-4">
                <div class="col-auto">
                    @php($customerInitials = collect(preg_split('/\s+/', trim($customer->name)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode(''))
                    @if ($customer->photo)
                        <img src="{{ asset('uploads/customers/' . $customer->photo) }}" alt="Foto" style="width:78px;height:78px;border-radius:50%;object-fit:cover;border:3px solid #dbe4f0;">
                    @else
                        <span class="cp-avatar cp-avatar-lg" aria-hidden="true">{{ $customerInitials ?: 'C' }}</span>
                    @endif
                </div>
                <div class="col">
                    <h5 class="mb-1">{{ $customer->name }}</h5>
                    <div class="cp-muted-small">{{ $customer->email }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('customer.profile.update') }}" enctype="multipart/form-data" class="cp-form">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 form-group mb-3"><label>Nama</label><input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required></div>
                    <div class="col-md-6 form-group mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}" required></div>
                </div>

                <div class="row">
                    <div class="col-md-7 form-group mb-3"><label>Alamat</label><input type="text" name="address" class="form-control" value="{{ old('address', $customer->address) }}" required></div>
                    <div class="col-md-3 form-group mb-3"><label>Kota</label><input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}" required></div>
                    <div class="col-md-2 form-group mb-3"><label>Telepon</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" required></div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group mb-3"><label>Password Baru (opsional)</label><input type="password" name="password" class="form-control"></div>
                    <div class="col-md-6 form-group mb-3"><label>Konfirmasi Password Baru</label><input type="password" name="password_confirmation" class="form-control"></div>
                </div>

                <div class="form-group mb-3"><label>Foto Profil (opsional)</label><input type="file" name="photo" class="form-control"></div>

                <button type="submit" class="btn btn-primary">Update Profil</button>
            </form>
        </div>
    </div>
</div>
@endsection
