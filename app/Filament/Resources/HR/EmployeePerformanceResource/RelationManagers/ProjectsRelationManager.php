<?php

namespace App\Filament\Resources\HR\EmployeePerformanceResource\RelationManagers;

use App\Filament\Resources\HR\EmployeePerformanceResource;
use App\Filament\Resources\Project\ProjectResource;
use App\Models\HR\Employee;
use App\Models\Project\Project;
use App\Models\Project\Ticket;
use App\Models\Project\TicketStatus;
use Carbon\Carbon;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    protected static ?string $title = 'Riwayat Project';

    protected static ?string $modelLabel = 'Project';

    protected static ?string $pluralModelLabel = 'Project';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->projects->count();
    }

    public function table(Table $table): Table
    {
        $employee = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('name')
            ->heading('Riwayat Project')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-briefcase'),
                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Role / PIC')
                    ->placeholder('Anggota')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_tasks')
                    ->label('Total Task')
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(fn(Project $record) => $this->computeMetric($record, 'total')),
                Tables\Columns\TextColumn::make('completed_tasks')
                    ->label('Completed')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn(Project $record) => $this->computeMetric($record, 'completed')),
                Tables\Columns\TextColumn::make('overdue_tasks')
                    ->label('Overdue')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(fn(Project $record) => $this->computeMetric($record, 'overdue')),
                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Completion Rate')
                    ->getStateUsing(fn(Project $record) => $this->computeMetric($record, 'rate'))
                    ->formatStateUsing(fn(float $state): string => $state . '%')
                    ->color(fn(float $state) => $state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Project $record) => EmployeePerformanceResource::getUrl('project', [
                        'record' => $employee->id,
                        'project' => $record->id,
                    ])),
                Tables\Actions\Action::make('view_project')
                    ->label('View Project')
                    ->icon('heroicon-o-briefcase')
                    ->color('info')
                    ->url(fn(Project $record) => ProjectResource::getUrl('view', [
                        'record' => $record->id,
                    ])),
            ])
            ->emptyStateHeading('Karyawan belum bergabung ke project manapun.')
            ->defaultSort('end_date', 'desc');
    }

    protected function computeMetric(Project $project, string $key): int|float
    {
        $employee = $this->getOwnerRecord();
        $today = Carbon::today();

        $completedStatusIds = TicketStatus::where('project_id', $project->id)
            ->where('is_completed', true)
            ->pluck('id');

        $base = Ticket::where('project_id', $project->id)
            ->where(function ($q) use ($employee) {
                $q->whereHas('assignees', fn($sub) => $sub->where('employee_id', $employee->id))
                    ->orWhere('created_by', $employee->id);
            });

        return match ($key) {
            'total'     => $base->count(),
            'completed' => $base->whereIn('ticket_status_id', $completedStatusIds)->count(),
            'overdue'   => (clone $base)
                ->whereNotIn('ticket_status_id', $completedStatusIds)
                ->whereDate('due_date', '<', $today)
                ->count(),
            'rate'      => $this->computeMetric($project, 'total') > 0
                ? round(($this->computeMetric($project, 'completed') / $this->computeMetric($project, 'total')) * 100, 1)
                : 0.0,
            default     => 0,
        };
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
