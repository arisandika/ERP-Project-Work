<!DOCTYPE html>
<html>

<head>
    <title>Purchase Order - <?php echo e($purchaseOrder->po_number); ?></title>
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
        <h1>PURCHASE ORDER</h1>
        <p>No: <?php echo e($purchaseOrder->po_number); ?></p>

        
        <?php
            $statusColor = match ($purchaseOrder->status) {
                'completed' => 'background-color: #dff0d8; color: #3c763d; border-color: #d6e9c6;', // Hijau
                'cancelled' => 'background-color: #f2dede; color: #a94442; border-color: #ebccd1;', // Merah
                'partial' => 'background-color: #d9edf7; color: #31708f; border-color: #bce8f1;', // Biru
                'sent' => 'background-color: #d9edf7; color: #31708f; border-color: #bce8f1;', // Biru
                default => 'background-color: #fcf8e3; color: #8a6d3b; border-color: #faebcc;', // Kuning (Draft)
            };
            $statusLabel = match ($purchaseOrder->status) {
                'completed' => 'SELESAI',
                'cancelled' => 'DIBATALKAN',
                'partial' => 'DITERIMA SEBAGIAN',
                'sent' => 'DIKIRIM KE SUPPLIER',
                default => 'DRAFT',
            };
        ?>
        <span class="status-badge" style="<?php echo e($statusColor); ?>"><?php echo e($statusLabel); ?></span>
    </div>

    <!-- 3. INFO SUPPLIER & TANGGAL -->
    <table class="details-table">
        <tr>
            <!-- Kiri: Info Supplier -->
            <td style="width: 55%;">
                <div class="client-box">
                    <span style="color: #666; font-size: 10px; text-transform: uppercase;">Kepada Yth. / Vendor:</span><br>
                    <strong style="font-size: 14px;"><?php echo e($purchaseOrder->supplier?->name ?? 'Supplier'); ?></strong><br>
                    <div style="margin-top: 5px; color: #444;">
                        <?php echo e($purchaseOrder->supplier?->address ?? 'Alamat tidak tersedia'); ?><br>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($purchaseOrder->supplier?->email)): ?> Email: <?php echo e($purchaseOrder->supplier->email); ?> <br><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($purchaseOrder->supplier?->phone)): ?> Telp: <?php echo e($purchaseOrder->supplier->phone); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </td>
            <!-- Kanan: Info Tanggal -->
            <td style="width: 45%; padding-left: 20px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 40%; color: #666;">Tanggal Pesan:</td>
                        <td style="font-weight: bold;"><?php echo e(\Carbon\Carbon::parse($purchaseOrder->order_date)->format('d F Y')); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #666;">Estimasi Tiba:</td>
                        <td style="font-weight: bold; color: #c00;"><?php echo e($purchaseOrder->expected_delivery_date ? \Carbon\Carbon::parse($purchaseOrder->expected_delivery_date)->format('d F Y') : '-'); ?></td>
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
                <th style="width: 45%;">Deskripsi Barang</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 20%;">Harga Satuan</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $purchaseOrder->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="<?php echo e($index % 2 == 0 ? '' : 'row-bg'); ?>">
                    <td class="text-center"><?php echo e($index + 1); ?></td>
                    <td>
                        <strong><?php echo e($item->product?->product_name ?? 'Produk'); ?></strong>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item->product?->sku)): ?>
                            <br><small style="color: #666;">SKU: <?php echo e($item->product->sku); ?></small>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo e($item->quantity); ?></td>
                    <td class="text-right">IDR <?php echo e(number_format((float) ($item->unit_price ?? 0), 0, ',', '.')); ?></td>
                    <td class="text-right">IDR <?php echo e(number_format((float) ($item->total_price ?? 0), 0, ',', '.')); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <!-- 5. TOTAL & INFO PENGIRIMAN -->
    <div class="totals-section">

        <!-- Info Pengiriman -->
        <div class="payment-info">
            <h4>Instruksi Pengiriman</h4>
            <p style="margin: 0; font-size: 11px;">
                Mohon kirimkan barang beserta kelengkapannya ke alamat kami:<br>
                <strong>PT. NEXT GENERATION SOLUTIONS</strong><br>
                Jl. Lingkar Selatan Sengkol No. 18, Setu<br>
                Tangerang Selatan<br>
                <br>
                <em style="font-size: 10px; color: #666;">*Mohon cantumkan No. PO (<?php echo e($purchaseOrder->po_number); ?>)
                    pada Surat Jalan / Invoice Anda.</em>
            </p>
        </div>

        <!-- Tabel Kalkulasi -->
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">IDR <?php echo e(number_format((float) ($purchaseOrder->subtotal ?? 0), 0, ',', '.')); ?></td>
            </tr>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($purchaseOrder->discount_amount > 0): ?>
                <tr>
                    <td class="label" style="color: #c00;">Diskon:</td>
                    <td class="amount" style="color: #c00;">
                        - IDR <?php echo e(number_format((float) ($purchaseOrder->discount_amount ?? 0), 0, ',', '.')); ?>

                    </td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($purchaseOrder->tax_amount > 0): ?>
                <tr>
                    <td class="label">Pajak (PPN):</td>
                    <td class="amount">
                        IDR <?php echo e(number_format((float) ($purchaseOrder->tax_amount ?? 0), 0, ',', '.')); ?>

                    </td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <tr class="grand-total-row">
                <td class="label">GRAND TOTAL:</td>
                <td class="amount">IDR <?php echo e(number_format((float) ($purchaseOrder->grand_total ?? 0), 0, ',', '.')); ?></td>
            </tr>
        </table>
    </div>

    <div class="clearfix"></div>

    <!-- 6. CATATAN KAKI -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($purchaseOrder->notes): ?>
        <div class="notes-section">
            <strong>Catatan Tambahan:</strong><br>
            <?php echo e($purchaseOrder->notes); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- 7. TANDA TANGAN -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Tangerang Selatan, <?php echo e(now()->format('d F Y')); ?></p>
            <p style="font-weight: bold;">PT. NEXT GENERATION SOLUTIONS</p>

            <div class="sign-line"></div>

            <p style="font-weight: bold; margin-bottom: 0;">
                Procurement Department
            </p>
            <span style="font-size: 10px;">Authorized Signature</span>
        </div>
    </div>

</body>

</html>
<?php /**PATH /var/www/erp-app-main/resources/views/pdf/purchase-order.blade.php ENDPATH**/ ?>