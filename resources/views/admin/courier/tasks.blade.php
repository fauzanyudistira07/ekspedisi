@extends('be.master')

@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card mb-4 border-0 shadow-sm page-hero page-hero--courier">
      <div class="card-body py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:16px;">
          <div class="page-hero-copy">
            <div class="text-uppercase small mb-2 page-hero-eyebrow">Courier Workspace</div>
            <h4 class="mb-2 page-hero-title">Tugas pengantaran dan update perjalanan paket</h4>
            <p class="mb-0 page-hero-text">Kurir cukup fokus pada assignment, update lokasi, status paket, dan bukti serah terima ketika delivered.</p>
          </div>
          <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('shipments.index') }}" class="btn btn-warning text-dark">Shipment Saya</a>
            <a href="{{ route('shipment-trackings.index') }}" class="btn btn-outline-light">Riwayat Tracking</a>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
          <div>
            <h4 class="card-title mb-1">Task Kurir</h4>
            <div class="small text-muted">Cari assignment aktif lalu lakukan update status langsung dari halaman ini.</div>
          </div>
        </div>

        <form method="GET" action="{{ route('courier.tasks') }}">
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
            @if (!$isCourierView)
              <div class="col-md-3 mb-2">
                <select name="courier_id" class="form-control">
                  <option value="">Semua Courier</option>
                  @foreach($couriers as $courier)
                    <option value="{{ $courier->id }}" {{ (string)($filters['courier_id'] ?? '') === (string)$courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>
                  @endforeach
                </select>
              </div>
            @endif
            <div class="{{ $isCourierView ? 'col-md-5' : 'col-md-2' }} mb-2 d-flex" style="gap:8px;">
              <button type="submit" class="btn btn-outline-light btn-block">Filter</button>
              <a href="{{ route('courier.tasks') }}" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Assigned Total</h6><h3>{{ number_format($summary['assigned_total']) }}</h3><div class="small text-muted">Total paket yang pernah diassign</div></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Assigned Hari Ini</h6><h3>{{ number_format($summary['today_assigned']) }}</h3><div class="small text-muted">Task masuk pada hari ini</div></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Task Aktif</h6><h3>{{ number_format($summary['active']) }}</h3><div class="small text-muted">Paket yang masih harus diupdate</div></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card border-0 shadow-sm"><div class="card-body"><h6 class="text-muted dashboard-kpi-label">Delivered</h6><h3>{{ number_format($summary['delivered']) }}</h3><div class="small text-muted">Task yang sudah selesai</div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:12px;">
          <div>
            <h4 class="card-title mb-1">Assignment Aktif</h4>
            <div class="small text-muted">Setiap update akan langsung membuat tracking baru dan mengubah status shipment.</div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-dark table-striped">
            <thead>
              <tr>
                <th>Tracking</th>
                <th>Rute</th>
                <th>PIC</th>
                <th>Status</th>
                <th>Tracking Terakhir</th>
                <th>Update Cepat</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($shipments as $shipment)
                @php
                  $nextStatuses = \App\Models\Shipment::nextTrackingStatuses($shipment->status);
                  $lastTracking = $shipment->trackings->first();
                @endphp
                <tr>
                  <td>
                    <div class="font-weight-bold">{{ $shipment->tracking_number }}</div>
                    <small>{{ $shipment->shipment_date?->format('Y-m-d') }}</small>
                  </td>
                  <td>{{ $shipment->originBranch->city ?? '-' }} <i class="mdi mdi-arrow-right"></i> {{ $shipment->destinationBranch->city ?? '-' }}</td>
                  <td>
                    <div>Kurir: {{ $shipment->courier->name ?? '-' }}</div>
                    <small>Receiver: {{ $shipment->receiver->name ?? '-' }}</small>
                  </td>
                  <td><span class="badge badge-{{ $shipment->status === \App\Models\Shipment::STATUS_DELIVERED ? 'success' : ($shipment->status === \App\Models\Shipment::STATUS_PENDING ? 'warning' : 'primary') }}">{{ \App\Models\Shipment::statusLabel($shipment->status) }}</span></td>
                  <td>
                    @if ($lastTracking)
                      <div>{{ \App\Models\ShipmentTracking::statusLabel($lastTracking->status) }}</div>
                      <small>{{ $lastTracking->location }} - {{ $lastTracking->tracked_at?->format('Y-m-d H:i') }}</small>
                      <div class="mt-2">
                        <a href="{{ route('shipment-trackings.edit', $lastTracking) }}" class="btn btn-sm btn-outline-light">Edit Tracking Terakhir</a>
                      </div>
                    @else
                      <small>Belum ada tracking.</small>
                      <div class="mt-2">
                        <a href="{{ route('shipment-trackings.create', ['shipment_id' => $shipment->id]) }}" class="btn btn-sm btn-outline-light">Buat Tracking Pertama</a>
                      </div>
                    @endif
                  </td>
                  <td style="min-width:280px;">
                    @if (empty($nextStatuses))
                      <span class="text-success">Task selesai.</span>
                    @else
                      <form method="POST" action="{{ route('courier.tasks.update-status', $shipment) }}" enctype="multipart/form-data" class="courier-quick-form">
                        @csrf
                        @method('PATCH')
                        <div class="mb-1">
                          <select name="status" class="form-control form-control-sm js-courier-status" required>
                            @foreach($nextStatuses as $status)
                              <option value="{{ $status }}">{{ \App\Models\Shipment::statusLabel($status) }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="mb-1">
                          <input type="text" name="location" class="form-control form-control-sm" value="{{ old('location', $lastTracking->location ?? '') }}" placeholder="Lokasi update (contoh: Gudang Bandung)" required>
                        </div>
                        <div class="mb-1">
                          <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description', $lastTracking->description ?? '') }}" placeholder="Deskripsi singkat update">
                        </div>
                        <div class="courier-proof-fields" style="display:none;">
                          <div class="mb-1">
                            <input type="text" name="received_by" class="form-control form-control-sm" placeholder="Diterima oleh">
                          </div>
                          <div class="mb-1">
                            <input type="text" name="receiver_relation" class="form-control form-control-sm" placeholder="Hubungan penerima, contoh: keluarga / security">
                          </div>
                          <div class="mb-1">
                            <input type="file" name="proof_photo" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                          </div>
                          <small class="text-warning d-block mb-2">Bukti serah terima wajib saat status delivered.</small>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Update Status</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center">Tidak ada shipment task.</td></tr>
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

@push('scripts')
<script>
(function () {
  document.querySelectorAll('form').forEach(function (form) {
    const statusInput = form.querySelector('.js-courier-status');
    const proofFields = form.querySelector('.courier-proof-fields');

    if (!statusInput || !proofFields) {
      return;
    }

    function toggleProofFields() {
      proofFields.style.display = statusInput.value === 'delivered' ? '' : 'none';
    }

    statusInput.addEventListener('change', toggleProofFields);
    toggleProofFields();
  });
})();
</script>
@endpush
