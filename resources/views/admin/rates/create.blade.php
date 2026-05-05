@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">@include('admin.partials.alerts')
<div class="card"><div class="card-body"><h4 class="card-title">Create Rate</h4>
<form method="POST" action="{{ route('rates.store') }}">@csrf
@include('admin.rates._form')
<button type="submit" class="btn btn-primary">Simpan</button>
<a href="{{ route('rates.index') }}" class="btn btn-secondary">Kembali</a>
</form></div></div></div></div>
@endsection
