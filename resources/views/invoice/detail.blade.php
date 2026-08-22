<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Validasi Invoice {{ $invoice->invoice_number }} - {{ config('app.name') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Plus Jakarta Sans:400,500,600,700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Livewire Styles -->
    @livewireStyles

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        "main-primary": "#1c9cf0",
                        "main-secondary": "#1d91de",

                        "main-light": "#ffffff",
                        "main-dark": "#000", // 'main-dark': '#1c2433',

                        "secondary-light": "#f7f8f8",
                        "secondary-dark": "#17181c", // 'secondary-dark': '#2a303f',

                        "accent-light": "#e5e5e6",
                        "accent-dark": "#232428", // 'accent-dark': '#2a3656',

                        "border-light": "#d9dbdc",
                        "border-dark": "#454649", // 'border-dark': '#3d4354',

                        "main-accent": "#9da1a640",
                        "secondary-accent": "#334c82",
                    },
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body
    class="px-4 antialiased text-black transition-colors duration-300 bg-main-light dark:bg-main-dark dark:text-white md:px-0">

    <div x-data="{
        darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        init() {
            if (this.darkMode) document.documentElement.classList.add('dark');
        }
    }" class="flex flex-col justify-center min-h-screen py-12 sm:px-6 lg:px-8">

        <!-- Theme Toggle -->
        <div class="absolute top-4 right-4">
            <button @click="toggleTheme()"
                class="p-2 text-gray-500 transition-colors duration-200 rounded-full shadow-sm ring-1 ring-border-light dark:ring-border-dark bg-secondary-light hover:bg-main-light dark:bg-secondary-dark dark:text-gray-400 dark:hover:bg-main-dark focus:outline-none focus:ring-2 focus:ring-border-light dark:focus:ring-border-dark">
                <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                    </path>
                </svg>
            </button>
        </div>

        <main
            class="w-full max-w-lg px-6 py-12 mx-auto my-16 shadow-sm bg-secondary-light fi-simple-main ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark rounded-2xl sm:px-12">

            <div class="fi-simple-page">
                @php
                    $status = strtolower((string) ($invoice->status ?? 'unknown'));
                    $isPaid = in_array($status, ['paid', 'lunas', 'settled'], true);
                    $statusLabel = $isPaid ? 'LUNAS' : 'BELUM LUNAS';
                    $statusClass = $isPaid
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 ring-1 ring-green-600/20'
                        : 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30 ring-1 ring-red-200 dark:ring-red-900/50';
                @endphp

                <section class="grid auto-cols-fr gap-y-8">
                    <header class="flex flex-col items-center fi-simple-header">
                        <div style="height: 1.5rem;" class="flex mb-8 fi-logo">
                            <div class="flex items-center">
                                <img src="{{ asset('assets/logo-dark.webp') }}" alt="Logo" class="h-8 dark:hidden">
                                <img src="{{ asset('assets/logo-light.webp') }}" alt="Logo"
                                    class="hidden h-8 dark:block">
                            </div>

                        </div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-center text-black fi-simple-header-heading dark:text-white">
                            Dokumen Valid
                        </h1>
                        <p
                            class="mt-2 text-sm text-center text-gray-500 fi-simple-header-subheading dark:text-gray-400">
                            Dokumen berhasil diverifikasi
                        </p>
                        <div
                            class="flex items-center justify-center w-16 h-16 mt-8 bg-green-100 rounded-full dark:bg-green-900/20 ring-4 ring-green-50 dark:ring-green-900/10">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </header>

                    <div class="grid fi-form gap-y-6">
                        <div
                            class="p-4 rounded-lg bg-main-light dark:bg-secondary-dark ring-1 ring-border-light dark:ring-border-dark">
                            <div class="space-y-4 text-sm">
                                <div
                                    class="flex justify-between pb-3 border-b border-border-light dark:border-border-dark">
                                    <span class="text-gray-500 dark:text-gray-400">No. Invoice</span>
                                    <span
                                        class="font-bold text-black break-all dark:text-white">{{ $invoice->invoice_number }}</span>
                                </div>

                                <div
                                    class="flex justify-between pb-3 border-b border-border-light dark:border-border-dark">
                                    <span class="text-gray-500 dark:text-gray-400">Pelanggan</span>
                                    <span
                                        class="font-semibold text-right text-black dark:text-white">{{ $invoice->customer->name ?? '-' }}</span>
                                </div>

                                @if(!empty($invoice->invoice_date))
                                    <div
                                        class="flex justify-between pb-3 border-b border-border-light dark:border-border-dark">
                                        <span class="text-gray-500 dark:text-gray-400">Tanggal</span>
                                        <span class="font-medium text-black dark:text-white">
                                            {{ \Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                                        </span>
                                    </div>
                                @endif

                                <div
                                    class="flex items-center justify-between pb-3 border-b border-border-light dark:border-border-dark">
                                    <span class="text-gray-500 dark:text-gray-400">Status</span>
                                    <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-gray-500 dark:text-gray-400">Total Tagihan</span>
                                    <span class="text-lg font-bold text-main-primary">
                                        IDR {{ number_format((float) $invoice->grand_total, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if(!$isPaid)
                            <div
                                class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 ring-1 ring-yellow-600/20 dark:ring-yellow-400/20">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 mt-0.5 text-yellow-500 dark:text-yellow-500 flex-shrink-0"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p
                                            class="mb-1 text-xs font-semibold tracking-wide text-yellow-500 uppercase dark:text-yellow-400">
                                            Instruksi Pembayaran</p>
                                        <p class="text-sm font-medium text-black dark:text-white">Bank BCA</p>
                                        <p class="font-mono text-lg font-bold tracking-tight text-black dark:text-white">
                                            555-000-1234</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">a.n PT. Next Generation
                                            Solutions
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3" x-data="{ isDownloading: false }">

                            <a href="{{ route('invoice.download', ['record' => $invoice->id]) }}"
                                @click="isDownloading = true; setTimeout(() => isDownloading = false, 3000)"
                                :class="{ 'opacity-75 cursor-wait pointer-events-none': isDownloading }"
                                class="w-full fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-full fi-color-main-primary fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2.5 text-sm inline-grid shadow-sm bg-main-primary text-white hover:bg-main-primary/90 focus-visible:ring-main-primary/50 dark:bg-main-primary dark:hover:bg-main-primary/90 dark:focus-visible:ring-main-primary/50 fi-ac-action fi-ac-btn-action">

                                <div x-show="!isDownloading" class="flex items-center gap-1.5">
                                    <svg class="w-5 h-5 transition-transform group-hover:-translate-y-0.5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <span>Download PDF</span>
                                </div>

                                <div x-show="isDownloading" style="display: none;" class="flex items-center gap-2">
                                    <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </div>
                            </a>

                            <a href="https://www.nexicon.id"
                                class="block w-full py-2 text-sm font-medium text-center text-gray-500 transition-colors hover:text-black dark:text-gray-400 dark:hover:text-white">
                                &larr; Kembali ke Website
                            </a>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center">
                        <p class="text-xs text-gray-500">
                            PT. NEXT GENERATION SOLUTIONS<br>
                            <a href="https://www.nexicon.id"
                                class="font-medium transition-colors hover:text-blue-600 dark:hover:text-blue-400">www.nexicon.id</a>
                        </p>
                    </div>

                </section>

            </div>

        </main>

    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>

</html>