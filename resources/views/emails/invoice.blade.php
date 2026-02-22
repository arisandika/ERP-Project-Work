<!DOCTYPE html>
<html>
<head>
    <title>Invoice Anda</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <h2>Halo, {{ $invoice->customer->name }}</h2>

    <p>Terima kasih telah bertransaksi dengan <strong>NEXICON</strong>.</p>

    <p>Berikut kami lampirkan Invoice <strong>#{{ $invoice->invoice_number }}</strong> untuk pesanan Anda.</p>

    <p>Total Tagihan: <strong>IDR {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong></p>

    <p>Silakan lakukan pembayaran sebelum tanggal jatuh tempo: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}.</p>

    <br>
    <p>Salam Hangat,<br>Tim Nexicon</p>
</body>
</html>
