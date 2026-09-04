<!DOCTYPE html>
<html>

<head>
    <title>Invoice - {{ $invoice->invoice_number }}</title>
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
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: 800;
            color: #111827;
        }

        .document-title p {
            margin: 5px 0 0 0;
            font-size: 13px;
            font-weight: bold;
        }

        .status-badge {
            font-size: 9px;
            padding: 4px 10px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            display: inline-block;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
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
            margin-bottom: 20px;
            font-size: 11px;
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

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .items-table .row-bg {
            background-color: #f9fafb;
        }

        .totals-section {
            width: 100%;
            display: table;
        }

        .payment-info {
            width: 55%;
            float: left;
            border: 1px dashed #d1d5db;
            padding: 12px;
            background: #f9fafb;
            border-radius: 4px;
        }

        .payment-info h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            text-decoration: underline;
            color: #111827;
        }

        .totals-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 0;
        }

        .totals-table .label {
            font-weight: bold;
            text-align: right;
            padding-right: 15px;
        }

        .totals-table .amount {
            text-align: right;
        }

        .totals-table .grand-total-row td {
            border-top: 2px solid #1f2937;
            border-bottom: 2px solid #1f2937;
            padding: 8px 0;
            font-size: 14px;
            font-weight: bold;
            background-color: #f3f4f6;
        }

        .notes-section {
            margin-top: 30px;
            font-style: italic;
            color: #4b5563;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-box {
            float: right;
            width: 35%;
            text-align: center;
        }

        .qr-container {
            margin: 15px auto;
            padding: 5px;
            background: #fff;
            display: inline-block;
            border: 1px solid #e5e7eb;
        }

        .sign-line {
            margin-top: 50px;
            border-top: 1px solid #1f2937;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        /* Helper */
        .clearfix {
            clear: both;
        }
    </style>
</head>

<body>

    <!-- 1. HEADER PERUSAHAAN -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <?php $logoPath = public_path('assets/logo2.png'); ?>
                <?php if (is_file($logoPath)): ?>
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo">
                <?php endif; ?>
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

    <!-- Status lookup: array + string key, tidak pernah throw apa pun bentuk $invoice->status -->
    <?php
        $statusMap = [
            'paid'      => ['label' => 'LUNAS',        'style' => 'background-color: #dcfce7; color: #15803d;'],
            'cancelled' => ['label' => 'DIBATALKAN',   'style' => 'background-color: #fee2e2; color: #b91c1c;'],
            'partial'   => ['label' => 'SEBAGIAN',     'style' => 'background-color: #dbeafe; color: #1d4ed8;'],
            'sent'      => ['label' => 'TERKIRIM',     'style' => 'background-color: #dbeafe; color: #1d4ed8;'],
            'unpaid'    => ['label' => 'BELUM LUNAS',  'style' => 'background-color: #fef9c3; color: #a16207;'],
        ];

        $rawInvoiceStatus = $invoice->status ?? 'unpaid';
        $invoiceStatusKey = is_object($rawInvoiceStatus)
            ? (string) ($rawInvoiceStatus->value ?? $rawInvoiceStatus->name ?? '')
            : (string) $rawInvoiceStatus;

        $currentInvoiceStatus = $statusMap[$invoiceStatusKey] ?? $statusMap['unpaid'];
        $statusLabel = $currentInvoiceStatus['label'];
        $statusColor = $currentInvoiceStatus['style'];
    ?>

    <!-- 2. JUDUL DOKUMEN -->
    <div class="document-title">
        <h1>INVOICE</h1>
        <p>No: {{ $invoice->invoice_number }}</p>
        <span class="status-badge" style="{{ $statusColor }}">{{ $statusLabel }}</span>
    </div>

    <!-- 3. INFO CUSTOMER & TANGGAL -->
    <table class="details-table">
        <tr>
            <!-- Kiri: Info Client -->
            <td style="width: 55%;">
                <div class="client-box">
                    <span style="color: #6b7280; font-size: 10px; text-transform: uppercase;">Ditagihkan Kepada:</span><br>
                    <strong style="font-size: 14px;">{{ $invoice->customer->name }}</strong><br>
                    <div style="margin-top: 5px; color: #4b5563;">
                        {{ $invoice->customer->address ?? 'Alamat tidak tersedia' }}<br>
                        @if($invoice->customer->email) Email: {{ $invoice->customer->email }} @endif
                    </div>
                </div>
            </td>
            <!-- Kanan: Info Tanggal -->
            <td style="width: 45%; padding-left: 20px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 40%; color: #6b7280;">Tanggal Invoice:</td>
                        <td style="font-weight: bold;">{{ $invoice->invoice_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Jatuh Tempo:</td>
                        <td style="font-weight: bold; color: #b91c1c;">{{ $invoice->due_date->format('d F Y') }}</td>
                    </tr>

                    <!-- LOGIC DINAMIS: Cek PO Customer -->
                    @if(!empty($invoice->salesOrder->customer_po_number))
                        <tr>
                            <td style="color: #6b7280;">No. PO Klien:</td>
                            <td style="font-weight: bold;">{{ $invoice->salesOrder->customer_po_number }}</td>
                        </tr>
                        <!-- Tampilkan SO Internal sebagai info tambahan (opsional) -->
                        <tr>
                            <td style="color: #6b7280;">Ref. Internal:</td>
                            <td style="font-size: 10px;">{{ $invoice->salesOrder->order_number }}</td>
                        </tr>
                    @else
                        <!-- Jika tidak ada PO (Order WA), tampilkan SO sebagai referensi utama -->
                        <tr>
                            <td style="color: #6b7280;">Ref. Order:</td>
                            <td>{{ $invoice->salesOrder->order_number ?? '-' }}</td>
                        </tr>
                    @endif

                </table>
            </td>
        </tr>
    </table>

    <!-- 4. TABEL ITEM -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 45%;">Deskripsi Item</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 20%;">Harga Satuan</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                <tr class="{{ $index % 2 == 0 ? '' : 'row-bg' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->item_code)
                            <br><small style="color: #6b7280;">Kode: {{ $item->item_code }}</small>
                        @endif
                    </td>
                    {{-- Menggunakan logic fallback Qty --}}
                    <td class="text-center">{{ $item->qty ?? $item->quantity ?? 0 }}</td>
                    <td class="text-right">IDR {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">IDR {{ number_format($item->line_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 5. TOTAL & PEMBAYARAN -->
    <div class="totals-section">

        <!-- Info Rekening -->
        <div class="payment-info">
            <h4>Instruksi Pembayaran</h4>
            <p style="margin: 0; font-size: 11px;">
                Silakan transfer pembayaran ke rekening berikut:<br>
                <strong>BCA (Bank Central Asia)</strong><br>
                No. Rek: <strong>555-000-1234</strong><br>
                A/N: <strong>PT. NEXT GENERATION SOLUTIONS</strong><br>
                <br>
                <em style="font-size: 10px; color: #6b7280;">*Mohon cantumkan No. Invoice ({{ $invoice->invoice_number }})
                    pada berita transfer.</em>
            </p>
        </div>

        <!-- Tabel Kalkulasi -->
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">IDR {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
            </tr>

            <?php $discAmount = $invoice->subtotal * (($invoice->discount ?? 0) / 100); ?>
            @if($invoice->discount > 0)
                <tr>
                    <td class="label" style="color: #b91c1c;">Diskon ({{ $invoice->discount }}%):</td>
                    <td class="amount" style="color: #b91c1c;">
                        - IDR {{ number_format($discAmount, 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            <?php
                $afterDisc = $invoice->subtotal * (1 - (($invoice->discount ?? 0) / 100));
                $taxAmount = $afterDisc * (($invoice->tax ?? 0) / 100);
            ?>
            @if($invoice->tax > 0)
                <tr>
                    <td class="label">PPN ({{ $invoice->tax }}%):</td>
                    <td class="amount">
                        IDR {{ number_format($taxAmount, 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            <tr class="grand-total-row">
                <td class="label">TOTAL:</td>
                <td class="amount">IDR {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="clearfix"></div>

    <!-- 6. CATATAN KAKI -->
    @if($invoice->notes)
        <div class="notes-section">
            <strong>Catatan Tambahan:</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- 7. TANDA TANGAN & QR CODE -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Tangerang Selatan, {{ now()->format('d F Y') }}</p>
            <p style="font-weight: bold;">PT. NEXT GENERATION SOLUTIONS</p>

            <div class="qr-container">
                {{-- QR Code untuk Validasi (Scan HP) --}}
                <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Validasi" style="width: 80px; height: 80px;">
            </div>

            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">
                {{ $invoice->approved_by ?? 'Finance Department' }}
            </p>
            <span style="font-size: 10px;">Authorized Signature</span>
        </div>
    </div>

</body>

</html>