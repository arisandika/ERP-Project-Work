<x-filament-panels::page>
    <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
        <div class="w-full md:w-2/3">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2 text-gray-800 dark:text-gray-200">
                <x-heroicon-o-funnel class="w-5 h-5 text-primary-500" />
                Filter Tanggal Laporan
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
                Laporan Neraca
            </h1>

            <p class="text-gray-500 mt-2 font-medium">
                Per Tanggal:
                <span class="text-gray-800 dark:text-gray-200">
                    {{ $asOfDate }}
                </span>
            </p>
        </div>

        <div class="max-w-5xl mx-auto text-sm md:text-base tabular-nums">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                {{-- ASET --}}
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
                        Aset
                    </h3>

                    <div class="space-y-1">
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                Kas & Bank
                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span>
                                    {{ $cashBalance < 0 ? '(' : '' }}{{ number_format(abs($cashBalance), 0, ',', '.') }}{{ $cashBalance < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>

                        @forelse ($assets as $item)
                            <div class="flex justify-between py-1.5 px-2 rounded">
                                <span class="text-gray-700 dark:text-gray-300 pl-4">
                                    {{ $item['category'] }}
                                </span>

                                <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                    <span class="text-gray-400">Rp</span>
                                    <span>
                                        {{ $item['amount'] < 0 ? '(' : '' }}{{ number_format(abs($item['amount']), 0, ',', '.') }}{{ $item['amount'] < 0 ? ')' : '' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-gray-400 pl-4 py-1.5 italic">
                                Tidak ada aset lain yang tercatat.
                            </div>
                        @endforelse
                    </div>

                    <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                        <span class="uppercase text-xs md:text-sm">
                            Total Aset
                        </span>

                        <div class="w-48 flex justify-between">
                            <span class="text-gray-400">Rp</span>
                            <span>
                                {{ $totalAssets < 0 ? '(' : '' }}{{ number_format(abs($totalAssets), 0, ',', '.') }}{{ $totalAssets < 0 ? ')' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    {{-- LIABILITAS --}}
                    <div class="mb-8">
                        <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
                            Liabilitas
                        </h3>

                        <div class="space-y-1">
                            @forelse ($liabilities as $item)
                                <div class="flex justify-between py-1.5 px-2 rounded">
                                    <span class="text-gray-700 dark:text-gray-300 pl-4">
                                        {{ $item['category'] }}
                                    </span>

                                    <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                        <span class="text-gray-400">Rp</span>
                                        <span>
                                            {{ $item['amount'] < 0 ? '(' : '' }}{{ number_format(abs($item['amount']), 0, ',', '.') }}{{ $item['amount'] < 0 ? ')' : '' }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-gray-400 pl-4 py-1.5 italic">
                                    Tidak ada liabilitas yang tercatat.
                                </div>
                            @endforelse
                        </div>

                        <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                            <span class="uppercase text-xs md:text-sm">
                                Total Liabilitas
                            </span>

                            <div class="w-48 flex justify-between">
                                <span class="text-gray-400">Rp</span>
                                <span>{{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- EKUITAS --}}
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
                            Ekuitas
                        </h3>

                        <div class="space-y-1">
                            @forelse ($equities as $item)
                                <div class="flex justify-between py-1.5 px-2 rounded">
                                    <span class="text-gray-700 dark:text-gray-300 pl-4">
                                        {{ $item['category'] }}
                                    </span>

                                    <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                        <span class="text-gray-400">Rp</span>
                                        <span>
                                            {{ $item['amount'] < 0 ? '(' : '' }}{{ number_format(abs($item['amount']), 0, ',', '.') }}{{ $item['amount'] < 0 ? ')' : '' }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-gray-400 pl-4 py-1.5 italic">
                                    Tidak ada modal yang tercatat.
                                </div>
                            @endforelse

                            <div class="flex justify-between py-1.5 px-2 rounded">
                                <span class="text-gray-700 dark:text-gray-300 pl-4">
                                    Laba Berjalan
                                </span>

                                <div class="w-48 flex justify-between font-medium {{ $currentYearProfit >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-danger-600 dark:text-danger-400' }}">
                                    <span class="text-gray-400">Rp</span>
                                    <span>
                                        {{ $currentYearProfit < 0 ? '(' : '' }}{{ number_format(abs($currentYearProfit), 0, ',', '.') }}{{ $currentYearProfit < 0 ? ')' : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                            <span class="uppercase text-xs md:text-sm">
                                Total Ekuitas
                            </span>

                            <div class="w-48 flex justify-between">
                                <span class="text-gray-400">Rp</span>
                                <span>
                                    {{ $totalEquity < 0 ? '(' : '' }}{{ number_format(abs($totalEquity), 0, ',', '.') }}{{ $totalEquity < 0 ? ')' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TOTAL CHECK --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-10">
                <div class="flex justify-between items-center py-4 px-4 border-t-2 border-b-4 border-double border-gray-900 dark:border-white font-black text-lg text-gray-900 dark:text-white">
                    <span class="uppercase tracking-widest">
                        Total Aset
                    </span>

                    <div class="w-56 flex justify-between text-primary-600 dark:text-primary-500">
                        <span class="opacity-50">Rp</span>
                        <span>
                            {{ $totalAssets < 0 ? '(' : '' }}{{ number_format(abs($totalAssets), 0, ',', '.') }}{{ $totalAssets < 0 ? ')' : '' }}
                        </span>
                    </div>
                </div>

                <div class="flex justify-between items-center py-4 px-4 border-t-2 border-b-4 border-double border-gray-900 dark:border-white font-black text-lg text-gray-900 dark:text-white">
                    <span class="uppercase tracking-widest">
                        Total Liabilitas + Ekuitas
                    </span>

                    <div class="w-56 flex justify-between text-primary-600 dark:text-primary-500">
                        <span class="opacity-50">Rp</span>
                        <span>
                            {{ $totalLiabilitiesAndEquity < 0 ? '(' : '' }}{{ number_format(abs($totalLiabilitiesAndEquity), 0, ',', '.') }}{{ $totalLiabilitiesAndEquity < 0 ? ')' : '' }}
                        </span>
                    </div>
                </div>
            </div>

            @if (abs($difference) > 1)
                <div class="mt-6 p-4 rounded-xl bg-warning-50 text-warning-800 ring-1 ring-warning-200 dark:bg-warning-500/10 dark:text-warning-300 dark:ring-warning-500/20 print:hidden">
                    <div class="font-bold mb-1">
                        Neraca belum balance
                    </div>

                    <div class="text-sm">
                        Selisih:
                        <strong>
                            Rp {{ number_format(abs($difference), 0, ',', '.') }}
                        </strong>.
                        Ini normal untuk tahap awal kalau data modal, inventory, hutang/piutang, atau laba ditahan belum lengkap.
                    </div>
                </div>
            @endif

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
