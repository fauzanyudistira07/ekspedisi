@extends('be.master')
@section('content')
@php
  $isCourierRole = Auth::user()?->role === \App\Models\User::ROLE_COURIER;
  $trackingPermissions = config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_trackings', []);
  $canReadTrackings = in_array('read', $trackingPermissions, true);
@endphp
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card mb-4 border-0 shadow-sm page-hero page-hero--blue">
      <div class="card-body py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:16px;">
          <div class="page-hero-copy">
            <div class="text-uppercase small mb-2 page-hero-eyebrow">Shipment Control</div>
            <h4 class="mb-2 page-hero-title">Monitoring pengiriman dari pickup sampai delivered</h4>
            <p class="mb-0 page-hero-text">Admin bisa langsung memantau pipeline pengiriman, kurir aktif, dan shipment yang masih butuh tindakan.</p>
          </div>
          <div class="d-flex flex-wrap" style="gap:8px;">
            @if (in_array('create', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipments', []), true))
              <a href="{{ route('shipments.create') }}" class="btn btn-warning text-dark">Tambah Shipment</a>
            @endif
            @if ($canReadTrackings)
              <a href="{{ route('shipment-trackings.index') }}" class="btn btn-outline-light">Tracking</a>
            @endif
            <a href="{{ route('payments.index') }}" class="btn btn-outline-light">Payment</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Total Shipment</div>
            <h3 class="mb-1">{{ number_format($summary['total'] ?? 0) }}</h3>
            <div class="small text-muted">Seluruh shipment yang terdaftar</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Menunggu Diproses</div>
            <h3 class="mb-1 text-warning">{{ number_format($summary['pending'] ?? 0) }}</h3>
            <div class="small text-muted">Perlu pickup atau penjadwalan awal</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Aktif di Lapangan</div>
            <h3 class="mb-1 text-primary">{{ number_format($summary['in_transit'] ?? 0) }}</h3>
            <div class="small text-muted">Pickup, transit, atau sedang diantar</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Delivered</div>
            <h3 class="mb-1 text-success">{{ number_format($summary['delivered'] ?? 0) }}</h3>
            <div class="small text-muted">Pengiriman yang sudah selesai</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Exception</div>
            <h3 class="mb-1 text-danger">{{ number_format($summary['exception'] ?? 0) }}</h3>
            <div class="small text-muted">Shipment hold, gagal antar, atau retur</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:12px;">
          <div>
            <h4 class="card-title mb-1">Daftar Shipment</h4>
            <div class="small text-muted">Gunakan filter untuk mencari resi, memantau status, atau melihat beban kurir.</div>
          </div>
          <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('shipments.index', ['status' => \App\Models\Shipment::STATUS_PENDING]) }}" class="btn btn-sm btn-outline-warning">Pending</a>
            <a href="{{ route('shipments.index', ['status' => \App\Models\Shipment::STATUS_IN_TRANSIT]) }}" class="btn btn-sm btn-outline-primary">In Transit</a>
            <a href="{{ route('shipments.index', ['status' => \App\Models\Shipment::STATUS_DELIVERED]) }}" class="btn btn-sm btn-outline-success">Delivered</a>
            <a href="{{ route('shipments.index', ['status' => \App\Models\Shipment::STATUS_EXCEPTION_HOLD]) }}" class="btn btn-sm btn-outline-danger">Exception</a>
          </div>
        </div>

        <form method="GET" action="{{ route('shipments.index') }}" class="mb-4">
          <div class="row">
            <div class="col-md-4 mb-2">
              <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari resi / pengirim / penerima">
            </div>
            <div class="col-md-3 mb-2">
              <select name="status" class="form-control">
                <option value="">Semua Status</option>
                @foreach($statuses as $status)
                  <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ \App\Models\Shipment::statusLabel($status) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3 mb-2">
              <select name="courier_id" class="form-control">
                <option value="">Semua Kurir</option>
                @foreach($couriers as $courier)
                  <option value="{{ $courier->id }}" {{ (string)($filters['courier_id'] ?? '') === (string)$courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 mb-2 d-flex" style="gap:8px;">
              <button type="submit" class="btn btn-outline-light btn-block">Filter</button>
              <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-dark table-striped align-middle">
            <thead>
              <tr>
                <th>Tracking</th>
                <th>Rute</th>
                <th>Pengirim / Penerima</th>
                <th>Kurir</th>
                <th>Status</th>
                <th>Berat</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($shipments as $shipment)
                @php
                  $statusTone = match ($shipment->status) {
                      \App\Models\Shipment::STATUS_DELIVERED => 'success',
                      \App\Models\Shipment::STATUS_CANCELLED => 'danger',
                      \App\Models\Shipment::STATUS_PENDING => 'warning',
                      default => 'primary',
                  };
                @endphp
                <tr>
                  <td>
                    <div class="font-weight-bold">{{ $shipment->tracking_number }}</div>
                    <div class="small text-muted">Rate: {{ $shipment->rate->origin_city ?? '-' }} ke {{ $shipment->rate->destination_city ?? '-' }}</div>
                  </td>
                  <td>
                    <div>{{ $shipment->originBranch->name ?? '-' }}</div>
                    <div class="small text-muted">{{ $shipment->destinationBranch->name ?? '-' }}</div>
                  </td>
                  <td>
                    <div>{{ $shipment->sender->name ?? '-' }}</div>
                    <div class="small text-muted">ke {{ $shipment->receiver->name ?? '-' }}</div>
                  </td>
                  <td>{{ $shipment->courier->name ?? '-' }}</td>
                  <td><span class="badge badge-{{ $statusTone }}">{{ \App\Models\Shipment::statusLabel($shipment->status) }}</span></td>
                  <td>{{ number_format((float) $shipment->total_weight, 2) }} kg</td>
                  <td>Rp {{ number_format((float) $shipment->total_price, 0, ',', '.') }}</td>
                  <td>{{ $shipment->shipment_date?->format('d M Y') }}</td>
                  <td>
                    <div class="d-flex flex-wrap" style="gap:6px;">
                      <a href="{{ route('shipments.show', $shipment) }}" class="btn btn-sm btn-info">Detail</a>
                      <a href="{{ route('shipments.label', $shipment) . ($isCourierRole ? '?preview=1' : '') }}" class="btn btn-sm btn-outline-light" target="_blank">{{ $isCourierRole ? 'Resi' : 'Label' }}</a>
                      @if (in_array('update', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipments', []), true))
                        <a href="{{ route('shipments.edit', $shipment) }}" class="btn btn-sm btn-warning">Edit</a>
                      @endif
                      @if (in_array('delete', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipments', []), true))
                        <form method="POST" action="{{ route('shipments.destroy', $shipment) }}" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center">Belum ada data shipment.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">{{ $shipments->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
