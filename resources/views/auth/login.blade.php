<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Login' }} | Ekspedisi Online</title>
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
            <a href="{{ route('track.index') }}" class="text-light"><i class="fa fa-search-location mr-2"></i>Cek Resi</a>
        </div>
    </div>

    <main class="cp-main d-flex align-items-center" style="min-height: calc(100vh - 40px);">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="cp-hero mb-3">
                        <h1 class="mb-2">Masuk Ke Ekspedisi Online</h1>
                        <p class="mb-0">Satu login untuk semua akun: admin, manager, cashier, courier, dan customer.</p>
                    </div>

                    <div class="cp-card">
                        <div class="cp-card-body">
                            @if (session('success'))
                                <div class="alert alert-success border-0">{{ session('success') }}</div>
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

                            <form action="{{ route('auth.authenticate') }}" method="POST" class="cp-form">
                                @csrf
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="nama@email.com" required autofocus>
                                </div>

                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">Login</button>
                            </form>

                            <div class="text-center mt-3 cp-muted-small">
                                Belum punya akun customer?
                                <a href="{{ route('auth.register') }}">Daftar di sini</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
