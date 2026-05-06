@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
                <h2 class="mb-0">Track Shipment</h2>
                @guest('customer')
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Login</a>
                @endguest
            </div>
            <p class="cp-subtext mt-2 mb-3">Masukkan nomor resi untuk melihat status dan timeline pengiriman.</p>

            <form method="POST" action="{{ route('track.search') }}" class="cp-form">
                @csrf
                <div class="input-group">
                    <input type="text" name="tracking_number" class="form-control" placeholder="Masukkan nomor resi" value="{{ $searchedTrackingNumber ?? '' }}" required>
                    <div class="input-group-append"><button class="btn btn-primary" type="submit">Cari</button></div>
                </div>
            </form>
        </div>
    </div>

    @if (isset($searchedTrackingNumber) && !$shipment)
        <div class="alert alert-warning border-0">Nomor resi <strong>{{ $searchedTrackingNumber }}</strong> tidak ditemukan.</div>
    @endif

    @if ($shipment)
        <div class="cp-card mb-3">
            <div class="cp-card-body">
                <div class="row">
                    <div class="col-md-4 mb-2"><span class="cp-muted-small d-block">No. Resi</span><strong>{{ $shipment->tracking_number }}</strong></div>
                    <div class="col-md-4 mb-2"><span class="cp-muted-small d-block">Status</span><span class="cp-badge {{ $shipment->status }}">{{ \App\Models\Shipment::statusLabel($shipment->status) }}</span></div>
                    <div class="col-md-4 mb-2"><span class="cp-muted-small d-block">Penerima</span><strong>{{ $shipment->receiver->name ?? '-' }}</strong></div>
                </div>
            </div>
        </div>

        <div class="cp-card">
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
                            <strong>Belum ada data tracking</strong>
                            <div class="cp-muted-small">Data tracking belum tersedia.</div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
