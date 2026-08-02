<?php

namespace App\Filament\Widgets\Project;

use App\Models\Project\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTicketsTable extends BaseWidget
{
    protected static ?string $heading = 'Tiket Terbaru';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()
                    ->with(['project', 'ticketStatus', 'priority'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Ticket')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Judul')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->limit(15),

                Tables\Columns\TextColumn::make('ticketStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->ticketStatus?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('priority.name')
                    ->label('Priority')
                    ->badge()
                    ->color(fn ($record) => $record->priority?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
