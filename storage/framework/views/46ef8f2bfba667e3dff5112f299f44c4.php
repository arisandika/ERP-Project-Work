<!DOCTYPE html>
<html>

<head>
    <title>Laporan Transaksi Stok - <?php echo e(now()->format('d F Y')); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

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
            font-size: 22px;
            letter-spacing: 3px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .document-title p {
            margin: 5px 0 0;
            font-size: 12px;
            font-weight: bold;
        }

        .status-badge {
            font-size: 9px;
            padding: 4px 10px;
            border-radius: 12px;
            border: 1px solid #333;
            display: inline-block;
            margin-top: 8px;
            letter-spacing: 1px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #d9edf7;
            color: #31708f;
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
            font-size: 11px;
            margin-bottom: 20px;
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

        .items-table .text-center {
            text-align: center;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .row-bg {
            background-color: #fafafa;
        }

        .badge {
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #fff;
        }

        .badge-masuk {
            background-color: #5cb85c;
        }

        .badge-keluar {
            background-color: #d9534f;
        }

        .notes-section {
            margin-top: 30px;
            font-style: italic;
            font-size: 10px;
            color: #555;
            border-top: 1px solid #eee;
            padding-top: 10px;
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

    <!-- TITLE -->
    <div class="document-title">
        <h1>LAPORAN TRANSAKSI STOK</h1>
        <p>Per <?php echo e(now()->format('d F Y')); ?></p>
        <span class="status-badge">STOCK TRANSACTION REPORT</span>
    </div>

    <!-- DETAILS -->
    <table class="details-table">
        <tr>
            <td style="width:55%;">
                <div class="client-box">
                    <span style="font-size:10px; color:#666; text-transform:uppercase;">Periode Laporan</span><br>
                    <strong style="font-size:14px;">
                        <?php echo e($fromDate ? \Carbon\Carbon::parse($fromDate)->format('d F Y') : '-'); ?>

                        s/d
                        <?php echo e($untilDate ? \Carbon\Carbon::parse($untilDate)->format('d F Y') : '-'); ?>

                    </strong>
                </div>
            </td>
            <td style="width:45%; padding-left:20px;">
                <table style="width:100%; font-size:11px;">
                    <tr>
                        <td style="color:#666;">Dicetak Pada:</td>
                        <td style="font-weight:bold;"><?php echo e(now()->format('d F Y H:i')); ?></td>
                    </tr>
                    <tr>
                        <td style="color:#666;">Total Transaksi:</td>
                        <td style="font-weight:bold;"><?php echo e($transactions->count()); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;" class="text-center">No</th>
                <th style="width:14%;">Tanggal</th>
                <th style="width:10%;">Kode Product</th>
                <th style="width:18%;">Nama Product</th>
                <th style="width:13%;">Gudang</th>
                <th style="width:9%;" class="text-center">Jenis</th>
                <th style="width:9%;" class="text-center">Qty</th>
                <th style="width:11%;" class="text-right">Harga</th>
                <th style="width:11%;" class="text-right">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $trx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="<?php echo e($index % 2 ? 'row-bg' : ''); ?>">
                    <td class="text-center"><?php echo e($index + 1); ?></td>
                    <td><?php echo e($trx->transaction_date->format('d M Y H:i')); ?></td>
                    <td style="font-family: monospace;"><?php echo e($trx->product->product_code ?? '-'); ?></td>
                    <td><strong><?php echo e($trx->product->product_name ?? '-'); ?></strong></td>
                    <td><?php echo e($trx->warehouse->warehouse_name ?? '-'); ?></td>
                    <td class="text-center">
                        <span class="badge badge-<?php echo e($trx->type); ?>">
                            <?php echo e(strtoupper($trx->type)); ?>

                        </span>
                    </td>
                    <td class="text-center"><?php echo e(number_format($trx->quantity)); ?></td>
                    <td class="text-right">IDR <?php echo e(number_format($trx->price, 0, ',', '.')); ?></td>
                    <td class="text-right">IDR <?php echo e(number_format($trx->total_price, 0, ',', '.')); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data transaksi</td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="notes-section">
        Laporan ini dihasilkan secara otomatis oleh sistem dan sah tanpa tanda tangan.
    </div>

</body>

</html><?php /**PATH /var/www/erp-app-main/resources/views/pdf/transaction-report.blade.php ENDPATH**/ ?>