{{-- resources/views/filament/infolists/components/quotation-items-list.blade.php --}}
<div class="border border-gray-300 divide-y divide-gray-300 rounded-lg dark:border-white/10 dark:divide-white/10">
    {{-- Header Tabel --}}
    <div class="flex text-sm font-medium text-black dark:text-white bg-main-light dark:bg-white/5">
        <div class="w-1/2 p-2">Nama Item</div>
        <div class="w-1/4 p-2 text-right">Jumlah</div>
        <div class="w-1/4 p-2 text-right">Harga Satuan</div>
        <div class="w-1/4 p-2 text-right">Subtotal</div>
    </div>

    {{-- Body Tabel --}}
    <div class="divide-y divide-gray-300 dark:divide-white/10">
        @forelse($items as $item)
            <div class="flex text-sm text-gray-700 dark:text-gray-200">
                <div class="w-1/2 p-2">
                    <p class="font-medium">{{ $item->item_name }}</p>
                    <p class="text-xs text-gray-500">{{ $item->item_code }}</p>
                </div>
                <div class="w-1/4 p-2 text-right">{{ number_format($item->qty, 0) }}</div>
                <div class="w-1/4 p-2 text-right">{{ number_format($item->unit_price, 2, ',', '.') }}</div>
                <div class="w-1/4 p-2 text-right font-semibold">{{ number_format($item->line_total, 2, ',', '.') }}</div>
            </div>
        @empty
            <div class="p-4 text-center text-gray-500">
                Tidak ada item.
            </div>
        @endforelse
    </div>
</div>
