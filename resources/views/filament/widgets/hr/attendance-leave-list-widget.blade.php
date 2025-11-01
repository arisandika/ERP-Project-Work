<x-filament::widget>
    <x-filament::section>

        <div class="flex items-center justify-between mb-2">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                Karyawan Cuti / Izin Hari Ini
            </h2>
            <span class="text-xs text-gray-500">{{ now()->format('d M Y') }}</span>
        </div>

        @php $leaves = $this->todayLeaves; @endphp

        @if ($leaves->isEmpty())
            <p class="text-sm italic text-gray-500">
                Tidak ada karyawan yang cuti atau izin hari ini.
            </p>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[300px] overflow-y-auto">
                @foreach ($leaves as $item)
                    <div class="flex items-center gap-3 py-2">
                        <img src="{{ $item->employee->photo ? Storage::url($item->employee->photo) : asset('assets/placeholder.jpg') }}"
                            alt="{{ $item->employee->full_name }}"
                            class="object-cover w-10 h-10 border border-gray-300 rounded-full">

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $item->employee->full_name }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $item->employee->position ?? '-' }}</p>
                            <p class="text-xs italic text-gray-400">
                                {{ $item->leave->leave_type ?? 'Cuti/Izin' }}
                            </p>
                        </div>

                        <button wire:click="showEmployeeDetail({{ $item->employee->id }})"
                            class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                            Lainnya
                        </button>
                    </div>
                @endforeach
            </div>
        @endif


        <div x-show="$wire.isShowingEmployeeDetailModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="$wire.closeEmployeeDetailModal()"
                class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 w-[500px] max-w-full">
                @if ($this->selectedEmployee)
                    @php $employee = (object) $this->selectedEmployee; @endphp
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-12 h-12 overflow-hidden rounded-full bg-slate-100">
                            <img src="{{ $item->employee->photo ? Storage::url($item->employee->photo) : asset('assets/placeholder.jpg') }}"
                                alt="{{ $item->employee->full_name }}" class="object-cover w-12 h-12 rounded-full">
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold leading-6">{{ $employee->name }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->position ?? '-' }}</p>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-4 mt-6 text-sm">
                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Departemen</dt>
                            <dd class="font-medium">{{ $employee->department ?? '-' }}</dd>
                        </div>

                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Kantor</dt>
                            <dd class="font-medium">{{ $employee->office ?? '-' }}</dd>
                        </div>

                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Jadwal</dt>
                            <dd class="font-medium">
                                {{ $employee->shift['name'] ?? '-' }}
                                ({{ $employee->shift['start'] ? \Carbon\Carbon::parse($employee->shift['start'])->format('H:i') : '-' }}
                                -
                                {{ $employee->shift['end'] ? \Carbon\Carbon::parse($employee->shift['end'])->format('H:i') : '-' }})
                            </dd>
                        </div>

                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Tipe Karyawan</dt>
                            <dd class="font-medium">
                                {{ $employee->can_wfa ? 'Bekerja dari rumah' : 'Bekerja dari kantor' }}
                                &
                                {{ $employee->can_unlock_shift ? 'Jam kerja fleksibel' : 'Jam kerja tetap' }}
                            </dd>
                        </div>
                    </dl>
                @endif

                <div class="mt-6 text-right">
                    <button wire:click="closeEmployeeDetailModal"
                        class="px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament::widget>