<?php

namespace App\Filament\Widgets\CRM;

use App\Models\CRM\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentLeadsTable extends BaseWidget
{
    protected static ?string $heading = 'Leads Terbaru';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->limit(20),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'primary',
                        'qualified' => 'success',
                        'unqualified' => 'gray',
                        'converted' => 'success',
                        'dead' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords($state)),

                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
