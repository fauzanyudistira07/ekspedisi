<div class="main-panel">
  <div class="content-wrapper">
    <div class="row">
      <div class="col-12 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:12px;">
              <div>
                <h4 class="card-title mb-2">Dashboard Role {{ strtoupper($currentRole ?? '-') }}</h4>
                <p class="text-muted mb-0">{{ $roleFocus }}</p>
              </div>
              <div class="d-flex flex-wrap" style="gap:8px;">
                @foreach ($quickActions as $action)
                  <a href="{{ $action['route'] }}" class="btn {{ $action['style'] }}">{{ $action['label'] }}</a>
                @endforeach
              </div>
            </div>
            <hr>
            <ul class="mb-0 pl-3">
              @forelse ($currentRoleFeatures as $feature)
                <li class="mb-1">{{ $feature }}</li>
              @empty
                <li>Tidak ada fitur yang terkonfigurasi untuk role ini.</li>
              @endforelse
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted font-weight-normal">Total Shipment</h6>
            <h3 class="mb-0">{{ number_format($stats['shipments_total']) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted font-weight-normal">Shipment In Transit</h6>
            <h3 class="mb-0">{{ number_format($stats['shipments_in_transit']) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted font-weight-normal">Payment Menunggu</h6>
            <h3 class="mb-0">{{ number_format($stats['payments_waiting']) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted font-weight-normal">Omzet Paid Hari Ini</h6>
            <h3 class="mb-0">Rp {{ number_format((float) $stats['payments_paid_today'], 0, ',', '.') }}</h3>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted font-weight-normal">Shipment Hari Ini</h6>
            <h3 class="mb-0">{{ number_format($stats['shipments_today']) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted font-weight-normal">Tracking Update Hari Ini</h6>
            <h3 class="mb-0">{{ number_format($stats['trackings_today']) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted font-weight-normal">Shipment Delivered</h6>
            <h3 class="mb-0">{{ number_format($stats['shipments_delivered']) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted font-weight-normal">Payment Failed</h6>
            <h3 class="mb-0">{{ number_format($stats['payments_failed']) }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6 grid-margin stretch-card">
        <div class="card">
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
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
              <h4 class="card-title mb-0">Payment Terbaru</h4>
              <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-light">Lihat</a>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr><th>Resi</th><th>Nominal</th><th>Status</th><th>Metode</th></tr>
                </thead>
                <tbody>
                  @forelse ($recentPayments as $payment)
                    <tr>
                      <td>{{ $payment->shipment->tracking_number ?? '-' }}</td>
                      <td>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                      <td>{{ \App\Models\Payment::statusLabel($payment->payment_status) }}</td>
                      <td>{{ strtoupper($payment->payment_method) }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center">Belum ada payment.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
              <h4 class="card-title mb-0">Tracking Terbaru</h4>
              <a href="{{ route('shipment-trackings.index') }}" class="btn btn-sm btn-outline-light">Lihat</a>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr><th>Resi</th><th>Lokasi</th><th>Status</th><th>Waktu</th></tr>
                </thead>
                <tbody>
                  @forelse ($recentTrackings as $tracking)
                    <tr>
                      <td>{{ $tracking->shipment->tracking_number ?? '-' }}</td>
                      <td>{{ $tracking->location }}</td>
                      <td>{{ \App\Models\ShipmentTracking::statusLabel($tracking->status) }}</td>
                      <td>{{ $tracking->tracked_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center">Belum ada update tracking.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
