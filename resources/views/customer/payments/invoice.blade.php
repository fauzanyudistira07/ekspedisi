<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { margin-bottom: 18px; }
        .title { font-size: 20px; font-weight: 700; color: #0f4c81; margin: 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .section { margin-top: 16px; }
        .grid td { border: none; padding: 2px 0; }
        .badge { display:inline-block; padding:4px 8px; border-radius:20px; background:#dcfce7; color:#166534; font-weight:700; }
        .footer { margin-top: 24px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">INVOICE PEMBAYARAN</h1>
        <p class="muted">Ekspedisi Online</p>
    </div>

    <table class="grid">
        <tr><td><strong>No. Resi</strong></td><td>{{ $shipment->tracking_number }}</td><td><strong>Tanggal Invoice</strong></td><td>{{ now()->format('d M Y') }}</td></tr>
        <tr><td><strong>Customer</strong></td><td>{{ $customer->name }}</td><td><strong>Status Pembayaran</strong></td><td><span class="badge">{{ strtoupper($payment->payment_status) }}</span></td></tr>
        <tr><td><strong>Pengirim</strong></td><td>{{ $shipment->sender->name ?? '-' }}</td><td><strong>Penerima</strong></td><td>{{ $shipment->receiver->name ?? '-' }}</td></tr>
        <tr><td><strong>Asal</strong></td><td>{{ $shipment->originBranch->name ?? '-' }} ({{ $shipment->originBranch->city ?? '-' }})</td><td><strong>Tujuan</strong></td><td>{{ $shipment->destinationBranch->name ?? '-' }} ({{ $shipment->destinationBranch->city ?? '-' }})</td></tr>
        <tr><td><strong>Gateway</strong></td><td>{{ strtoupper($payment->gateway_provider ?? $payment->payment_method) }}</td><td><strong>Order ID</strong></td><td>{{ $payment->gateway_order_id ?? '-' }}</td></tr>
    </table>

    <div class="section">
        <strong>Rincian Item</strong>
        <table>
            <thead>
                <tr>
                    <th>Nama Item</th>
                    <th>Qty</th>
                    <th>Berat/Item (kg)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shipment->items as $item)
                    <tr>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->weight, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;">Tidak ada item.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <strong>Rincian Pembayaran</strong>
        <table>
            <tr><th>Total Berat</th><td>{{ number_format($shipment->total_weight, 2) }} kg</td></tr>
            <tr><th>Metode Pembayaran</th><td>{{ strtoupper($payment->payment_channel ?? $payment->payment_method) }}</td></tr>
            <tr><th>Tanggal Pembayaran</th><td>{{ $payment->paid_at?->format('d M Y H:i') ?: '-' }}</td></tr>
            <tr><th>Total Bayar</th><td><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong></td></tr>
        </table>
    </div>

    <div class="footer">
        Dokumen ini dihasilkan otomatis oleh sistem Ekspedisi Online.
    </div>
</body>
</html>
