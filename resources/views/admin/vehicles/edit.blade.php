@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">@include('admin.partials.alerts')
<div class="card"><div class="card-body"><h4 class="card-title">Edit Vehicle</h4>
<form method="POST" action="{{ route('vehicles.update', $vehicle) }}">@csrf @method('PUT')
@include('admin.vehicles._form')
<button type="submit" class="btn btn-primary">Update</button>
<a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Kembali</a>
</form></div></div></div></div>
@endsection
