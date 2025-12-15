<!DOCTYPE html>
<html>
<body>
    <h2>Halo, {{ $invoice->customer->name }}</h2>
    <p>Kami ingin mengingatkan bahwa tagihan berikut telah jatuh tempo:</p>

    <ul>
        <li><strong>No. Invoice:</strong> {{ $invoice->invoice_number }}</li>
        <li><strong>Tanggal Jatuh Tempo:</strong> {{ $invoice->due_date->format('d M Y') }}</li>
        <li><strong>Total Tagihan:</strong> Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</li>
    </ul>

    <p>Mohon segera melakukan pembayaran.</p>
    <p>Terima kasih,<br>Tim Nexicon</p>
</body>
</html>
