<!DOCTYPE html>
<html>

<head>
    <title>Laporan Transaksi Stok - {{ now()->format('d F Y') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

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

        .details-table {
            width: 100%;
            margin-bottom: 30px;
        }

        .details-table td {
            vertical-align: top;
            padding: 2px;
        }

        .client-box {
            border-left: 3px solid #d1d5db;
            padding-left: 10px;
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

        .badge {
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #fff;
        }

        .badge-masuk {
            background-color: #16a34a;
        }

        .badge-keluar {
            background-color: #dc2626;
        }

        .notes-section {
            margin-top: 30px;
            font-style: italic;
            font-size: 10px;
            color: #4b5563;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    <!-- 1. HEADER PERUSAHAAN -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                {{-- Pastikan file ada di public/assets/logo2.png --}}
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

    <!-- TITLE -->
    <div class="document-title">
        <h1>LAPORAN TRANSAKSI STOK</h1>
        <p>Per {{ now()->format('d F Y') }}</p>
        <span class="status-badge">STOCK TRANSACTION REPORT</span>
    </div>

    <!-- DETAILS -->
    <table class="details-table">
        <tr>
            <td style="width:55%;">
                <div class="client-box">
                    <span style="font-size:10px; color:#6b7280; text-transform:uppercase;">Periode Laporan</span><br>
                    <strong style="font-size:14px;">
                        {{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('d F Y') : '-' }}
                        s/d
                        {{ $untilDate ? \Carbon\Carbon::parse($untilDate)->format('d F Y') : '-' }}
                    </strong>
                </div>
            </td>
            <td style="width:45%; padding-left:20px;">
                <table style="width:100%; font-size:11px;">
                    <tr>
                        <td style="color:#6b7280;">Dicetak Pada:</td>
                        <td style="font-weight:bold;">{{ now()->format('d F Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280;">Total Transaksi:</td>
                        <td style="font-weight:bold;">{{ $transactions->count() }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;" class="text-center">No</th>
                <th style="width:14%;">Tanggal</th>
                <th style="width:10%;">Kode Product</th>
                <th style="width:18%;">Nama Product</th>
                <th style="width:13%;">Gudang</th>
                <th style="width:9%;" class="text-center">Jenis</th>
                <th style="width:9%;" class="text-center">Qty</th>
                <th style="width:11%;" class="text-right">Harga</th>
                <th style="width:11%;" class="text-right">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $trx)
                <tr class="{{ $index % 2 ? 'row-bg' : '' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $trx->transaction_date->format('d M Y H:i') }}</td>
                    <td>{{ $trx->product->product_code ?? '-' }}</td>
                    <td><strong>{{ $trx->product->product_name ?? '-' }}</strong></td>
                    <td>{{ $trx->warehouse->warehouse_name ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $trx->type }}">
                            {{ strtoupper($trx->type) }}
                        </span>
                    </td>
                    <td class="text-center">{{ number_format($trx->quantity) }}</td>
                    <td class="text-right">IDR {{ number_format($trx->price, 0, ',', '.') }}</td>
                    <td class="text-right">IDR {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data transaksi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="notes-section">
        Laporan ini dihasilkan secara otomatis oleh sistem dan sah tanpa tanda tangan.
    </div>

</body>

</html>