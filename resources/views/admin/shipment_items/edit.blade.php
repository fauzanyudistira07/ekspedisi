@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">@include('admin.partials.alerts')
<div class="card"><div class="card-body"><h4 class="card-title">Edit Shipment Item</h4>
<form method="POST" action="{{ route('shipment-items.update', $shipmentItem) }}" enctype="multipart/form-data">@csrf @method('PUT')
@include('admin.shipment_items._form')
<button type="submit" class="btn btn-primary">Update</button>
<a href="{{ route('shipment-items.index') }}" class="btn btn-secondary">Kembali</a>
</form></div></div></div></div>
@endsection
