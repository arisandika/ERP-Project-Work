<?php
namespace App\Filament\Widgets\Marketing;

use App\Models\Marketing\PromoCode;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class ActivePromoCodesTable extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected function getHeading(): ?string
    {
        return 'Promo Code Aktif';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PromoCode::available()->latest())
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'percentage' => 'success',
                        'fixed' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'percentage' => 'Persen (%)',
                        'fixed' => 'Nominal (Rp)',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->formatStateUsing(fn($state, PromoCode $record): string => $record->type === 'percentage' ? "{$state}%" : 'Rp ' . number_format($state, 0, ',', '.'))
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('usage_progress')
                    ->label('Penggunaan')
                    ->getStateUsing(function (PromoCode $record): string {
                        if (!$record->usage_limit) {
                            return "{$record->times_used} / ∞";
                        }
                        $percent = round(($record->times_used / $record->usage_limit) * 100);
                        return "{$record->times_used} / {$record->usage_limit} ({$percent}%)";
                    })
                    ->color(function (PromoCode $record): ?string {
                        if (!$record->usage_limit)
                            return null;
                        $percent = ($record->times_used / $record->usage_limit) * 100;
                        if ($percent >= 90)
                            return 'danger';
                        if ($percent >= 70)
                            return 'warning';
                        return 'success';
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Berakhir')
                    ->date('d M Y')
                    ->sortable()
                    ->color(function (PromoCode $record): ?string {
                        if (!$record->end_date)
                            return null;
                        if ($record->end_date->diffInDays(now()) <= 7)
                            return 'danger';
                        return null;
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
            ]);
    }
}
