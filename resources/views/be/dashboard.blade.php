<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card mb-4 border-0 shadow-sm dashboard-hero">
      <div class="card-body py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:16px;">
          <div class="dashboard-hero-main" style="max-width:720px;">
            <div class="text-uppercase small mb-2 dashboard-hero-eyebrow">Control Center</div>
            <h3 class="mb-2 dashboard-hero-title">{{ $currentRole === \App\Models\User::ROLE_COURIER ? 'Workspace Kurir' : 'Dashboard ' . strtoupper($currentRole ?? '-') }}</h3>
            <p class="mb-3 dashboard-hero-text">{{ $roleFocus }}</p>
            <div class="d-flex flex-wrap" style="gap:8px;">
              @foreach ($quickActions as $action)
                <a href="{{ $action['route'] }}" class="btn {{ $action['style'] === 'btn-primary' ? 'btn-warning text-dark' : 'btn-outline-light' }}">{{ $action['label'] }}</a>
              @endforeach
            </div>
          </div>
          <div class="rounded px-3 py-3 dashboard-hero-panel" style="min-width:260px;">
            <div class="small text-uppercase mb-2 dashboard-hero-eyebrow">{{ $currentRole === \App\Models\User::ROLE_COURIER ? 'Checklist Kurir' : 'Role Capability' }}</div>
            <ul class="mb-0 pl-3">
              @forelse ($currentRoleFeatures as $feature)
                <li class="mb-1">{{ $feature }}</li>
              @empty
                <li>Tidak ada fitur yang terkonfigurasi.</li>
              @endforelse
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      @foreach ($kpiCards as $card)
        <div class="col-xl-3 col-md-6 grid-margin stretch-card">
          <a href="{{ $card['route'] }}" class="card text-decoration-none shadow-sm border-0 w-100 dashboard-kpi-card" style="color:inherit;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <div class="text-muted small text-uppercase dashboard-kpi-label">{{ $card['label'] }}</div>
                  <h3 class="mb-1 dashboard-kpi-value">{{ $card['value'] }}</h3>
                </div>
                <span class="badge badge-{{ $card['tone'] === 'success' ? 'success' : ($card['tone'] === 'warning' ? 'warning' : ($card['tone'] === 'danger' ? 'danger' : 'primary')) }}">{{ strtoupper($card['tone']) }}</span>
              </div>
              <div class="small text-muted">{{ $card['meta'] }}</div>
            </div>
          </a>
        </div>
      @endforeach
    </div>

    <div class="row">
      <div class="col-lg-5 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="card-title mb-0">Operational Alerts</h4>
              <span class="badge badge-dark">{{ $alerts->count() }} alert</span>
            </div>

            @forelse ($alerts as $alert)
              <div class="border rounded p-3 mb-3 bg-light">
                <div class="d-flex justify-content-between align-items-start" style="gap:12px;">
                  <div>
                    <div class="font-weight-bold text-{{ $alert['tone'] }}">{{ $alert['title'] }}</div>
                    <div class="small text-muted mt-1">{{ $alert['description'] }}</div>
                  </div>
                  <a href="{{ $alert['route'] }}" class="btn btn-sm btn-outline-{{ $alert['tone'] === 'danger' ? 'danger' : ($alert['tone'] === 'warning' ? 'warning' : 'primary') }}">{{ $alert['action'] }}</a>
                </div>
              </div>
            @empty
              <div class="text-center py-4 text-muted">
                Tidak ada alert kritikal. Operasional terlihat sehat.
              </div>
            @endforelse
          </div>
        </div>
      </div>

      <div class="col-lg-7 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="card-title mb-0">Performance Snapshot</h4>
              <a href="{{ route('manager.reports') }}" class="btn btn-sm btn-outline-light {{ in_array($currentRole, ['admin','manager'], true) ? '' : 'disabled' }}">Report</a>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <div class="border rounded p-3 h-100">
                  <div class="text-muted small text-uppercase">Shipment Pipeline</div>
                  <div class="mt-2 d-flex justify-content-between"><span>Hari ini</span><strong>{{ number_format($stats['shipments_today']) }}</strong></div>
                  <div class="mt-2 d-flex justify-content-between"><span>In transit</span><strong>{{ number_format($stats['shipments_in_transit']) }}</strong></div>
                  <div class="mt-2 d-flex justify-content-between"><span>Delivered</span><strong>{{ number_format($stats['shipments_delivered']) }}</strong></div>
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="border rounded p-3 h-100">
                  <div class="text-muted small text-uppercase">Payment Pipeline</div>
                  <div class="mt-2 d-flex justify-content-between"><span>Pending</span><strong>{{ number_format($stats['payments_waiting']) }}</strong></div>
                  <div class="mt-2 d-flex justify-content-between"><span>Failed</span><strong>{{ number_format($stats['payments_failed']) }}</strong></div>
                  <div class="mt-2 d-flex justify-content-between"><span>Paid hari ini</span><strong>Rp {{ number_format((float) $stats['payments_paid_today'], 0, ',', '.') }}</strong></div>
                </div>
              </div>
              <div class="col-12">
                <div class="border rounded p-3">
                  <div class="text-muted small text-uppercase mb-2">Activity Today</div>
                  <div class="row">
                    <div class="col-md-4 mb-2"><strong>{{ number_format($stats['trackings_today']) }}</strong><div class="small text-muted">tracking update</div></div>
                    <div class="col-md-4 mb-2"><strong>{{ number_format($stats['shipments_today']) }}</strong><div class="small text-muted">shipment dibuat</div></div>
                    <div class="col-md-4 mb-2"><strong>{{ number_format($stats['shipments_delivered']) }}</strong><div class="small text-muted">shipment delivered</div></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
              <h4 class="card-title mb-0">Shipment Terbaru</h4>
              <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-outline-light">Lihat</a>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr><th>Resi</th><th>Kurir</th><th>Status</th><th>Tgl</th></tr>
                </thead>
                <tbody>
                  @forelse ($recentShipments as $shipment)
                    <tr>
                      <td>{{ $shipment->tracking_number }}</td>
                      <td>{{ $shipment->courier->name ?? '-' }}</td>
                      <td>{{ \App\Models\Shipment::statusLabel($shipment->status) }}</td>
                      <td>{{ $shipment->shipment_date?->format('Y-m-d') }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center">Belum ada shipment.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
              <h4 class="card-title mb-0">{{ $currentRole === \App\Models\User::ROLE_COURIER ? 'Tracking Terbaru' : 'Payment Terbaru' }}</h4>
              <a href="{{ $currentRole === \App\Models\User::ROLE_COURIER ? route('shipment-trackings.index') : route('payments.index') }}" class="btn btn-sm btn-outline-light">Lihat</a>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  @if ($currentRole === \App\Models\User::ROLE_COURIER)
                    <tr><th>Resi</th><th>Lokasi</th><th>Status</th><th>Waktu</th></tr>
                  @else
                    <tr><th>Resi</th><th>Nominal</th><th>Status</th><th>Channel</th></tr>
                  @endif
                </thead>
                <tbody>
                  @if ($currentRole === \App\Models\User::ROLE_COURIER)
                    @forelse ($recentTrackings as $tracking)
                      <tr>
                        <td>{{ $tracking->shipment->tracking_number ?? '-' }}</td>
                        <td>{{ $tracking->location }}</td>
                        <td>{{ \App\Models\ShipmentTracking::statusLabel($tracking->status) }}</td>
                        <td>{{ $tracking->tracked_at?->format('Y-m-d H:i') }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="4" class="text-center">Belum ada tracking.</td></tr>
                    @endforelse
                  @else
                    @forelse ($recentPayments as $payment)
                      <tr>
                        <td>{{ $payment->shipment->tracking_number ?? '-' }}</td>
                        <td>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                        <td>{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</td>
                        <td>{{ strtoupper($payment->payment_channel ?? $payment->payment_method) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="4" class="text-center">Belum ada payment.</td></tr>
                    @endforelse
                  @endif
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if ($currentRole !== \App\Models\User::ROLE_COURIER)
    <div class="row">
      <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
              <h4 class="card-title mb-0">Tracking Terbaru</h4>
              <a href="{{ route('shipment-trackings.index') }}" class="btn btn-sm btn-outline-light">Lihat</a>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr><th>Resi</th><th>Lokasi</th><th>Status</th><th>Waktu</th><th>POD</th></tr>
                </thead>
                <tbody>
                  @forelse ($recentTrackings as $tracking)
                    <tr>
                      <td>{{ $tracking->shipment->tracking_number ?? '-' }}</td>
                      <td>{{ $tracking->location }}</td>
                      <td>{{ \App\Models\ShipmentTracking::statusLabel($tracking->status) }}</td>
                      <td>{{ $tracking->tracked_at?->format('Y-m-d H:i') }}</td>
                      <td>{{ $tracking->proof_photo ? 'Ada Bukti' : '-' }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="5" class="text-center">Belum ada update tracking.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif
  </div>
</div>
