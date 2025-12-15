<x-filament-panels::page>
    <style>
        [x-cloak] { display: none !important; }
        /* Fix chart height */
        .filament-widgets-chart-widget canvas {
            max-height: 100% !important;
        }
    </style>

    <div class="space-y-6">

        {{-- SECTION 1: FILTER --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-funnel" class="w-5 h-5 text-gray-500" />
                    <span>Filter Periode</span>
                </div>
            </x-slot>
            {{ $this->form }}
        </x-filament::section>

        {{-- SECTION 2: STATISTIK --}}
        @livewire(\App\Filament\Widgets\Sales\SalesSummaryStats::class, ['filters' => $this->getFilterData()])

        {{-- SECTION 3: CHART & PROMO (Grid 3 Kolom) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- KIRI (2/3): Sales Funnel Chart --}}
            <div class="lg:col-span-2">
                @livewire(\App\Filament\Widgets\Sales\SalesPipelineChart::class, [
                    'filters' => $this->getFilterData(),
                    'class' => 'h-full'
                ])
            </div>

            {{-- KANAN (1/3): Promo Performance --}}
            {{-- Promo ditaruh sini karena tabelnya kecil/simpel, cocok buat side-bar --}}
            <div class="flex flex-col h-full">
                @livewire(\App\Filament\Widgets\Sales\PromoReportTable::class, ['filters' => $this->getFilterData()])
            </div>
        </div>

        {{-- SECTION 4: JATUH TEMPO (Full Width - EKSKLUSIF) --}}
        {{-- Ini ditaruh di luar grid supaya LEBAR --}}
        <div class="w-full">
            @livewire(\App\Filament\Widgets\Sales\OperationalAlertTable::class)
        </div>

        {{-- SECTION 5: DETAIL TRANSAKSI (Tabs) --}}
        <x-filament::section>
            <x-slot name="heading">Detail Transaksi</x-slot>

            <div x-data="{ activeTab: 'quotations' }" class="mt-4">
                {{-- Tabs Header --}}
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="-mb-px flex gap-6 overflow-x-auto no-scrollbar">
                        @foreach(['quotations', 'sales_orders', 'delivery_orders', 'invoices'] as $tab)
                            <button
                                @click="activeTab = '{{ $tab }}'"
                                type="button"
                                :class="activeTab === '{{ $tab }}'
                                    ? 'border-primary-500 text-primary-600 border-b-2 font-bold'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 border-b-2 font-medium'"
                                class="whitespace-nowrap py-3 px-2 text-sm capitalize transition-all"
                            >
                                {{ str_replace('_', ' ', $tab) }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Tabs Content --}}
                <div class="min-h-[300px]">
                    <div x-show="activeTab === 'quotations'" x-transition.opacity>
                        @livewire(\App\Filament\Widgets\Sales\QuotationReportTable::class, ['filters' => $this->getFilterData()])
                    </div>
                    <div x-show="activeTab === 'sales_orders'" x-cloak x-transition.opacity>
                        @livewire(\App\Filament\Widgets\Sales\SalesOrderReportTable::class, ['filters' => $this->getFilterData()])
                    </div>
                    <div x-show="activeTab === 'delivery_orders'" x-cloak x-transition.opacity>
                        @livewire(\App\Filament\Widgets\Sales\DeliveryOrderReportTable::class, ['filters' => $this->getFilterData()])
                    </div>
                    <div x-show="activeTab === 'invoices'" x-cloak x-transition.opacity>
                        @livewire(\App\Filament\Widgets\Sales\InvoiceReportTable::class, ['filters' => $this->getFilterData()])
                    </div>
                </div>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
