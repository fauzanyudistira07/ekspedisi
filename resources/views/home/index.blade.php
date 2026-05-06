@extends('fe.master')

@section('content')
<div class="container mb-4">
    <div class="cp-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>Selamat Datang, {{ Auth::guard('customer')->user()->name }}</h1>
                <p class="mb-4">Semua kebutuhan pengiriman ada di satu tempat: buat shipment, bayar, dan pantau tracking secara real-time.</p>
                <div class="d-flex flex-wrap" style="gap:10px;">
                    <a href="{{ route('customer.shipments.create') }}" class="btn btn-warning text-dark font-weight-bold">Buat Shipment</a>
                    <a href="{{ route('customer.payments.create') }}" class="btn btn-light">Bayar via Midtrans</a>
                </div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <form method="POST" action="{{ route('track.search') }}" class="bg-white rounded p-3 text-dark">
                    @csrf
                    <label class="font-weight-bold mb-2">Cek Resi Cepat</label>
                    <input type="text" name="tracking_number" class="form-control mb-2" placeholder="Contoh: EXP20260501AB12CD" required>
                    <button type="submit" class="btn btn-primary btn-block">Track Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mb-4">
    <div class="row">
        <div class="col-6 col-lg-3 mb-3">
            <div class="cp-stat">
                <span class="cp-muted-small">Total Shipment</span>
                <span class="value">{{ $stats['total'] }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
            <div class="cp-stat">
                <span class="cp-muted-small">Pending</span>
                <span class="value">{{ $stats['pending'] }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
            <div class="cp-stat">
                <span class="cp-muted-small">Dalam Perjalanan</span>
                <span class="value">{{ $stats['in_transit'] }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
            <div class="cp-stat">
                <span class="cp-muted-small">Pending Pembayaran</span>
                <span class="value">{{ $stats['need_payment'] }}</span>
            </div>
        </div>
        <div class="col-12 col-lg-3 mb-3">
            <div class="cp-stat">
                <span class="cp-muted-small">Address Book Tersimpan</span>
                <span class="value">{{ $stats['address_book'] }}</span>
            </div>
        </div>
    </div>
</div>

<div class="container mb-4">
    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="cp-card h-100">
                <div class="cp-card-header">
                    <h3 class="cp-section-title">Alur Pengiriman Customer</h3>
                </div>
                <div class="cp-card-body">
                    <ol class="cp-flow">
                        @foreach ($workflow as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>

                    <div class="cp-action-list mt-3">
                        @foreach ($quickActions as $action)
                            <div class="cp-action-item {{ $action['highlight'] ? 'highlight' : '' }}">
                                <h6 class="mb-1">{{ $action['title'] }}</h6>
                                <p class="mb-2 cp-muted-small">{{ $action['desc'] }}</p>
                                <a href="{{ $action['route'] }}" class="btn btn-sm btn-outline-primary">{{ $action['button'] }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="cp-card h-100">
                <div class="cp-card-header d-flex justify-content-between align-items-center">
                    <h3 class="cp-section-title">Shipment Terbaru</h3>
                    <a href="{{ route('customer.shipments.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="cp-card-body p-0">
                    <div class="table-responsive">
                        <table class="table cp-table mb-0">
                            <thead>
                                <tr>
                                    <th>Resi</th>
                                    <th>Penerima</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentShipments as $shipment)
                                    <tr>
                                        <td>{{ $shipment->tracking_number }}</td>
                                        <td>{{ $shipment->receiver->name ?? '-' }}</td>
                                        <td><span class="cp-badge {{ $shipment->status }}">{{ \App\Models\Shipment::statusLabel($shipment->status) }}</span></td>
                                        <td>{{ $shipment->shipment_date?->format('d M Y') }}</td>
                                        <td><a href="{{ route('customer.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-4">Belum ada shipment. Mulai dengan klik "Buat Shipment".</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
