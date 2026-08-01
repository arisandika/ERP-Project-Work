<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverdueTicketsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Ticket Terlambat — Perlu Tindak Lanjut';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()
                    ->whereDate('due_date', '<', now())
                    ->whereHas('status', fn($q) => $q->where('is_completed', false))
                    ->orderBy('due_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Ticket ID')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Ticket')
                    ->limit(30),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('priority.name')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'High'   => 'danger',
                        'Medium' => 'warning',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->dateTime('d M Y')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('Keterlambatan')
                    ->getStateUsing(fn(Ticket $record) => abs($record->remaining_days) . ' Hari')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('assignees.full_name')
                    ->label('Ditugaskan')
                    ->badge()
                    ->listWithLineBreaks()
                    ->placeholder('Belum Ditugaskan'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Ticket $record) => route('filament.admin.resources.project.tickets.view', $record)),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
