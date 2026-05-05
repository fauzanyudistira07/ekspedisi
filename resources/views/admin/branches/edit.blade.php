@extends('be.master')

@section('content')
<div class="main-panel">
  <div class="content-wrapper">
    @include('admin.partials.alerts')

    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Edit Branch</h4>
        <form method="POST" action="{{ route('branches.update', $branch) }}">
          @csrf
          @method('PUT')
          @include('admin.branches._form')
          <button type="submit" class="btn btn-primary">Update</button>
          <a href="{{ route('branches.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
