<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP | Ekspedisi Online</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/stylee.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/customer-portal.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
</head>
<body class="customer-portal">
    <div class="cp-topbar">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="fa fa-envelope-open-text mr-2"></i>Verifikasi email register customer</span>
            <a href="{{ route('auth.register') }}" class="text-light"><i class="fa fa-arrow-left mr-2"></i>Kembali ke register</a>
        </div>
    </div>

    <main class="cp-main">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="cp-card">
                        <div class="cp-card-header">
                            <h2 class="cp-section-title mb-1">Masukkan OTP</h2>
                            <div class="cp-muted-small">Kami kirim kode verifikasi 6 digit ke <strong>{{ $email }}</strong>.</div>
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

                            <form action="{{ route('auth.register.verify.submit') }}" method="POST" class="cp-form">
                                @csrf
                                <div class="form-group">
                                    <label>Kode OTP</label>
                                    <input type="text" name="otp" class="form-control text-center" value="{{ old('otp') }}" inputmode="numeric" maxlength="6" placeholder="000000" required>
                                </div>

                                <div class="cp-info-box mb-3">
                                    OTP berlaku sampai <strong>{{ \Carbon\Carbon::createFromTimestamp((int) $expiresAt)->format('H:i') }}</strong>.
                                    Jika email belum masuk, cek folder spam lalu kirim ulang OTP.
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">Verifikasi & Buat Akun</button>
                            </form>

                            <form action="{{ route('auth.register.resend-otp') }}" method="POST" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-block">Kirim Ulang OTP</button>
                            </form>

                            <div class="text-center mt-3 cp-muted-small">
                                Akun baru akan masuk ke database hanya setelah OTP berhasil diverifikasi.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
