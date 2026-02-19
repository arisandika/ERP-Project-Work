<!DOCTYPE html>
<html>
<head>
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <style>
        @page { margin: 20px 25px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .header-table .logo-cell { width: 15%; vertical-align: middle; }
        .header-table .logo-cell img { max-width: 80px; }
        .header-table .company-info-cell { vertical-align: middle; padding-left: 20px; }
        .header-table .company-name { font-size: 28px; font-weight: bold; margin: 0; }
        .header-table .company-tagline { font-size: 14px; margin: 0; }
        .header-table .company-address { font-size: 11px; margin: 5px 0 0 0; }

        .header-divider { border-bottom: 4px double #000; margin-bottom: 30px; }

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
        .totals-table .grand-total { font-weight: bold; font-size: 14px; background-color: #f2f2f2; border-top: 2px solid #333; border-bottom: 2px solid #333; }
    </style>
</head>
<body>

    <!-- Header Sesuai Gambar -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('assets/logo2.png') }}" alt="Nexicon Logo">
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

    <!-- Judul Dokumen -->
    <div class="document-title">
        <h1>Penawaran</h1>
        <p>No: {{ $quotation->quotation_number }}</p>
    </div>

    <!-- Detail Client dan Tanggal -->
    <table class="details-table">
        <tr>
            <td style="width: 60%;">
                <strong>Ditujukan Kepada:</strong><br>
                <strong>{{ $quotation->customer->name }}</strong><br>
                {{ $quotation->customer->address ?? 'Alamat tidak tersedia' }}<br>
                {{ $quotation->customer->email ?? '' }}
            </td>
            <td style="width: 40%;">
                <strong>Tanggal:</strong> {{ $quotation->quotation_date->format('d F Y') }}<br>
                <strong>Berlaku Hingga:</strong> {{ $quotation->valid_until->format('d F Y') }}<br>
                <strong>Dibuat Oleh:</strong> {{ $quotation->employee->full_name }}
            </td>
        </tr>
    </table>

    <!-- Tabel Item -->
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
            @foreach ($quotation->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->item_name }}</strong><br>
                        @if($item->item_code)<small>Kode: {{ $item->item_code }}</small>@endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Tabel Total -->
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="text-right">Rp {{ number_format($quotation->subtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Diskon ({{ $quotation->discount }}%):</td>
            <td class="text-right">- Rp {{ number_format($quotation->subtotal * ($quotation->discount / 100), 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Pajak ({{ $quotation->tax }}%):</td>
            <td class="text-right">Rp {{ number_format(($quotation->subtotal - ($quotation->subtotal * ($quotation->discount / 100))) * ($quotation->tax / 100), 0, ',', '.') }}</td>
        </tr>
        <tr class="grand-total">
            <td class="label">Grand Total:</td>
            <td class="text-right">Rp {{ number_format($quotation->grand_total, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <!-- Catatan Tambahan -->
    @if($quotation->notes)
    <div style="margin-top: 30px;">
        <strong>Catatan:</strong>
        <p style="white-space: pre-wrap;">{{ $quotation->notes }}</p>
    </div>
    @endif

</body>
</html>
