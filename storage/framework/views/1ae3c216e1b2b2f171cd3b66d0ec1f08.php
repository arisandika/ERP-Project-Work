<!DOCTYPE html>
<html>

<head>
    <title>Surat Jalan - <?php echo e($record->do_number); ?></title>
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
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }

        .header-table .company-tagline {
            font-size: 12px;
            margin: 2px 0;
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
            margin-bottom: 20px;
        }

        .document-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .document-title h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 800;
            border: 2px solid #333;
            display: inline-block;
            padding: 5px 20px;
        }

        .document-title p {
            margin: 5px 0 0 0;
            font-size: 12px;
            font-weight: bold;
        }

        .details-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .details-table td {
            vertical-align: top;
            padding: 3px;
            font-size: 11px;
        }

        .client-box {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
            background: #fcfcfc;
            min-height: 80px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #eee;
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }

        .items-table td {
            border: 1px solid #999;
            padding: 8px;
            font-size: 11px;
            vertical-align: top;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        /* Style khusus untuk list Serial Number agar rapi */
        .sn-container {
            margin-top: 5px;
            padding: 5px;
            background-color: #f9f9f9;
            border: 1px dashed #bbb;
            font-size: 9px;
            color: #222;
        }

        .sn-label {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
            display: block;
        }

        .sn-list {
            font-family: 'Courier', monospace; /* Monospace agar mudah dibaca per karakter */
            letter-spacing: 0.3px;
        }

        .signature-table {
            width: 100%;
            margin-top: 40px;
            text-align: center;
            page-break-inside: avoid;
        }

        .signature-table td {
            padding: 5px;
            vertical-align: top;
        }

        .signature-header {
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
        }

        .signature-line {
            border-top: 1px solid #333;
            width: 60%;
            margin: 95px auto 5px auto;
        }

        .qr-container {
            margin: 10px auto;
            padding: 5px;
            background: #fff;
            display: inline-block;
            border: 1px solid #eee;
        }

        .footer-note {
            font-size: 9px;
            font-style: italic;
            color: #555;
            margin-top: 20px;
            border-top: 1px dashed #ccc;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="<?php echo e(public_path('assets/logo2.png')); ?>" alt="Logo">
            </td>
            <td class="company-info-cell">
                <h2 class="company-name">NEXICON</h2>
                <p class="company-tagline">PT. NEXT GENERATION SOLUTIONS</p>
                <p class="company-address">
                    Jl. Lingkar Selatan Sengkol No. 18, Setu, Tangerang Selatan, Banten<br>
                    Telp: 0838-1100-3426 | Email: nexicon.id@gmail.com
                </p>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    <div class="document-title">
        <h1>SURAT JALAN</h1>
        <p>No: <?php echo e($record->do_number); ?></p>
    </div>

    <table class="details-table">
        <tr>
            <td style="width: 55%; padding-right: 20px;">
                <strong>Dikirim Kepada:</strong>
                <div class="client-box">
                    <strong style="font-size: 13px; text-transform: uppercase;"><?php echo e($record->customer->name ?? 'UMUM'); ?></strong><br>
                    <div style="margin-top: 5px; color: #444;">
                        <?php echo e($record->customer->address ?? 'Alamat tidak tersedia'); ?><br>
                        Telp: <?php echo e($record->customer->phone ?? '-'); ?>

                    </div>
                </div>
            </td>
            <td style="width: 45%;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 40%;">Tanggal Kirim</td>
                        <td>: <strong><?php echo e(\Carbon\Carbon::parse($record->do_date)->format('d F Y')); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Referensi SO</td>
                        <td>: <?php echo e($record->salesOrder->order_number ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td>No. PO Customer</td>
                        <td>: <?php echo e($record->salesOrder->customer_po_number ?? '-'); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

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
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $record->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="text-center"><?php echo e($index + 1); ?></td>
                    <td><?php echo e($item->item_code ?? '-'); ?></td>
                    <td>
                        <strong><?php echo e($item->item_name); ?></strong>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->item_desc): ?>
                            <br><small style="color:#666"><?php echo e($item->item_desc); ?></small>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item->scanned_sns)): ?>
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
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo e($item->uom ?? 'Pcs'); ?></td>
                    <td class="text-center"><?php echo e(number_format($item->qty_ordered, 0, ',', '.')); ?></td>
                    <td class="text-center" style="font-weight: bold; font-size: 12px;">
                        <?php echo e(number_format($item->qty, 0, ',', '.')); ?>

                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="text-center">Data item tidak ditemukan.</td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->notes): ?>
        <div style="margin-bottom: 20px; font-size: 10px; border: 1px dashed #aaa; padding: 5px;">
            <strong>Catatan Pengiriman:</strong> <?php echo e($record->notes); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <span class="signature-header">Hormat Kami,</span>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($qrCode)): ?>
                    <div class="qr-container">
                        <img src="data:image/png;base64,<?php echo e($qrCode); ?>" alt="QR Validasi" style="width: 80px; height: 80px;">
                    </div>
                <?php else: ?>
                    <div class="signature-line" style="margin-top: 80px; margin-bottom: 14px;"></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0; margin-top: 5px;">
                    PT. NEXT GENERATION SOLUTIONS
                </p>
                <span style="font-size: 10px; display:block; margin-top:2px;">
                    PIC: <?php echo e($record->employee->full_name ?? 'Gudang / Admin'); ?>

                </span>
            </td>

            <td style="width: 50%;">
                <span class="signature-header">
                    Penerima Barang<br>
                    <span style="font-size: 10px; font-weight: normal; color: #555;">
                        (<?php echo e($record->customer->name ?? 'UMUM'); ?>)
                    </span>
                </span>

                <div class="signature-line"></div>

                <p style="margin-bottom: 0; margin-top: 5px;">
                    ( Nama Jelas & Stempel )
                </p>
            </td>
        </tr>
    </table>

    <p class="footer-note">
        * Barang yang sudah diterima dalam kondisi baik tidak dapat ditukar atau dikembalikan.<br>
        * Scan QR Code di atas untuk memvalidasi Surat Jalan & melacak status pengiriman secara real-time.
    </p>

</body>

</html>
<?php /**PATH D:\Nurul Fauziah\Project Work\Nexicon\ERP-Project-Work\resources\views/pdf/delivery-order.blade.php ENDPATH**/ ?>