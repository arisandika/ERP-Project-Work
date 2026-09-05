<div class="min-h-screen transition-colors duration-300 bg-main-light dark:bg-main-dark">

    {{-- NAVBAR --}}
    <nav class="sticky top-0 z-40 w-full border-b backdrop-blur-md bg-secondary-light dark:bg-secondary-dark border-border-light dark:border-border-dark">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div>
                    <div style="height: 1.5rem;" class="flex fi-logo">
                        <div class="flex items-center">
                            <img src="{{ asset('assets/logo-dark.webp') }}" alt="Logo" class="h-8 dark:hidden">
                            <img src="{{ asset('assets/logo-light.webp') }}" alt="Logo" class="hidden h-8 dark:block">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ route('customer-portal.dashboard') }}"
                        class="flex items-center px-3 py-2 space-x-1 text-sm font-medium transition-colors duration-200 rounded-lg text-black/70 dark:text-white/70 hover:bg-main-light dark:hover:bg-main-dark">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span class="hidden sm:inline">Kembali</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- HEADER --}}
    <div class="max-w-4xl px-4 py-6 mx-auto md:py-0 lg:px-8">
        <div class="sm:py-6">
            <p class="mb-1 text-sm font-semibold text-gray-600 uppercase dark:text-gray-400">Customer Portal</p>
            <h1 class="text-2xl font-bold text-black sm:text-3xl dark:text-white">Detail Return #{{ $returnRequest->rma_number }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Informasi lengkap pengajuan return Anda.</p>
        </div>
    </div>

    <div class="max-w-4xl px-4 pb-12 mx-auto lg:px-8 space-y-6">

        {{-- STATUS CARD --}}
        @php $badge = $this->getStatusBadgeColor($returnRequest->status); @endphp
        <div class="flex items-center justify-between p-6 border rounded-2xl bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium {{ $badge['bg'] }} {{ $badge['text'] }}">
                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $badge['dot'] }}"></span>
                    {{ $statusLabels[$returnRequest->status] ?? $returnRequest->status }}
                </span>
            </div>
            <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                Diajukan: {{ $returnRequest->created_at->format('d M Y H:i') }}
            </div>
        </div>

        {{-- DETAIL GRID --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            {{-- Product Info --}}
            <div class="p-6 border rounded-2xl bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
                <p class="text-xs text-gray-500 dark:text-gray-400">Produk</p>
                <p class="mt-1 font-semibold text-black dark:text-white">
                    {{ $returnRequest->serialNumber?->product?->name
                        ?? $returnRequest->product?->name
                        ?? '-' }}
                </p>
                @if($returnRequest->serialNumber)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        SN: <span class="font-mono text-black dark:text-white">{{ $returnRequest->serialNumber->serial_number }}</span>
                    </p>
                @endif
                @if($returnRequest->qty)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Qty: {{ $returnRequest->qty }}</p>
                @endif
            </div>

            {{-- Invoice Info --}}
            <div class="p-6 border rounded-2xl bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
                <p class="text-xs text-gray-500 dark:text-gray-400">Invoice</p>
                <p class="mt-1 font-semibold text-black dark:text-white">{{ $returnRequest->invoice?->invoice_number ?? '-' }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Tanggal: {{ $returnRequest->invoice?->invoice_date?->format('d M Y') ?? '-' }}
                </p>
            </div>

            {{-- Issue Type --}}
            <div class="p-6 border rounded-2xl bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
                <p class="text-xs text-gray-500 dark:text-gray-400">Jenis Kendala</p>
                <p class="mt-1 font-semibold text-black dark:text-white capitalize">
                    {{ \App\Models\AfterSales\ReturnRequest::getIssueTypeLabels()[$returnRequest->issue_type] ?? '-' }}
                </p>
                @if($returnRequest->resolution_type)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Penyelesaian: <span class="font-medium text-black dark:text-white">{{ ucfirst($returnRequest->resolution_type) }}</span>
                    </p>
                @endif
            </div>
        </div>

        {{-- ISSUE DESCRIPTION --}}
        <div class="p-6 border rounded-2xl bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
            <p class="text-xs text-gray-500 dark:text-gray-400">Keluhan / Detail Kerusakan</p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $returnRequest->issue_description }}</p>
        </div>

        {{-- EVIDENCE --}}
        @if($returnRequest->evidence_files && count($returnRequest->evidence_files) > 0)
            <div class="p-6 border rounded-2xl bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Bukti Foto ({{ count($returnRequest->evidence_files) }})</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach($returnRequest->evidence_files as $file)
                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="block overflow-hidden border rounded-xl border-border-light dark:border-border-dark">
                            <img src="{{ asset('storage/' . $file) }}" alt="Bukti" class="object-cover w-full h-24 rounded-xl">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- INTERNAL NOTES --}}
        @if($returnRequest->internal_notes)
            <div class="p-6 border rounded-2xl bg-yellow-50 dark:bg-yellow-900/10 ring-1 ring-yellow-200 dark:ring-yellow-900/50">
                <p class="text-xs text-yellow-700 dark:text-yellow-400">Catatan Internal CS</p>
                <p class="mt-1 text-sm text-yellow-800 dark:text-yellow-300">{{ $returnRequest->internal_notes }}</p>
            </div>
        @endif

        {{-- VENDOR NOTES --}}
        @if($returnRequest->vendor_notes)
            <div class="p-6 border rounded-2xl bg-blue-50 dark:bg-blue-900/10 ring-1 ring-blue-200 dark:ring-blue-900/50">
                <p class="text-xs text-blue-700 dark:text-blue-400">Catatan dari Vendor</p>
                <p class="mt-1 text-sm text-blue-800 dark:text-blue-300">{{ $returnRequest->vendor_notes }}</p>
            </div>
        @endif

        {{-- DATES --}}
        <div class="p-6 border rounded-2xl bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Riwayat Status</p>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Tanggal Diajukan</span>
                    <span class="text-black dark:text-white">{{ $returnRequest->created_at->format('d M Y') }}</span>
                </div>
                @if($returnRequest->received_date)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Tanggal Diterima</span>
                        <span class="text-black dark:text-white">{{ \Carbon\Carbon::parse($returnRequest->received_date)->format('d M Y') }}</span>
                    </div>
                @endif
                @if($returnRequest->sent_to_vendor_date)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Dikirim ke Vendor</span>
                        <span class="text-black dark:text-white">{{ \Carbon\Carbon::parse($returnRequest->sent_to_vendor_date)->format('d M Y') }}</span>
                    </div>
                @endif
                @if($returnRequest->back_from_vendor_date)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Kembali dari Vendor</span>
                        <span class="text-black dark:text-white">{{ \Carbon\Carbon::parse($returnRequest->back_from_vendor_date)->format('d M Y') }}</span>
                    </div>
                @endif
                @if($returnRequest->returned_to_client_date)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Selesai / Dikirim ke Klien</span>
                        <span class="text-black dark:text-white">{{ \Carbon\Carbon::parse($returnRequest->returned_to_client_date)->format('d M Y') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>