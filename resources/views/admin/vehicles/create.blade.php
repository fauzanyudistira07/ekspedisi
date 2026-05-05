@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">@include('admin.partials.alerts')
<div class="card"><div class="card-body"><h4 class="card-title">Create Vehicle</h4>
<form method="POST" action="{{ route('vehicles.store') }}">@csrf
@include('admin.vehicles._form')
<button type="submit" class="btn btn-primary">Simpan</button>
<a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Kembali</a>
</form></div></div></div></div>
@endsection
