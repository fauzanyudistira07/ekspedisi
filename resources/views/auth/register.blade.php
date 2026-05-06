<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Ekspedisi Online</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/stylee.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/customer-portal.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
</head>
<body class="customer-portal">
    <div class="cp-topbar">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="fa fa-headset mr-2"></i>Support Customer: +62 21 0000 0000</span>
            <a href="{{ route('login') }}" class="text-light"><i class="fa fa-sign-in-alt mr-2"></i>Login</a>
        </div>
    </div>

    <main class="cp-main">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="cp-card">
                        <div class="cp-card-header">
                            <h2 class="cp-section-title mb-1">Daftar Akun Customer</h2>
                            <div class="cp-muted-small">Isi data dengan benar agar proses pengiriman lebih cepat.</div>
                        </div>
                        <div class="cp-card-body">
                            @if (session('success'))
                                <div class="alert alert-success border-0">{{ session('success') }}</div>
                            @endif

                            @if (session('warning'))
                                <div class="alert alert-warning border-0">{{ session('warning') }}</div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger border-0">
                                    <ul class="mb-0 pl-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('auth.register.store') }}" method="POST" enctype="multipart/form-data" class="cp-form">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Nama</label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Konfirmasi Password</label>
                                        <input type="password" name="password_confirmation" class="form-control" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Alamat</label>
                                    <textarea name="address" class="form-control" rows="3" required>{{ old('address') }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Kota</label>
                                        <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>No. HP</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Foto (opsional)</label>
                                    <input type="file" name="photo" class="form-control">
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">Register</button>
                            </form>

                            <div class="text-center mt-3 cp-muted-small">
                                Setelah register, akun akan diverifikasi dulu dengan OTP yang dikirim ke email Anda.
                            </div>
                            <div class="text-center mt-3 cp-muted-small">
                                Sudah punya akun?
                                <a href="{{ route('login') }}">Login sekarang</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
