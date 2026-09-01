<?php

namespace App\Filament\Resources\HR\EmployeePerformanceResource\Pages;

use App\Filament\Resources\HR\EmployeePerformanceResource;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\Project\Project;
use App\Models\Project\TicketStatus;
use App\Support\EmployeePerformanceMetrics;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;

class ListEmployeePerformance extends ListRecords
{
    protected static string $resource = EmployeePerformanceResource::class;

    protected static ?string $title = 'Employee Performance';

    /** @var array<string,mixed> */
    #[Url]
    public ?array $dateRange = null;

    #[Url]
    public ?string $filter_department_id = null;

    #[Url]
    public ?string $filter_position = null;

    #[Url]
    public ?string $filter_project_id = null;

    #[Url]
    public ?string $filter_project_status = null;

    #[Url]
    public ?string $filter_start_date = null;

    #[Url]
    public ?string $filter_end_date = null;

    #[Url]
    public ?string $filter_period = null;

    public function getHeader(): ?View
    {
        return view('filament.pages.hr.employee-performance-filter', [
            'departments' => Department::pluck('name', 'id')->toArray(),
            'positions' => Employee::whereNotNull('position')
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('position')
                ->mapWithKeys(fn($v) => [$v => ucwords(str_replace('_', ' ', $v))])
                ->toArray(),
            'projects' => Project::pluck('name', 'id')->toArray(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Compute once per employee per request (multiple columns/row).
     */
    protected function metricsFor(Employee $record): object
    {
        $from = $this->dateRange['from'] ?? ($this->filter_start_date ?? null);
        $until = $this->dateRange['until'] ?? ($this->filter_end_date ?? null);

        // Apply project-period if set and no explicit date.
        if ($filterFrom = $this->resolvePeriodFrom()) {
            $from = $from ?? $filterFrom;
        }
        if ($filterUntil = $this->resolvePeriodUntil()) {
            $until = $until ?? $filterUntil;
        }

        $cacheKey = $record->id . '|' . ($from ?? '') . '|' . ($until ?? '');

        if (isset(static::$metricsCache[$cacheKey])) {
            return static::$metricsCache[$cacheKey];
        }

        return static::$metricsCache[$cacheKey] = EmployeePerformanceMetrics::forEmployee(
            $record,
            $from ? Carbon::parse($from) : null,
            $until ? Carbon::parse($until) : null,
        );
    }

    protected static array $metricsCache = [];

    /**
     * Apply date filter from the selected period (e.g. 30/90/180/365 days).
     */
    protected function resolvePeriodFrom(): ?Carbon
    {
        if (! $this->filter_period) {
            return null;
        }

        return Carbon::now()->subDays((int) $this->filter_period)->startOfDay();
    }

    protected function resolvePeriodUntil(): ?Carbon
    {
        if (! $this->filter_period) {
            return null;
        }

        return Carbon::now()->endOfDay();
    }

    public function applyFilters(): void
    {
        $this->dateRange = [
            'from' => $this->filter_start_date,
            'until' => $this->filter_end_date,
        ];
    }

    public function resetFilters(): void
    {
        $this->filter_department_id = null;
        $this->filter_position = null;
        $this->filter_project_id = null;
        $this->filter_project_status = null;
        $this->filter_start_date = null;
        $this->filter_end_date = null;
        $this->filter_period = null;
        $this->dateRange = null;
        static::$metricsCache = [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Karyawan di Bawah Supervisor')
            ->description('Pilih seorang karyawan untuk melihat riwayat project dan kontribusi.')
            ->recordTitleAttribute('full_name')
            ->query(fn() => $this->getSubordinateQuery())
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->formatStateUsing(fn(?string $state) => ucfirst(str_replace('_', ' ', $state ?? '-')))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('m_total_projects')
                    ->label('Total Projects')
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(fn(Employee $record) => $this->metricsFor($record)->totalProjects),
                Tables\Columns\TextColumn::make('m_completed_projects')
                    ->label('Completed Projects')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn(Employee $record) => $this->metricsFor($record)->completedProjects),
                Tables\Columns\TextColumn::make('m_completed_on_time')
                    ->label('Completed On Time')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn(Employee $record) => $this->metricsFor($record)->completedOnTime),
                Tables\Columns\TextColumn::make('m_total_tasks')
                    ->label('Total Tasks')
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(fn(Employee $record) => $this->metricsFor($record)->totalTasks),
                Tables\Columns\TextColumn::make('m_overdue_tasks')
                    ->label('Overdue Tasks')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(fn(Employee $record) => $this->metricsFor($record)->overdueTasks),
                Tables\Columns\TextColumn::make('m_performance_score')
                    ->label('Performance Score')
                    ->badge()
                    ->color(fn($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'))
                    ->getStateUsing(fn(Employee $record) => $this->metricsFor($record)->performanceScore),
                Tables\Columns\TextColumn::make('m_status')
                    ->label('Performance Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Excellent' => 'success',
                        'Good' => 'warning',
                        'Needs Improvement' => 'danger',
                        default => 'gray',
                    })
                    ->getStateUsing(fn(Employee $record) => $this->metricsFor($record)->status),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('full_name');
    }

    /**
     * Apply filter conditions to subordinate query.
     */
    protected function getSubordinateQuery(): Builder
    {
        $user = auth()->user();

        $base = $user->hasRole('super_admin')
            ? Employee::query()
            : ($user->employee
                ? Employee::where('supervisor_id', $user->employee->id)
                : Employee::where('id', 0));

        $base->when($this->filter_department_id, function (Builder $q, $val) {
            $q->where('department_id', $val);
        });

        $base->when($this->filter_position, function (Builder $q, $val) {
            $q->where('position', $val);
        });

        $base->when($this->filter_project_id, function (Builder $q, $val) {
            $q->whereHas('projects', fn(Builder $sq) => $sq->where('nx_projects.id', $val));
        });

        // Project Status filtering is handled per-row via metricsFor.
        $base->when($this->filter_project_status, function (Builder $q, $val) {
            $q->whereHas('projects', function (Builder $sq) use ($val) {
                $completedIds = TicketStatus::where('is_completed', true)->pluck('id');
                match ($val) {
                    'active' => $sq->whereNotIn('nx_tickets.ticket_status_id', $completedIds),
                    'completed' => $sq->whereDoesntHave('tickets', fn(Builder $tq) =>
                        $tq->whereNotIn('ticket_status_id', $completedIds)),
                    'on_hold' => $sq->where('status', 'on_hold'),
                    default => null,
                };
            });
        });

        return $base;
    }

    /**
     * Supervisor sees direct subordinates; super_admin sees all.
     */
    public function getEmployeeOptions(): array
    {
        return Employee::whereIn('id', $this->getSubordinateQuery()->pluck('id'))
            ->pluck('full_name', 'id')
            ->toArray();
    }
}
