<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Manager Report PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .header { margin-bottom: 18px; }
        .title { font-size: 24px; font-weight: 700; margin-bottom: 6px; }
        .subtitle { color: #475569; line-height: 1.5; }
        .meta { margin-top: 10px; font-size: 11px; color: #334155; }
        .grid { width: 100%; margin: 18px 0; }
        .grid td { width: 33.33%; vertical-align: top; padding: 0 8px 12px 0; }
        .card { border: 1px solid #dbe4f0; border-radius: 12px; padding: 12px; }
        .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 8px; }
        .value { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .muted { color: #64748b; font-size: 11px; }
        h2 { font-size: 16px; margin: 22px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dbe4f0; padding: 8px 10px; text-align: left; }
        th { background: #eff6ff; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #334155; }
        td { font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Manager Report</div>
        <div class="subtitle">Ringkasan exception, SLA, payment, dan performa branch pada periode terpilih.</div>
        <div class="meta">
            Periode: {{ $filters['date_from'] }} s/d {{ $filters['date_to'] }}<br>
            Branch: {{ $filters['branch_name'] }}
        </div>
    </div>

    <table class="grid">
        <tr>
            <td><div class="card"><div class="label">Total Shipment</div><div class="value">{{ number_format($summary['total_shipments']) }}</div><div class="muted">Seluruh shipment pada periode terpilih</div></div></td>
            <td><div class="card"><div class="label">Delivered</div><div class="value">{{ number_format($summary['delivered']) }}</div><div class="muted">Shipment yang selesai terkirim</div></div></td>
            <td><div class="card"><div class="label">In Transit</div><div class="value">{{ number_format($summary['in_transit']) }}</div><div class="muted">Shipment yang masih aktif</div></div></td>
        </tr>
        <tr>
            <td><div class="card"><div class="label">Exception</div><div class="value">{{ number_format($summary['exceptions']) }}</div><div class="muted">Gagal antar, hold, atau retur</div></div></td>
            <td><div class="card"><div class="label">Waiting Payment</div><div class="value">{{ number_format($summary['waiting_payment']) }}</div><div class="muted">Pembayaran masih pending</div></div></td>
            <td><div class="card"><div class="label">Total Paid</div><div class="value">Rp {{ number_format($summary['paid_amount'], 0, ',', '.') }}</div><div class="muted">Pembayaran berhasil dibayar</div></div></td>
        </tr>
    </table>

    <h2>Performa Branch</h2>
    <table>
        <thead>
            <tr>
                <th>Branch</th>
                <th>City</th>
                <th>Outgoing</th>
                <th>Incoming</th>
                <th>Revenue Delivered</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($branchPerformance as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->city }}</td>
                    <td>{{ number_format($row->outgoing_shipments) }}</td>
                    <td>{{ number_format($row->incoming_shipments) }}</td>
                    <td>Rp {{ number_format((float) ($row->outgoing_revenue ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Belum ada data branch.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
