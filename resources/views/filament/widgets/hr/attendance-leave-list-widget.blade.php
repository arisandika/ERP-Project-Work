<x-filament::widget>
    <x-filament::section heading="Karyawan Cuti/Izin Hari Ini" class="h-[300px] max-h-[287px] pb-6">

        @php $leaves = $this->todayLeaves; @endphp

        @if ($leaves->isEmpty())
            <p class="flex items-center text-sm italic text-gray-500">
                Tidak ada karyawan yang cuti atau izin hari ini.
            </p>
        @else
            <div class="flex flex-col gap-3 h-[200px] overflow-y-auto no-scrollbar">

                @foreach ($leaves as $item)
                    <div
                        class="flex items-start gap-4 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                        <img src="{{ $item['photo'] ?? asset('assets/placeholder.jpg') }}" alt="{{ $item['employee_name'] }}"
                            class="object-cover w-10 h-10 border border-gray-300 rounded-full dark:border-gray-600">

                        <div class="flex-1 min-w-0">
                            <p class="mb-1 text-base font-medium text-gray-900 dark:text-white">
                                {{ $item['employee_name'] }}
                            </p>
                            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $item['department'] }} | {{ $item['office'] }}
                            </p>
                            <p class="mb-2 text-sm font-bold text-gray-600 dark:text-gray-300">
                                {{ $item['leave_type'] }}
                            </p>
                            <p class="text-sm text-gray-400 dark:text-gray-500">
                                {{ $item['start_date'] }} s/d {{ $item['end_date'] }}
                                ({{ $item['total_days'] }} hari)
                            </p>
                        </div>
                    </div>
                @endforeach

            </div>
        @endif

    </x-filament::section>
</x-filament::widget>