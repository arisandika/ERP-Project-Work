<x-filament-panels::page>
    <div class="space-y-8">
        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm ring-1 ring-gray-950/5 dark:border-gray-800 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 bg-gradient-to-r from-white via-gray-50 to-white px-6 py-5 dark:border-gray-800 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 ring-1 ring-primary-600/10 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">
                            <x-filament::icon
                                icon="heroicon-o-chart-bar-square"
                                class="h-6 w-6"
                            />
                        </div>

                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                                Finance Performance
                            </h2>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Monitoring pemasukan, pengeluaran, piutang, hutang, dan saldo akhir.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <x-filament::icon
                                icon="heroicon-o-calendar-days"
                                class="h-5 w-5 text-gray-400"
                            />

                            <select
                                wire:model.live="selectedYear"
                                class="min-w-[88px] border-0 bg-transparent p-0 text-sm font-semibold text-gray-700 outline-none ring-0 focus:ring-0 dark:text-gray-200"
                            >
                                @foreach ($this->yearOptions as $year)
                                    <option value="{{ $year }}">
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="inline-flex rounded-2xl border border-gray-200 bg-gray-100 p-1 dark:border-gray-700 dark:bg-gray-800">
                            <button
                                type="button"
                                wire:click="setSummaryMode('monthly')"
                                @class([
                                    'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition',
                                    'bg-white text-primary-600 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-primary-400 dark:ring-white/10' => $summaryMode === 'monthly',
                                    'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $summaryMode !== 'monthly',
                                ])
                            >
                                <x-filament::icon
                                    icon="heroicon-o-calendar"
                                    class="h-5 w-5"
                                />
                                Bulanan
                            </button>

                            <button
                                type="button"
                                wire:click="setSummaryMode('yearly')"
                                @class([
                                    'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition',
                                    'bg-white text-primary-600 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-primary-400 dark:ring-white/10' => $summaryMode === 'yearly',
                                    'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $summaryMode !== 'yearly',
                                ])
                            >
                                <x-filament::icon
                                    icon="heroicon-o-presentation-chart-line"
                                    class="h-5 w-5"
                                />
                                Tahunan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
                    <div class="flex items-start justify-between gap-5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Pemasukan Tahun Ini
                            </p>

                            <h3 class="mt-4 truncate text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                                {{ $this->formatMoney($this->selectedYearSummary['income']) }}
                            </h3>

                            <p class="mt-3 text-sm font-semibold text-success-600 dark:text-success-400">
                                Total cash inflow
                            </p>
                        </div>

                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400">
                            <x-filament::icon
                                icon="heroicon-o-arrow-trending-up"
                                class="h-6 w-6"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
                    <div class="flex items-start justify-between gap-5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Pengeluaran Tahun Ini
                            </p>

                            <h3 class="mt-4 truncate text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                                {{ $this->formatMoney($this->selectedYearSummary['expense']) }}
                            </h3>

                            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400">
                                Total cash outflow
                            </p>
                        </div>

                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                            <x-filament::icon
                                icon="heroicon-o-arrow-trending-down"
                                class="h-6 w-6"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
                    <div class="flex items-start justify-between gap-5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Laba / Rugi Tahun Ini
                            </p>

                            <h3 @class([
                                'mt-4 truncate text-2xl font-bold tracking-tight',
                                'text-success-600 dark:text-success-400' => $this->selectedYearSummary['net_profit'] >= 0,
                                'text-danger-600 dark:text-danger-400' => $this->selectedYearSummary['net_profit'] < 0,
                            ])>
                                {{ $this->formatMoney($this->selectedYearSummary['net_profit']) }}
                            </h3>

                            <p class="mt-3 text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Pemasukan - pengeluaran
                            </p>
                        </div>

                        <div @class([
                            'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl',
                            'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' => $this->selectedYearSummary['net_profit'] >= 0,
                            'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400' => $this->selectedYearSummary['net_profit'] < 0,
                        ])>
                            <x-filament::icon
                                :icon="$this->selectedYearSummary['net_profit'] >= 0 ? 'heroicon-o-check-badge' : 'heroicon-o-exclamation-triangle'"
                                class="h-6 w-6"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
                    <div class="flex items-start justify-between gap-5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Saldo Akhir
                            </p>

                            <h3 @class([
                                'mt-4 truncate text-2xl font-bold tracking-tight',
                                'text-success-600 dark:text-success-400' => $this->selectedYearSummary['ending_balance'] >= 0,
                                'text-danger-600 dark:text-danger-400' => $this->selectedYearSummary['ending_balance'] < 0,
                            ])>
                                {{ $this->formatMoney($this->selectedYearSummary['ending_balance']) }}
                            </h3>

                            <p class="mt-3 text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Akumulasi saldo kas
                            </p>
                        </div>

                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                            <x-filament::icon
                                icon="heroicon-o-wallet"
                                class="h-6 w-6"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ $summaryMode === 'monthly' ? 'Ringkasan Bulanan' : 'Ringkasan Tahunan' }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $summaryMode === 'monthly'
                                ? 'Data ditampilkan per bulan dalam tahun yang dipilih.'
                                : 'Data ditampilkan per tahun berdasarkan transaksi yang tersedia.' }}
                        </p>
                    </div>

                    <div class="inline-flex w-fit items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <x-filament::icon
                            icon="heroicon-o-funnel"
                            class="h-4 w-4"
                        />

                        Tahun {{ $selectedYear }}
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-950/60">
                            <th class="px-6 py-4 text-left font-bold text-gray-700 dark:text-gray-200">
                                Periode
                            </th>

                            <th class="px-6 py-4 text-right font-bold text-gray-700 dark:text-gray-200">
                                Pemasukan
                            </th>

                            <th class="px-6 py-4 text-right font-bold text-gray-700 dark:text-gray-200">
                                Pengeluaran
                            </th>

                            <th class="px-6 py-4 text-right font-bold text-gray-700 dark:text-gray-200">
                                Laba / Rugi
                            </th>

                            <th class="px-6 py-4 text-right font-bold text-gray-700 dark:text-gray-200">
                                Sisa Piutang
                            </th>

                            <th class="px-6 py-4 text-right font-bold text-gray-700 dark:text-gray-200">
                                Sisa Hutang
                            </th>

                            <th class="px-6 py-4 text-right font-bold text-gray-700 dark:text-gray-200">
                                Saldo Akhir
                            </th>

                            <th class="px-6 py-4 text-center font-bold text-gray-700 dark:text-gray-200">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @php
                            $rows = $summaryMode === 'monthly'
                                ? $this->monthlySummaries
                                : $this->yearlySummaries;
                        @endphp

                        @forelse ($rows as $row)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                            <x-filament::icon
                                                :icon="$summaryMode === 'monthly' ? 'heroicon-o-calendar' : 'heroicon-o-calendar-days'"
                                                class="h-5 w-5"
                                            />
                                        </div>

                                        <div>
                                            <div class="font-bold text-gray-950 dark:text-white">
                                                {{ $row['period'] }}
                                            </div>

                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Finance summary
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-success-600 dark:text-success-400">
                                    {{ $this->formatMoney($row['income']) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-danger-600 dark:text-danger-400">
                                    {{ $this->formatMoney($row['expense']) }}
                                </td>

                                <td @class([
                                    'whitespace-nowrap px-6 py-4 text-right font-bold',
                                    'text-success-600 dark:text-success-400' => $row['net_profit'] >= 0,
                                    'text-danger-600 dark:text-danger-400' => $row['net_profit'] < 0,
                                ])>
                                    {{ $this->formatMoney($row['net_profit']) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-warning-600 dark:text-warning-400">
                                    {{ $this->formatMoney($row['receivable_remaining']) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-orange-600 dark:text-orange-400">
                                    {{ $this->formatMoney($row['payable_remaining']) }}
                                </td>

                                <td @class([
                                    'whitespace-nowrap px-6 py-4 text-right font-bold',
                                    'text-success-600 dark:text-success-400' => $row['ending_balance'] >= 0,
                                    'text-danger-600 dark:text-danger-400' => $row['ending_balance'] < 0,
                                ])>
                                    {{ $this->formatMoney($row['ending_balance']) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    @if ($row['net_profit'] >= 0)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-xs font-bold text-success-700 ring-1 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20">
                                            <x-filament::icon
                                                icon="heroicon-o-check-circle"
                                                class="h-4 w-4"
                                            />
                                            Profit
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-danger-50 px-3 py-1 text-xs font-bold text-danger-700 ring-1 ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-400 dark:ring-danger-500/20">
                                            <x-filament::icon
                                                icon="heroicon-o-exclamation-circle"
                                                class="h-4 w-4"
                                            />
                                            Rugi
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                                            <x-filament::icon
                                                icon="heroicon-o-folder-open"
                                                class="h-7 w-7"
                                            />
                                        </div>

                                        <h3 class="mt-4 text-base font-bold text-gray-950 dark:text-white">
                                            Belum ada data transaksi
                                        </h3>

                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Data akan muncul setelah ada catatan pemasukan, pengeluaran, piutang, atau hutang.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
