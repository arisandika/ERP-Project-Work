@php $data = $this->getClockData(); @endphp

<x-filament::widget>
    <x-filament::section>
        <x-slot name="heading">Rata-rata Jam Masuk & Pulang</x-slot>
        <x-slot name="description">Analisis kebiasaan kerja berdasarkan {{ $data['total_records'] }} hari data</x-slot>

        <div class="grid grid-cols-2 gap-4">
            {{-- Clock In --}}
            <div class="p-4 text-center rounded-xl bg-green-50 dark:bg-green-900/20">
                <div class="mb-2 text-xs font-semibold tracking-wider text-green-600 uppercase dark:text-green-400">
                    Rata-rata Masuk
                </div>
                <div class="text-3xl font-bold text-green-700 dark:text-green-300">
                    {{ $data['avg_in'] }}
                </div>
                <div class="mt-2 flex justify-between text-[11px] text-gray-500 dark:text-gray-400">
                    <span>⬆ Paling pagi: {{ $data['earliest_in'] }}</span>
                    <span>⬇ Paling siang: {{ $data['latest_in'] }}</span>
                </div>
            </div>

            {{-- Clock Out --}}
            <div class="p-4 text-center rounded-xl bg-blue-50 dark:bg-blue-900/20">
                <div class="mb-2 text-xs font-semibold tracking-wider text-blue-600 uppercase dark:text-blue-400">
                    Rata-rata Pulang
                </div>
                <div class="text-3xl font-bold text-blue-700 dark:text-blue-300">
                    {{ $data['avg_out'] }}
                </div>
                <div class="mt-2 flex justify-between text-[11px] text-gray-500 dark:text-gray-400">
                    <span>⬆ Paling awal: {{ $data['earliest_out'] }}</span>
                    <span>⬇ Paling akhir: {{ $data['latest_out'] }}</span>
                </div>
            </div>
        </div>

        {{-- Insight keterlambatan --}}
        @if($data['late_day'] !== '-')
            <div
                class="flex items-center gap-3 px-4 py-3 mt-4 border rounded-lg border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20">
                <x-heroicon-o-light-bulb class="flex-shrink-0 w-5 h-5 text-amber-500" />
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    Kamu paling sering terlambat di hari <strong>{{ $data['late_day'] }}</strong>
                    ({{ $data['late_day_cnt'] }}x dalam periode ini).
                </p>
            </div>
        @else
            <div
                class="flex items-center gap-3 px-4 py-3 mt-4 border border-green-200 rounded-lg bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                <x-heroicon-o-check-circle class="flex-shrink-0 w-5 h-5 text-green-500" />
                <p class="text-sm text-green-700 dark:text-green-300">
                    Tidak ada keterlambatan dalam periode ini. Pertahankan! 🎉
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament::widget>