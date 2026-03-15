<x-filament-panels::page>
    <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6">
        <h2 class="text-lg font-semibold mb-4">Filter Periode Laporan</h2>
        {{ $this->form }}
    </div>

    <div class="bg-white p-8 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

        <div class="text-center mb-8 border-b pb-6 dark:border-gray-700">
            <h1 class="text-2xl font-bold uppercase tracking-wider">Laporan Laba Rugi</h1>
            <p class="text-gray-500 mt-1">Periode: {{ $period }}</p>
        </div>

        <div class="space-y-6 text-sm md:text-base">

            <div>
                <h3 class="font-bold text-gray-800 dark:text-gray-200 border-b pb-2 mb-3">PENDAPATAN (REVENUE)</h3>
                @forelse ($revenueDetails as $category => $items)
                    <div class="flex justify-between py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded">
                        <span class="text-gray-600 dark:text-gray-400 pl-4">{{ $category ?: 'Lain-lain' }}</span>
                        <span class="font-medium">Rp {{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="text-gray-400 pl-4 py-1 italic">Tidak ada transaksi pendapatan.</div>
                @endforelse
                <div class="flex justify-between py-2 px-2 mt-2 bg-gray-100 dark:bg-gray-800 font-bold rounded">
                    <span>Total Pendapatan</span>
                    <span class="text-green-600">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>
            </div>

            <div>
                <h3 class="font-bold text-gray-800 dark:text-gray-200 border-b pb-2 mb-3 mt-6">HARGA POKOK PENJUALAN (HPP)</h3>
                <div class="flex justify-between py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded">
                    <span class="text-gray-600 dark:text-gray-400 pl-4">Pembelian Stok Barang (Purchase Order)</span>
                    <span class="font-medium">Rp {{ number_format($totalCogs, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between py-2 px-2 mt-2 bg-gray-100 dark:bg-gray-800 font-bold rounded">
                    <span>Total HPP</span>
                    <span class="text-red-500">Rp {{ number_format($totalCogs, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="flex justify-between py-3 px-4 mt-4 bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 font-bold text-lg rounded-lg">
                <span class="text-primary-700 dark:text-primary-400">LABA KOTOR (Gross Profit)</span>
                <span class="{{ $grossProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rp {{ number_format($grossProfit, 0, ',', '.') }}
                </span>
            </div>

            <div>
                <h3 class="font-bold text-gray-800 dark:text-gray-200 border-b pb-2 mb-3 mt-6">BIAYA OPERASIONAL (OPEX)</h3>
                @forelse ($opexDetails as $category => $items)
                    <div class="flex justify-between py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded">
                        <span class="text-gray-600 dark:text-gray-400 pl-4">{{ $category ?: 'Lain-lain' }}</span>
                        <span class="font-medium">Rp {{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="text-gray-400 pl-4 py-1 italic">Tidak ada transaksi operasional.</div>
                @endforelse
                <div class="flex justify-between py-2 px-2 mt-2 bg-gray-100 dark:bg-gray-800 font-bold rounded">
                    <span>Total Biaya Operasional</span>
                    <span class="text-red-500">Rp {{ number_format($totalOpex, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="flex justify-between py-4 px-4 mt-6 bg-gray-800 dark:bg-gray-100 text-white dark:text-gray-900 font-bold text-xl rounded-xl shadow-md transition-transform hover:scale-[1.01]">
                <span>LABA BERSIH (Net Profit)</span>
                <span class="{{ $netProfit >= 0 ? 'text-green-400 dark:text-green-600' : 'text-red-400 dark:text-red-600' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </span>
            </div>

        </div>
    </div>
</x-filament-panels::page>
