<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Transaksi</title>
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
        .badge-masuk {
            background-color: #10b981;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            display: inline-block;
        }
        .badge-keluar {
            background-color: #ef4444;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Transaksi</h1>
    </div>

    @if($fromDate || $untilDate)
    <div class="filter-info">
        <strong>Periode:</strong>
        @if($fromDate)
            Dari: {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}
        @endif
        @if($untilDate)
            @if($fromDate) - @endif
            Sampai: {{ \Carbon\Carbon::parse($untilDate)->format('d M Y') }}
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Barang</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
            <tr>
                <td>{{ isset($transaction['transaction_date']) ? \Carbon\Carbon::parse($transaction['transaction_date'])->format('d M Y') : '-' }}</td>
                <td>{{ $transaction['product_name'] ?? '-' }}</td>
                <td>
                    <span class="badge-{{ $transaction['type'] }}">
                        {{ $transaction['type'] === 'masuk' ? 'Masuk' : 'Keluar' }}
                    </span>
                </td>
                <td>{{ number_format($transaction['quantity'] ?? 0, 0, ',', '.') }} {{ $transaction['unit_symbol'] ?? 'pcs' }}</td>
                <td>{{ $transaction['notes'] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center;">Tidak ada data transaksi</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d M Y H:i:s') }}</p>
        <p>Total Record: {{ $transactions->count() }}</p>
    </div>
</body>
</html>

