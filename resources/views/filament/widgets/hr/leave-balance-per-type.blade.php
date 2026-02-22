@php
    use App\Models\HR\Leave;
    use App\Models\HR\LeaveRequest;

    $employee = auth()->user()?->employee;
    $rows = [];

    if ($employee) {

        // Query leave berdasarkan gender
        $leaves = Leave::query()
            ->when($employee->gender !== 'Perempuan', function ($query) {
                $query->where('is_female_only', false);
            })
            ->get();

        foreach ($leaves as $leave) {

            $used = LeaveRequest::where('employee_id', $employee->id)
                ->where('leave_id', $leave->id)
                ->where('status', 'approved')
                ->sum('total_days');

            $remaining = max($leave->days_count - $used, 0);
            $percentage = $leave->days_count > 0
                ? round(($used / $leave->days_count) * 100)
                : 0;

            $badgeColor = $percentage < 40
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-700 dark:text-emerald-100'
                : ($percentage < 70
                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-700 dark:text-amber-100'
                    : 'bg-red-100 text-red-700 dark:bg-red-700 dark:text-red-100');

            $icons = [
                'Cuti Tahunan' => 'heroicon-o-sun',
                'Cuti Sakit' => 'heroicon-o-heart',
                'Cuti Melahirkan' => 'heroicon-o-gift',
                'Cuti Keguguran' => 'heroicon-o-heart',
                'Cuti Menikah' => 'heroicon-o-sparkles',
                'Cuti Istri Melahirkan/Keguguran' => 'heroicon-o-clipboard-document-list',
            ];

            $icon = $icons[$leave->leave_type] ?? 'heroicon-o-calendar';

            $rows[] = [
                'type' => $leave->leave_type,
                'quota' => $leave->days_count,
                'used' => $used,
                'remaining' => $remaining,
                'percentage' => $percentage,
                'badge' => $badgeColor,
                'icon' => $icon,
            ];
        }
    }
@endphp

<x-filament::widget>
    <x-filament::section heading="Sisa Cuti per Jenis"
        description="Berikut adalah rincian sisa cuti Anda berdasarkan jenis cuti." collapsible="true" collapsed="true">

        <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-3">

            @foreach($rows as $row)
                <div
                    class="w-full p-6 border border-gray-200 rounded-lg bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 dark:border-gray-700">

                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <x-filament::icon :icon="$row['icon']" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            <h3 class="text-lg font-semibold">{{ $row['type'] }}</h3>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Kuota</span>
                            <span class="font-medium">{{ $row['quota'] }} hari</span>
                        </div>

                        <div class="flex justify-between">
                            <span>Dipakai</span>
                            <span class="font-medium">{{ $row['used'] }} hari</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="font-semibold">Sisa</span>
                            <span class="font-bold text-primary-600 dark:text-primary-400">{{ $row['remaining'] }}
                                hari</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="w-full h-2 overflow-hidden bg-gray-200 rounded-full dark:bg-gray-700">
                            <div class="h-full bg-primary-600 dark:bg-primary-400" style="width: {{ $row['percentage'] }}%">
                            </div>
                        </div>

                        <div class="mt-1 text-xs text-right text-gray-500 dark:text-gray-400">
                            {{ $row['percentage'] }}% dari {{ $row['quota'] }} hari
                        </div>
                    </div>

                </div>
            @endforeach

        </div>
        
    </x-filament::section>
</x-filament::widget>