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
    <div class="p-6 mb-6 shadow-sm bg-secondary-light dark:bg-secondary-dark ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl">
        <div class="flex items-center gap-2 mb-4">
            <x-filament::icon icon="heroicon-m-bolt" class="w-5 h-5 text-primary-500" />
            <h3 class="text-sm font-bold tracking-widest text-gray-700 uppercase dark:text-gray-300">
                Aksi Cepat
            </h3>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-2">
            <x-filament::button href="/inventory/products/create" tag="a" icon="heroicon-m-plus" color="primary" class="w-full h-16 text-lg">
                Produk Baru
            </x-filament::button>
            <x-filament::button href="/inventory/stock-reports" tag="a" icon="heroicon-m-document-text" color="primary" class="w-full h-16 text-lg">
                Laporan Stok
            </x-filament::button>
            <x-filament::button href="/inventory/monitoring-transactions" tag="a" icon="heroicon-m-chart-bar" color="primary" class="w-full h-16 text-lg">
                Live Monitor
            </x-filament::button>
            <x-filament::button href="/inventory/warehouses" tag="a" icon="heroicon-m-building-office" color="primary" class="w-full h-16 text-lg">
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
