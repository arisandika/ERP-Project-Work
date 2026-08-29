<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Ticket;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class MyTasksTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Task Saya';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        $employeeId = auth()->user()->employee?->id;

        return $table
            ->query(
                Ticket::query()
                    ->whereHas('assignees', fn($q) => $q->where('nx_employees.id', $employeeId))
                    ->whereHas('status', fn($q) => $q->where('is_completed', false))
                    ->orderBy('due_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ticket')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => $record->status?->color ?? 'gray'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Deadline')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('Sisa Waktu')
                    ->getStateUsing(fn(Ticket $record) => $record->remaining_days < 0
                        ? 'Terlambat ' . abs($record->remaining_days) . ' Hari'
                        : $record->remaining_days . ' Hari')
                    ->badge()
                    ->color(fn(Ticket $record) => $record->remaining_days < 0 ? 'danger' : ($record->remaining_days <= 3 ? 'warning' : 'success')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Kerjakan')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->url(fn(Ticket $record) => route('filament.admin.resources.project.tickets.view', $record)),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Tidak ada task aktif')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
