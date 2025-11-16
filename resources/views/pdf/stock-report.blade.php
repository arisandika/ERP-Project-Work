<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Produk - Nexicon ERP Dashboard</title>
    <style>
        /* === Layout === */
        @page {
            size: A4 landscape;
            margin: 40px 25px 60px 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
        }

        header {
            position: fixed;
            top: -30px;
            left: 0;
            right: 0;
            height: 60px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
        }

        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 40px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
        }

        /* === Table === */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 80px;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }

        th {
            background-color: #f3f4f6;
            font-weight: 600;
            text-align: left;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* === Badge colors === */
        .badge {
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 11px;
            color: white;
            display: inline-block;
        }

        .badge-success { background-color: #16a34a; }
        .badge-warning { background-color: #facc15; color: #1f2937; }
        .badge-danger { background-color: #dc2626; }

        /* === Header Logo === */
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand img {
            height: 35px;
        }

        /* === Page counter === */
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>

<header>
    <div class="brand">
        <img src="{{ public_path('images/logo-nexicon.png') }}" alt="Nexicon Logo">
        <h1 style="font-size:14px; font-weight:700; color:#111827;">Nexicon ERP Dashboard</h1>
    </div>
    <div style="text-align:right;">
        <p style="font-size:12px; color:#6b7280;">Laporan Stok Produk</p>
        <p style="font-size:11px; color:#9ca3af;">{{ now()->format('d M Y H:i') }}</p>
    </div>
</header>

<footer>
    <div>
        © {{ now()->year }} Nexicon ERP • Semua hak dilindungi
    </div>
    <div class="page-number">
        Halaman 
    </div>
</footer>

<main>
    <table>
        <thead>
            <tr>
                <th style="width:80px;">Kode Produk</th>
                <th>Nama Produk</th>
                <th style="width:140px;">Kategori</th>
                <th style="width:90px;">Stok</th>
                <th style="width:100px;">Satuan</th>
                <th style="width:120px; text-align:right;">Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                @php
                    $totalStock = $product->productStocks->sum('qty');
                    $statusClass = $totalStock <= 0 ? 'badge-danger' :
                                   ($totalStock <= 10 ? 'badge-warning' : 'badge-success');
                @endphp
                <tr>
                    <td style="font-family: monospace;">
                        BRG-{{ str_pad($product->id, 6, '0', STR_PAD_LEFT) }}
                    </td>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td style="text-align:center;">
                        <span class="badge {{ $statusClass }}">{{ $totalStock }}</span>
                    </td>
                    <td style="text-align:center;">
                        {{ $product->unit->symbol ?? $product->unit->name ?? '-' }}
                    </td>
                    <td style="text-align:right;">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</main>

</body>
</html>
