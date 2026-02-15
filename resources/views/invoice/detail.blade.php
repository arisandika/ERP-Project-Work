<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invoice {{ $invoice->invoice_number }} - Nexicon</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">

        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 mb-2">Invoice Valid!</h2>
        <p class="text-gray-500 mb-6">Dokumen ini resmi terdaftar di sistem kami.</p>

        @php
            $status = strtolower((string) ($invoice->status ?? 'unknown'));
            $isPaid = in_array($status, ['paid', 'lunas', 'settled'], true);
            $isPending = in_array($status, ['pending', 'unpaid', 'belum bayar', 'belum_bayar'], true);
        @endphp

        <div class="border-t border-b border-gray-200 py-4 text-left space-y-2 text-sm">
            <div class="flex justify-between gap-4">
                <span class="text-gray-600">No. Invoice:</span>
                <span class="font-bold text-right break-all">{{ $invoice->invoice_number }}</span>
            </div>

            <div class="flex justify-between gap-4">
                <span class="text-gray-600">Client:</span>
                <span class="font-bold text-right">{{ $invoice->customer->name ?? '-' }}</span>
            </div>

            @if(!empty($invoice->invoice_date))
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600">Tanggal:</span>
                    <span class="font-bold text-right">
                        {{ \Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                    </span>
                </div>
            @endif

            <div class="flex justify-between gap-4">
                <span class="text-gray-600">Total:</span>
                <span class="font-bold text-right">
                    Rp {{ number_format((float) $invoice->grand_total, 0, ',', '.') }}
                </span>
            </div>

            <div class="flex justify-between items-center gap-4">
                <span class="text-gray-600">Status:</span>

                <span @class([
                    'px-2 py-1 rounded text-xs font-bold uppercase',
                    'bg-green-200 text-green-800' => $isPaid,
                    'bg-yellow-200 text-yellow-800' => $isPending && ! $isPaid,
                    'bg-gray-200 text-gray-800' => ! $isPaid && ! $isPending,
                ])>
                    {{ $invoice->status ?? 'unknown' }}
                </span>
            </div>
        </div>

        <div class="mt-6 text-xs text-gray-400">
            <div>PT. NEXT GENERATION SOLUTIONS</div>
            <div>www.nexicon.id</div>
        </div>

        <div class="mt-6">
            <a href="{{ route('invoice.verify.form', ['number' => $invoice->invoice_number]) }}"
               class="text-sm text-blue-600 hover:underline">
                Kembali ke halaman verifikasi
            </a>
        </div>
    </div>
</body>
</html>
