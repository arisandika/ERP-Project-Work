@php
    $calendarData = $this->getCalendarData();

    // Group by month
    $byMonth = collect($calendarData)->groupBy('month');

    $statusConfig = [
        'hadir' => ['color' => 'bg-green-500', 'dark' => 'dark:bg-green-600', 'label' => 'Hadir', 'dot' => '#22c55e'],
        'terlambat' => ['color' => 'bg-amber-400', 'dark' => 'dark:bg-amber-500', 'label' => 'Terlambat', 'dot' => '#f59e0b'],
        'absen' => ['color' => 'bg-red-500', 'dark' => 'dark:bg-red-600', 'label' => 'Absen', 'dot' => '#ef4444'],
        'cuti' => ['color' => 'bg-blue-400', 'dark' => 'dark:bg-blue-500', 'label' => 'Cuti', 'dot' => '#3b82f6'],
        'izin' => ['color' => 'bg-purple-400', 'dark' => 'dark:bg-purple-500', 'label' => 'Izin', 'dot' => '#a855f7'],
        'no_checkout' => ['color' => 'bg-orange-400', 'dark' => 'dark:bg-orange-500', 'label' => 'Lupa Checkout', 'dot' => '#f97316'],
        'holiday' => ['color' => 'bg-rose-200', 'dark' => 'dark:bg-rose-900', 'label' => 'Libur Nasional', 'dot' => '#fda4af'],
        'weekend' => ['color' => 'bg-gray-100', 'dark' => 'dark:bg-gray-800', 'label' => 'Akhir Pekan', 'dot' => '#e5e7eb'],
        'libur' => ['color' => 'bg-rose-200', 'dark' => 'dark:bg-rose-900', 'label' => 'Libur', 'dot' => '#fda4af'],
        'belum_presensi' => ['color' => 'bg-gray-200', 'dark' => 'dark:bg-gray-700', 'label' => 'Belum Presensi', 'dot' => '#d1d5db'],
    ];
@endphp

<x-filament::widget>
    <x-filament::section>
        <x-slot name="heading">Kalender Kehadiran</x-slot>
        <x-slot name="description">Visualisasi kehadiran harian dalam periode yang dipilih</x-slot>

        {{-- Legend --}}
        <div class="flex flex-wrap gap-3 mb-5">
            @foreach(['hadir', 'terlambat', 'absen', 'cuti', 'izin', 'no_checkout', 'holiday', 'weekend'] as $s)
                <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                    <span
                        class="h-3 w-3 rounded-sm {{ $statusConfig[$s]['color'] }} {{ $statusConfig[$s]['dark'] }}"></span>
                    {{ $statusConfig[$s]['label'] }}
                </div>
            @endforeach
        </div>

        {{-- Calendar per month --}}
        <div class="space-y-6">
            @foreach($byMonth as $month => $days)
                <div>
                    <p class="mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                        {{ $month }}</p>

                    {{-- Day headers --}}
                    <div class="grid grid-cols-7 mb-1">
                        @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)
                            <div class="text-center text-[10px] font-medium text-gray-400">{{ $d }}</div>
                        @endforeach
                    </div>

                    {{-- Grid cells --}}
                    @php
                        $firstDay = $days->first();
                        $startOffset = ($firstDay['dayOfWeek'] - 1); // 0-based Mon
                    @endphp

                    <div class="grid grid-cols-7 gap-1">
                        {{-- Empty cells sebelum hari pertama --}}
                        @for($i = 0; $i < $startOffset; $i++)
                            <div></div>
                        @endfor

                        @foreach($days as $day)
                            @php
                                $cfg = $statusConfig[$day['status']] ?? $statusConfig['belum_presensi'];
                                $opacity = $day['isFuture'] ? 'opacity-30' : '';
                            @endphp
                            <div x-data x-tooltip.raw="{{ $day['date'] }}: {{ $day['label'] }}"
                                class="relative flex h-8 w-full items-center justify-center rounded-md text-[11px] font-medium cursor-default select-none
                                            {{ $cfg['color'] }} {{ $cfg['dark'] }} {{ $opacity }}
                                            {{ in_array($day['status'], ['hadir', 'terlambat', 'absen', 'cuti', 'izin', 'no_checkout']) ? 'text-white' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $day['day'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament::widget>