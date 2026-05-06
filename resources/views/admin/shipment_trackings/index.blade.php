@extends('be.master')
@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card mb-4 border-0 shadow-sm page-hero page-hero--teal">
      <div class="card-body py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:16px;">
          <div class="page-hero-copy">
            <div class="text-uppercase small mb-2 page-hero-eyebrow">Tracking Desk</div>
            <h4 class="mb-2 page-hero-title">Pastikan update tracking berurutan dan punya bukti saat delivered</h4>
            <p class="mb-0 page-hero-text">Halaman ini dipakai untuk menjaga SLA operasional dan mengaudit proof of delivery dari kurir.</p>
          </div>
          <div class="d-flex flex-wrap" style="gap:8px;">
            @if (in_array('create', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_trackings', []), true))
              <a href="{{ route('shipment-trackings.create') }}" class="btn btn-warning text-dark">Tambah Tracking</a>
            @endif
            <a href="{{ route('shipments.index') }}" class="btn btn-outline-light">Shipment</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Total Tracking</div>
            <h3 class="mb-1">{{ number_format($summary['total'] ?? 0) }}</h3>
            <div class="small text-muted">Seluruh event tracking tersimpan</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Update Hari Ini</div>
            <h3 class="mb-1 text-primary">{{ number_format($summary['today'] ?? 0) }}</h3>
            <div class="small text-muted">Aktivitas tracking pada tanggal berjalan</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Status Delivered</div>
            <h3 class="mb-1 text-success">{{ number_format($summary['delivered'] ?? 0) }}</h3>
            <div class="small text-muted">Tracking akhir pengiriman selesai</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase dashboard-kpi-label">Dengan POD</div>
            <h3 class="mb-1 text-info">{{ number_format($summary['with_proof'] ?? 0) }}</h3>
            <div class="small text-muted">Foto bukti serah terima tersimpan</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:12px;">
          <div>
            <h4 class="card-title mb-1">Timeline Tracking</h4>
            <div class="small text-muted">Cari resi atau lokasi untuk audit pergerakan paket dan validasi bukti delivery.</div>
          </div>
          <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('shipment-trackings.index', ['status' => \App\Models\ShipmentTracking::STATUS_OUT_FOR_DELIVERY]) }}" class="btn btn-sm btn-outline-primary">Out for Delivery</a>
            <a href="{{ route('shipment-trackings.index', ['status' => \App\Models\ShipmentTracking::STATUS_DELIVERED]) }}" class="btn btn-sm btn-outline-success">Delivered</a>
          </div>
        </div>

        <form method="GET" action="{{ route('shipment-trackings.index') }}" class="mb-4">
          <div class="row">
            <div class="col-md-5 mb-2">
              <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari resi / lokasi">
            </div>
            <div class="col-md-4 mb-2">
              <select name="status" class="form-control">
                <option value="">Semua Status</option>
                @foreach($statuses as $status)
                  <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ \App\Models\ShipmentTracking::statusLabel($status) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3 mb-2 d-flex" style="gap:8px;">
              <button type="submit" class="btn btn-outline-light btn-block">Filter</button>
              <a href="{{ route('shipment-trackings.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-dark table-striped align-middle">
            <thead>
              <tr>
                <th>Shipment</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Deskripsi</th>
                <th>Proof of Delivery</th>
                <th>Tracked At</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($trackings as $tracking)
                @php
                  $statusTone = $tracking->status === \App\Models\ShipmentTracking::STATUS_DELIVERED ? 'success' : ($tracking->status === \App\Models\ShipmentTracking::STATUS_OUT_FOR_DELIVERY ? 'warning' : 'primary');
                @endphp
                <tr>
                  <td>
                    <div class="font-weight-bold">{{ $tracking->shipment->tracking_number ?? '-' }}</div>
                    <div class="small text-muted">{{ $tracking->shipment->status ? \App\Models\Shipment::statusLabel($tracking->shipment->status) : '-' }}</div>
                  </td>
                  <td>{{ $tracking->location }}</td>
                  <td><span class="badge badge-{{ $statusTone }}">{{ \App\Models\ShipmentTracking::statusLabel($tracking->status) }}</span></td>
                  <td>{{ $tracking->description ?: '-' }}</td>
                  <td>
                    @if ($tracking->proofPhotoExists())
                      <div class="font-weight-bold text-success">Bukti tersedia</div>
                      <div class="small text-muted">{{ $tracking->received_by ?: 'Penerima tidak dicatat' }}</div>
                      <div class="mt-2">
                        <img src="{{ $tracking->proofPhotoUrl() }}" alt="POD {{ $tracking->shipment->tracking_number ?? '' }}" style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,.15);">
                      </div>
                      <div class="mt-2">
                        <a href="{{ $tracking->proofPhotoUrl() }}" target="_blank" class="btn btn-sm btn-info">Lihat Foto</a>
                      </div>
                    @elseif ($tracking->proof_photo)
                      <span class="text-warning">File bukti tidak ditemukan</span>
                    @else
                      <span class="text-muted">Belum ada bukti</span>
                    @endif
                  </td>
                  <td>{{ $tracking->tracked_at?->format('d M Y H:i') }}</td>
                  <td>
                    <div class="d-flex flex-wrap" style="gap:6px;">
                      @if (in_array('update', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_trackings', []), true))
                        <a href="{{ route('shipment-trackings.edit', $tracking) }}" class="btn btn-sm btn-warning">Edit</a>
                      @endif
                      @if (in_array('delete', config('role_feature_matrix.roles.' . (Auth::user()->role ?? '') . '.tables.shipment_trackings', []), true))
                        <form method="POST" action="{{ route('shipment-trackings.destroy', $tracking) }}" class="d-inline">
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
                  <td colspan="7" class="text-center">Belum ada data tracking.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">{{ $trackings->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
