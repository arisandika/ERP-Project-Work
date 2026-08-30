@php
    use Illuminate\Support\Str;
@endphp

<x-filament-panels::page>

    {{-- Project Header --}}
    <div class="grid grid-cols-1 gap-y-6 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-black dark:text-white flex items-center gap-2">
                <x-heroicon-o-briefcase class="w-5 h-5 text-primary-600" />
                {{ $project->name }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $project->start_date?->format('d M Y') }} — {{ $project->end_date?->format('d M Y') ?? '—' }}
            </p>
        </div>

        @if($project->projectManager)
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Project Manager: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $project->projectManager->full_name }}</span>
            </div>
        @endif

        @if($role)
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Role / PIC:
                @if(in_array($role, ['PIC', 'Project Manager']))
                    <span class="px-2 py-0.5 text-xs font-semibold text-white bg-primary-600 rounded-full">
                        {{ $role }}
                    </span>
                @else
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $role }}</span>
                @endif
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Role / PIC: <span class="font-medium text-gray-800 dark:text-gray-200">Anggota</span>
            </div>
        @endif
    </div>

    {{-- Metrics Summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-filament::card class="text-center py-4">
            <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $totalTasks }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Total Task</div>
        </x-filament::card>

        <x-filament::card class="text-center py-4">
            <div class="text-2xl font-bold text-success-600">{{ $completedTasks }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Completed</div>
        </x-filament::card>

        <x-filament::card class="text-center py-4">
            <div class="text-2xl font-bold text-danger-600">{{ $overdueTasks }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Overdue</div>
        </x-filament::card>

        <x-filament::card class="text-center py-4">
            @php
                $rate = $completionRate;
                $color = $rate >= 80 ? 'text-success-600' : ($rate >= 50 ? 'text-warning-600' : 'text-danger-600');
            @endphp
            <div class="text-2xl font-bold {{ $color }}">{{ $rate }}%</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">Completion Rate</div>
            <div class="mt-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full"
                     style="width: {{ $rate }}%; background-color: var(--tw-colors-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}-600);"></div>
            </div>
        </x-filament::card>
    </div>

    {{-- Task List --}}
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-3 flex items-center gap-2">
            <x-heroicon-o-ticket class="w-4 h-4" />
            Daftar Task (Ticket)
        </h3>

        @if($tasks->isEmpty())
            <x-filament::card>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-ticket class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>Tidak ada task yang dikerjakan pada project ini.</p>
                </div>
            </x-filament::card>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Ticket ID</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Nama Task</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Epic</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Status</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Prioritas</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Due Date</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Dibuat Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($tasks as $task)
                            @php
                                $rowClass = '';
                                if ($task->is_overdue) {
                                    $rowClass = 'bg-danger-50 dark:bg-danger-950/20';
                                } elseif ($task->is_completed) {
                                    $rowClass = 'bg-success-50 dark:bg-success-950/20';
                                }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="px-4 py-2 font-mono text-xs">{{ $task->uuid }}</td>
                                <td class="px-4 py-2 font-medium">{{ $task->name }}</td>
                                <td class="px-4 py-2">{{ $task->epic ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    @if($task->is_completed)
                                        <span class="px-2 py-0.5 text-xs font-semibold text-success-700 bg-success-100 dark:bg-success-900/30 dark:text-success-300 rounded-full">
                                            Selesai
                                        </span>
                                    @elseif($task->is_overdue)
                                        <span class="px-2 py-0.5 text-xs font-semibold text-danger-700 bg-danger-100 dark:bg-danger-900/30 dark:text-danger-300 rounded-full">
                                            Terlambat
                                        </span>
                                    @else
                                        @if($task->status_color)
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                                  style="background-color: {{ $task->status_color }};color:#fff;">
                                                {{ $task->status }}
                                            </span>
                                        @else
                                            {{ $task->status ?? '—' }}
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @if($task->priority)
                                        @php
                                            $priorityColor = match($task->priority) {
                                                'High' => 'text-danger-600',
                                                'Medium' => 'text-warning-600',
                                                'Low' => 'text-success-600',
                                                default => 'text-gray-600',
                                            };
                                        @endphp
                                        <span class="{{ $priorityColor }} font-medium">{{ $task->priority }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $task->due_date ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $task->creator ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>

    {{-- Performance Evaluations --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-black dark:text-white flex items-center gap-2">
                <x-heroicon-o-star class="w-4 h-4" />
                Penilaian Kinerja
            </h3>
        </div>

        @if($evaluations->isEmpty())
            <x-filament::card>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-star class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>Belum ada penilaian kinerja untuk karyawan ini pada project ini.</p>
                    <p class="mt-2 text-xs">Klik "Tambah Penilaian" untuk memberikan rating dan feedback.</p>
                </div>
            </x-filament::card>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 mb-4">
                <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Periode</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Rating</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Feedback</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Penilai</th>
                            <th class="px-4 py-2 font-medium text-gray-900 dark:text-gray-200">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($evaluations as $evaluation)
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ $evaluation->period }}</td>
                                <td class="px-4 py-2">
                                    @php
                                        $badge = match(true) {
                                            $evaluation->rating >= 4 => 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-300',
                                            $evaluation->rating == 3 => 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300',
                                            $evaluation->rating <= 2 => 'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $badge }}">
                                        {{ $evaluation->rating }} - {{ $evaluation->rating_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ Str::limit($evaluation->feedback, 100) ?: '—' }}</td>
                                <td class="px-4 py-2">{{ $evaluation->evaluator ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $evaluation->evaluated_at ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::card>
        @endif
    </div>

</x-filament-panels::page>
