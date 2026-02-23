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
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
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
            margin: 95px auto 5px auto; /* Jarak untuk TTD basah penerima */
        }

        /* Style QR Code persis Invoice */
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

    <!-- 1. HEADER PERUSAHAAN -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('assets/logo2.png') }}" alt="Logo">
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

    <!-- 2. JUDUL DOKUMEN -->
    <div class="document-title">
        <h1>SURAT JALAN</h1>
        <p>No: {{ $record->do_number }}</p>
    </div>

    <!-- 3. INFO PENGIRIMAN -->
    <table class="details-table">
        <tr>
            <!-- Kiri: Penerima -->
            <td style="width: 55%; padding-right: 20px;">
                <strong>Dikirim Kepada:</strong>
                <div class="client-box">
                    <strong style="font-size: 13px; text-transform: uppercase;">{{ $record->customer->name ?? 'UMUM' }}</strong><br>
                    <div style="margin-top: 5px; color: #444;">
                        {{ $record->customer->address ?? 'Alamat tidak tersedia' }}<br>
                        Telp: {{ $record->customer->phone ?? '-' }}
                    </div>
                </div>
            </td>
            <!-- Kanan: Detail Dokumen -->
            <td style="width: 45%;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 40%;">Tanggal Kirim</td>
                        <td>: <strong>{{ \Carbon\Carbon::parse($record->do_date)->format('d F Y') }}</strong></td>
                    </tr>
                    <tr>
                        <td>Referensi SO</td>
                        <td>: {{ $record->salesOrder->order_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>No. PO Customer</td>
                        <td>: {{ $record->salesOrder->customer_po_number ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 4. TABEL BARANG (NO PRICES!) -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 15%;">Kode Barang</th>
                <th style="width: 40%;">Nama Barang / Deskripsi</th>
                <th style="width: 10%;" class="text-center">Satuan</th>
                <th style="width: 15%;" class="text-center">Qty Pesan</th>
                <th style="width: 15%;" class="text-center">Qty Kirim</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($record->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->item_code ?? '-' }}</td>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->item_desc) <br><small style="color:#666">{{ $item->item_desc }}</small> @endif
                    </td>
                    <td class="text-center">{{ $item->uom ?? 'Pcs' }}</td>
                    <td class="text-center">{{ number_format($item->qty_ordered, 0, ',', '.') }}</td>
                    <td class="text-center" style="font-weight: bold;">
                        {{ number_format($item->qty, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Data item tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 5. CATATAN -->
    @if($record->notes)
        <div style="margin-bottom: 20px; font-size: 10px; border: 1px dashed #aaa; padding: 5px;">
            <strong>Catatan Pengiriman:</strong> {{ $record->notes }}
        </div>
    @endif

    <!-- 6. TANDA TANGAN (Hanya 2 Kolom: Pengirim dgn QR & Penerima) -->
    <table class="signature-table">
        <tr>
            <!-- Kiri: Pengirim / Admin (Pakai QR Code sebagai TTD Digital) -->
            <td style="width: 50%;">
                <span class="signature-header">Hormat Kami,</span>

                @if(isset($qrCode))
                    <div class="qr-container">
                        <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Validasi" style="width: 80px; height: 80px;">
                    </div>
                @else
                    <div class="signature-line" style="margin-top: 80px; margin-bottom: 14px;"></div>
                @endif

                <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0; margin-top: 5px;">
                    PT. NEXT GENERATION SOLUTIONS
                </p>
                <span style="font-size: 10px; display:block; margin-top:2px;">
                    PIC: {{ $record->employee->full_name ?? 'Gudang / Admin' }}
                </span>
            </td>

            <!-- Kanan: Penerima Barang (TTD Basah) -->
            <td style="width: 50%;">
                <span class="signature-header">
                    Penerima Barang<br>
                    <span style="font-size: 10px; font-weight: normal; color: #555;">
                        ({{ $record->customer->name ?? 'UMUM' }})
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
        * Scan QR Code di atas untuk memvalidasi Surat Jalan & melacak status pengiriman.
    </p>

</body>

</html>
