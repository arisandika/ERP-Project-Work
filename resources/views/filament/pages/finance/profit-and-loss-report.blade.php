<x-filament-panels::page>

    <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
        <div class="w-full md:w-2/3">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2 text-gray-800 dark:text-gray-200">
                <x-heroicon-o-funnel class="w-5 h-5 text-primary-500" />
                Filter Periode Laporan
            </h2>
            {{ $this->form }}
        </div>
        <div class="w-full md:w-auto flex justify-end mt-4 md:mt-0">
            <x-filament::button icon="heroicon-o-printer" color="gray" onclick="window.print()">
                Cetak Laporan
            </x-filament::button>
        </div>
    </div>

    <div id="print-area" class="bg-white p-8 md:p-12 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 print:shadow-none print:ring-0 print:p-0">

        <div class="text-center mb-10 pb-6 border-b-2 border-gray-200 dark:border-gray-800">
            <h1 class="text-2xl md:text-3xl font-extrabold uppercase tracking-widest text-gray-900 dark:text-white">Laporan Laba Rugi</h1>
            <p class="text-gray-500 mt-2 font-medium">Periode: <span class="text-gray-800 dark:text-gray-200">{{ $period }}</span></p>
        </div>

        <div class="max-w-4xl mx-auto space-y-8 text-sm md:text-base tabular-nums">

            <div>
                <h3 class="font-bold text-primary-600 dark:text-primary-400 uppercase mb-3 flex items-center gap-2">
                    <x-heroicon-o-arrow-trending-up class="w-5 h-5" />
                    Pendapatan (Revenue)
                </h3>
                <div class="space-y-1">
                    @forelse ($revenueDetails as $category => $items)
                        <div class="flex justify-between py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition rounded-lg">
                            <span class="text-gray-600 dark:text-gray-400 pl-6 relative before:content-[''] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-gray-400 before:rounded-full">
                                {{ $category ?: 'Pendapatan Lain-lain' }}
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-6 py-2 italic">Tidak ada transaksi pendapatan.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-3 px-3 mt-2 border-t border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">
                    <span>Total Pendapatan</span>
                    <span>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>
            </div>

            <div>
                <h3 class="font-bold text-danger-600 dark:text-danger-400 uppercase mb-3 flex items-center gap-2 mt-8">
                    <x-heroicon-o-shopping-bag class="w-5 h-5" />
                    Harga Pokok Penjualan (HPP)
                </h3>
                <div class="space-y-1">
                    <div class="flex justify-between py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition rounded-lg">
                        <span class="text-gray-600 dark:text-gray-400 pl-6 relative before:content-[''] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-gray-400 before:rounded-full">
                            Pembelian Stok Barang
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($totalCogs, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="flex justify-between py-3 px-3 mt-2 border-t border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">
                    <span>Total HPP</span>
                    <span class="text-danger-600 dark:text-danger-500">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</span>
                </div>
            </div>

            <div class="flex justify-between items-center py-4 px-5 mt-6 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 font-bold text-lg rounded-xl">
                <span class="text-gray-900 dark:text-white uppercase tracking-wide text-sm md:text-base">Laba Kotor (Gross Profit)</span>
                <span class="{{ $grossProfit >= 0 ? 'text-gray-900 dark:text-white' : 'text-danger-600 dark:text-danger-500' }}">
                    Rp {{ number_format($grossProfit, 0, ',', '.') }}
                </span>
            </div>

            <div>
                <h3 class="font-bold text-warning-600 dark:text-warning-500 uppercase mb-3 flex items-center gap-2 mt-8">
                    <x-heroicon-o-building-office class="w-5 h-5" />
                    Biaya Operasional (Opex)
                </h3>
                <div class="space-y-1">
                    @forelse ($opexDetails as $category => $items)
                        <div class="flex justify-between py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition rounded-lg">
                            <span class="text-gray-600 dark:text-gray-400 pl-6 relative before:content-[''] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-gray-400 before:rounded-full">
                                {{ $category ?: 'Biaya Lain-lain' }}
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-6 py-2 italic">Tidak ada transaksi operasional.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-3 px-3 mt-2 border-t border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">
                    <span>Total Biaya Operasional</span>
                    <span class="text-danger-600 dark:text-danger-500">(Rp {{ number_format($totalOpex, 0, ',', '.') }})</span>
                </div>
            </div>

            <div class="flex justify-between items-center py-6 px-6 mt-8 {{ $netProfit >= 0 ? 'bg-primary-600 dark:bg-primary-500 ring-primary-600/20 dark:ring-primary-500/20' : 'bg-danger-600 dark:bg-danger-500 ring-danger-600/20 dark:ring-danger-500/20' }} text-white font-bold text-xl md:text-2xl rounded-2xl shadow-lg ring-4 transition-transform hover:scale-[1.01]">
                <div class="flex items-center gap-3">
                    <x-heroicon-s-banknotes class="w-8 h-8 opacity-80" />
                    <span class="uppercase tracking-wide">Laba Bersih <span class="hidden md:inline font-normal text-sm opacity-80">(Net Profit)</span></span>
                </div>
                <span class="tracking-tight">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </span>
            </div>

        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print\:hidden {
                display: none !important;
            }
            #print-area, #print-area * {
                visibility: visible;
            }
            #print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none !important;
            }
            /* Memaksa background color ikut tercetak (Chrome/Edge) */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</x-filament-panels::page>
