<?php
namespace App\Filament\Widgets\Marketing;

use App\Models\Marketing\PopupBanner;
use App\Models\Marketing\Slider;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ContentOverviewTable extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'Konten Terbaru (Slider & Banner)';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Slider::withTrashed()->withoutGlobalScope('ordered')
                    ->selectRaw("id, 'Slider' as type, title, is_active, start_date, end_date, created_at")
                    ->union(
                        PopupBanner::withTrashed()
                            ->selectRaw("id, 'Banner' as type, title, is_active, start_date, end_date, created_at")
                    )
            )
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->hidden(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Slider' => 'info',
                        'Banner' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Slider' => 'heroicon-o-photo',
                        'Banner' => 'heroicon-o-megaphone',
                        default => 'heroicon-o-document',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($state, $record): string {
                        $now = now();
                        if (! $record->is_active) return 'Nonaktif';
                        if ($record->start_date && $record->start_date->greaterThan($now)) return 'Terjadwal';
                        if ($record->end_date && $record->end_date->lessThan($now)) return 'Expired';
                        return 'Aktif';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Terjadwal' => 'info',
                        'Expired' => 'danger',
                        'Nonaktif' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y H:i'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y H:i')
                    ->color(function ($record): ?string {
                        if ($record->end_date && $record->end_date->diffInDays(now()) <= 3 && $record->is_active) {
                            return 'danger';
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable(),
            ]);
    }
}
