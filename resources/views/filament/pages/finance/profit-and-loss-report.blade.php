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
        <div class="text-center mb-10 pb-6 border-b-2 ring-border-light dark:ring-border-dark">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-1">
                PT. Next Generation Solutions
            </h2>

            <h1 class="text-2xl md:text-3xl font-extrabold uppercase tracking-widest text-gray-900 dark:text-white">
                Laporan Laba Rugi
            </h1>

            <p class="text-gray-500 mt-2 font-medium">
                Periode:
                <span class="text-gray-800 dark:text-gray-200">
                    {{ $period }}
                </span>
            </p>
        </div>

        <div class="max-w-4xl mx-auto text-sm md:text-base tabular-nums">

            {{-- PENDAPATAN --}}
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
                    Pendapatan Usaha
                </h3>

                <div class="space-y-1">
                    @forelse ($revenueDetails as $category => $items)
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                {{ $category ?: 'Pendapatan Lain-lain' }}
                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span>{{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada pendapatan pada periode ini.
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Pendapatan</span>

                    <div class="w-48 flex justify-between">
                        <span class="text-gray-400">Rp</span>
                        <span>{{ number_format($totalRevenue, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- HPP --}}
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
                    Harga Pokok Penjualan / Beban Pokok
                </h3>

                <div class="space-y-1">
                    @forelse ($cogsDetails as $category => $items)
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                {{ $category ?: 'HPP Lain-lain' }}
                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span>{{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada HPP pada periode ini.
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total HPP</span>

                    <div class="w-48 flex justify-between text-danger-600 dark:text-danger-400">
                        <span class="opacity-60">Rp</span>
                        <span>({{ number_format($totalCogs, 0, ',', '.') }})</span>
                    </div>
                </div>
            </div>

            {{-- LABA KOTOR --}}
            <div class="flex justify-between items-center py-3 px-4 mb-8 bg-gray-100 dark:bg-gray-800 border-l-4 border-gray-400 font-bold text-lg rounded-r-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase">
                    Laba Kotor
                </span>

                <div class="w-48 flex justify-between {{ $grossProfit >= 0 ? 'text-gray-900 dark:text-white' : 'text-danger-600 dark:text-danger-400' }}">
                    <span class="opacity-60">Rp</span>
                    <span>
                        {{ $grossProfit < 0 ? '(' : '' }}{{ number_format(abs($grossProfit), 0, ',', '.') }}{{ $grossProfit < 0 ? ')' : '' }}
                    </span>
                </div>
            </div>

            {{-- OPEX --}}
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
                    Biaya Operasional
                </h3>

                <div class="space-y-1">
                    @forelse ($opexDetails as $category => $items)
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                {{ $category ?: 'Biaya Operasional Lain-lain' }}
                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span>{{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada biaya operasional pada periode ini.
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Biaya Operasional</span>

                    <div class="w-48 flex justify-between text-danger-600 dark:text-danger-400">
                        <span class="opacity-60">Rp</span>
                        <span>({{ number_format($totalOpex, 0, ',', '.') }})</span>
                    </div>
                </div>
            </div>

            {{-- LABA OPERASIONAL --}}
            <div class="flex justify-between items-center py-3 px-4 mb-8 bg-gray-100 dark:bg-gray-800 border-l-4 border-gray-400 font-bold text-lg rounded-r-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase">
                    Laba Operasional
                </span>

                <div class="w-48 flex justify-between {{ $operatingProfit >= 0 ? 'text-gray-900 dark:text-white' : 'text-danger-600 dark:text-danger-400' }}">
                    <span class="opacity-60">Rp</span>
                    <span>
                        {{ $operatingProfit < 0 ? '(' : '' }}{{ number_format(abs($operatingProfit), 0, ',', '.') }}{{ $operatingProfit < 0 ? ')' : '' }}
                    </span>
                </div>
            </div>

            {{-- OTHER INCOME --}}
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
                    Pendapatan Lain-lain
                </h3>

                <div class="space-y-1">
                    @forelse ($otherIncomeDetails as $category => $items)
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                {{ $category ?: 'Pendapatan Lain-lain' }}
                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span>{{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada pendapatan lain-lain.
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Pendapatan Lain-lain</span>

                    <div class="w-48 flex justify-between">
                        <span class="text-gray-400">Rp</span>
                        <span>{{ number_format($totalOtherIncome, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- OTHER EXPENSE --}}
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
                    Beban Lain-lain
                </h3>

                <div class="space-y-1">
                    @forelse ($otherExpenseDetails as $category => $items)
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                {{ $category ?: 'Beban Lain-lain' }}
                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span>{{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada beban lain-lain.
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Beban Lain-lain</span>

                    <div class="w-48 flex justify-between text-danger-600 dark:text-danger-400">
                        <span class="opacity-60">Rp</span>
                        <span>({{ number_format($totalOtherExpense, 0, ',', '.') }})</span>
                    </div>
                </div>
            </div>

            {{-- PROFIT BEFORE TAX --}}
            <div class="flex justify-between items-center py-3 px-4 mb-4 bg-gray-50 dark:bg-gray-900 border ring-border-light dark:border-gray-700 font-bold text-lg rounded-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                    Laba Sebelum Pajak
                </span>

                <div class="w-48 flex justify-between {{ $profitBeforeTax >= 0 ? 'text-gray-900 dark:text-white' : 'text-danger-600 dark:text-danger-400' }}">
                    <span class="opacity-60">Rp</span>
                    <span>
                        {{ $profitBeforeTax < 0 ? '(' : '' }}{{ number_format(abs($profitBeforeTax), 0, ',', '.') }}{{ $profitBeforeTax < 0 ? ')' : '' }}
                    </span>
                </div>
            </div>

            {{-- TAX --}}
            <div class="flex justify-between py-2 px-4 mb-8 text-gray-700 dark:text-gray-300">
                <span>Pajak</span>

                <div class="w-48 flex justify-between">
                    <span class="text-gray-400">Rp</span>
                    <span>{{ number_format($taxExpense, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- NET PROFIT --}}
            <div class="flex justify-between items-center py-4 px-2 mt-6 border-t-2 border-b-4 border-double border-gray-900 dark:border-white font-black text-xl md:text-2xl text-gray-900 dark:text-white">
                <span class="uppercase tracking-widest">
                    Laba Bersih
                </span>

                <div class="w-56 flex justify-between {{ $netProfit >= 0 ? 'text-primary-600 dark:text-primary-500' : 'text-danger-600 dark:text-danger-500' }}">
                    <span class="opacity-50">Rp</span>
                    <span>
                        {{ $netProfit < 0 ? '(' : '' }}{{ number_format(abs($netProfit), 0, ',', '.') }}{{ $netProfit < 0 ? ')' : '' }}
                    </span>
                </div>
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

            #print-area,
            #print-area * {
                visibility: visible;
            }

            #print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
                box-shadow: none !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</x-filament-panels::page>
