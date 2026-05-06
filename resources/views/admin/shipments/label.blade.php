<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Label {{ $shipment->tracking_number }}</title>
    <style>
        @page {
            size: 150mm 100mm;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #102f4a;
            margin: 0;
            font-size: 11px;
            width: 100%;
        }
        .label-shell {
            width: 100%;
            border: 2px solid #102f4a;
            border-radius: 14px;
            padding: 16px 18px;
        }
        .topline {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: #5b6b7f;
            margin-bottom: 6px;
        }
        .brand {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .tracking {
            border: 1px solid #d7e2ef;
            background: #f8fbff;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .tracking-code {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: .03em;
            word-break: break-all;
            line-height: 1.15;
        }
        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            table-layout: fixed;
            margin-bottom: 10px;
        }
        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        .box {
            border: 1px solid #d7e2ef;
            border-radius: 12px;
            padding: 10px 12px;
            min-height: 86px;
        }
        .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #6b7a90;
            margin-bottom: 6px;
        }
        .value {
            font-size: 12px;
            line-height: 1.5;
            word-break: break-word;
        }
        .meta {
            margin-top: 4px;
            color: #6b7a90;
            font-size: 10px;
            word-break: break-word;
        }
        .barcode {
            border: 1px solid #d7e2ef;
            border-radius: 12px;
            padding: 10px 12px 8px;
            text-align: center;
            margin-bottom: 10px;
        }
        .barcode-line {
            margin: 0 auto 2px;
            font-size: 0;
            line-height: 0;
        }
        .bar {
            display: inline-block;
            width: 2px;
            margin-right: 1px;
            background: #111827;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #6b7a90;
        }
    </style>
</head>
<body>
    <div class="label-shell">
        <div class="topline">Label Resi Ekspedisi</div>
        <div class="brand">Ekspedisi Online</div>

        <div class="tracking">
            <div class="label">Nomor Resi</div>
            <div class="tracking-code">{{ $shipment->tracking_number }}</div>
            <div class="meta">{{ \App\Models\Shipment::statusLabel($shipment->status) }} | {{ $shipment->shipment_date?->format('d M Y') }}</div>
        </div>

        <table class="grid">
            <tr>
                <td>
                    <div class="box">
                        <div class="label">Pengirim</div>
                        <div class="value">{{ $shipment->sender->name ?? '-' }}</div>
                        <div class="meta">{{ $shipment->originBranch->name ?? '-' }}</div>
                        <div class="meta">{{ $shipment->sender->address ?? '-' }}</div>
                    </div>
                </td>
                <td>
                    <div class="box">
                        <div class="label">Penerima</div>
                        <div class="value">{{ $shipment->receiver->name ?? '-' }}</div>
                        <div class="meta">{{ $shipment->destinationBranch->name ?? '-' }}</div>
                        <div class="meta">{{ $shipment->receiver->address ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="grid">
            <tr>
                <td>
                    <div class="box">
                        <div class="label">Kurir / Layanan</div>
                        <div class="value">{{ $shipment->courier->name ?? '-' }}</div>
                        <div class="meta">Berat: {{ number_format((float) $shipment->total_weight, 2) }} kg</div>
                        <div class="meta">Item: {{ $shipment->items->count() }}</div>
                    </div>
                </td>
                <td>
                    <div class="box">
                        <div class="label">Rute</div>
                        <div class="value">{{ $shipment->originBranch->city ?? '-' }} ke {{ $shipment->destinationBranch->city ?? '-' }}</div>
                        <div class="meta">Ongkir: Rp {{ number_format((float) $shipment->total_price, 0, ',', '.') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="barcode">
            @foreach ($barcodeRows as $row)
                <div class="barcode-line">
                    @foreach (str_split($row) as $height)
                        <span class="bar" style="height: {{ ((int) $height * 8) }}px;"></span>
                    @endforeach
                </div>
            @endforeach
            <div class="meta">{{ $shipment->tracking_number }}</div>
        </div>

        <div class="footer">
            Label lokal siap print. Gunakan nomor resi untuk tracking dan proses operasional.
        </div>
    </div>
</body>
</html>
