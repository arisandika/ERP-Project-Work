{{-- resources/views/livewire/customer-portal-return-create.blade.php --}}
<div class="min-h-screen transition-colors duration-300 bg-main-light dark:bg-main-dark">

    @if (session('error'))
        <div class="fixed z-50 px-4 py-3 text-red-600 bg-red-100 rounded-md shadow-2xl top-20 right-4" id="error-message">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- NAVBAR -->
    <nav class="sticky top-0 z-40 w-full border-b backdrop-blur-md bg-secondary-light dark:bg-secondary-dark border-border-light dark:border-border-dark">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div style="height: 1.5rem;" class="flex fi-logo">
                    <div class="flex items-center">
                        <img src="{{ asset('assets/logo-dark.webp') }}" alt="Logo" class="h-8 dark:hidden">
                        <img src="{{ asset('assets/logo-light.webp') }}" alt="Logo" class="hidden h-8 dark:block">
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <button @click="toggleTheme()"
                        class="p-2 text-gray-600 transition-colors duration-200 rounded-full hover:bg-main-light dark:text-gray-400 dark:hover:bg-main-dark focus:outline-none focus:ring-2 focus:ring-border-light dark:focus:ring-border-dark">
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                            </path>
                        </svg>
                    </button>
                    <div class="h-6 mx-1 border-l border-border-light dark:border-border-dark"></div>
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

    <!-- Page Header -->
    <div class="max-w-3xl px-4 py-6 mx-auto md:py-0 lg:px-8">
        <div class="sm:py-6">
            <p class="mb-1 text-sm font-semibold text-gray-600 uppercase dark:text-gray-400">Customer Portal</p>
            <h1 class="text-2xl font-bold text-black sm:text-3xl dark:text-white">Ajukan Return</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ikuti langkah berikut untuk mengajukan klaim garansi / perbaikan produk Anda.</p>
        </div>
    </div>

    <div class="max-w-3xl px-4 pb-12 mx-auto lg:px-8">

        <!-- STEP INDICATOR -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                @php
                    $steps = [
                        1 => 'Invoice',
                        2 => 'Detail Kendala',
                        3 => 'Identifikasi Barang',
                        4 => 'Bukti & Kirim',
                    ];
                @endphp
                @foreach ($steps as $num => $label)
                    <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center gap-2">
                            <div class="flex items-center justify-center w-9 h-9 text-sm font-bold rounded-full shrink-0 transition-colors
                                {{ $step > $num
                                    ? 'bg-main-primary text-white'
                                    : ($step === $num
                                        ? 'bg-main-primary text-white ring-4 ring-main-primary/20'
                                        : 'bg-gray-200 text-gray-500 dark:bg-white/10 dark:text-gray-400') }}">
                                @if ($step > $num)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                @else
                                    {{ $num }}
                                @endif
                            </div>
                            <span class="text-[11px] font-medium text-center whitespace-nowrap {{ $step >= $num ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $label }}
                            </span>
                        </div>
                        @if (!$loop->last)
                            <div class="flex-1 h-0.5 mx-2 mb-5 transition-colors {{ $step > $num ? 'bg-main-primary' : 'bg-gray-200 dark:bg-white/10' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">

            {{-- ================= STEP 1: CARI INVOICE ================= --}}
            @if ($step === 1)
                <div class="p-6 lg:p-8">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Masukkan Nomor Invoice</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Nomor invoice bisa dilihat pada dokumen invoice yang Anda terima dari kami.</p>

                    <form wire:submit.prevent="findInvoice" class="mt-5">
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <div class="flex-1 fi-input-wrp py-1.5 flex rounded-2xl shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-blue-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-blue-500 overflow-hidden">
                                <input type="text" wire:model="invoice_number_input" placeholder="Contoh: 001/INV/NEX/VIII/2026"
                                    class="fi-input block w-full border-none py-1.5 text-base text-black transition duration-75 placeholder:text-gray-400 focus:ring-0 outline-none focus:outline-none dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3" />
                            </div>
                            <button type="submit" wire:loading.attr="disabled" wire:target="findInvoice"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white transition-colors rounded-full shadow-sm bg-main-primary hover:bg-main-primary/90 disabled:opacity-50">
                                <svg wire:loading.remove wire:target="findInvoice" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.35-5.15a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <svg wire:loading wire:target="findInvoice" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Cari Invoice
                            </button>
                        </div>
                        @error('invoice_number_input')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </form>

                    {{-- Hasil pencarian invoice --}}
                    @if ($invoice)
                        <div class="pt-6 mt-6 border-t border-border-light dark:border-border-dark">
                            <div class="flex flex-wrap items-center justify-between gap-2 p-4 rounded-2xl bg-main-light dark:bg-white/5 ring-1 ring-border-light dark:ring-border-dark">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No. Invoice</p>
                                    <p class="font-semibold text-black dark:text-white">{{ $invoice->invoice_number }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal</p>
                                    <p class="font-semibold text-black dark:text-white">{{ $invoice->invoice_date?->format('d M Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                                    <p class="font-semibold text-black dark:text-white">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400">
                                    {{ strtoupper($invoice->status) }}
                                </span>
                            </div>

                            <p class="mt-5 mb-3 text-sm font-semibold text-black dark:text-white">Pilih Produk yang Ingin Diretur</p>

                            @if (count($invoiceItems) === 0)
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada produk yang bisa diajukan return pada invoice ini.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($invoiceItems as $item)
                                        <button type="button" wire:click="proceedToStep2({{ $item->id }})"
                                            class="flex items-center justify-between w-full p-4 text-left transition-colors border rounded-2xl border-border-light dark:border-border-dark hover:border-main-primary hover:bg-main-primary/5 dark:hover:bg-main-primary/10 group">
                                            <div>
                                                <p class="font-medium text-black dark:text-white">{{ $item->item_name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Kode: {{ $item->item_code }} &middot; Qty: {{ $item->qty }}</p>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400 transition-colors group-hover:text-main-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- ================= STEP 2: DETAIL KENDALA ================= --}}
            @if ($step === 2 && $selectedItem)
                <div class="p-6 lg:p-8">
                    <button type="button" wire:click="backTo(1)" class="flex items-center gap-1 mb-4 text-sm text-gray-500 hover:text-black dark:hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Ganti produk
                    </button>

                    <div class="p-4 mb-6 rounded-2xl bg-main-light dark:bg-white/5 ring-1 ring-border-light dark:ring-border-dark">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Produk dipilih</p>
                        <p class="font-semibold text-black dark:text-white">{{ $selectedItem->item_name }}</p>
                    </div>

                    <h3 class="text-lg font-semibold text-black dark:text-white">Detail Kendala</h3>

                    <form wire:submit.prevent="proceedToStep3" class="grid mt-5 fi-form gap-y-6">
                        <div class="fi-fo-field-wrp">
                            <div class="grid gap-y-2">
                                <label class="text-sm font-medium leading-6 text-black dark:text-white">
                                    Jenis Kendala <sup class="text-red-600 dark:text-red-400">*</sup>
                                </label>
                                <div class="fi-input-wrp py-1.5 flex rounded-2xl shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-blue-600 dark:focus-within:ring-blue-500 overflow-hidden">
                                    <select wire:model="issue_type"
                                        class="fi-input block w-full border-none py-1.5 text-base text-black transition duration-75 focus:ring-0 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3 dark:bg-transparent">
                                        <option value="">-- Pilih jenis kendala --</option>
                                        <option value="damaged">Barang rusak / cacat</option>
                                        <option value="not_working">Barang tidak berfungsi</option>
                                        <option value="wrong_item">Barang yang diterima tidak sesuai</option>
                                        <option value="incomplete">Barang kurang / tidak lengkap</option>
                                        <option value="shipping_damage">Kerusakan saat pengiriman</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>
                                @error('issue_type') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="fi-fo-field-wrp">
                            <div class="grid gap-y-2">
                                <label class="text-sm font-medium leading-6 text-black dark:text-white">
                                    Deskripsi Kendala <sup class="text-red-600 dark:text-red-400">*</sup>
                                </label>
                                <div class="fi-input-wrp py-1.5 flex rounded-2xl shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-blue-600 dark:focus-within:ring-blue-500 overflow-hidden">
                                    <textarea wire:model="issue_description" rows="4" placeholder="Jelaskan kendala pada produk Anda secara detail..."
                                        class="fi-input block w-full border-none py-1.5 text-base text-black transition duration-75 placeholder:text-gray-400 focus:ring-0 outline-none dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3"></textarea>
                                </div>
                                @error('issue_description') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Checkbox: apakah produk ini memiliki Nomor Seri (SN)? --}}
                        <div class="fi-fo-field-wrp">
                            <div class="flex items-start gap-3">
                                <div class="flex items-center pt-0.5 h-5 shrink-0">
                                    <input type="checkbox" wire:model="has_serial" value="1"
                                        class="w-4 h-4 text-main-primary border-gray-300 rounded focus:ring-main-primary dark:bg-white/10 dark:border-gray-600">
                                </div>
                                <label class="text-sm leading-5 text-black dark:text-white">
                                    Produk ini memiliki Nomor Seri (SN). Saya akan scan/masukkan SN unit yang rusak.
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full px-4 py-2.5 text-sm font-semibold text-white rounded-full bg-main-primary hover:bg-main-primary/90">
                            Lanjut
                        </button>
                    </form>
                </div>
            @endif

            {{-- ================= STEP 3: SCAN SN / QTY ================= --}}
            @if ($step === 3 && $selectedItem)
                <div class="p-6 lg:p-8">
                    <button type="button" wire:click="backTo(2)" class="flex items-center gap-1 mb-4 text-sm text-gray-500 hover:text-black dark:hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </button>

                    @if ($productIsSerialized)
                        <h3 class="text-lg font-semibold text-black dark:text-white">Identifikasi Barang (Scan SN)</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Produk ini memiliki SN. Scan barcode/QR atau masukkan SN secara manual.</p>

                        <div class="mt-5">
                            @if ($matchedSerialNumberId)
                                <div class="flex items-center justify-between p-4 border border-green-200 rounded-2xl bg-green-50 dark:bg-green-900/20 dark:border-green-900/50">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 text-green-600 bg-green-100 rounded-full dark:bg-green-900/40 dark:text-green-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs text-green-700 dark:text-green-400">SN Terverifikasi</p>
                                            <p class="font-mono font-semibold text-black dark:text-white">{{ $matchedSerialNumberLabel }}</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="resetScan" class="text-xs font-medium text-gray-500 hover:text-red-600">
                                        Scan Ulang
                                    </button>
                                </div>
                            @else
                                <div x-data="{ showManual: false }">
                                    @include('livewire.partials.sn-scanner', ['wireModel' => 'scannedSerialNumber'])
                                    @error('scannedSerialNumber')
                                        <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror

                                    <div class="mt-4 text-center">
                                        <button type="button" @click="showManual = true"
                                            class="text-sm text-gray-500 hover:text-black dark:hover:text-white underline decoration-gray-400 dark:decoration-gray-500">
                                            Masukkan SN secara manual
                                        </button>
                                    </div>

                                    <div x-show="showManual" class="mt-3" x-cloak>
                                        <div class="fi-input-wrp py-1.5 flex rounded-2xl shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-blue-600 dark:focus-within:ring-blue-500 overflow-hidden">
                                            <input type="text" wire:model="scannedSerialNumber"
                                                placeholder="Masukkan SN di sini..."
                                                class="fi-input block w-full border-none py-1.5 text-base text-black transition duration-75 placeholder:text-gray-400 focus:ring-0 outline-none dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <h3 class="text-lg font-semibold text-black dark:text-white">Jumlah Barang</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Produk ini tidak memiliki SN. Masukkan jumlah unit yang ingin diretur.</p>

                        <div class="mt-5 fi-fo-field-wrp">
                            <div class="grid gap-y-2">
                                <label class="text-sm font-medium leading-6 text-black dark:text-white">
                                    Jumlah (maks. {{ $selectedItem->qty }}) <sup class="text-red-600 dark:text-red-400">*</sup>
                                </label>
                                <div class="fi-input-wrp py-1.5 flex rounded-2xl shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-blue-600 dark:focus-within:ring-blue-500 overflow-hidden max-w-[200px]">
                                    <input type="number" wire:model="qty" min="1" max="{{ $selectedItem->qty }}"
                                        class="fi-input block w-full border-none py-1.5 text-base text-black transition duration-75 focus:ring-0 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3" />
                                </div>
                                @error('qty') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    <button type="button" wire:click="proceedToStep4"
                        class="w-full px-4 py-2.5 mt-6 text-sm font-semibold text-white rounded-full bg-main-primary hover:bg-main-primary/90">
                        Lanjut
                    </button>
                </div>
            @endif

            {{-- ================= STEP 4: UPLOAD BUKTI ================= --}}
            @if ($step === 4)
                <div class="p-6 lg:p-8">
                    <button type="button" wire:click="backTo(3)" class="flex items-center gap-1 mb-4 text-sm text-gray-500 hover:text-black dark:hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </button>

                    <h3 class="text-lg font-semibold text-black dark:text-white">Upload Bukti Kerusakan/Kendala</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Lampirkan foto kondisi produk (maks. 5 foto, masing-masing 5MB).</p>

                    <div class="mt-5">
                        <label for="evidence-upload"
                            class="flex flex-col items-center justify-center w-full gap-2 px-4 py-8 transition-all border-2 border-dashed cursor-pointer rounded-2xl border-main-primary/50 bg-main-primary/5 hover:bg-main-primary/10 dark:bg-white/5 dark:hover:bg-white/10">
                            <svg class="w-8 h-8 text-main-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <span class="text-sm font-medium text-black dark:text-white">Klik untuk pilih foto</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">JPG, PNG — maks. 5MB per file</span>
                            <input id="evidence-upload" type="file" wire:model="evidenceUploads" multiple accept="image/*" class="hidden">
                        </label>

                        <div wire:loading wire:target="evidenceUploads" class="flex items-center gap-2 mt-3 text-sm text-gray-500">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Mengunggah...
                        </div>

                        @error('evidenceUploads') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        @error('evidenceUploads.*') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                        @if (count($evidenceUploads))
                            <div class="grid grid-cols-3 gap-3 mt-4 sm:grid-cols-4">
                                @foreach ($evidenceUploads as $index => $file)
                                    <div class="relative overflow-hidden border aspect-square rounded-xl border-border-light dark:border-border-dark group">
                                        <img src="{{ $file->temporaryUrl() }}" class="object-cover w-full h-full">
                                        <button type="button" wire:click="removeEvidence({{ $index }})"
                                            class="absolute flex items-center justify-center w-6 h-6 text-white transition-opacity bg-red-600 rounded-full opacity-0 top-1.5 right-1.5 group-hover:opacity-100">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Ringkasan sebelum submit --}}
                    <div class="p-4 mt-6 space-y-2 text-sm rounded-2xl bg-main-light dark:bg-white/5 ring-1 ring-border-light dark:ring-border-dark">
                        <p class="mb-2 font-semibold text-black dark:text-white">Ringkasan Pengajuan</p>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Invoice</span><span class="font-medium text-black dark:text-white">{{ $invoice?->invoice_number }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Produk</span><span class="font-medium text-black dark:text-white">{{ $selectedItem?->item_name }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Jenis Kendala</span><span class="font-medium text-black capitalize dark:text-white">{{ \App\Models\AfterSales\ReturnRequest::getIssueTypeLabels()[$issue_type] ?? $issue_type }}</span>
                        </div>
                        @if ($productIsSerialized)
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Serial Number</span><span class="font-mono font-medium text-black dark:text-white">{{ $matchedSerialNumberLabel }}</span>
                            </div>
                        @else
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Jumlah</span><span class="font-medium text-black dark:text-white">{{ $qty }} unit</span>
                            </div>
                        @endif
                    </div>

                    <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit"
                        class="w-full fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 rounded-full fi-color-main-primary fi-size-md gap-1.5 px-3 py-2.5 text-sm inline-grid shadow-sm bg-main-primary text-white hover:bg-main-primary/90 mt-6"
                        wire:loading.class="opacity-50 cursor-not-allowed">
                        <span wire:loading.remove wire:target="submit">Kirim Pengajuan</span>
                        <div wire:loading wire:target="submit" class="flex items-center justify-center w-full gap-2">
                            <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </button>
                </div>
            @endif

        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const errorMessage = document.getElementById('error-message');
        if (errorMessage) setTimeout(() => { errorMessage.style.opacity = '0'; setTimeout(() => errorMessage.remove(), 300); }, 5000);
    });
</script>
@endpush
