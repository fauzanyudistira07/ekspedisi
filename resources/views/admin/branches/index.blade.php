@extends('be.master')

@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="card-title mb-0">Branches</h4>
          <a href="{{ route('branches.create') }}" class="btn btn-primary">Tambah Branch</a>
        </div>

        <div class="table-responsive">
          <table class="table table-dark table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>City</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($branches as $branch)
                <tr>
                  <td>{{ $loop->iteration + ($branches->firstItem() ?? 0) - 1 }}</td>
                  <td>{{ $branch->name }}</td>
                  <td>{{ $branch->city }}</td>
                  <td>{{ $branch->address }}</td>
                  <td>{{ $branch->phone }}</td>
                  <td>
                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form method="POST" action="{{ route('branches.destroy', $branch) }}" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center">Belum ada data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">{{ $branches->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
