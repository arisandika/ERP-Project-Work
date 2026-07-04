<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penawaran - <?php echo e($quotation->quotation_number); ?></title>
    <style>
        @page {
            margin: 20px 25px;
        }
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
        }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .header-table .logo-cell { width: 15%; vertical-align: middle; }
        .header-table .logo-cell img { max-width: 80px; }
        .header-table .company-info-cell { vertical-align: middle; padding-left: 20px; }
        .header-table .company-name { font-size: 28px; font-weight: bold; margin: 0; }
        .header-table .company-tagline { font-size: 14px; margin: 0; }
        .header-table .company-address { font-size: 11px; margin: 5px 0 0 0; }

        .header-divider { border-bottom: 4px double #000; margin-bottom: 30px; }

        .document-title { text-align: center; margin-bottom: 20px; }
        .document-title h1 { margin: 0; font-size: 22px; text-transform: uppercase; }
        .document-title p { margin: 0; }

        .details-table { width: 100%; margin-bottom: 25px; }
        .details-table td { vertical-align: top; padding: 2px 5px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .items-table th { background-color: #f2f2f2; }
        .items-table .text-right { text-align: right; }

        .totals-table { width: 45%; float: right; }
        .totals-table td { padding: 5px 8px; }
        .totals-table .label { font-weight: bold; }
        .totals-table .grand-total {
            font-weight: bold;
            font-size: 14px;
            background-color: #f2f2f2;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }

        .clearfix { clear: both; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="<?php echo e(public_path('assets/logo2.png')); ?>" alt="Nexicon Logo">
            </td>
            <td class="company-info-cell">
                <p class="company-name">NEXICON</p>
                <p class="company-tagline">PT. NEXT GENERATION SOLUTIONS</p>
                <p class="company-address">
                    Jl. Lingkar Selatan Sengkol No. 18, Setu, Tangerang Selatan, Banten<br>
                    Telp. 083811003426, Website: www.nexicon.id<br>
                    Email: nexicon.id@gmail.com
                </p>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    <div class="document-title">
        <h1>Penawaran</h1>
        <p>No: <?php echo e($quotation->quotation_number); ?></p>
    </div>

    <?php
        // Ambil relasi klien dengan aman (Mencegah Null Property Error)
        $client = $quotation->deal?->customer ?? $quotation->deal?->lead;

        $clientName    = $client?->name ?? 'Nama Klien Tidak Tersedia';
        $clientAddress = $client?->address ?? 'Alamat tidak tersedia';
        $clientEmail   = $client?->email ?? '';

        // Ambil pembuat penawaran (Menyesuaikan dengan Filament Schema)
        $creatorName = $quotation->internalPic?->full_name
                    ?? $quotation->createdBy?->full_name
                    ?? 'Tim Sales';
    ?>

    <table class="details-table">
        <tr>
            <td style="width: 60%;">
                <strong>Ditujukan Kepada:</strong><br>
                <strong style="font-size: 12px;"><?php echo e($clientName); ?></strong><br>
                <?php echo e($clientAddress); ?><br>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($clientEmail): ?>
                    Email: <?php echo e($clientEmail); ?>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </td>
            <td style="width: 40%;">
                <strong>Tanggal:</strong> <?php echo e(\Carbon\Carbon::parse($quotation->quotation_date)->format('d F Y')); ?><br>
                <strong>Berlaku Hingga:</strong> <?php echo e(\Carbon\Carbon::parse($quotation->valid_until)->format('d F Y')); ?><br>
                <strong>Dibuat Oleh:</strong> <?php echo e($creatorName); ?>

            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Item / Deskripsi</th>
                <th class="text-right">Jumlah</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $quotation->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <strong><?php echo e($item->item_name); ?></strong><br>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->item_code): ?>
                            <small style="color: #666;">Kode: <?php echo e($item->item_code); ?></small>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="text-right"><?php echo e($item->qty ?? $item->quantity ?? 1); ?></td>
                    <td class="text-right">IDR <?php echo e(number_format((float) ($item->unit_price ?? 0), 0, ',', '.')); ?></td>
                    <td class="text-right">IDR <?php echo e(number_format((float) ($item->line_total ?? 0), 0, ',', '.')); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="4" class="text-center">Tidak ada item dalam penawaran ini.</td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="text-right">IDR <?php echo e(number_format((float) ($quotation->subtotal ?? 0), 0, ',', '.')); ?></td>
        </tr>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $quotation->discount_amount > 0): ?>
        <tr>
            <td class="label">Diskon:</td>
            <td class="text-right" style="color: red;">
                - IDR <?php echo e(number_format((float) $quotation->discount_amount, 0, ',', '.')); ?>

            </td>
        </tr>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $quotation->tax > 0): ?>
        <tr>
            <?php
                // Menghitung nominal pajak (Subtotal - Diskon) * (Tax / 100)
                $subtotalAfterDiscount = (float) $quotation->subtotal - (float) $quotation->discount_amount;
                $taxNominal = $subtotalAfterDiscount * ((float) $quotation->tax / 100);
            ?>
            <td class="label">Pajak PPN (<?php echo e($quotation->tax); ?>%):</td>
            <td class="text-right">IDR <?php echo e(number_format($taxNominal, 0, ',', '.')); ?></td>
        </tr>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <tr class="grand-total">
            <td class="label">Grand Total:</td>
            <td class="text-right">IDR <?php echo e(number_format((float) ($quotation->grand_total ?? 0), 0, ',', '.')); ?></td>
        </tr>
    </table>

    <div class="clearfix"></div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quotation->notes): ?>
    <div style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px;">
        <strong>Syarat & Ketentuan / Catatan:</strong>
        <p style="white-space: pre-wrap; margin-top: 5px; color: #555;"><?php echo e($quotation->notes); ?></p>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</body>
</html>
<?php /**PATH D:\Nurul Fauziah\Project Work\Nexicon\ERP-Project-Work\resources\views/pdf/quotation.blade.php ENDPATH**/ ?>