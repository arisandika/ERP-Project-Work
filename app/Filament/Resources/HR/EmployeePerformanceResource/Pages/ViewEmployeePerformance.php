<?php

namespace App\Filament\Resources\HR\EmployeePerformanceResource\Pages;

use App\Filament\Resources\HR\EmployeePerformanceResource;
use App\Filament\Resources\HR\EmployeePerformanceResource\RelationManagers\PerformanceEvaluationsRelationManager;
use App\Filament\Resources\HR\EmployeePerformanceResource\RelationManagers\ProjectsRelationManager;
use App\Models\HR\Employee;
use App\Support\EmployeePerformanceMetrics;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Concerns\HasRelationManagers;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

class ViewEmployeePerformance extends ViewRecord
{
    use HasRelationManagers;

    protected static string $resource = EmployeePerformanceResource::class;

    protected static ?string $title = 'Employee Performance';

    public ?object $metrics = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(EmployeePerformanceResource::getUrl('index')),
        ];
    }

    /**
     * Ensure only supervisor/subordinates or self can view.
     */
    public function getRecord(): Employee
    {
        $record = parent::getRecord();

        $user = auth()->user();

        // super_admin bypass
        if ($user->hasRole('super_admin')) {
            return $record;
        }

        $employee = $user->employee;

        if ($employee && $employee->can('viewMetrics', $record)) {
            return $record;
        }

        abort(403);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $this->metrics = EmployeePerformanceMetrics::forEmployee($this->getRecord());

        return $infolist
            ->schema([
                Section::make('Informasi Karyawan')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Nama Lengkap')
                            ->weight('semibold')
                            ->size('lg')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->color('warning'),
                        TextEntry::make('position')
                            ->label('Jabatan')
                            ->formatStateUsing(fn(?string $state) => ucwords(str_replace('_', ' ', $state ?? '-')))
                            ->placeholder('—'),
                        TextEntry::make('department.name')
                            ->label('Departemen')
                            ->placeholder('—'),
                        TextEntry::make('join_date')
                            ->label('Tanggal Masuk')
                            ->date('d M Y')
                            ->placeholder('—'),
                        TextEntry::make('supervisor.full_name')
                            ->label('Supervisor')
                            ->placeholder('—')
                            ->visible(fn(Employee $record) => $record->supervisor_id !== null),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
                Section::make('Performance Summary')
                    ->description('Score and indicators derived from existing project & task contributions.')
                    ->schema([
                        TextEntry::make('performance.score')
                            ->label('Performance Score')
                            ->state(fn() => $this->metrics?->performanceScore ?? 0)
                            ->formatStateUsing(fn($state) => $state . '/100')
                            ->color(fn() => ($this->metrics?->performanceScore ?? 0) >= 80 ? 'success' : (($this->metrics?->performanceScore ?? 0) >= 60 ? 'warning' : 'danger'))
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('performance.status')
                            ->label('Performance Status')
                            ->state(fn() => $this->metrics?->status ?? '-')
                            ->color(fn($state) => match ($state) {
                                'Excellent' => 'success',
                                'Good' => 'warning',
                                'Needs Improvement' => 'danger',
                                default => 'gray',
                            })
                            ->badge(),
                        TextEntry::make('performance.total_projects')
                            ->label('Total Projects')
                            ->state(fn() => $this->metrics?->totalProjects ?? 0),
                        TextEntry::make('performance.completed_projects')
                            ->label('Completed Projects')
                            ->state(fn() => $this->metrics?->completedProjects ?? 0),
                        TextEntry::make('performance.total_tasks')
                            ->label('Total Tasks')
                            ->state(fn() => $this->metrics?->totalTasks ?? 0),
                        TextEntry::make('performance.completed_tasks')
                            ->label('Completed Tasks')
                            ->state(fn() => $this->metrics?->completedTasks ?? 0),
                        TextEntry::make('performance.overdue_tasks')
                            ->label('Overdue Tasks')
                            ->state(fn() => $this->metrics?->overdueTasks ?? 0),
                        TextEntry::make('performance.completed_on_time')
                            ->label('Completed On Time')
                            ->state(fn() => $this->metrics?->completedOnTime ?? 0),
                        TextEntry::make('performance.completion_rate')
                            ->label('Completion Rate')
                            ->state(fn() => ($this->metrics?->completionRate ?? 0) . '%'),
                        TextEntry::make('performance.evaluation_average')
                            ->label('Evaluation Average')
                            ->state(fn() => $this->metrics?->evaluationAverage !== null ? $this->metrics->evaluationAverage . '/5' : '—'),
                    ])
                    ->columns(['default' => 1, 'md' => 4]),
                Section::make('Performance Trend')
                    ->description('Historical supervisor evaluations (only shown when data exists).')
                    ->visible(fn(Employee $record) => $record->performanceEvaluations()->exists())
                    ->schema([
                        TextEntry::make('trend_placeholder')
                            ->label('')
                            ->state(function (Employee $record) {
                                $evaluations = $record->performanceEvaluations()
                                    ->with('evaluator')
                                    ->latest('evaluated_at')
                                    ->limit(8)
                                    ->get();

                                if ($evaluations->isEmpty()) {
                                    return '—';
                                }

                                $rows = $evaluations->map(function ($e) {
                                    $avg = $e->criterion_average;
                                    $avgLabel = $avg !== null ? " (avg {$avg}/5)" : '';
                                    $date = $e->evaluated_at ? $e->evaluated_at->format('d M Y') : '—';
                                    $evaluator = $e->evaluator?->full_name ?? '—';

                                    return "- {$e->period}: Overall {$e->rating}/5{$avgLabel} — {$date} (by {$evaluator})";
                                })->implode("\n");

                                return $rows;
                            })
                            ->html(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProjectsRelationManager::class,
            PerformanceEvaluationsRelationManager::class,
        ];
    }
}
