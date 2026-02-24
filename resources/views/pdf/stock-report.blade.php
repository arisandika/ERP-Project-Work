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
            color: #333;
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
            color: #222;
        }

        .header-table .company-tagline {
            font-size: 12px;
            margin: 2px 0 5px 0;
            font-weight: bold;
            color: #555;
        }

        .header-table .company-address {
            font-size: 10px;
            margin: 0;
            color: #444;
        }

        .header-divider {
            border-bottom: 3px double #333;
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
        }

        .document-title p {
            margin: 5px 0 0;
            font-size: 12px;
            font-weight: bold;
        }

        .status-badge {
            font-size: 9px;
            padding: 4px 10px;
            border-radius: 12px;
            border: 1px solid #333;
            display: inline-block;
            margin-top: 8px;
            letter-spacing: 1px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #d9edf7;
            color: #31708f;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #f4f4f4;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .items-table .text-center {
            text-align: center;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .row-bg {
            background-color: #fafafa;
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
            background: #5cb85c;
        }

        .stock-warning {
            background: #f0ad4e;
        }

        .stock-danger {
            background: #d9534f;
        }

        .notes-section {
            margin-top: 30px;
            font-size: 10px;
            color: #555;
            border-top: 1px solid #eee;
            padding-top: 10px;
            font-style: italic;
        }
    </style>
</head>

<body>

    <!-- 1. HEADER PERUSAHAAN -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                {{-- Pastikan file ada di public/assets/logo2.png --}}
                <img src="{{ public_path('assets/logo2.png') }}" alt="Logo">
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

    <!-- TITLE -->
    <div class="document-title">
        <h1>LAPORAN STOK PRODUCT</h1>
        <p>Per {{ now()->format('d F Y H:i') }}</p>
        <span class="status-badge">INVENTORY REPORT</span>
    </div>

    <!-- TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;" class="text-center">No</th>
                <th style="width: 13%;">Kode Product</th>
                <th style="width: 22%;">Nama Product</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 10%;" class="text-center">Stok</th>
                <th style="width: 8%;" class="text-center">Satuan</th>
                <th style="width: 15%;" class="text-right">Harga</th>
                <th style="width: 15%;" class="text-right">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $index => $product)
                @php
                    $totalStock = $product->productStocks->sum('qty');
                    $stockClass = $totalStock <= 0 ? 'stock-danger' :
                        ($totalStock <= 10 ? 'stock-warning' : 'stock-success');
                    $price = $product->selling_price ?? $product->price;
                    $totalHarga = $totalStock * $price;
                @endphp
                <tr class="{{ $index % 2 ? 'row-bg' : '' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace;">{{ $product->product_code }}</td>
                    <td>
                        <strong>{{ $product->product_name }}</strong>
                    </td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td class="text-center">
                        <span class="stock-badge {{ $stockClass }}">
                            {{ $totalStock }}
                        </span>
                    </td>
                    <td class="text-center">
                        {{ $product->unit->symbol ?? $product->unit->name ?? '-' }}
                    </td>
                    <td class="text-right">
                        IDR {{ number_format($price, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        IDR {{ number_format($totalHarga, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>