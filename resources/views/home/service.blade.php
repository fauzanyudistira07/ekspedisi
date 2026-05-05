@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4"><div class="cp-card-body"><h2 class="mb-2">Layanan Customer</h2><p class="cp-subtext mb-0">Layanan yang bisa langsung kamu gunakan dari portal customer.</p></div></div>

    <div class="row">
        <div class="col-md-6 col-lg-3 mb-3"><div class="cp-card h-100"><div class="cp-card-body"><h6>Buat Shipment</h6><p class="cp-subtext mb-0">Input data pengirim, penerima, item, cabang, dan jadwal kirim.</p></div></div></div>
        <div class="col-md-6 col-lg-3 mb-3"><div class="cp-card h-100"><div class="cp-card-body"><h6>Tracking Resi</h6><p class="cp-subtext mb-0">Pantau status dari pending sampai delivered.</p></div></div></div>
        <div class="col-md-6 col-lg-3 mb-3"><div class="cp-card h-100"><div class="cp-card-body"><h6>Pembayaran</h6><p class="cp-subtext mb-0">Bayar kiriman via cash, transfer, atau e-wallet.</p></div></div></div>
        <div class="col-md-6 col-lg-3 mb-3"><div class="cp-card h-100"><div class="cp-card-body"><h6>Riwayat</h6><p class="cp-subtext mb-0">Lihat semua pengiriman dan pembayaran yang pernah dibuat.</p></div></div></div>
    </div>
</div>
@endsection
