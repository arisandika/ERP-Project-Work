<?php
namespace App\Filament\Widgets\CRM;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\Deal;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class HotDealsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Deal Bernilai Besar — Perlu Diprioritaskan';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Deal::query()
                    ->where('status', Deal::STATUS_OPEN)
                    ->orderByDesc('estimated_value')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('deal_number')
                    ->label('No. Deal')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Deal')
                    ->limit(30),
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Klien')
                    ->state(fn(Deal $record) => $record->customer?->name ?? $record->lead()->withTrashed()->first()?->name ?? '-'),
                Tables\Columns\TextColumn::make('stage.name')
                    ->label('Stage')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('Est. Value')
                    ->money('IDR')
                    ->weight('semibold')
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.full_name')
                    ->label('Sales')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('deal_date')
                    ->label('Umur Deal')
                    ->getStateUsing(fn(Deal $record) => $record->deal_date
                        ? \Carbon\Carbon::parse($record->deal_date)->diffInDays(now()) . ' Hari'
                        : '—')
                    ->badge()
                    ->color(fn(Deal $record) => \Carbon\Carbon::parse($record->deal_date)->diffInDays(now()) > 30 ? 'danger' : 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Deal $record) => DealResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('Tidak ada deal open saat ini');
    }
}
