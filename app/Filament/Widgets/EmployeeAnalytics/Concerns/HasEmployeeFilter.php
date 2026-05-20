<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Concerns;

use App\Models\HR\Employee;
use Illuminate\Support\Carbon;

/**
 * Shared trait untuk semua Employee Analytics Widget.
 * - Menyediakan getEmployee() dengan eager-load supaya tidak N+1
 * - Menyediakan getStartDate() / getEndDate() dari filter dashboard
 *
 * Cara kerja filter:
 * Filament 3 HasFiltersForm secara otomatis inject $filters ke semua widget
 * di dalam page yang sama via Livewire. Cukup declare public ?array $filters.
 */
trait HasEmployeeFilter
{
    public ?array $filters = null;

    /**
     * Ambil employee yang sedang login beserta relasi yang dibutuhkan.
     * Semua widget pakai method ini — query hanya 1x per widget render.
     */
    protected function getEmployee(): Employee
    {
        return Employee::with([
            'attendances',
            'leaveRequests.leave',
            'reimbursementRequests',
        ])
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    protected function getStartDate(): Carbon
    {
        return isset($this->filters['startDate']) && $this->filters['startDate']
            ? Carbon::parse($this->filters['startDate'])
            : now()->startOfMonth();
    }

    protected function getEndDate(): Carbon
    {
        return isset($this->filters['endDate']) && $this->filters['endDate']
            ? Carbon::parse($this->filters['endDate'])
            : now()->endOfMonth();
    }
}