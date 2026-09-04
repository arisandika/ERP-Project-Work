<!DOCTYPE html>
<html>

<head>
    <title>Laporan Stok Product - {{ now()->format('d F Y') }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #374151;
            line-height: 1.3;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .header-table .logo-cell {
            width: 15%;
            vertical-align: middle;
        }

        .header-table .logo-cell img {
            max-width: 80px;
            height: auto;
        }

        .header-table .company-info-cell {
            vertical-align: middle;
            padding-left: 20px;
        }

        .header-table .company-name {
            font-size: 26px;
            font-weight: bold;
            margin: 0;
            color: #111827;
        }

        .header-table .company-tagline {
            font-size: 12px;
            margin: 2px 0 5px 0;
            font-weight: bold;
            color: #4b5563;
        }

        .header-table .company-address {
            font-size: 10px;
            margin: 0;
            color: #4b5563;
        }

        .header-divider {
            border-bottom: 3px double #1f2937;
            margin-bottom: 25px;
        }

        .document-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .document-title h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 3px;
            font-weight: 800;
            text-transform: uppercase;
            color: #111827;
        }

        .document-title p {
            margin: 5px 0 0;
            font-size: 13px;
            font-weight: bold;
        }

        .status-badge {
            font-size: 9px;
            padding: 4px 10px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            display: inline-block;
            margin-top: 8px;
            letter-spacing: 1px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            color: #111827;
        }

        .items-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        .items-table .text-center {
            text-align: center;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .row-bg {
            background-color: #f9fafb;
        }

        .stock-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            color: #fff;
        }

        .stock-success {
            background: #16a34a;
        }

        .stock-warning {
            background: #d97706;
        }

        .stock-danger {
            background: #dc2626;
        }

        .notes-section {
            margin-top: 30px;
            font-size: 10px;
            color: #4b5563;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-style: italic;
        }

        /* Tambahan styling untuk detail valuasi */
        .summary-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }

        .summary-table th, .summary-table td {
            padding: 6px;
            border: 1px solid #d1d5db;
        }

        .summary-table th {
            background-color: #f3f4f6;
            text-align: left;
            color: #111827;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @php($logoPath = public_path('assets/logo2.png'))
                @if(is_file($logoPath))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo">
                @endif
            </td>
            <td class="company-info-cell">
                <h2 class="company-name">NEXICON</h2>
                <p class="company-tagline">PT. NEXT GENERATION SOLUTIONS</p>
                <p class="company-address">
                    Jl. Lingkar Selatan Sengkol No. 18, Setu, Tangerang Selatan<br>
                    WA: 0838-1100-3426 | Email: nexicon.id@gmail.com<br>
                    Website: www.nexicon.id
                </p>
            </td>
        </tr>
    </table>

    <div class="header-divider"></div>

    <div class="document-title">
        <h1>LAPORAN VALUASI STOK PRODUCT</h1>
        <p>Per {{ now()->format('d F Y H:i') }}</p>
        <span class="status-badge">INVENTORY AUDIT REPORT</span>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;" class="text-center">No</th>
                <th style="width: 13%;">Kode Product</th>
                <th style="width: 22%;">Nama Product</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 10%;" class="text-center">Stok Fisik</th>
                <th style="width: 8%;" class="text-center">Satuan</th>
                <th style="width: 15%;" class="text-right">Harga Satuan</th>
                <th style="width: 15%;" class="text-right">Valuasi Stok</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotalValuation = 0; @endphp
            @foreach ($products as $index => $product)
                @php
                    // REVISI ARSITEKTUR: Menghitung total fisik dari ke-3 kolom
                    $available = $product->productStocks->sum('qty_available');
                    $reserved  = $product->productStocks->sum('qty_reserved');
                    $delivery  = $product->productStocks->sum('qty_on_delivery');

                    $totalPhysicalStock = $available + $reserved + $delivery;

                    // REVISI: Logika warna tetap menggunakan stok siap jual (available)
                    $stockClass = $available <= 0 ? 'stock-danger' :
                        ($available <= 10 ? 'stock-warning' : 'stock-success');

                    // Prioritaskan harga beli (purchase_price) untuk laporan valuasi aset
                    $price = $product->purchase_price ?? $product->selling_price ?? $product->price ?? 0;

                    $totalValuation = $totalPhysicalStock * $price;
                    $grandTotalValuation += $totalValuation;
                @endphp
                <tr class="{{ $index % 2 ? 'row-bg' : '' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->product_code }}</td>
                    <td>
                        <strong>{{ $product->product_name }}</strong>
                    </td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td class="text-center">
                        <span class="stock-badge {{ $stockClass }}">
                            {{ $totalPhysicalStock }}
                        </span>
                    </td>
                    <td class="text-center">
                        {{ $product->unit->symbol ?? $product->unit->name ?? '-' }}
                    </td>
                    <td class="text-right">
                        IDR {{ number_format($price, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        IDR {{ number_format($totalValuation, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <th>Total Valuasi Aset Inventaris:</th>
            <td class="text-right" style="font-weight: bold; font-size: 13px;">
                IDR {{ number_format($grandTotalValuation, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <div class="notes-section">
        <strong>Catatan Sistem:</strong><br>
        1. Stok fisik merupakan akumulasi dari barang tersedia, dipesan, dan dalam perjalanan.<br>
        2. Status warna (Merah/Kuning/Hijau) merepresentasikan tingkat ketersediaan barang yang siap jual.<br>
        3. Valuasi dihitung berdasarkan Harga Beli (HPP) terakhir dari master data produk.
    </div>
</body>

</html>