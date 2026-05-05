@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
            <h2 class="cp-section-title">Riwayat Shipment</h2>
            <a href="{{ route('customer.shipments.create') }}" class="btn btn-primary">+ Buat Shipment</a>
        </div>
        <div class="cp-card-body">
            <form method="GET" action="{{ route('customer.shipments.index') }}" class="cp-form">
                <div class="row">
                    <div class="col-md-5 mb-2">
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cari nomor resi / nama pengirim / penerima">
                    </div>
                    <div class="col-md-4 mb-2">
                        <select name="status" class="custom-select">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ \App\Models\Shipment::statusLabel($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex" style="gap:8px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="{{ route('customer.shipments.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="cp-card d-none d-lg-block">
        <div class="cp-card-body p-0">
            <div class="table-responsive">
                <table class="table cp-table mb-0">
                    <thead>
                        <tr>
                            <th>Resi</th>
                            <th>Relasi</th>
                            <th>Rute</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shipments as $shipment)
                            @php
                                $isSender = $shipment->sender_id === Auth::guard('customer')->id();
                                $paymentStatus = $shipment->payment?->payment_status ?? 'unpaid';
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $shipment->tracking_number }}</div>
                                    <div class="cp-muted-small">{{ $shipment->shipment_date?->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <div class="cp-muted-small">{{ $isSender ? 'Anda sebagai Pengirim' : 'Anda sebagai Penerima' }}</div>
                                    <div>{{ $isSender ? ($shipment->receiver->name ?? '-') : ($shipment->sender->name ?? '-') }}</div>
                                </td>
                                <td>{{ $shipment->originBranch->city ?? '-' }} <i class="fa fa-arrow-right mx-1"></i> {{ $shipment->destinationBranch->city ?? '-' }}</td>
                                <td><span class="cp-badge {{ $shipment->status }}">{{ \App\Models\Shipment::statusLabel($shipment->status) }}</span></td>
                                <td>Rp {{ number_format($shipment->total_price, 0, ',', '.') }}</td>
                                <td>
                                    @if ($paymentStatus === 'unpaid')
                                        <span class="cp-badge failed">BELUM DIBAYAR</span>
                                    @else
                                        <span class="cp-badge {{ $paymentStatus }}">{{ \App\Models\Payment::statusLabel($paymentStatus) }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('customer.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4">Belum ada shipment yang cocok dengan filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-lg-none">
        @forelse ($shipments as $shipment)
            @php
                $isSender = $shipment->sender_id === Auth::guard('customer')->id();
                $paymentStatus = $shipment->payment?->payment_status ?? 'unpaid';
            @endphp
            <div class="cp-mobile-card">
                <div class="d-flex justify-content-between align-items-start mb-2" style="gap:10px;">
                    <div>
                        <div class="font-weight-bold">{{ $shipment->tracking_number }}</div>
                        <div class="cp-muted-small">{{ $shipment->shipment_date?->format('d M Y') }}</div>
                    </div>
                    <span class="cp-badge {{ $shipment->status }}">{{ \App\Models\Shipment::statusLabel($shipment->status) }}</span>
                </div>
                <div class="cp-mobile-kv"><span>Relasi</span><strong>{{ $isSender ? 'Pengirim' : 'Penerima' }}</strong></div>
                <div class="cp-mobile-kv"><span>Kontak Terkait</span><strong>{{ $isSender ? ($shipment->receiver->name ?? '-') : ($shipment->sender->name ?? '-') }}</strong></div>
                <div class="cp-mobile-kv"><span>Rute</span><strong>{{ $shipment->originBranch->city ?? '-' }} → {{ $shipment->destinationBranch->city ?? '-' }}</strong></div>
                <div class="cp-mobile-kv"><span>Total</span><strong>Rp {{ number_format($shipment->total_price, 0, ',', '.') }}</strong></div>
                <div class="cp-mobile-kv"><span>Pembayaran</span>
                    @if ($paymentStatus === 'unpaid')
                        <span class="cp-badge failed">BELUM DIBAYAR</span>
                    @else
                        <span class="cp-badge {{ $paymentStatus }}">{{ \App\Models\Payment::statusLabel($paymentStatus) }}</span>
                    @endif
                </div>
                <a href="{{ route('customer.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-primary btn-block mt-2">Lihat Detail</a>
            </div>
        @empty
            <div class="cp-card">
                <div class="cp-card-body text-center py-4">Belum ada shipment yang cocok dengan filter.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $shipments->links() }}</div>
</div>
@endsection
