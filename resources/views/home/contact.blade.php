@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-body">
            <h2 class="mb-3">Bantuan Customer</h2>
            <p class="cp-subtext mb-4">Jika ada kendala pengiriman, pembayaran, atau tracking, tim support siap membantu.</p>
            <div class="row">
                <div class="col-md-4 mb-3"><div class="cp-info-box"><strong>Hotline</strong><br>+62 21 0000 0000</div></div>
                <div class="col-md-4 mb-3"><div class="cp-info-box"><strong>Email</strong><br>support@ekspedisi.test</div></div>
                <div class="col-md-4 mb-3"><div class="cp-info-box"><strong>Jam Layanan</strong><br>24 Jam Setiap Hari</div></div>
            </div>
            <div class="mt-3">
                <a href="{{ route('track.index') }}" class="btn btn-primary mr-2">Track Shipment</a>
                <a href="{{ route('customer.shipments.create') }}" class="btn btn-outline-primary">Buat Shipment</a>
            </div>
        </div>
    </div>
</div>
@endsection
