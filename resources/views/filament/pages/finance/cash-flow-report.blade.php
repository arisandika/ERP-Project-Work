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
                Laporan Arus Kas
            </h1>

            <p class="text-gray-500 mt-2 font-medium">
                Periode:
                <span class="text-gray-800 dark:text-gray-200">
                    {{ $period }}
                </span>
            </p>
        </div>

        <div class="max-w-4xl mx-auto text-sm md:text-base tabular-nums">

            {{-- SALDO AWAL --}}
            <div class="flex justify-between items-center py-3 px-4 mb-8 bg-gray-100 dark:bg-gray-800 border-l-4 border-gray-400 font-bold text-lg rounded-r-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase">
                    Saldo Kas Awal
                </span>

                <div class="w-56 flex justify-between text-gray-900 dark:text-white">
                    <span class="opacity-60">Rp</span>
                    <span>
                        {{ $openingBalance < 0 ? '(' : '' }}{{ number_format(abs($openingBalance), 0, ',', '.') }}{{ $openingBalance < 0 ? ')' : '' }}
                    </span>
                </div>
            </div>

            {{-- OPERATING --}}
            @include('filament.pages.finance.partials.cash-flow-section', [
                'title' => 'Arus Kas dari Aktivitas Operasional',
                'details' => $operatingDetails,
                'total' => $totalOperatingCashFlow,
            ])

            {{-- INVESTING --}}
            @include('filament.pages.finance.partials.cash-flow-section', [
                'title' => 'Arus Kas dari Aktivitas Investasi',
                'details' => $investingDetails,
                'total' => $totalInvestingCashFlow,
            ])

            {{-- FINANCING --}}
            @include('filament.pages.finance.partials.cash-flow-section', [
                'title' => 'Arus Kas dari Aktivitas Pendanaan',
                'details' => $financingDetails,
                'total' => $totalFinancingCashFlow,
            ])

            {{-- NET CASH FLOW --}}
            <div class="flex justify-between items-center py-3 px-4 mt-8 bg-gray-50 dark:bg-gray-900 border ring-border-light dark:border-gray-700 font-bold text-lg rounded-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                    Kenaikan / (Penurunan) Kas Bersih
                </span>

                <div class="w-56 flex justify-between {{ $netCashFlow >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                    <span class="opacity-60">Rp</span>
                    <span>
                        {{ $netCashFlow < 0 ? '(' : '' }}{{ number_format(abs($netCashFlow), 0, ',', '.') }}{{ $netCashFlow < 0 ? ')' : '' }}
                    </span>
                </div>
            </div>

            {{-- ENDING BALANCE --}}
            <div class="flex justify-between items-center py-4 px-2 mt-6 border-t-2 border-b-4 border-double border-gray-900 dark:border-white font-black text-xl md:text-2xl text-gray-900 dark:text-white">
                <span class="uppercase tracking-widest">
                    Saldo Kas Akhir
                </span>

                <div class="w-64 flex justify-between {{ $endingBalance >= 0 ? 'text-primary-600 dark:text-primary-500' : 'text-danger-600 dark:text-danger-500' }}">
                    <span class="opacity-50">Rp</span>
                    <span>
                        {{ $endingBalance < 0 ? '(' : '' }}{{ number_format(abs($endingBalance), 0, ',', '.') }}{{ $endingBalance < 0 ? ')' : '' }}
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
