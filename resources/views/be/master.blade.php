<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Dashboard' }} | Ekspedisi Online</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Portal Internal Ekspedisi Online" name="description">

    <link href="{{ asset('assets/images/favicon.ico') }}" rel="icon">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link href="{{ asset('assets/css/stylee.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/customer-portal.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/internal-customer-theme.css') }}" rel="stylesheet">
</head>
<body class="customer-portal role-{{ Auth::user()->role ?? 'guest' }}">
    @php
        $role = Auth::user()->role ?? null;
        $menus = collect(config('admin_sidebar', []))
            ->filter(fn ($menu) => in_array($role, $menu['roles'], true))
            ->groupBy(fn ($menu) => $menu['group'] ?? 'Menu');
        $portalLabel = match ($role) {
            'courier' => 'Workspace Kurir',
            'cashier' => 'Desk Keuangan',
            'manager' => 'Control Tower Manager',
            default => 'Portal Internal Ekspedisi Online',
        };
        $footerLinks = match ($role) {
            'courier' => [
                ['label' => 'Dashboard', 'route' => route('dashboard.index')],
                ['label' => 'Task Saya', 'route' => route('courier.tasks')],
                ['label' => 'Tracking', 'route' => route('shipment-trackings.index')],
            ],
            'cashier' => [
                ['label' => 'Dashboard', 'route' => route('dashboard.index')],
                ['label' => 'Payments', 'route' => route('payments.index')],
                ['label' => 'Shipment', 'route' => route('shipments.index')],
            ],
            default => [
                ['label' => 'Dashboard', 'route' => route('dashboard.index')],
                ['label' => 'Shipment', 'route' => route('shipments.index')],
                ['label' => 'Payment', 'route' => route('payments.index')],
            ],
        };
    @endphp

    <div class="cp-topbar">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="fa fa-briefcase mr-2"></i>{{ $portalLabel }}</span>
            <div class="d-flex align-items-center" style="gap:12px;">
                <span class="d-none d-md-inline cp-topbar-pill"><i class="fa fa-wallet mr-2"></i>Midtrans {{ config('services.midtrans.is_production') ? 'Production' : 'Sandbox' }}</span>
                <span class="d-none d-md-inline"><i class="fa fa-user-shield mr-2"></i>Role: {{ strtoupper($role ?? '-') }}</span>
            </div>
        </div>
    </div>

    <div class="cp-admin-shell">
        <aside class="cp-sidebar">
            <div class="cp-sidebar-inner">
                <div>
                    <a href="{{ route('dashboard.index') }}" class="cp-sidebar-brand">
                        <i class="fa {{ $role === 'courier' ? 'fa-route' : 'fa-shipping-fast' }} mr-2"></i>{{ $role === 'courier' ? 'Courier Workspace' : 'Ekspedisi Internal' }}
                    </a>

                    <div class="cp-sidebar-role">
                        <div class="cp-sidebar-role-label">Role Aktif</div>
                        <div class="cp-sidebar-role-value">{{ strtoupper($role ?? '-') }}</div>
                    </div>

                    <nav class="cp-sidebar-menu">
                        @foreach ($menus as $group => $items)
                            <div class="cp-nav-group">
                                <div class="cp-nav-group-title">{{ $group }}</div>
                                @foreach ($items as $menu)
                                    <a href="{{ route($menu['route']) }}" class="cp-side-link {{ request()->routeIs($menu['route']) || request()->routeIs($menu['route'] . '.*') ? 'active' : '' }}">
                                        @if (!empty($menu['icon']))
                                            <i class="{{ $menu['icon'] }}"></i>
                                        @endif
                                        <span>{{ $menu['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </nav>
                </div>

                <div class="cp-sidebar-footer">
                    <div class="cp-sidebar-footer-title">{{ $role === 'courier' ? 'Mode Kerja' : 'Portal Status' }}</div>
                    <div class="cp-sidebar-footer-copy">
                        {{ $role === 'courier'
                            ? 'Fokus pada assignment, update lokasi, dan bukti serah terima.'
                            : 'Kelola operasional, data, dan monitoring sesuai hak akses role.' }}
                    </div>
                </div>
            </div>
        </aside>

        <div class="cp-admin-content">
            <div class="cp-admin-header">
                <div>
                    <div class="cp-admin-eyebrow">Operasional Internal</div>
                    <h1 class="cp-admin-title mb-0">{{ $title ?? 'Dashboard' }}</h1>
                </div>

                <div class="cp-desktop-user d-flex align-items-center" style="gap:10px;">
                    <div class="cp-header-meta">
                        <div class="cp-header-meta-label">Hari ini</div>
                        <div class="cp-header-meta-value">{{ now()->translatedFormat('d M Y') }}</div>
                    </div>
                    <div class="dropdown">
                        <button class="cp-profile-btn dropdown-toggle" data-toggle="dropdown" type="button">
                            <img src="{{ asset('assets/images/user.jpg') }}" alt="Profile">
                            <span>{{ Auth::user()->name ?? 'User' }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-item-text cp-muted-small">Role: {{ strtoupper($role ?? '-') }}</div>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <main class="cp-main">
                @yield('content')
            </main>

            <footer class="py-4 border-top" style="background:#fff; border-color:#dbe4f0 !important;">
                <div class="container d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <p class="mb-2 mb-md-0 cp-muted-small">&copy; {{ date('Y') }} Ekspedisi Online Internal. Operasional real-time per role.</p>
                    <div class="cp-muted-small">
                        @foreach ($footerLinks as $link)
                            <a href="{{ $link['route'] }}" class="{{ !$loop->last ? 'mr-3' : '' }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/lib/easing/easing.min.js') }}"></script>
    <script src="{{ asset('assets/lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
