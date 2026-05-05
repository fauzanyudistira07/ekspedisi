@extends('be.master')

@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
          <h4 class="card-title mb-0">Courier Tasks</h4>
          <a href="{{ route('shipment-trackings.index') }}" class="btn btn-outline-light btn-sm">Riwayat Tracking</a>
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
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Assigned Total</h6><h3>{{ number_format($summary['assigned_total']) }}</h3></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Assigned Hari Ini</h6><h3>{{ number_format($summary['today_assigned']) }}</h3></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Task Aktif</h6><h3>{{ number_format($summary['active']) }}</h3></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Delivered</h6><h3>{{ number_format($summary['delivered']) }}</h3></div></div></div>
    </div>

    <div class="card">
      <div class="card-body">
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
                  <td>{{ \App\Models\Shipment::statusLabel($shipment->status) }}</td>
                  <td>
                    @if ($lastTracking)
                      <div>{{ \App\Models\ShipmentTracking::statusLabel($lastTracking->status) }}</div>
                      <small>{{ $lastTracking->location }} - {{ $lastTracking->tracked_at?->format('Y-m-d H:i') }}</small>
                    @else
                      <small>Belum ada tracking.</small>
                    @endif
                  </td>
                  <td style="min-width:280px;">
                    @if (empty($nextStatuses))
                      <span class="text-success">Task selesai.</span>
                    @else
                      <form method="POST" action="{{ route('courier.tasks.update-status', $shipment) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-1">
                          <select name="status" class="form-control form-control-sm" required>
                            @foreach($nextStatuses as $status)
                              <option value="{{ $status }}">{{ \App\Models\Shipment::statusLabel($status) }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="mb-1">
                          <input type="text" name="location" class="form-control form-control-sm" placeholder="Lokasi update (contoh: Gudang Bandung)" required>
                        </div>
                        <div class="mb-1">
                          <input type="text" name="description" class="form-control form-control-sm" placeholder="Deskripsi singkat update">
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
