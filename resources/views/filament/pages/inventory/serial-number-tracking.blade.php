<x-filament-panels::page>
    <div class="space-y-6">
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

        @if($notFound)
            <x-filament::section>
                <div class="text-sm font-medium text-danger-600">
                    Serial Number tidak ditemukan.
                </div>
            </x-filament::section>
        @endif

        @if($trackingResult)
            <x-filament::section heading="Identitas & Status Unit">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                    <div>
                        <div class="text-gray-500">Serial Number</div>
                        <div class="font-semibold">{{ $trackingResult['serial_number'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Status Saat Ini</div>
                        <div class="font-semibold">{{ $trackingResult['status'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Nama Product</div>
                        <div class="font-semibold">{{ $trackingResult['product_name'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Kode Product</div>
                        <div>{{ $trackingResult['product_code'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Gudang Saat Ini</div>
                        <div>{{ $trackingResult['warehouse_name'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Supplier</div>
                        <div>{{ $trackingResult['supplier_name'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Purchase Order</div>
                        <div>{{ $trackingResult['purchase_order_number'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Customer</div>
                        <div>{{ $trackingResult['customer_name'] }}</div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Riwayat Unit">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                    <div>
                        <div class="text-gray-500">Tanggal Masuk</div>
                        <div>{{ $trackingResult['inbound_date'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Tanggal Keluar</div>
                        <div>{{ $trackingResult['outbound_date'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Garansi Habis</div>
                        <div>{{ $trackingResult['warranty_expired_at'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Status Garansi</div>
                        <div class="font-semibold">{{ $trackingResult['warranty_status'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Data Dibuat</div>
                        <div>{{ $trackingResult['created_at'] }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Terakhir Diperbarui</div>
                        <div>{{ $trackingResult['updated_at'] }}</div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Histori Audit Transaksi">
                @forelse($transactionHistory as $history)
                    <div class="rounded-xl border p-4 space-y-2">
                        <div><span class="font-medium">No. Transaksi:</span> {{ $history['transaction_code'] }}</div>
                        <div><span class="font-medium">Tanggal:</span> {{ $history['transaction_date'] }}</div>
                        <div><span class="font-medium">Jenis Mutasi:</span> {{ $history['mutation_type'] }}</div>
                        <div><span class="font-medium">Referensi:</span> {{ $history['reference_number'] }}</div>
                        <div><span class="font-medium">Gudang:</span> {{ $history['warehouse_name'] }}</div>
                        <div><span class="font-medium">Diproses Oleh:</span> {{ $history['created_by'] }}</div>
                        <div><span class="font-medium">Catatan:</span> {{ $history['notes'] }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">
                        Belum ada histori audit untuk serial number ini.
                    </div>
                @endforelse
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
