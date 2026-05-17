<x-filament-panels::page>

    {{-- Merender Header Widgets (InventoryStatsOverview) secara otomatis --}}
    @if ($headerWidgets = $this->getVisibleHeaderWidgets())
        <x-filament-widgets::widgets
            :columns="$this->getHeaderWidgetsColumns()"
            :data="$this->getWidgetData()"
            :widgets="$headerWidgets"
            class="mb-6"
        />
    @endif

    {{-- Pintas Cepat (Quick Links) --}}
    <div class="mb-6 bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl p-6">
        <div class="flex items-center gap-2 mb-4">
            <x-filament::icon icon="heroicon-m-bolt" class="h-5 w-5 text-primary-500" />
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 tracking-widest uppercase">
                Aksi Cepat
            </h3>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-filament::button href="/admin/inventory/products/create" tag="a" icon="heroicon-m-plus" color="gray" outlined class="w-full">
                Produk Baru
            </x-filament::button>
            <x-filament::button href="/admin/inventory/stock-reports" tag="a" icon="heroicon-m-document-text" color="gray" outlined class="w-full">
                Laporan Stok
            </x-filament::button>
            <x-filament::button href="/admin/inventory/monitoring-transactions" tag="a" icon="heroicon-m-chart-bar" color="gray" outlined class="w-full">
                Live Monitor
            </x-filament::button>
            <x-filament::button href="/admin/inventory/warehouses" tag="a" icon="heroicon-m-building-office" color="gray" outlined class="w-full">
                Daftar Gudang
            </x-filament::button>
        </div>
    </div>

    {{-- Merender Footer Widgets (Grafik Tren & Distribusi) secara otomatis --}}
    @if ($footerWidgets = $this->getVisibleFooterWidgets())
        <x-filament-widgets::widgets
            :columns="$this->getFooterWidgetsColumns()"
            :data="$this->getWidgetData()"
            :widgets="$footerWidgets"
        />
    @endif

</x-filament-panels::page>
