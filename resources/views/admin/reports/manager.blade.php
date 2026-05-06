@extends('be.master')

@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card mb-4 border-0 shadow-sm page-hero page-hero--blue">
      <div class="card-body py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:16px;">
          <div class="page-hero-copy">
            <div class="text-uppercase small mb-2 page-hero-eyebrow">Control Tower Manager</div>
            <h4 class="mb-2 page-hero-title">Pantau exception, SLA, dan performa cabang</h4>
            <p class="mb-0 page-hero-text">Dashboard ini dipakai untuk melihat bottleneck operasional, shipment yang macet, dan performa kurir/cabang dalam satu periode.</p>
          </div>
          <a href="{{ route('manager.reports.export', request()->query()) }}" class="btn btn-warning text-dark">Export PDF</a>
        </div>
      </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm report-filter-card">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
          <div>
            <h4 class="card-title mb-1">Filter Laporan</h4>
            <div class="small text-muted">Batasi periode dan cabang untuk melihat control tower yang lebih fokus.</div>
          </div>
        </div>

        <form method="GET" action="{{ route('manager.reports') }}">
          <div class="row">
            <div class="col-md-3 mb-2">
              <label class="mb-1">Date From</label>
              <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-3 mb-2">
              <label class="mb-1">Date To</label>
              <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-4 mb-2">
              <label class="mb-1">Branch</label>
              <select name="branch_id" class="form-control">
                <option value="">Semua Branch</option>
                @foreach($branches as $branch)
                  <option value="{{ $branch->id }}" {{ (string)$filters['branch_id'] === (string)$branch->id ? 'selected' : '' }}>{{ $branch->name }} - {{ $branch->city }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end" style="gap:8px;">
              <button type="submit" class="btn btn-outline-light btn-block">Filter</button>
              <a href="{{ route('manager.reports') }}" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="row report-kpi-grid">
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Total Shipment</h6><h3 class="dashboard-kpi-value">{{ number_format($summary['total_shipments']) }}</h3><div class="small text-muted">Seluruh shipment pada periode terpilih</div></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Delivered</h6><h3 class="dashboard-kpi-value text-success">{{ number_format($summary['delivered']) }}</h3><div class="small text-muted">Shipment yang selesai terkirim</div></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">In Transit</h6><h3 class="dashboard-kpi-value text-primary">{{ number_format($summary['in_transit']) }}</h3><div class="small text-muted">Shipment masih bergerak di lapangan</div></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Stale > 2 Hari</h6><h3 class="dashboard-kpi-value text-warning">{{ number_format($summary['stale_shipments']) }}</h3><div class="small text-muted">Butuh follow up operasional</div></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Exception</h6><h3 class="dashboard-kpi-value text-danger">{{ number_format($summary['exceptions']) }}</h3><div class="small text-muted">Gagal antar, hold, atau retur</div></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Total Paid</h6><div class="dashboard-kpi-money mb-1"><span class="dashboard-kpi-money-prefix">Rp</span><span class="dashboard-kpi-money-value">{{ number_format($summary['paid_amount'], 0, ',', '.') }}</span></div><div class="small text-muted">{{ number_format($summary['failed_payment']) }} pembayaran gagal</div></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Waiting Payment</h6><h3 class="dashboard-kpi-value text-warning">{{ number_format($summary['waiting_payment']) }}</h3><div class="small text-muted">Pembayaran masih pending</div></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Failed Payment</h6><h3 class="dashboard-kpi-value text-danger">{{ number_format($summary['failed_payment']) }}</h3><div class="small text-muted">Transaksi gagal pada periode ini</div></div></div></div>
    </div>

    <div class="row report-section-grid">
      <div class="col-xl-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="card-title mb-0">SLA Alerts</h4>
              <span class="badge badge-dark">{{ $slaAlerts->count() }} alert</span>
            </div>
            <div class="manager-alert-stack">
              @forelse ($slaAlerts as $alert)
                <div class="manager-alert-card tone-{{ $alert['tone'] }}">
                  <div class="font-weight-bold">{{ $alert['title'] }}</div>
                  <div class="small mt-1">{{ $alert['description'] }}</div>
                </div>
              @empty
                <div class="text-muted">Tidak ada alert penting pada periode ini.</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h4 class="card-title mb-3">Breakdown Exception</h4>
            <div class="manager-mini-grid">
              @foreach ($exceptionBreakdown as $item)
                <div class="manager-mini-card">
                  <div class="small text-muted text-uppercase dashboard-kpi-label">{{ $item['label'] }}</div>
                  <div class="h3 mb-0 {{ $item['count'] > 0 ? 'text-danger' : '' }}">{{ number_format($item['count']) }}</div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row report-section-grid">
      <div class="col-xl-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h4 class="card-title">Performa Branch</h4>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr><th>Branch</th><th>Outgoing</th><th>Incoming</th><th>Revenue Delivered</th></tr>
                </thead>
                <tbody>
                  @forelse ($branchPerformance as $row)
                    <tr>
                      <td>{{ $row->name }} ({{ $row->city }})</td>
                      <td>{{ number_format($row->outgoing_shipments) }}</td>
                      <td>{{ number_format($row->incoming_shipments) }}</td>
                      <td>Rp {{ number_format((float) ($row->outgoing_revenue ?? 0), 0, ',', '.') }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center">Belum ada data branch.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h4 class="card-title">Top Courier (Delivered)</h4>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr><th>Courier</th><th>Delivered</th><th>Active</th></tr>
                </thead>
                <tbody>
                  @forelse ($courierPerformance as $courier)
                    <tr>
                      <td>{{ $courier->name }}</td>
                      <td>{{ number_format($courier->delivered_shipments) }}</td>
                      <td>{{ number_format($courier->active_shipments) }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="3" class="text-center">Belum ada data courier.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row report-section-grid">
      <div class="col-xl-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h4 class="card-title mb-3">Shipment Butuh Follow Up</h4>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr><th>Resi</th><th>Rute</th><th>Kurir</th><th>Status</th><th>Tgl</th></tr>
                </thead>
                <tbody>
                  @forelse ($staleShipments as $shipment)
                    <tr>
                      <td>{{ $shipment->tracking_number }}</td>
                      <td>{{ $shipment->originBranch->city ?? '-' }} ke {{ $shipment->destinationBranch->city ?? '-' }}</td>
                      <td>{{ $shipment->courier->name ?? '-' }}</td>
                      <td>{{ \App\Models\Shipment::statusLabel($shipment->status) }}</td>
                      <td>{{ $shipment->shipment_date?->format('d M Y') }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="5" class="text-center">Tidak ada shipment stale.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h4 class="card-title mb-3">Exception Terbaru</h4>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr><th>Resi</th><th>Status</th><th>Lokasi</th><th>Waktu</th></tr>
                </thead>
                <tbody>
                  @forelse ($recentExceptionTrackings as $tracking)
                    <tr>
                      <td>{{ $tracking->shipment->tracking_number ?? '-' }}</td>
                      <td>{{ \App\Models\ShipmentTracking::statusLabel($tracking->status) }}</td>
                      <td>{{ $tracking->location }}</td>
                      <td>{{ $tracking->tracked_at?->format('d M Y H:i') }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center">Tidak ada exception pada periode ini.</td></tr>
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
@endsection
