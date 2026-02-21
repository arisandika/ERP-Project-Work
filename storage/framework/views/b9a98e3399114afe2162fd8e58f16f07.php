<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Validasi Invoice <?php echo e($invoice->invoice_number); ?> - <?php echo e(config('app.name')); ?></title>

    <link rel="icon" type="image/x-icon" href="https://www.nexicon.id/favicon.ico">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="antialiased transition-colors duration-300 bg-gray-50 text-gray-950 dark:bg-gray-950 dark:text-white">

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
                class="p-2 text-gray-500 transition-colors duration-200 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700">
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
            class="w-full max-w-lg px-6 py-12 mx-auto my-16 bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 sm:rounded-xl sm:px-12">

            <div class="fi-simple-page">
                <!-- Logic Status PHP -->
                <?php
                    $status = strtolower((string) ($invoice->status ?? 'unknown'));
                    $isPaid = in_array($status, ['paid', 'lunas', 'settled'], true);
                    $statusLabel = $isPaid ? 'LUNAS' : 'BELUM LUNAS';
                    $statusClass = $isPaid
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 ring-1 ring-green-600/20'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 ring-1 ring-yellow-600/20';
                ?>

                <section class="grid auto-cols-fr gap-y-8">
                    <!-- Header: Logo & Status Icon -->
                    <header class="flex flex-col items-center fi-simple-header">
                        <div style="height: 1.5rem;" class="flex mb-8 fi-logo">
                            <div class="flex items-center">
                                <img src="https://erp.arihub.my.id/assets/logo.png" alt="Logo" class="h-11">
                            </div>

                        </div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-center fi-simple-header-heading text-gray-950 dark:text-white">
                            Dokumen Valid
                        </h1>
                        <p class="mt-2 text-sm text-center text-gray-500 fi-simple-header-subheading dark:text-gray-400">
                            Dokumen berhasil diverifikasi
                        </p>
                        <div
                            class="flex items-center justify-center w-16 h-16 mt-4 bg-green-100 rounded-full dark:bg-green-900/20 ring-4 ring-green-50 dark:ring-green-900/10">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </header>

                    <div class="grid fi-form gap-y-6">
                        <!-- Detail Invoice -->
                        <div
                            class="p-4 rounded-lg bg-gray-50 dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="space-y-4 text-sm">
                                <!-- No Invoice -->
                                <div class="flex justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-gray-500 dark:text-gray-400">No. Invoice</span>
                                    <span
                                        class="font-bold text-gray-900 break-all dark:text-white"><?php echo e($invoice->invoice_number); ?></span>
                                </div>

                                <!-- Client -->
                                <div class="flex justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-gray-500 dark:text-gray-400">Pelanggan</span>
                                    <span
                                        class="font-semibold text-right text-gray-900 dark:text-white"><?php echo e($invoice->customer->name ?? '-'); ?></span>
                                </div>

                                <!-- Tanggal -->
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($invoice->invoice_date)): ?>
                                    <div class="flex justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                                        <span class="text-gray-500 dark:text-gray-400">Tanggal</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            <?php echo e(\Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y')); ?>

                                        </span>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <!-- Status -->
                                <div
                                    class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-gray-500 dark:text-gray-400">Status</span>
                                    <span class="px-2.5 py-0.5 rounded-md text-xs font-bold <?php echo e($statusClass); ?>">
                                        <?php echo e($statusLabel); ?>

                                    </span>
                                </div>

                                <!-- Total -->
                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-gray-500 dark:text-gray-400">Total Tagihan</span>
                                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                        Rp <?php echo e(number_format((float) $invoice->grand_total, 0, ',', '.')); ?>

                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Rekening (Conditional) -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isPaid): ?>
                            <div
                                class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 ring-1 ring-yellow-600/20 dark:ring-yellow-400/20">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 mt-0.5 text-yellow-600 dark:text-yellow-500 flex-shrink-0"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p
                                            class="mb-1 text-xs font-bold tracking-wide text-yellow-800 uppercase dark:text-yellow-400">
                                            Instruksi Pembayaran</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Bank BCA</p>
                                        <p class="font-mono text-lg font-bold tracking-tight text-gray-900 dark:text-white">
                                            555-000-1234</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">a.n PT. Next Generation
                                            Solutions
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <!-- Buttons -->
                        <div class="space-y-3">
                            <!-- Tombol Download -->
                            <a href="<?php echo e(route('invoice.download', ['record' => $invoice->id])); ?>"
                                class="relative inline-grid items-center justify-center w-full grid-flow-col gap-2 px-3 py-2 text-sm font-semibold text-white transition duration-75 bg-blue-600 rounded-lg shadow-sm outline-none fi-btn focus-visible:ring-2 fi-color-blue fi-btn-color-blue fi-size-md fi-btn-size-md hover:bg-blue-500 focus-visible:ring-blue-500/50 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus-visible:ring-blue-400/50 fi-ac-action fi-ac-btn-action">
                                <svg class="w-5 h-5 transition-transform group-hover:-translate-y-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                <span>Download PDF</span>
                            </a>

                            <a href="https://www.nexicon.id"
                                class="block w-full py-2 text-sm font-medium text-center text-gray-500 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                &larr; Kembali ke Website
                            </a>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-500">
                            PT. NEXT GENERATION SOLUTIONS<br>
                            <a href="https://www.nexicon.id"
                                class="font-medium transition-colors hover:text-blue-600 dark:hover:text-blue-400">www.nexicon.id</a>
                        </p>
                    </div>

                </section>

            </div>

        </main>
    </div>
</body>

</html><?php /**PATH C:\laragon\www\erp-app\resources\views\invoice\detail.blade.php ENDPATH**/ ?>