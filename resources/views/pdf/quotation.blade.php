<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penawaran - {{ $quotation->quotation_number }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.3;
        }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .header-table .logo-cell { width: 15%; vertical-align: middle; }
        .header-table .logo-cell img { max-width: 80px; }
        .header-table .company-info-cell { vertical-align: middle; padding-left: 20px; }
        .header-table .company-name { font-size: 26px; font-weight: bold; margin: 0; color: #222; }
        .header-table .company-tagline { font-size: 12px; margin: 2px 0 5px 0; font-weight: bold; color: #555; }
        .header-table .company-address { font-size: 10px; margin: 0; color: #444; }

        .header-divider { border-bottom: 3px double #333; margin-bottom: 25px; }

        .document-title { text-align: center; margin-bottom: 20px; }
        .document-title h1 { margin: 0; font-size: 22px; text-transform: uppercase; }
        .document-title p { margin: 0; }

        .details-table { width: 100%; margin-bottom: 25px; }
        .details-table td { vertical-align: top; padding: 2px 5px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .items-table th { background-color: #f2f2f2; }
        .items-table .text-right { text-align: right; }

        .totals-table { width: 45%; float: right; }
        .totals-table td { padding: 5px 8px; }
        .totals-table .label { font-weight: bold; }
        .totals-table .grand-total {
            font-weight: bold;
            font-size: 14px;
            background-color: #f2f2f2;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }

        .clearfix { clear: both; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @php($logoPath = public_path('assets/logo2.png'))
                @if(is_file($logoPath))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Nexicon Logo">
                @endif
            </td>
            <td class="company-info-cell">
                <p class="company-name">NEXICON</p>
                <p class="company-tagline">PT. NEXT GENERATION SOLUTIONS</p>
                <p class="company-address">
                    Jl. Lingkar Selatan Sengkol No. 18, Setu, Tangerang Selatan, Banten<br>
                    Telp. 083811003426, Website: www.nexicon.id<br>
                    Email: nexicon.id@gmail.com
                </p>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    <div class="document-title">
        <h1>Penawaran</h1>
        <p>No: {{ $quotation->quotation_number }}</p>
    </div>

    @php
        // Ambil relasi klien dengan aman (Mencegah Null Property Error)
        $client = $quotation->deal?->customer ?? $quotation->deal?->lead;

        $clientName    = $client?->name ?? 'Nama Klien Tidak Tersedia';
        $clientAddress = $client?->address ?? 'Alamat tidak tersedia';
        $clientEmail   = $client?->email ?? '';

        // Ambil pembuat penawaran (Menyesuaikan dengan Filament Schema)
        $creatorName = $quotation->internalPic?->full_name
                    ?? $quotation->createdBy?->full_name
                    ?? 'Tim Sales';
    @endphp

    <table class="details-table">
        <tr>
            <td style="width: 60%;">
                <strong>Ditujukan Kepada:</strong><br>
                <strong style="font-size: 12px;">{{ $clientName }}</strong><br>
                {{ $clientAddress }}<br>
                @if($clientEmail)
                    Email: {{ $clientEmail }}
                @endif
            </td>
            <td style="width: 40%;">
                <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d F Y') }}<br>
                <strong>Berlaku Hingga:</strong> {{ \Carbon\Carbon::parse($quotation->valid_until)->format('d F Y') }}<br>
                <strong>Dibuat Oleh:</strong> {{ $creatorName }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Item / Deskripsi</th>
                <th class="text-right">Jumlah</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($quotation->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->item_name }}</strong><br>
                        @if($item->item_code)
                            <small style="color: #666;">Kode: {{ $item->item_code }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->qty ?? $item->quantity ?? 1 }}</td>
                    <td class="text-right">IDR {{ number_format((float) ($item->unit_price ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">IDR {{ number_format((float) ($item->line_total ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada item dalam penawaran ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="text-right">IDR {{ number_format((float) ($quotation->subtotal ?? 0), 0, ',', '.') }}</td>
        </tr>

        @if((float) $quotation->discount_amount > 0)
        <tr>
            <td class="label">Diskon:</td>
            <td class="text-right" style="color: red;">
                - IDR {{ number_format((float) $quotation->discount_amount, 0, ',', '.') }}
            </td>
        </tr>
        @endif

        @if((float) $quotation->tax > 0)
        <tr>
            @php
                // Menghitung nominal pajak (Subtotal - Diskon) * (Tax / 100)
                $subtotalAfterDiscount = (float) $quotation->subtotal - (float) $quotation->discount_amount;
                $taxNominal = $subtotalAfterDiscount * ((float) $quotation->tax / 100);
            @endphp
            <td class="label">Pajak PPN ({{ $quotation->tax }}%):</td>
            <td class="text-right">IDR {{ number_format($taxNominal, 0, ',', '.') }}</td>
        </tr>
        @endif

        <tr class="grand-total">
            <td class="label">Grand Total:</td>
            <td class="text-right">IDR {{ number_format((float) ($quotation->grand_total ?? 0), 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="clearfix"></div>

    @if($quotation->notes)
    <div style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px;">
        <strong>Syarat & Ketentuan / Catatan:</strong>
        <p style="white-space: pre-wrap; margin-top: 5px; color: #555;">{{ $quotation->notes }}</p>
    </div>
    @endif

</body>
</html>
