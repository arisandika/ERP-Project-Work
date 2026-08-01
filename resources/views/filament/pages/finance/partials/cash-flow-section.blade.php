<div class="mb-8">
    <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 ring-border-dark dark:ring-border-light pb-2">
        {{ $title }}
    </h3>

    <div class="space-y-1">
        @forelse ($details as $item)
            <div class="flex justify-between py-1.5 px-2 rounded">
                <span class="text-gray-700 dark:text-gray-300 pl-4">
                    {{ $item['category'] }}
                </span>

                <div class="w-56 flex justify-between font-medium {{ $item['net'] >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-danger-600 dark:text-danger-400' }}">
                    <span class="text-gray-400">Rp</span>
                    <span>
                        {{ $item['net'] < 0 ? '(' : '' }}{{ number_format(abs($item['net']), 0, ',', '.') }}{{ $item['net'] < 0 ? ')' : '' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-gray-400 pl-4 py-1.5 italic">
                Tidak ada transaksi pada bagian ini.
            </div>
        @endforelse
    </div>

    <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
        <span class="uppercase text-xs md:text-sm">
            Total {{ $title }}
        </span>

        <div class="w-56 flex justify-between {{ $total >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
            <span class="opacity-60">Rp</span>
            <span>
                {{ $total < 0 ? '(' : '' }}{{ number_format(abs($total), 0, ',', '.') }}{{ $total < 0 ? ')' : '' }}
            </span>
        </div>
    </div>
</div>
