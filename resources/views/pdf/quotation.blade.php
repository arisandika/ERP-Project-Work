<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Penawaran - {{ $quotation->quotation_number }}</title>
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
        <h1>PENAWARAN</h1>
        <p>No: {{ $quotation->quotation_number }}</p>

        {{-- Status Badge Logic --}}
        <?php
            $quotationStatus = $quotation->status?->value ?? $quotation->status;
            $statusColor = match ($quotationStatus) {
                'accepted' => 'background-color: #dcfce7; color: #15803d;', // Hijau
                'rejected' => 'background-color: #fee2e2; color: #b91c1c;', // Merah
                'sent' => 'background-color: #dbeafe; color: #1d4ed8;', // Biru
                'negotiation' => 'background-color: #dbeafe; color: #1d4ed8;', // Biru
                'expired' => 'background-color: #e5e7eb; color: #374151;', // Abu-abu
                default => 'background-color: #fef9c3; color: #a16207;', // Kuning (Baru)
            };
            $statusLabel = match ($quotationStatus) {
                'accepted' => 'DITERIMA',
                'rejected' => 'DITOLAK',
                'sent' => 'TERKIRIM',
                'negotiation' => 'NEGOSIASI',
                'expired' => 'EXPIRED',
                default => 'BARU',
            };
        ?>
        <span class="status-badge" style="{{ $statusColor }}">{{ $statusLabel }}</span>
    </div>

    <!-- 3. INFO KLIEN & TANGGAL -->
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
            <!-- Kiri: Info Klien -->
            <td style="width: 55%;">
                <div class="client-box">
                    <span style="color: #6b7280; font-size: 10px; text-transform: uppercase;">Ditujukan Kepada:</span><br>
                    <strong style="font-size: 14px;">{{ $clientName }}</strong><br>
                    <div style="margin-top: 5px; color: #4b5563;">
                        {{ $clientAddress }}<br>
                        <?php if ($clientEmail): ?>
                            Email: {{ $clientEmail }}
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <!-- Kanan: Info Tanggal -->
            <td style="width: 45%; padding-left: 20px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 40%; color: #6b7280;">Tanggal Penawaran:</td>
                        <td style="font-weight: bold;">{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Berlaku Hingga:</td>
                        <td style="font-weight: bold; color: #b91c1c;">{{ \Carbon\Carbon::parse($quotation->valid_until)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Dibuat Oleh:</td>
                        <td style="font-weight: bold;">{{ $creatorName }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 4. TABEL ITEM -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Item / Deskripsi</th>
                <th class="text-right" style="width: 20%;">Jumlah</th>
                <th class="text-right" style="width: 20%;">Harga Satuan</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($quotation->items as $index => $item)
                <tr class="{{ $index % 2 == 0 ? '' : 'row-bg' }}">
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        <?php if ($item->item_code): ?>
                            <br><small style="color: #6b7280;">Kode: {{ $item->item_code }}</small>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">{{ $item->qty ?? $item->quantity ?? 1 }}</td>
                    <td class="text-right">IDR {{ number_format((float) ($item->unit_price ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">IDR {{ number_format((float) ($item->line_total ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada item dalam penawaran ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 5. TOTAL & CATATAN -->
    <div class="totals-section">

        <!-- Info Tambahan -->
        <div class="payment-info">
            <h4>Syarat & Ketentuan</h4>
            <p style="margin: 0; font-size: 11px;">
                Penawaran ini berlaku hingga tanggal yang tercantum di atas. Harga dapat berubah
                sewaktu-waktu setelah tanggal tersebut.<br>
                <br>
                <em style="font-size: 10px; color: #6b7280;">*Mohon cantumkan No. Penawaran ({{ $quotation->quotation_number }})
                    saat konfirmasi pemesanan.</em>
            </p>
        </div>

        <!-- Tabel Kalkulasi -->
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">IDR {{ number_format((float) ($quotation->subtotal ?? 0), 0, ',', '.') }}</td>
            </tr>

            <?php if ((float) $quotation->discount_amount > 0): ?>
                <tr>
                    <td class="label" style="color: #b91c1c;">Diskon:</td>
                    <td class="amount" style="color: #b91c1c;">
                        - IDR {{ number_format((float) $quotation->discount_amount, 0, ',', '.') }}
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ((float) $quotation->tax > 0): ?>
                <?php
                    // Menghitung nominal pajak (Subtotal - Diskon) * (Tax / 100)
                    $subtotalAfterDiscount = (float) $quotation->subtotal - (float) $quotation->discount_amount;
                    $taxNominal = $subtotalAfterDiscount * ((float) $quotation->tax / 100);
                ?>
                <tr>
                    <td class="label">Pajak (PPN {{ $quotation->tax }}%):</td>
                    <td class="amount">IDR {{ number_format($taxNominal, 0, ',', '.') }}</td>
                </tr>
            <?php endif; ?>

            <tr class="grand-total-row">
                <td class="label">GRAND TOTAL:</td>
                <td class="amount">IDR {{ number_format((float) ($quotation->grand_total ?? 0), 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="clearfix"></div>

    <!-- 6. CATATAN KAKI -->
    <?php if ($quotation->notes): ?>
        <div class="notes-section">
            <strong>Catatan Tambahan:</strong><br>
            {{ $quotation->notes }}
        </div>
    <?php endif; ?>

    <!-- 7. TANDA TANGAN -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Tangerang Selatan, {{ now()->format('d F Y') }}</p>
            <p style="font-weight: bold;">PT. NEXT GENERATION SOLUTIONS</p>

            <div class="sign-line"></div>

            <p style="font-weight: bold; margin-bottom: 0;">
                {{ $creatorName }}
            </p>
            <span style="font-size: 10px;">Sales Department</span>
        </div>
    </div>

</body>

</html>