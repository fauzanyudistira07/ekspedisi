<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resi {{ $shipment->tracking_number }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, #f4f7fb 0%, #e9eef6 100%);
            color: #102f4a;
        }

        .page {
            min-height: 100vh;
            padding: 20px 14px 32px;
        }

        .preview-actions {
            width: min(100%, 920px);
            margin: 0 auto 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .preview-actions a {
            text-decoration: none;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 700;
        }

        .preview-actions .primary {
            background: #102f4a;
            color: #fff;
        }

        .preview-actions .ghost {
            background: #fff;
            color: #102f4a;
            border: 1px solid #cdd8e6;
        }

        .label-card {
            width: min(100%, 920px);
            margin: 0 auto;
            background: #fff;
            border: 2px solid #102f4a;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 16px 40px rgba(16, 47, 74, 0.12);
        }

        .topline {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .24em;
            color: #5b6b7f;
            margin-bottom: 8px;
        }

        .brand {
            font-size: clamp(30px, 5vw, 42px);
            font-weight: 800;
            margin-bottom: 16px;
        }

        .tracking {
            border: 1px solid #d7e2ef;
            background: #f8fbff;
            border-radius: 18px;
            padding: 16px 18px;
            margin-bottom: 16px;
        }

        .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .18em;
            color: #6b7a90;
            margin-bottom: 8px;
        }

        .tracking-code {
            font-size: clamp(26px, 6vw, 44px);
            font-weight: 800;
            line-height: 1.08;
            overflow-wrap: anywhere;
            margin-bottom: 8px;
        }

        .meta,
        .footer {
            color: #6b7a90;
            font-size: 14px;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .box {
            border: 1px solid #d7e2ef;
            border-radius: 18px;
            padding: 14px 16px;
            min-height: 130px;
        }

        .value {
            font-size: 20px;
            line-height: 1.45;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .barcode {
            border: 1px solid #d7e2ef;
            border-radius: 18px;
            padding: 14px 12px 12px;
            text-align: center;
            margin-top: 12px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .barcode-line {
            margin: 0 auto 2px;
            font-size: 0;
            line-height: 0;
            white-space: nowrap;
        }

        .bar {
            display: inline-block;
            width: 2px;
            margin-right: 1px;
            background: #111827;
        }

        .footer {
            text-align: center;
        }

        @media (max-width: 640px) {
            .page {
                padding: 12px 10px 24px;
            }

            .label-card {
                border-radius: 18px;
                padding: 16px 14px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .box {
                min-height: 0;
            }

            .value {
                font-size: 18px;
            }

            .meta,
            .footer {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="preview-actions">
            <a href="{{ route('shipments.label', $shipment) }}" target="_blank" class="primary">Buka PDF Print</a>
            <a href="{{ route('shipments.show', $shipment) }}" class="ghost">Kembali ke Detail</a>
        </div>

        <div class="label-card">
            <div class="topline">Label Resi Ekspedisi</div>
            <div class="brand">Ekspedisi Online</div>

            <div class="tracking">
                <div class="label">Nomor Resi</div>
                <div class="tracking-code">{{ $shipment->tracking_number }}</div>
                <div class="meta">{{ \App\Models\Shipment::statusLabel($shipment->status) }} | {{ $shipment->shipment_date?->format('d M Y') }}</div>
            </div>

            <div class="info-grid">
                <div class="box">
                    <div class="label">Pengirim</div>
                    <div class="value">{{ $shipment->sender->name ?? '-' }}</div>
                    <div class="meta">{{ $shipment->originBranch->name ?? '-' }}</div>
                    <div class="meta">{{ $shipment->sender->address ?? '-' }}</div>
                </div>
                <div class="box">
                    <div class="label">Penerima</div>
                    <div class="value">{{ $shipment->receiver->name ?? '-' }}</div>
                    <div class="meta">{{ $shipment->destinationBranch->name ?? '-' }}</div>
                    <div class="meta">{{ $shipment->receiver->address ?? '-' }}</div>
                </div>
                <div class="box">
                    <div class="label">Kurir / Layanan</div>
                    <div class="value">{{ $shipment->courier->name ?? '-' }}</div>
                    <div class="meta">Berat: {{ number_format((float) $shipment->total_weight, 2) }} kg</div>
                    <div class="meta">Item: {{ $shipment->items->count() }}</div>
                </div>
                <div class="box">
                    <div class="label">Rute</div>
                    <div class="value">{{ $shipment->originBranch->city ?? '-' }} ke {{ $shipment->destinationBranch->city ?? '-' }}</div>
                    <div class="meta">Ongkir: Rp {{ number_format((float) $shipment->total_price, 0, ',', '.') }}</div>
                </div>
            </div>

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
                Preview resi responsif untuk layar mobile. Gunakan tombol PDF jika perlu print.
            </div>
        </div>
    </div>
</body>
</html>
