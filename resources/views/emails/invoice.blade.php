<!DOCTYPE html>
<html>
<head>
    <title>Invoice Anda</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>Halo, {{ $invoice->customer->name }}</h2>

    <p>Terima kasih telah bertransaksi dengan <strong>NEXICON</strong>.</p>

    <p>Berikut kami lampirkan Invoice <strong>#{{ $invoice->invoice_number }}</strong> untuk pesanan Anda. File PDF invoice juga sudah kami sertakan sebagai lampiran (attachment) pada email ini.</p>

    <p>Total Tagihan: <strong>IDR {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong></p>

    <p>Silakan lakukan pembayaran sebelum tanggal jatuh tempo: <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</strong>.</p>

    <div style="margin: 24px 0;">
        <a href="{{ $downloadUrl }}"
           style="background-color: #10b981; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin-right: 10px;">
            Download Invoice (PDF)
        </a>

        <a href="{{ $verifyUrl }}"
           style="background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
            Cek Keaslian Invoice
        </a>
    </div>

    <p style="font-size: 13px; color: #6b7280;">
        Jika tombol di atas tidak berfungsi, salin dan buka link berikut di browser Anda:<br>
        Download: {{ $downloadUrl }}<br>
        Verifikasi: {{ $verifyUrl }}
    </p>

    <br>
    <p>Salam Hangat,<br>Tim Nexicon</p>
</body>
</html>
