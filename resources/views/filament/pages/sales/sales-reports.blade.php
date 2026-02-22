<x-filament-panels::page>

    <style>
        [x-cloak] { display: none !important; }
        .filament-widgets-chart-widget canvas {
            max-height: 100% !important;
        }
    </style>

    <div class="space-y-6">

        <x-filament::section collapsible collapsed>

            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-funnel" class="w-5 h-5 text-gray-500" />
                    <span>Filter Periode</span>
                </div>
            </x-slot>
            {{ $this->form }}

        </x-filament::section>

        @livewire(\App\Filament\Widgets\Sales\SalesSummaryStats::class, ['filters' => $this->getFilterData()])

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="lg:col-span-2">
                @livewire(\App\Filament\Widgets\Sales\SalesPipelineChart::class, [
                    'filters' => $this->getFilterData(),
                    'class' => 'h-full'
                ])
            </div>

            <div class="flex flex-col h-full">
                @livewire(\App\Filament\Widgets\Sales\PromoReportTable::class, ['filters' => $this->getFilterData()])
            </div>
        </div>

        <div class="w-full">
            @livewire(\App\Filament\Widgets\Sales\OperationalAlertTable::class)
        </div>

        <x-filament::section>

            <x-slot name="heading">Detail Transaksi</x-slot>

            <div x-data="{ activeTab: 'quotations' }" class="mt-4">
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex gap-6 -mb-px overflow-x-auto no-scrollbar">
                        @foreach(['quotations', 'sales_orders', 'delivery_orders', 'invoices'] as $tab)
                            <button
                                @click="activeTab = '{{ $tab }}'"
                                type="button"
                                :class="activeTab === '{{ $tab }}'
                                    ? 'border-primary-500 text-primary-600 border-b-2 font-bold'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 border-b-2 font-medium'"
                                class="px-2 py-3 text-sm capitalize transition-all whitespace-nowrap"
                            >
                                {{ str_replace('_', ' ', $tab) }}
                            </button>
                        @endforeach
                    </nav>
                </div>

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
