<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTicketsTable extends BaseWidget
{
    protected static ?string $heading = 'Ticket Terbaru';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl'      => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()->latest('created_at')->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Ticket ID')
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Ticket')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => match ($record->status?->name) {
                        'To Do'       => 'warning',
                        'In Progress' => 'info',
                        'Review'      => 'primary',
                        'Done'        => 'success',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('priority.name')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'High'   => 'danger',
                        'Medium' => 'warning',
                        'Low'    => 'success',
                        default  => 'gray',
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('assignees.full_name')
                    ->label('Ditugaskan')
                    ->badge()
                    ->color('gray')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tenggat')
                    ->dateTime('d M Y')
                    ->color(fn($record) => $record->due_date && $record->due_date->isPast() && $record->status?->name !== 'Done' ? 'danger' : null),
            ])
            ->paginated(false);
    }
}
