<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Stok Barang</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .filter-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge-low {
            background-color: #ef4444;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            display: inline-block;
        }
        .badge-ok {
            background-color: #10b981;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            display: inline-block;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Stok Barang</h1>
    </div>

    @if($categoryFilter && $categoryFilter !== 'Semua Kategori')
    <div class="filter-info">
        <strong>Filter:</strong> Kategori - {{ $categoryFilter }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kategori Barang</th>
                <th class="text-center">Stok</th>
                <th class="text-center">Satuan</th>
                <th class="text-right">Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>{{ $product['kode_barang'] }}</td>
                <td>{{ $product['product_name'] }}</td>
                <td>{{ $product['category_name'] }}</td>
                <td class="text-center">
                    @if($product['total_stock'] > 0)
                        <span class="badge-ok">{{ number_format($product['total_stock'], 0, ',', '.') }}</span>
                    @else
                        <span class="badge-low">{{ number_format($product['total_stock'], 0, ',', '.') }}</span>
                    @endif
                </td>
                <td class="text-center">{{ $product['unit_symbol'] }}</td>
                <td class="text-right">Rp {{ number_format($product['price'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">Tidak ada data stok barang</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d M Y H:i:s') }}</p>
        <p>Total Record: {{ $products->count() }}</p>
    </div>
</body>
</html>


