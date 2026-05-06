@extends('be.master')
@section('content')
<div class="main-panel"><div class="content-wrapper">
@include('admin.partials.alerts')
<div class="card border-0 shadow-sm"><div class="card-body">
<h4 class="card-title mb-3">Edit Manifest {{ $manifest->manifest_number }}</h4>
<form method="POST" action="{{ route('manifests.update', $manifest) }}">@csrf @method('PUT')
<div class="row">
<div class="col-md-4 mb-3"><label>No. Manifest</label><input type="text" name="manifest_number" class="form-control" value="{{ old('manifest_number', $manifest->manifest_number) }}" required></div>
<div class="col-md-4 mb-3"><label>Tipe Manifest</label><select name="manifest_type" class="form-control" required>@foreach($types as $type)<option value="{{ $type }}" {{ old('manifest_type', $manifest->manifest_type) === $type ? 'selected' : '' }}>{{ \App\Models\ShipmentManifest::typeLabel($type) }}</option>@endforeach</select></div>
<div class="col-md-4 mb-3"><label>Status</label><select name="status" class="form-control" required>@foreach($statuses as $status)<option value="{{ $status }}" {{ old('status', $manifest->status) === $status ? 'selected' : '' }}>{{ \App\Models\ShipmentManifest::statusLabel($status) }}</option>@endforeach</select></div>
<div class="col-md-4 mb-3"><label>Branch</label><select name="branch_id" class="form-control"><option value="">-</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" {{ (string) old('branch_id', $manifest->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach</select></div>
<div class="col-md-4 mb-3"><label>Kendaraan</label><select name="vehicle_id" class="form-control"><option value="">-</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" {{ (string) old('vehicle_id', $manifest->vehicle_id) === (string) $vehicle->id ? 'selected' : '' }}>{{ $vehicle->plate_number }}{{ $vehicle->courier ? ' - ' . $vehicle->courier->name : '' }}</option>@endforeach</select></div>
<div class="col-md-4 mb-3"><label>Kurir</label><select name="courier_id" class="form-control"><option value="">-</option>@foreach($couriers as $courier)<option value="{{ $courier->id }}" {{ (string) old('courier_id', $manifest->courier_id) === (string) $courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>@endforeach</select></div>
<div class="col-md-6 mb-3"><label>Departed At</label><input type="datetime-local" name="departed_at" class="form-control" value="{{ old('departed_at', $manifest->departed_at?->format('Y-m-d\\TH:i')) }}"></div>
<div class="col-md-6 mb-3"><label>Arrived At</label><input type="datetime-local" name="arrived_at" class="form-control" value="{{ old('arrived_at', $manifest->arrived_at?->format('Y-m-d\\TH:i')) }}"></div>
<div class="col-12 mb-3"><label>Catatan</label><textarea name="notes" class="form-control">{{ old('notes', $manifest->notes) }}</textarea></div>
<div class="col-12 mb-3"><label>Shipment yang Masuk Manifest</label><select name="shipment_ids[]" class="form-control" multiple size="10" required>@foreach($shipments as $shipment)<option value="{{ $shipment->id }}" {{ collect(old('shipment_ids', $manifest->shipments->pluck('id')->all()))->contains($shipment->id) ? 'selected' : '' }}>{{ $shipment->tracking_number }} | {{ $shipment->originBranch->city ?? '-' }} ke {{ $shipment->destinationBranch->city ?? '-' }} | {{ \App\Models\Shipment::statusLabel($shipment->status) }}</option>@endforeach</select></div>
</div>
<button type="submit" class="btn btn-primary">Update Manifest</button>
<a href="{{ route('manifests.show', $manifest) }}" class="btn btn-secondary">Kembali</a>
</form>
</div></div>
</div></div>
@endsection
