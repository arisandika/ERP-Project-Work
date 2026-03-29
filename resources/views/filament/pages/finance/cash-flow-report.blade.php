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

        <div class="text-center mb-10">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-1">NEX CORPORATE</h2>
            <h1 class="text-2xl md:text-3xl font-extrabold uppercase tracking-widest text-gray-900 dark:text-white">Laporan Arus Kas</h1>
            <p class="text-gray-500 mt-2 font-medium">Periode: <span class="text-gray-800 dark:text-gray-200">{{ $period }}</span></p>
        </div>

        <div class="max-w-4xl mx-auto text-sm md:text-base tabular-nums">

            <div class="flex justify-between items-center py-3 px-4 mb-8 bg-gray-100 dark:bg-gray-800 border-l-4 border-gray-400 font-bold text-lg rounded-r-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase">Saldo Kas Awal</span>
                <div class="w-48 flex justify-between text-gray-900 dark:text-white">
                    <span class="opacity-60">Rp</span>
                    <span>{{ number_format($openingBalance, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 border-gray-800 dark:border-gray-200 pb-2">
                    Arus Kas Masuk (Inflow)
                </h3>
                <div class="space-y-1">
                    @forelse ($cashInflows as $category => $items)
                        <div class="flex justify-between py-1.5 px-2 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">{{ $category ?: 'Pemasukan Lain-lain' }}</span>
                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span>{{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-4 py-1.5 italic">Tidak ada arus kas masuk.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Kas Masuk</span>
                    <div class="w-48 flex justify-between">
                        <span class="text-gray-400">Rp</span>
                        <span class="text-success-600 dark:text-success-500">{{ number_format($totalInflow, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 border-gray-800 dark:border-gray-200 pb-2">
                    Arus Kas Keluar (Outflow)
                </h3>
                <div class="space-y-1">
                    @forelse ($cashOutflows as $category => $items)
                        <div class="flex justify-between py-1.5 px-2 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">{{ $category ?: 'Pengeluaran Lain-lain' }}</span>
                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span>{{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-4 py-1.5 italic">Tidak ada arus kas keluar.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Kas Keluar</span>
                    <div class="w-48 flex justify-between">
                        <span class="text-gray-400">Rp</span>
                        <span class="text-danger-600 dark:text-danger-500">({{ number_format($totalOutflow, 0, ',', '.') }})</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center py-3 px-4 mt-8 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-bold text-lg rounded-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase tracking-wide">Kenaikan / (Penurunan) Kas</span>
                <div class="w-48 flex justify-between {{ $netCashFlow >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                    <span class="opacity-60">Rp</span>
                    <span>{{ $netCashFlow < 0 ? '(' : '' }}{{ number_format(abs($netCashFlow), 0, ',', '.') }}{{ $netCashFlow < 0 ? ')' : '' }}</span>
                </div>
            </div>

            <div class="flex justify-between items-center py-4 px-2 mt-6 border-t-2 border-b-4 border-double border-gray-900 dark:border-white font-black text-xl md:text-2xl text-gray-900 dark:text-white">
                <span class="uppercase tracking-widest">Saldo Kas Akhir</span>
                <div class="w-56 flex justify-between {{ $endingBalance >= 0 ? 'text-primary-600 dark:text-primary-500' : 'text-danger-600 dark:text-danger-500' }}">
                    <span class="opacity-50">Rp</span>
                    <span>{{ $endingBalance < 0 ? '(' : '' }}{{ number_format(abs($endingBalance), 0, ',', '.') }}{{ $endingBalance < 0 ? ')' : '' }}</span>
                </div>
            </div>

        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            .print\:hidden { display: none !important; }
            #print-area, #print-area * { visibility: visible; }
            #print-area {
                position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 20px; box-shadow: none !important;
            }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</x-filament-panels::page>
