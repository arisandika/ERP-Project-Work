<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Validasi Invoice {{ $invoice->invoice_number }} - Nexicon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100">

        <!-- Header Validasi -->
        <div class="bg-green-50 p-6 text-center border-b border-green-100">
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-green-100 mb-3 ring-4 ring-white">
                <svg class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Dokumen Valid</h2>
            <p class="text-xs text-green-700 font-medium uppercase tracking-wide mt-1">Terverifikasi Resmi Nexicon</p>
        </div>

        @php
            $status = strtolower((string) ($invoice->status ?? 'unknown'));
            $isPaid = in_array($status, ['paid', 'lunas', 'settled'], true);
            $statusLabel = $isPaid ? 'LUNAS' : 'BELUM LUNAS';
            $statusClass = $isPaid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
        @endphp

        <div class="p-6">
            <!-- Detail Utama -->
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">No. Invoice</span>
                    <span class="font-bold text-gray-900">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Pelanggan</span>
                    <span class="font-semibold text-gray-900 text-right">{{ $invoice->customer->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Tanggal</span>
                    <span class="font-medium text-gray-900">
                        {{ \Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                    </span>
                </div>
                <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Status</span>
                    <span class="px-2 py-1 rounded text-xs font-bold {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-gray-500">Total Tagihan</span>
                    <span class="text-lg font-bold text-blue-600">
                        Rp {{ number_format((float) $invoice->grand_total, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Info Rekening (Hanya muncul jika BELUM LUNAS) -->
            @if(!$isPaid)
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-yellow-600 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-xs font-bold text-yellow-800 uppercase tracking-wide mb-1">Instruksi Pembayaran</p>
                        <p class="text-sm text-gray-800">Bank BCA</p>
                        <p class="text-lg font-mono font-bold text-gray-900 tracking-tight">555-000-1234</p>
                        <p class="text-xs text-gray-500 mt-1">a.n PT. Next Generation Solutions</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Tombol Action -->
            <div class="mt-6 space-y-3">
                <!-- Tombol Download -->
                <!-- Pastikan route 'invoice.download' sudah dibuat di web.php -->
                <a href="{{ route('invoice.download', ['record' => $invoice->id]) }}"
                   class="flex items-center justify-center w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition duration-200 shadow-sm group">
                    <svg class="w-5 h-5 mr-2 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download PDF
                </a>

                <a href="https://www.nexicon.id" class="block w-full text-center text-sm text-gray-400 hover:text-gray-600 transition py-2">
                    &larr; Kembali ke Website
                </a>
            </div>
        </div>

        <div class="bg-gray-50 py-3 text-center border-t border-gray-100">
            <p class="text-[10px] text-gray-400 font-medium">© {{ date('Y') }} PT. NEXT GENERATION SOLUTIONS</p>
        </div>
    </div>
</body>
</html>
