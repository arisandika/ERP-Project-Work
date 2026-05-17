<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold tracking-tight">Kapasitas & Utilisasi Gudang</h2>
            <span class="text-xs text-gray-400">Target Kapasitas: 5,000 Unit/Gudang</span>
        </div>

        <div class="space-y-6">
            @foreach($this->getWarehouseData() as $item)
            <div>
                <div class="flex justify-between mb-1 text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $item['name'] }}</span>
                    <span class="font-bold">{{ number_format($item['qty']) }} Unit ({{ round($item['utilization']) }}%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div class="bg-{{ $item['color'] }}-600 h-2.5 rounded-full shadow-sm transition-all duration-500"
                         style="width: {{ $item['utilization'] }}%">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
