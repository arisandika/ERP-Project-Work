<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Pencarian -->
        <x-filament::section heading="Pelacakan Serial Number">
            <div class="space-y-4">
                {{ $this->form }}

                <div class="flex gap-2">
                    <x-filament::button wire:click="search">
                        Cari
                    </x-filament::button>

                    <x-filament::button color="gray" wire:click="resetSearch">
                        Reset
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <!-- Kondisi: Tidak Ditemukan -->
        @if(isset($notFound) && $notFound)
            <x-filament::section>
                <div class="text-sm font-medium text-danger-600">
                    Serial Number tidak ditemukan.
                </div>
            </x-filament::section>
        @endif

        <!-- Kondisi: Ditemukan -->
        @if(!empty($trackingResult))
            <x-filament::section heading="Identitas & Status Unit">
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                    <div>
                        <div class="text-gray-500">Serial Number</div>
                        <div class="font-semibold">{{ data_get($trackingResult, 'serial_number', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Status Saat Ini</div>
                        <div class="font-semibold">{{ data_get($trackingResult, 'status', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Nama Product</div>
                        <div class="font-semibold">{{ data_get($trackingResult, 'product_name', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Kode Product</div>
                        <div>{{ data_get($trackingResult, 'product_code', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Gudang Saat Ini</div>
                        <div>{{ data_get($trackingResult, 'warehouse_name', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Supplier</div>
                        <div>{{ data_get($trackingResult, 'supplier_name', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Purchase Order</div>
                        <div>{{ data_get($trackingResult, 'purchase_order_number', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Customer</div>
                        <div>{{ data_get($trackingResult, 'customer_name', '-') }}</div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Riwayat Unit">
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                    <div>
                        <div class="text-gray-500">Tanggal Masuk</div>
                        <div>{{ data_get($trackingResult, 'inbound_date', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Tanggal Keluar</div>
                        <div>{{ data_get($trackingResult, 'outbound_date', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Garansi Habis</div>
                        <div>{{ data_get($trackingResult, 'warranty_expired_at', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Status Garansi</div>
                        <div class="font-semibold">{{ data_get($trackingResult, 'warranty_status', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Data Dibuat</div>
                        <div>{{ data_get($trackingResult, 'created_at', '-') }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Terakhir Diperbarui</div>
                        <div>{{ data_get($trackingResult, 'updated_at', '-') }}</div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Histori Audit Transaksi">
                <div class="space-y-4">
                    @forelse(data_get($this, 'transactionHistory', []) as $history)
                        <div class="p-4 space-y-2 border rounded-xl">
                            <div><span class="font-medium">No. Transaksi:</span> {{ data_get($history, 'transaction_code', '-') }}</div>
                            <div><span class="font-medium">Tanggal:</span> {{ data_get($history, 'transaction_date', '-') }}</div>
                            <div><span class="font-medium">Jenis Mutasi:</span> {{ data_get($history, 'mutation_type', '-') }}</div>
                            <div><span class="font-medium">Referensi:</span> {{ data_get($history, 'reference_number', '-') }}</div>
                            <div><span class="font-medium">Gudang:</span> {{ data_get($history, 'warehouse_name', '-') }}</div>
                            <div><span class="font-medium">Diproses Oleh:</span> {{ data_get($history, 'created_by', '-') }}</div>
                            <div><span class="font-medium">Catatan:</span> {{ data_get($history, 'notes', '-') }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">
                            Belum ada histori audit untuk serial number ini.
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
