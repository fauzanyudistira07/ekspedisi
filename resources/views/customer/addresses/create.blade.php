@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex justify-content-between align-items-center">
            <h2 class="cp-section-title">Tambah Alamat Penerima</h2>
            <a href="{{ route('customer.addresses.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="cp-card-body">
            <form method="POST" action="{{ route('customer.addresses.store') }}" class="cp-form">
                @csrf
                @include('customer.addresses._form')
                <button type="submit" class="btn btn-primary">Simpan Alamat</button>
            </form>
        </div>
    </div>
</div>
@endsection
