@extends('be.master')

@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
          <h4 class="card-title mb-0">Manager Reports</h4>
          <a href="{{ route('manager.reports.export', request()->query()) }}" class="btn btn-success btn-sm">Export CSV</a>
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

    <div class="row">
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Total Shipment</h6><h3>{{ number_format($summary['total_shipments']) }}</h3></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Delivered</h6><h3>{{ number_format($summary['delivered']) }}</h3></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">In Transit</h6><h3>{{ number_format($summary['in_transit']) }}</h3></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Cancelled</h6><h3>{{ number_format($summary['cancelled']) }}</h3></div></div></div>
      <div class="col-xl-3 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Exception</h6><h3>{{ number_format($summary['exceptions']) }}</h3></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Total Paid</h6><h3>Rp {{ number_format($summary['paid_amount'], 0, ',', '.') }}</h3></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Waiting Payment</h6><h3>{{ number_format($summary['waiting_payment']) }}</h3></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Failed Payment</h6><h3>{{ number_format($summary['failed_payment']) }}</h3></div></div></div>
      <div class="col-xl-4 col-md-6 grid-margin stretch-card"><div class="card"><div class="card-body"><h6 class="text-muted">Manifest Dibuat</h6><h3>{{ number_format($summary['manifests']) }}</h3></div></div></div>
    </div>

    <div class="row">
      <div class="col-lg-7 grid-margin stretch-card">
        <div class="card">
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

      <div class="col-lg-5 grid-margin stretch-card">
        <div class="card">
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
  </div>
</div>
@endsection
