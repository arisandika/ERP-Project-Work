<x-filament-panels::page>
    <div class="space-y-6">

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    <span>Pencarian Cepat Serial Number</span>
                </div>
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}

                <div class="flex items-center gap-3 pt-2">
                    <x-filament::button wire:click="search" icon="heroicon-m-magnifying-glass" size="sm">
                        Cari Sekarang
                    </x-filament::button>

                    <x-filament::button color="gray" wire:click="resetSearch" icon="heroicon-m-arrow-path" size="sm" variant="outline">
                        Reset
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        @if(isset($notFound) && $notFound)
            <div class="p-4 border-l-4 rounded-r-lg bg-danger-50 border-danger-500 dark:bg-danger-500/10 dark:border-danger-500/50">
                <div class="flex items-start gap-3">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                    <div>
                        <h3 class="font-medium text-danger-800 dark:text-danger-400">Pencarian Gagal</h3>
                        <p class="mt-1 text-sm text-danger-700 dark:text-danger-300">
                            Serial Number yang Anda cari tidak terdaftar dalam database. Pastikan Anda mengetik atau men-scan dengan benar.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($trackingResult))
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-cpu-chip" class="w-5 h-5 text-primary-500" />
                            <span>Identitas Unit</span>
                        </div>
                    </x-slot>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                            <p class="text-xs font-medium text-gray-500 uppercase">Serial Number</p>
                            <p class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ data_get($trackingResult, 'serial_number', '-') }}
                            </p>
                        </div>

                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                            <p class="text-xs font-medium text-gray-500 uppercase">Status Saat Ini</p>
                            <x-filament::badge
                                color="{{ match(strtolower(data_get($trackingResult, 'status'))) {
                                    'sold', 'keluar' => 'success',
                                    'defective', 'rusak' => 'danger',
                                    'returned' => 'warning',
                                    default => 'info',
                                } }}"
                                class="mt-1">
                                {{ data_get($trackingResult, 'status', '-') }}
                            </x-filament::badge>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Produk</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ data_get($trackingResult, 'product_name', '-') }}</p>
                            <p class="text-xs text-gray-500">Kode: {{ data_get($trackingResult, 'product_code', '-') }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Lokasi Gudang</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ data_get($trackingResult, 'warehouse_name', '-') }}</p>
                        </div>

                        <div class="col-span-1 sm:col-span-2 pt-2 mt-2 border-t ring-border-light dark:border-gray-700">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Suplier (Asal)</p>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ data_get($trackingResult, 'supplier_name', '-') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Customer (Tujuan)</p>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ data_get($trackingResult, 'customer_name', '-') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 text-success-500" />
                            <span>Siklus Hidup & Garansi</span>
                        </div>
                    </x-slot>

                    <div class="space-y-6">
                        <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Status Garansi</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Berakhir pada: {{ data_get($trackingResult, 'warranty_expired_at', '-') }}
                                </p>
                            </div>
                            <x-filament::badge
                                color="{{ data_get($trackingResult, 'warranty_status') === 'Aktif' ? 'success' : 'danger' }}"
                                size="lg">
                                {{ data_get($trackingResult, 'warranty_status', '-') }}
                            </x-filament::badge>
                        </div>

                        <div class="relative pl-4 border-l-2 ring-border-light dark:border-gray-700">
                            <div class="mb-4">
                                <div class="absolute w-3 h-3 bg-gray-300 rounded-full -left-[7px] top-1 dark:bg-gray-600"></div>
                                <p class="text-xs text-gray-500">Tanggal Masuk (Inbound)</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ data_get($trackingResult, 'inbound_date', '-') }}</p>
                            </div>
                            <div>
                                <div class="absolute w-3 h-3 bg-gray-300 rounded-full -left-[7px] top-14 dark:bg-gray-600"></div>
                                <p class="text-xs text-gray-500">Tanggal Keluar (Outbound)</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ data_get($trackingResult, 'outbound_date', '-') }}</p>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-5 h-5 text-gray-500" />
                        <span>Log Histori Transaksi</span>
                    </div>
                </x-slot>

                <div class="overflow-x-auto ring-1 ring-gray-200 dark:ring-white/10 rounded-xl">
                    <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 font-medium">Tanggal</th>
                                <th class="px-4 py-3 font-medium">Mutasi</th>
                                <th class="px-4 py-3 font-medium">No. Referensi</th>
                                <th class="px-4 py-3 font-medium">PIC</th>
                                <th class="px-4 py-3 font-medium hidden sm:table-cell">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                            @forelse(data_get($this, 'transactionHistory', []) as $history)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ data_get($history, 'transaction_date', '-') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <x-filament::badge color="gray">{{ data_get($history, 'mutation_type', '-') }}</x-filament::badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ data_get($history, 'reference_number', '-') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ data_get($history, 'created_by', '-') }}</td>
                                    <td class="px-4 py-3 hidden sm:table-cell text-gray-500">{{ data_get($history, 'notes', '-') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Tidak ada histori mutasi untuk Serial Number ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
