<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status RMA</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f5; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; padding: 32px 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 600; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0; opacity: 0.85; font-size: 14px; }
        .body { padding: 32px 40px; color: #374151; font-size: 15px; line-height: 1.6; }
        .status-badge { display: inline-block; padding: 8px 18px; border-radius: 9999px; font-size: 14px; font-weight: 600; background: #3b82f6; color: #fff; margin: 16px 0; }
        table.details { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.details td { padding: 10px 0; border-bottom: 1px dashed #e5e7eb; font-size: 14px; }
        table.details td:first-child { color: #6b7280; width: 40%; }
        table.details td:last-child { color: #111827; font-weight: 500; }
        .btn { display: block; text-align: center; background: #16a34a; color: #fff; text-decoration: none; padding: 14px 0; border-radius: 10px; font-weight: 600; font-size: 15px; margin: 28px 0 8px; }
        .btn:hover { background: #15803d; }
        .note { font-size: 13px; color: #9ca3af; text-align: center; margin-top: 12px; }
        .footer { padding: 20px 40px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Update Status Pengajuan Garansi</h1>
            <p>RMA {{ $returnRequest->rma_number }}</p>
        </div>
        <div class="body">
            <p>Halo <strong>{{ $returnRequest->customer?->name ?? 'Pelanggan' }}</strong>,</p>
            <p>Status pengajuan garansi Anda telah diperbarui.</p>

            <div style="text-align:center;">
                <span class="status-badge">{{ $statusLabel }}</span>
            </div>

            <table class="details">
                <tr>
                    <td>No. RMA</td>
                    <td>{{ $returnRequest->rma_number }}</td>
                </tr>
                <tr>
                    <td>Produk</td>
                    <td>{{ $returnRequest->serialNumber?->product?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Serial Number</td>
                    <td>{{ $returnRequest->serialNumber?->serial_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Status Saat Ini</td>
                    <td>{{ $statusLabel }}</td>
                </tr>
            </table>

            <a href="{{ $portalUrl }}" class="btn">Lihat Detail Pengajuan</a>
            <p class="note">Anda juga bisa memantau pengajuan lewat dashboard Customer Portal.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Nexicon ERP. Email ini dikirim otomatis oleh sistem.
        </div>
    </div>
</body>
</html>