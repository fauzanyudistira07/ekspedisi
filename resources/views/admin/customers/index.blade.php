@extends('be.master')

@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="card-title mb-0">Customers</h4>
        </div>

        <form method="GET" action="{{ route('customers.index') }}" class="mb-3">
          <div class="row">
            <div class="col-md-5 mb-2">
              <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama / email / telepon">
            </div>
            <div class="col-md-4 mb-2">
              <select name="city" class="form-control">
                <option value="">Semua Kota</option>
                @foreach($cities as $city)
                  <option value="{{ $city }}" {{ ($filters['city'] ?? '') === $city ? 'selected' : '' }}>{{ $city }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3 mb-2 d-flex" style="gap:8px;">
              <button type="submit" class="btn btn-outline-light btn-block">Filter</button>
              <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-dark table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Kota</th>
                <th>Telepon</th>
                <th>Shipment (Kirim/Terima)</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($customers as $customer)
                <tr>
                  <td>{{ $loop->iteration + ($customers->firstItem() ?? 0) - 1 }}</td>
                  <td>{{ $customer->name }}</td>
                  <td>{{ $customer->email }}</td>
                  <td>{{ $customer->city ?? '-' }}</td>
                  <td>{{ $customer->phone ?? '-' }}</td>
                  <td>{{ $customer->sent_shipments_count }} / {{ $customer->received_shipments_count }}</td>
                  <td>
                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">Edit</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center">Belum ada data.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">{{ $customers->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
