<!DOCTYPE html>
<html>

<head>
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
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

    <!-- 2. JUDUL DOKUMEN -->
    <div class="document-title">
        <h1>PURCHASE ORDER</h1>
        <p>No: {{ $purchaseOrder->po_number }}</p>

        {{-- Status Badge Logic --}}
        <?php
            $purchaseOrderStatus = $purchaseOrder->status?->value ?? $purchaseOrder->status;
            $statusColor = match ($purchaseOrderStatus) {
                'completed' => 'background-color: #dcfce7; color: #15803d;', // Hijau
                'cancelled' => 'background-color: #fee2e2; color: #b91c1c;', // Merah
                'partial' => 'background-color: #dbeafe; color: #1d4ed8;', // Biru
                'sent' => 'background-color: #dbeafe; color: #1d4ed8;', // Biru
                default => 'background-color: #fef9c3; color: #a16207;', // Kuning (Draft)
            };
            $statusLabel = match ($purchaseOrderStatus) {
                'completed' => 'SELESAI',
                'cancelled' => 'DIBATALKAN',
                'partial' => 'DITERIMA SEBAGIAN',
                'sent' => 'DIKIRIM KE SUPPLIER',
                default => 'DRAFT',
            };
        ?>
        <span class="status-badge" style="{{ $statusColor }}">{{ $statusLabel }}</span>
    </div>

    <!-- 3. INFO SUPPLIER & TANGGAL -->
    <table class="details-table">
        <tr>
            <!-- Kiri: Info Supplier -->
            <td style="width: 55%;">
                <div class="client-box">
                    <span style="color: #6b7280; font-size: 10px; text-transform: uppercase;">Kepada Yth. / Vendor:</span><br>
                    <strong style="font-size: 14px;">{{ $purchaseOrder->supplier?->name ?? 'Supplier' }}</strong><br>
                    <div style="margin-top: 5px; color: #4b5563;">
                        {{ $purchaseOrder->supplier?->address ?? 'Alamat tidak tersedia' }}<br>
                        @if(!empty($purchaseOrder->supplier?->email)) Email: {{ $purchaseOrder->supplier?->email }} <br>@endif
                        @if(!empty($purchaseOrder->supplier?->phone)) Telp: {{ $purchaseOrder->supplier?->phone }} @endif
                    </div>
                </div>
            </td>
            <!-- Kanan: Info Tanggal -->
            <td style="width: 45%; padding-left: 20px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 40%; color: #6b7280;">Tanggal Pesan:</td>
                        <td style="font-weight: bold;">{{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Estimasi Tiba:</td>
                        <td style="font-weight: bold; color: #b91c1c;">{{ $purchaseOrder->expected_delivery_date ? \Carbon\Carbon::parse($purchaseOrder->expected_delivery_date)->format('d F Y') : '-' }}</td>
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
            @foreach ($purchaseOrder->items as $index => $item)
                <tr class="{{ $index % 2 == 0 ? '' : 'row-bg' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product?->product_name ?? 'Produk' }}</strong>
                        @if(!empty($item->product?->sku))
                            <br><small style="color: #6b7280;">SKU: {{ $item->product?->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">IDR {{ number_format((float) ($item->unit_price ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">IDR {{ number_format((float) ($item->total_price ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach
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
                <em style="font-size: 10px; color: #6b7280;">*Mohon cantumkan No. PO ({{ $purchaseOrder->po_number }})
                    pada Surat Jalan / Invoice Anda.</em>
            </p>
        </div>

        <!-- Tabel Kalkulasi -->
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">IDR {{ number_format((float) ($purchaseOrder->subtotal ?? 0), 0, ',', '.') }}</td>
            </tr>

            @if($purchaseOrder->discount_amount > 0)
                <tr>
                    <td class="label" style="color: #b91c1c;">Diskon:</td>
                    <td class="amount" style="color: #b91c1c;">
                        - IDR {{ number_format((float) ($purchaseOrder->discount_amount ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            @if($purchaseOrder->tax_amount > 0)
                <tr>
                    <td class="label">Pajak (PPN):</td>
                    <td class="amount">
                        IDR {{ number_format((float) ($purchaseOrder->tax_amount ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            <tr class="grand-total-row">
                <td class="label">GRAND TOTAL:</td>
                <td class="amount">IDR {{ number_format((float) ($purchaseOrder->grand_total ?? 0), 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="clearfix"></div>

    <!-- 6. CATATAN KAKI -->
    @if($purchaseOrder->notes)
        <div class="notes-section">
            <strong>Catatan Tambahan:</strong><br>
            {{ $purchaseOrder->notes }}
        </div>
    @endif

    <!-- 7. TANDA TANGAN -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Tangerang Selatan, {{ now()->format('d F Y') }}</p>
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