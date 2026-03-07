<!DOCTYPE html>
<html>

<head>
    <title>Invoice - <?php echo e($invoice->invoice_number); ?></title>
    <style>
        @page {
            margin: 25px 30px;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
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
            color: #222;
        }

        .header-table .company-tagline {
            font-size: 12px;
            margin: 2px 0 5px 0;
            font-weight: bold;
            color: #555;
        }

        .header-table .company-address {
            font-size: 10px;
            margin: 0;
            color: #444;
        }

        .header-divider {
            border-bottom: 3px double #333;
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
        }

        .document-title p {
            margin: 5px 0 0 0;
            font-size: 13px;
            font-weight: bold;
        }

        .status-badge {
            font-size: 9px;
            padding: 4px 10px;
            border: 1px solid #333;
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
            border-left: 3px solid #ddd;
            padding-left: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .items-table th {
            background-color: #f4f4f4;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .items-table .row-bg {
            background-color: #fafafa;
        }

        .totals-section {
            width: 100%;
            display: table;
        }

        .payment-info {
            width: 55%;
            float: left;
            border: 1px dashed #aaa;
            padding: 12px;
            background: #fdfdfd;
            border-radius: 4px;
        }

        .payment-info h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            text-decoration: underline;
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
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            padding: 8px 0;
            font-size: 14px;
            font-weight: bold;
            background-color: #f4f4f4;
        }

        .notes-section {
            margin-top: 30px;
            font-style: italic;
            color: #555;
            font-size: 10px;
            border-top: 1px solid #eee;
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
            border: 1px solid #eee;
        }

        .sign-line {
            margin-top: 50px;
            border-top: 1px solid #333;
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
                
                <img src="<?php echo e(public_path('assets/logo2.png')); ?>" alt="Logo">
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

    <!-- 2. JUDUL DOKUMEN -->
    <div class="document-title">
        <h1>INVOICE</h1>
        <p>No: <?php echo e($invoice->invoice_number); ?></p>

        
        <?php
            $statusColor = match ($invoice->status) {
                'paid' => 'background-color: #dff0d8; color: #3c763d; border-color: #d6e9c6;', // Hijau
                'cancelled' => 'background-color: #f2dede; color: #a94442; border-color: #ebccd1;', // Merah
                'partial' => 'background-color: #d9edf7; color: #31708f; border-color: #bce8f1;', // Biru
                default => 'background-color: #fcf8e3; color: #8a6d3b; border-color: #faebcc;', // Kuning (Unpaid/Sent)
            };
            $statusLabel = match ($invoice->status) {
                'paid' => 'LUNAS',
                'cancelled' => 'DIBATALKAN',
                'partial' => 'SEBAGIAN',
                'sent' => 'TERKIRIM',
                default => 'BELUM LUNAS',
            };
        ?>
        <span class="status-badge" style="<?php echo e($statusColor); ?>"><?php echo e($statusLabel); ?></span>
    </div>

    <!-- 3. INFO CUSTOMER & TANGGAL -->
    <table class="details-table">
        <tr>
            <!-- Kiri: Info Client -->
            <td style="width: 55%;">
                <div class="client-box">
                    <span style="color: #666; font-size: 10px; text-transform: uppercase;">Ditagihkan Kepada:</span><br>
                    <strong style="font-size: 14px;"><?php echo e($invoice->customer->name); ?></strong><br>
                    <div style="margin-top: 5px; color: #444;">
                        <?php echo e($invoice->customer->address ?? 'Alamat tidak tersedia'); ?><br>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->customer->email): ?> Email: <?php echo e($invoice->customer->email); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </td>
            <!-- Kanan: Info Tanggal -->
            <td style="width: 45%; padding-left: 20px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 40%; color: #666;">Tanggal Invoice:</td>
                        <td style="font-weight: bold;"><?php echo e($invoice->invoice_date->format('d F Y')); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #666;">Jatuh Tempo:</td>
                        <td style="font-weight: bold; color: #c00;"><?php echo e($invoice->due_date->format('d F Y')); ?></td>
                    </tr>

                    <!-- LOGIC DINAMIS: Cek PO Customer -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($invoice->salesOrder->customer_po_number)): ?>
                        <tr>
                            <td style="color: #666;">No. PO Klien:</td>
                            <td style="font-weight: bold;"><?php echo e($invoice->salesOrder->customer_po_number); ?></td>
                        </tr>
                        <!-- Tampilkan SO Internal sebagai info tambahan (opsional) -->
                        <tr>
                            <td style="color: #666;">Ref. Internal:</td>
                            <td style="font-size: 10px;"><?php echo e($invoice->salesOrder->order_number); ?></td>
                        </tr>
                    <?php else: ?>
                        <!-- Jika tidak ada PO (Order WA), tampilkan SO sebagai referensi utama -->
                        <tr>
                            <td style="color: #666;">Ref. Order:</td>
                            <td><?php echo e($invoice->salesOrder->order_number ?? '-'); ?></td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

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
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="<?php echo e($index % 2 == 0 ? '' : 'row-bg'); ?>">
                    <td class="text-center"><?php echo e($index + 1); ?></td>
                    <td>
                        <strong><?php echo e($item->item_name); ?></strong>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->item_code): ?>
                            <br><small style="color: #666;">Kode: <?php echo e($item->item_code); ?></small>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    
                    <td class="text-center"><?php echo e($item->qty ?? $item->quantity ?? 0); ?></td>
                    <td class="text-right">IDR <?php echo e(number_format($item->unit_price, 0, ',', '.')); ?></td>
                    <td class="text-right">IDR <?php echo e(number_format($item->line_total, 0, ',', '.')); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                <em style="font-size: 10px; color: #666;">*Mohon cantumkan No. Invoice (<?php echo e($invoice->invoice_number); ?>)
                    pada berita transfer.</em>
            </p>
        </div>

        <!-- Tabel Kalkulasi -->
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">IDR <?php echo e(number_format($invoice->subtotal, 0, ',', '.')); ?></td>
            </tr>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->discount > 0): ?>
                <tr>
                    <td class="label" style="color: #c00;">Diskon (<?php echo e($invoice->discount); ?>%):</td>
                    <td class="amount" style="color: #c00;">
                        <?php $discAmount = $invoice->subtotal * ($invoice->discount / 100); ?>
                        - IDR <?php echo e(number_format($discAmount, 0, ',', '.')); ?>

                    </td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->tax > 0): ?>
                <tr>
                    <td class="label">PPN (<?php echo e($invoice->tax); ?>%):</td>
                    <td class="amount">
                        <?php
                            $afterDisc = $invoice->subtotal * (1 - ($invoice->discount / 100));
                            $taxAmount = $afterDisc * ($invoice->tax / 100);
                        ?>
                        IDR <?php echo e(number_format($taxAmount, 0, ',', '.')); ?>

                    </td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <tr class="grand-total-row">
                <td class="label">TOTAL:</td>
                <td class="amount">IDR <?php echo e(number_format($invoice->grand_total, 0, ',', '.')); ?></td>
            </tr>
        </table>
    </div>

    <div class="clearfix"></div>

    <!-- 6. CATATAN KAKI -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->notes): ?>
        <div class="notes-section">
            <strong>Catatan Tambahan:</strong><br>
            <?php echo e($invoice->notes); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- 7. TANDA TANGAN & QR CODE -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Tangerang Selatan, <?php echo e(now()->format('d F Y')); ?></p>
            <p style="font-weight: bold;">PT. NEXT GENERATION SOLUTIONS</p>

            <div class="qr-container">
                
                <img src="data:image/png;base64,<?php echo e($qrCode); ?>" alt="QR Validasi" style="width: 80px; height: 80px;">
            </div>

            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">
                <?php echo e($invoice->approved_by ?? 'Finance Department'); ?>

            </p>
            <span style="font-size: 10px;">Authorized Signature</span>
        </div>
    </div>

</body>

</html><?php /**PATH C:\laragon\www\erp-app-main\resources\views/pdf/invoice.blade.php ENDPATH**/ ?>