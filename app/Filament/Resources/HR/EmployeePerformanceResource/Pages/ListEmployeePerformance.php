<?php

namespace App\Filament\Resources\HR\EmployeePerformanceResource\Pages;

use App\Filament\Resources\HR\EmployeePerformanceResource;
use App\Models\HR\Employee;
use App\Support\EmployeePerformanceMetrics;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListEmployeePerformance extends ListRecords
{
    protected static string $resource = EmployeePerformanceResource::class;

    protected static ?string $title = 'Employee Performance';

    /** @var array<string,mixed> */
    protected ?array $dateRange = null;

    /** @var array<int,object> per-request cache of metrics. */
    protected array $metricsCache = [];

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Compute once per employee per request (list can request ~6 columns/row).
     */
    protected function metricsFor(Employee $record): object
    {
        $key = $record->id . '|' . ($this->dateRange['from'] ?? '') . '|' . ($this->dateRange['until'] ?? '');

        return $this->metricsCache[$key] ??= EmployeePerformanceMetrics::forEmployee(
            $record,
            $this->dateRange['from'] ? Carbon::parse($this->dateRange['from']) : null,
            $this->dateRange['until'] ? Carbon::parse($this->dateRange['until']) : null,
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Karyawan di Bawah Supervizor')
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
            ->filters([
                Tables\Filters\Filter::make('period')
                    ->label('Period / Date Range')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                        DatePicker::make('until')
                            ->label('Sampai')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $this->dateRange = [
                            'from' => $data['from'] ?? null,
                            'until' => $data['until'] ?? null,
                        ];

                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->native(false),
                Tables\Filters\SelectFilter::make('id')
                    ->label('Employee')
                    ->options($this->getEmployeeOptions())
                    ->native(false),
            ])
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('full_name');
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

    protected function getSubordinateQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return Employee::query();
        }

        $employee = $user->employee;

        if (! $employee) {
            return Employee::where('id', 0); // empty
        }

        return Employee::where('supervisor_id', $employee->id);
    }
}
