<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Ringkasan Hutang Supplier">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="rounded-xl border p-4">
                    <div class="text-gray-500">Total Hutang</div>
                    <div class="font-semibold text-lg">
                        Rp {{ number_format($summary['total_debt'], 0, ',', '.') }}
                    </div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-gray-500">Total Dibayar</div>
                    <div class="font-semibold text-lg">
                        Rp {{ number_format($summary['total_paid'], 0, ',', '.') }}
                    </div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-gray-500">Sisa Hutang</div>
                    <div class="font-semibold text-lg">
                        Rp {{ number_format($summary['total_remaining'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Daftar Hutang Supplier">
            <div class="space-y-4">
                @forelse($debts as $debt)
                    <div class="rounded-xl border p-4 space-y-3">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div>
                                <div class="font-semibold">
                                    {{ $debt['reference_number'] }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $debt['transaction_date'] }}
                                </div>
                                <div class="text-sm mt-1">
                                    {{ $debt['description'] ?: '-' }}
                                </div>
                            </div>

                            <div class="text-sm space-y-1">
                                <div>Total: <span class="font-medium">Rp {{ number_format($debt['total_amount'], 0, ',', '.') }}</span></div>
                                <div>Dibayar: <span class="font-medium">Rp {{ number_format($debt['paid_amount'], 0, ',', '.') }}</span></div>
                                <div>Sisa: <span class="font-medium">Rp {{ number_format($debt['remaining_amount'], 0, ',', '.') }}</span></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <span
                                    @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-medium',
                                        'bg-green-100 text-green-700' => $debt['status'] === 'PAID',
                                        'bg-yellow-100 text-yellow-700' => $debt['status'] === 'PARTIAL',
                                        'bg-red-100 text-red-700' => $debt['status'] === 'UNPAID',
                                    ])
                                >
                                    {{ $debt['status'] }}
                                </span>
                            </div>

                            <div class="flex gap-2">
                                @if($selectedReferenceNumber === $debt['reference_number'])
                                    <x-filament::button
                                        color="gray"
                                        wire:click="clearSelection"
                                    >
                                        Batal Pilih
                                    </x-filament::button>
                                @elseif($debt['remaining_amount'] > 0)
                                    <x-filament::button
                                        color="success"
                                        wire:click="selectDebt('{{ $debt['reference_number'] }}')"
                                    >
                                        Pilih untuk Bayar
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">
                        Belum ada data hutang supplier.
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
