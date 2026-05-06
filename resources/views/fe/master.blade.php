<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} | Ekspedisi Online</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Portal Customer Ekspedisi Online" name="description">

    <link href="{{ asset('assets/images/favicon.ico') }}" rel="icon">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/stylee.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/customer-portal.css') }}" rel="stylesheet">
</head>
<body class="customer-portal">
    @php($customer = Auth::guard('customer')->user())
    @php($isCustomerAuth = Auth::guard('customer')->check())
    @php(
        $customerInitials = $customer
            ? collect(preg_split('/\s+/', trim($customer->name ?? 'Customer')))
                ->filter()
                ->take(2)
                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                ->implode('')
            : 'C'
    )

    <div class="cp-topbar">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="fa fa-headset mr-2"></i>Support Customer: +62 21 0000 0000</span>
            <span class="d-none d-md-inline"><i class="fa fa-clock mr-2"></i>Senin - Minggu, 24 Jam</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg cp-navbar py-2 py-lg-3">
        <div class="container">
            <a href="{{ $isCustomerAuth ? route('home.index') : route('track.index') }}" class="navbar-brand cp-brand mb-0">
                <i class="fa fa-shipping-fast mr-2"></i>Ekspedisi Online
            </a>

            <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#cpNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="cpNavbar">
                <div class="navbar-nav mx-auto py-2 py-lg-0">
                    @if ($isCustomerAuth)
                        <a href="{{ route('home.index') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('home.index') ? 'active' : '' }}">Dashboard</a>
                        <a href="{{ route('customer.shipments.index') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('customer.shipments.*') ? 'active' : '' }}">Shipment</a>
                        <a href="{{ route('customer.addresses.index') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('customer.addresses.*') ? 'active' : '' }}">Address Book</a>
                        <a href="{{ route('customer.payments.index') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('customer.payments.*') ? 'active' : '' }}">Payment</a>
                        <a href="{{ route('track.index') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('track.*') ? 'active' : '' }}">Tracking</a>
                        <a href="{{ route('home.contact') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('home.contact') ? 'active' : '' }}">Bantuan</a>
                    @else
                        <a href="{{ route('track.index') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('track.*') ? 'active' : '' }}">Tracking</a>
                        <a href="{{ route('login') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
                        <a href="{{ route('auth.register') }}" class="nav-item nav-link cp-nav-link {{ request()->routeIs('auth.register') ? 'active' : '' }}">Register</a>
                    @endif
                </div>

                @if ($isCustomerAuth)
                    <div class="cp-desktop-user d-flex align-items-center" style="gap:10px;">
                        <div class="dropdown">
                            <button class="btn btn-outline-primary rounded-pill position-relative" data-toggle="dropdown" type="button">
                                <i class="fa fa-bell"></i>
                                <span id="trackingNotifBadge" class="badge badge-danger position-absolute {{ ($trackingNotificationCount ?? 0) > 0 ? '' : 'd-none' }}" style="top:-7px; right:-7px;">{{ $trackingNotificationCount }}</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right p-0" style="min-width:320px;">
                                <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                    <strong>Notifikasi Tracking</strong>
                                    <form method="POST" action="{{ route('customer.notifications.read') }}" id="notifReadTopAction" class="{{ ($trackingNotificationCount ?? 0) > 0 ? '' : 'd-none' }}">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm p-0">Tandai Dibaca</button>
                                    </form>
                                </div>
                                <div id="trackingNotifList">
                                    @forelse (($trackingNotifications ?? collect()) as $notif)
                                        <a href="{{ route('customer.shipments.show', $notif->shipment_id) }}" class="dropdown-item py-2">
                                            <div class="small font-weight-bold">{{ strtoupper(str_replace('_', ' ', $notif->status)) }}</div>
                                            <div class="small text-muted">{{ $notif->shipment->tracking_number ?? '-' }} - {{ $notif->location }}</div>
                                            <div class="small text-muted">{{ $notif->tracked_at?->format('d M Y H:i') }}</div>
                                        </a>
                                    @empty
                                        <div class="px-3 py-3 small text-muted">Belum ada notifikasi tracking.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="dropdown">
                            <button class="cp-profile-btn dropdown-toggle" data-toggle="dropdown" type="button">
                                @if ($customer && $customer->photo)
                                    <img src="{{ asset('uploads/customers/' . $customer->photo) }}" alt="Profile">
                                @else
                                    <span class="cp-avatar" aria-hidden="true">{{ $customerInitials ?: 'C' }}</span>
                                @endif
                                <span>{{ $customer->name ?? 'Customer' }}</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a href="{{ route('customer.profile.edit') }}" class="dropdown-item">Profil Saya</a>
                                <a href="{{ route('customer.addresses.index') }}" class="dropdown-item">Address Book</a>
                                <a href="{{ route('customer.shipments.create') }}" class="dropdown-item">Buat Shipment</a>
                                <a href="{{ route('customer.payments.create') }}" class="dropdown-item">Bayar via Midtrans</a>
                                <div class="dropdown-divider"></div>
                                <form action="{{ route('customer.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="cp-desktop-user d-flex align-items-center" style="gap:10px;">
                        <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill px-3">Login</a>
                        <a href="{{ route('auth.register') }}" class="btn btn-primary rounded-pill px-3">Register</a>
                    </div>
                @endif
            </div>
        </div>
    </nav>

    <main class="cp-main">
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="trackingNotifBanner" class="alert alert-info border-0 shadow-sm d-flex flex-wrap justify-content-between align-items-center {{ $isCustomerAuth && ($trackingNotificationCount ?? 0) > 0 ? '' : 'd-none' }}" style="gap:10px;">
                <span>Ada {{ $trackingNotificationCount }} update tracking baru pada shipment kamu.</span>
                <form method="POST" action="{{ route('customer.notifications.read') }}" id="notifReadBannerAction">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary" type="submit">Tandai Dibaca</button>
                </form>
                </div>
        </div>

        @yield('content')
    </main>

    <footer class="py-4 border-top" style="background:#fff; border-color:#dbe4f0 !important;">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <p class="mb-2 mb-md-0 cp-muted-small">&copy; {{ date('Y') }} Ekspedisi Online. Portal customer untuk pengiriman cepat dan aman.</p>
            <div class="cp-muted-small">
                <a href="{{ route('home.about') }}" class="mr-3">Tentang</a>
                <a href="{{ route('home.service') }}" class="mr-3">Layanan</a>
                <a href="{{ route('home.blog') }}">Panduan</a>
            </div>
        </div>
    </footer>

    @if ($isCustomerAuth)
        <nav class="cp-mobile-tabs">
            <a href="{{ route('home.index') }}" class="{{ request()->routeIs('home.index') ? 'active' : '' }}"><i class="fa fa-home"></i>Home</a>
            <a href="{{ route('customer.shipments.index') }}" class="{{ request()->routeIs('customer.shipments.*') ? 'active' : '' }}"><i class="fa fa-box"></i>Shipment</a>
            <a href="{{ route('customer.addresses.index') }}" class="{{ request()->routeIs('customer.addresses.*') ? 'active' : '' }}"><i class="fa fa-address-book"></i>Alamat</a>
            <a href="{{ route('customer.payments.index') }}" class="{{ request()->routeIs('customer.payments.*') ? 'active' : '' }}"><i class="fa fa-wallet"></i>Payment</a>
            <a href="{{ route('customer.profile.edit') }}" class="{{ request()->routeIs('customer.profile.*') ? 'active' : '' }}"><i class="fa fa-user"></i>Akun</a>
        </nav>
    @endif

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/lib/easing/easing.min.js') }}"></script>
    <script src="{{ asset('assets/lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    @if ($isCustomerAuth)
        <script>
            (function () {
            const pollUrl = "{{ route('customer.notifications.poll') }}";
            const badge = document.getElementById('trackingNotifBadge');
            const list = document.getElementById('trackingNotifList');
            const banner = document.getElementById('trackingNotifBanner');
            const topAction = document.getElementById('notifReadTopAction');
            const bannerAction = document.getElementById('notifReadBannerAction');

            if (!badge || !list || !banner) {
                return;
            }

            const renderItems = (items) => {
                if (!items.length) {
                    list.innerHTML = '<div class="px-3 py-3 small text-muted">Belum ada notifikasi tracking.</div>';
                    return;
                }

                list.innerHTML = items.map((item) => {
                    return `
                        <a href="${item.url}" class="dropdown-item py-2">
                            <div class="small font-weight-bold">${item.status}</div>
                            <div class="small text-muted">${item.tracking_number} - ${item.location}</div>
                            <div class="small text-muted">${item.tracked_at}</div>
                        </a>
                    `;
                }).join('');
            };

            const toggleUnreadUi = (count) => {
                badge.textContent = count;
                badge.classList.toggle('d-none', count <= 0);
                banner.classList.toggle('d-none', count <= 0);
                topAction?.classList.toggle('d-none', count <= 0);
                bannerAction?.classList.toggle('d-none', count <= 0);

                if (count > 0) {
                    const span = banner.querySelector('span');
                    if (span) {
                        span.textContent = `Ada ${count} update tracking baru pada shipment kamu.`;
                    }
                }
            };

            const poll = async () => {
                try {
                    const response = await fetch(pollUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    const unreadCount = Number(data.unread_count || 0);
                    const items = Array.isArray(data.items) ? data.items : [];
                    toggleUnreadUi(unreadCount);
                    renderItems(items);
                } catch (error) {
                    console.debug('Polling notifikasi gagal:', error);
                }
            };

            poll();
            setInterval(poll, 15000);
            })();
        </script>
    @endif
    @stack('scripts')
</body>
</html>
