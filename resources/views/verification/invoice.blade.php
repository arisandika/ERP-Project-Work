<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Invoice - Nexicon</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
        <!-- Icon Centang Hijau -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 mb-2">Invoice Valid!</h2>
        <p class="text-gray-500 mb-6">Dokumen ini resmi terdaftar di sistem kami.</p>

        <div class="border-t border-b border-gray-200 py-4 text-left space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">No. Invoice:</span>
                <span class="font-bold">{{ $invoice->invoice_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Pelanggan:</span>
                <span class="font-bold">{{ $invoice->customer->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Total:</span>
                <span class="font-bold">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Status:</span>
                <span class="px-2 py-1 rounded text-xs font-bold uppercase
                    {{ $invoice->status == 'paid' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800' }}">
                    {{ $invoice->status }}
                </span>
            </div>
        </div>

        <p class="mt-6 text-xs text-gray-400">
            PT. NEXT GENERATION SOLUTIONS<br>
            www.nexicon.id
        </p>
    </div>

</body>
</html>
