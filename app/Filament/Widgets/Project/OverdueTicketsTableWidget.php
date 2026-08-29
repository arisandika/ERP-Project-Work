<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Ticket;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class OverdueTicketsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Ticket Terlambat';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = [
        'default' => 122,
        'xl' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()
                    ->whereDate('due_date', '<', now())
                    ->whereHas('status', fn($q) => $q->where('name', '!=', 'Done'))
                    ->orderBy('due_date')
                    ->limit(10)
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
                Tables\Columns\TextColumn::make('assignees.full_name')
                    ->label('Ditugaskan')
                    ->badge()
                    ->color('danger')
                    ->listWithLineBreaks()
                    ->placeholder('Belum ditugaskan'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tenggat')
                    ->dateTime('d M Y')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('Keterlambatan')
                    ->getStateUsing(fn(Ticket $record) => abs($record->remaining_days) . ' hari')
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada ticket terlambat')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
