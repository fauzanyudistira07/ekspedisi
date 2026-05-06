@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
            <div>
                <h2 class="cp-section-title mb-1">Detail Shipment</h2>
                <div class="cp-muted-small">Nomor Resi: <strong>{{ $shipment->tracking_number }}</strong></div>
            </div>
            <a href="{{ route('customer.shipments.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="cp-card-body">
            @if ($shipment->status === \App\Models\Shipment::STATUS_CANCELLED)
                <div class="alert alert-danger border-0">Shipment ini dibatalkan.</div>
            @else
                <div class="cp-stepper" style="grid-template-columns: repeat({{ count($statusFlow) }}, minmax(0,1fr));">
                    @foreach ($statusFlow as $index => $flowStatus)
                        @php($active = $currentStep >= ($index + 1))
                        <div class="cp-step" style="background:{{ $active ? '#dbeafe' : '#eef4fb' }}; border-color:{{ $active ? '#93c5fd' : '#dbe4f0' }}; font-weight:{{ $active ? '600' : '500' }};">
                            {{ \App\Models\Shipment::statusLabel($flowStatus) }}
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="row mt-3">
                <div class="col-md-6 mb-3">
                    <h6>Informasi Pengiriman</h6>
                    <p class="mb-1"><strong>Status:</strong> <span class="cp-badge {{ $shipment->status }}">{{ \App\Models\Shipment::statusLabel($shipment->status) }}</span></p>
                    <p class="mb-1"><strong>Tanggal Kirim:</strong> {{ $shipment->shipment_date?->format('d M Y') }}</p>
                    <p class="mb-1"><strong>Total Berat:</strong> {{ number_format($shipment->total_weight, 2) }} kg</p>
                    <p class="mb-0"><strong>Total Ongkir:</strong> Rp {{ number_format($shipment->total_price, 0, ',', '.') }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <h6>Rute dan PIC</h6>
                    <p class="mb-1"><strong>Asal:</strong> {{ $shipment->originBranch->name ?? '-' }} ({{ $shipment->originBranch->city ?? '-' }})</p>
                    <p class="mb-1"><strong>Tujuan:</strong> {{ $shipment->destinationBranch->name ?? '-' }} ({{ $shipment->destinationBranch->city ?? '-' }})</p>
                    <p class="mb-1"><strong>Kurir:</strong> {{ $shipment->courier->name ?? '-' }}</p>
                    <p class="mb-0"><strong>Penerima:</strong> {{ $shipment->receiver->name ?? '-' }} - {{ $shipment->receiver->phone ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="cp-card h-100">
                <div class="cp-card-header"><h3 class="cp-section-title">Item Kiriman</h3></div>
                <div class="cp-card-body p-0">
                    <div class="table-responsive">
                        <table class="table cp-table mb-0">
                            <thead><tr><th>Nama Item</th><th>Qty</th><th>Berat</th></tr></thead>
                            <tbody>
                                @forelse ($shipment->items as $item)
                                    <tr>
                                        <td>{{ $item->item_name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->weight, 2) }} kg</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-4">Belum ada item.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="cp-card h-100">
                <div class="cp-card-header"><h3 class="cp-section-title">Timeline Tracking</h3></div>
                <div class="cp-card-body">
                    <ul class="cp-timeline">
                        @forelse ($shipment->trackings->sortByDesc('tracked_at') as $tracking)
                            <li>
                                <div class="d-flex flex-wrap justify-content-between" style="gap:8px;">
                                    <strong>{{ \App\Models\Shipment::statusLabel($tracking->status) }}</strong>
                                    <span class="cp-muted-small">{{ $tracking->tracked_at?->format('d M Y H:i') }}</span>
                                </div>
                                <div>{{ $tracking->location }}</div>
                                <div class="cp-muted-small">{{ $tracking->description ?: 'Update status pengiriman.' }}</div>
                                @if ($tracking->received_by)
                                    <div class="cp-muted-small">Diterima oleh: <strong>{{ $tracking->received_by }}</strong>{{ $tracking->receiver_relation ? ' (' . $tracking->receiver_relation . ')' : '' }}</div>
                                @endif
                                @if ($tracking->proofPhotoExists())
                                    <div class="mt-2">
                                        <img src="{{ $tracking->proofPhotoUrl() }}" alt="Bukti serah terima {{ $shipment->tracking_number }}" style="width:96px;height:96px;object-fit:cover;border-radius:10px;border:1px solid #dbe4f0;">
                                    </div>
                                    <a href="{{ $tracking->proofPhotoUrl() }}" target="_blank" class="btn btn-sm btn-outline-info mt-2">Lihat Bukti Serah Terima</a>
                                @elseif ($tracking->proof_photo)
                                    <div class="small text-warning mt-2">File bukti tidak ditemukan.</div>
                                @endif
                            </li>
                        @empty
                            <li>
                                <strong>Belum ada tracking</strong>
                                <div class="cp-muted-small">Tracking akan muncul saat shipment diproses.</div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="cp-card mb-4">
        <div class="cp-card-header"><h3 class="cp-section-title">Pembayaran</h3></div>
        <div class="cp-card-body">
            @if ($shipment->payment)
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-3 mb-2"><span class="cp-muted-small d-block">Nominal</span><strong>Rp {{ number_format($shipment->payment->amount, 0, ',', '.') }}</strong></div>
                            <div class="col-md-3 mb-2"><span class="cp-muted-small d-block">Gateway</span><strong>{{ strtoupper($shipment->payment->gateway_provider ?? $shipment->payment->payment_method) }}</strong></div>
                            <div class="col-md-3 mb-2"><span class="cp-muted-small d-block">Status</span><span class="cp-badge {{ $shipment->payment->payment_status }}">{{ \App\Models\Payment::statusLabel($shipment->payment->payment_status) }}</span></div>
                            <div class="col-md-3 mb-2"><span class="cp-muted-small d-block">Paid At</span><strong>{{ $shipment->payment->paid_at?->format('d M Y H:i') ?: '-' }}</strong></div>
                            <div class="col-md-6 mb-2"><span class="cp-muted-small d-block">Order ID</span><strong>{{ $shipment->payment->gateway_order_id ?: '-' }}</strong></div>
                            <div class="col-md-6 mb-2"><span class="cp-muted-small d-block">Channel</span><strong>{{ strtoupper($shipment->payment->payment_channel ?? '-') }}</strong></div>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-right mt-2 mt-md-0">
                        @if ($shipment->sender_id === Auth::guard('customer')->id())
                            @if ($shipment->payment->payment_status === \App\Models\Payment::STATUS_PENDING && $shipment->payment->snap_token)
                                <a href="{{ route('customer.payments.checkout', $shipment->payment) }}" class="btn btn-outline-primary btn-sm">Lanjut Bayar</a>
                            @endif
                            <a href="{{ route('customer.payments.invoice', $shipment->payment) }}" class="btn btn-outline-primary btn-sm">Invoice PDF</a>
                        @endif
                    </div>
                </div>
            @else
                <p class="mb-3">Belum ada pembayaran untuk shipment ini.</p>
                @if ($shipment->sender_id === Auth::guard('customer')->id())
                    <a href="{{ route('customer.payments.create') }}" class="btn btn-primary">Buat Pembayaran</a>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
