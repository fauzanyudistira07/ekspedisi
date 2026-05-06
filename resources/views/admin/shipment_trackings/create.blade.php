@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">@include('admin.partials.alerts')
<div class="card"><div class="card-body"><h4 class="card-title">{{ (Auth::user()->role ?? null) === \App\Models\User::ROLE_COURIER ? 'Tambah Update Tracking' : 'Create Tracking' }}</h4>
<form method="POST" action="{{ route('shipment-trackings.store') }}" enctype="multipart/form-data">@csrf
@include('admin.shipment_trackings._form')
<button type="submit" class="btn btn-primary">Simpan</button>
<a href="{{ route('shipment-trackings.index') }}" class="btn btn-secondary">Kembali</a>
</form></div></div></div></div>
@endsection
