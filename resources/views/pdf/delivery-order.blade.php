<!DOCTYPE html>
<html>

<head>
    <title>Surat Jalan - {{ $record->do_number }}</title>
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

        /* Style khusus untuk list Serial Number agar rapi */
        .sn-container {
            margin-top: 5px;
            padding: 5px;
            background-color: #f9fafb;
            border: 1px dashed #d1d5db;
            font-size: 9px;
            color: #374151;
        }

        .sn-label {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
            display: block;
        }

        .sn-list {
            letter-spacing: 0.3px;
        }

        .payment-info {
            width: 100%;
            border: 1px dashed #d1d5db;
            padding: 12px;
            background: #f9fafb;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .payment-info h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            text-decoration: underline;
            color: #111827;
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

        .signature-table {
            width: 100%;
            text-align: center;
        }

        .signature-table td {
            width: 50%;
            padding: 5px;
            vertical-align: top;
        }

        .signature-table .signature-header {
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
        }

        .sign-line {
            margin-top: 80px;
            border-top: 1px solid #1f2937;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .qr-container {
            margin: 10px auto;
            padding: 5px;
            background: #fff;
            display: inline-block;
            border: 1px solid #e5e7eb;
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

    <!-- Status lookup: pakai array + string key, tidak pernah throw apa pun isi statusnya -->
    <?php
        $statusMap = [
            'draft'       => ['label' => 'DRAFT',             'style' => 'background-color: #fef9c3; color: #a16207;'],
            'ready'       => ['label' => 'SIAP KIRIM',         'style' => 'background-color: #dbeafe; color: #1d4ed8;'],
            'on_delivery' => ['label' => 'DALAM PENGIRIMAN',   'style' => 'background-color: #dbeafe; color: #1d4ed8;'],
            'delivered'   => ['label' => 'TERKIRIM',           'style' => 'background-color: #dcfce7; color: #15803d;'],
            'cancelled'   => ['label' => 'DIBATALKAN',         'style' => 'background-color: #fee2e2; color: #b91c1c;'],
        ];

        $rawStatus = $record->status ?? 'draft';
        $doStatus = is_object($rawStatus) ? (string) ($rawStatus->value ?? $rawStatus->name ?? '') : (string) $rawStatus;

        $currentStatus = $statusMap[$doStatus] ?? $statusMap['draft'];
        $statusLabel = $currentStatus['label'];
        $statusColor = $currentStatus['style'];
    ?>

    <!-- 2. JUDUL DOKUMEN -->
    <div class="document-title">
        <h1>SURAT JALAN</h1>
        <p>No: {{ $record->do_number }}</p>
        <span class="status-badge" style="{{ $statusColor }}">{{ $statusLabel }}</span>
    </div>

    <!-- 3. INFO PENERIMA & TANGGAL -->
    <table class="details-table">
        <tr>
            <!-- Kiri: Info Penerima -->
            <td style="width: 55%;">
                <div class="client-box">
                    <span style="color: #6b7280; font-size: 10px; text-transform: uppercase;">Dikirim Kepada:</span><br>
                    <strong style="font-size: 14px;">{{ $record->customer->name ?? 'UMUM' }}</strong><br>
                    <div style="margin-top: 5px; color: #4b5563;">
                        {{ $record->customer->address ?? 'Alamat tidak tersedia' }}<br>
                        Telp: {{ $record->customer->phone ?? '-' }}
                    </div>
                </div>
            </td>
            <!-- Kanan: Info Tanggal -->
            <td style="width: 45%; padding-left: 20px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 40%; color: #6b7280;">Tanggal Kirim:</td>
                        <td style="font-weight: bold;">{{ \Carbon\Carbon::parse($record->do_date)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Referensi SO:</td>
                        <td>{{ $record->salesOrder->order_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">No. PO Customer:</td>
                        <td>{{ $record->salesOrder->customer_po_number ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 4. TABEL ITEM -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 15%;">Kode Barang</th>
                <th style="width: 45%;">Nama Barang & Identitas Unit (SN)</th>
                <th style="width: 10%;" class="text-center">Satuan</th>
                <th style="width: 12.5%;" class="text-center">Qty Pesan</th>
                <th style="width: 12.5%;" class="text-center">Qty Kirim</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($record->items as $index => $item): ?>
                <tr class="<?php echo $index % 2 == 0 ? '' : 'row-bg'; ?>">
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td><?php echo $item->item_code ?? '-'; ?></td>
                    <td>
                        <strong><?php echo e($item->item_name); ?></strong>
                        <?php if ($item->item_desc): ?>
                            <br><small style="color: #6b7280;"><?php echo e($item->item_desc); ?></small>
                        <?php endif; ?>

                        <?php if (!empty($item->scanned_sns)): ?>
                            <div class="sn-container">
                                <span class="sn-label">Serial Number (S/N):</span>
                                <span class="sn-list">
                                    <?php
                                        $snArray = explode(',', $item->scanned_sns);
                                        $snClean = array_map('trim', $snArray);
                                        echo implode(', ', $snClean);
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo $item->uom ?? 'Pcs'; ?></td>
                    <td class="text-center"><?php echo number_format($item->qty_ordered, 0, ',', '.'); ?></td>
                    <td class="text-center" style="font-weight: bold; font-size: 12px;">
                        <?php echo number_format($item->qty, 0, ',', '.'); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($record->items) === 0): ?>
                <tr>
                    <td colspan="6" class="text-center">Data item tidak ditemukan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 5. CATATAN PENGIRIMAN -->
    <?php if ($record->notes): ?>
        <div class="payment-info">
            <h4>Catatan Pengiriman</h4>
            <p style="margin: 0; font-size: 11px;"><?php echo e($record->notes); ?></p>
        </div>
    <?php endif; ?>

    <div class="clearfix"></div>

    <!-- 6. CATATAN KAKI -->
    <div class="notes-section">
        * Barang yang sudah diterima dalam kondisi baik tidak dapat ditukar atau dikembalikan.<br>
        * Scan QR Code di bawah untuk memvalidasi Surat Jalan & melacak status pengiriman secara real-time.
    </div>

    <!-- 7. TANDA TANGAN -->
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <span class="signature-header">Hormat Kami,</span>

                    <?php if (isset($qrCode)): ?>
                        <div class="qr-container">
                            <img src="data:image/png;base64,<?php echo $qrCode; ?>" alt="QR Validasi" style="width: 80px; height: 80px;">
                        </div>
                    <?php else: ?>
                        <div class="sign-line"></div>
                    <?php endif; ?>

                    <p style="font-weight: bold; margin-bottom: 0; margin-top: 5px;">
                        PT. NEXT GENERATION SOLUTIONS
                    </p>
                    <span style="font-size: 10px;">
                        PIC: <?php echo e($record->employee->full_name ?? 'Gudang / Admin'); ?>
                    </span>
                </td>

                <td>
                    <span class="signature-header">
                        Penerima Barang<br>
                        <span style="font-size: 10px; font-weight: normal; color: #4b5563;">
                            (<?php echo e($record->customer->name ?? 'UMUM'); ?>)
                        </span>
                    </span>

                    <div class="sign-line"></div>

                    <p style="font-weight: bold; margin-bottom: 0;">
                        ( Nama Jelas & Stempel )
                    </p>
                    <span style="font-size: 10px;">Authorized Signature</span>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>