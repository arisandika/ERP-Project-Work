<?php
namespace App\Filament\Widgets\Marketing;

use App\Models\Marketing\PopupBanner;
use App\Models\Marketing\Slider;
use Carbon\Carbon;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

class ContentOverviewTable extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected function getHeading(): ?string
    {
        return 'Konten Terbaru (Slider & Banner)';
    }

    public function getTableRecords(): Collection
    {
        $sliders = Slider::withTrashed()
            ->withoutGlobalScope('ordered')
            ->selectRaw("'Slider' as type, id, title, is_active, start_date, end_date, created_at")
            ->get();

        $banners = PopupBanner::withTrashed()
            ->selectRaw("'Banner' as type, id, title, is_active, start_date, end_date, created_at")
            ->get();

        return $sliders->concat($banners)->sortByDesc('created_at')->values();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Slider::withTrashed()->withoutGlobalScope('ordered'))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Slider' => 'info',
                        'Banner' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Slider' => 'heroicon-o-photo',
                        'Banner' => 'heroicon-o-megaphone',
                        default => 'heroicon-o-document',
                    }),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->limit(40)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record): string {
                        $now = Carbon::now();
                        if (!$record->is_active)
                            return 'Nonaktif';
                        $start = $record->start_date ? Carbon::parse($record->start_date) : null;
                        $end = $record->end_date ? Carbon::parse($record->end_date) : null;
                        if ($start && $start->greaterThan($now))
                            return 'Terjadwal';
                        if ($end && $end->lessThan($now))
                            return 'Expired';
                        return 'Aktif';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
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
                        $end = $record->end_date ? Carbon::parse($record->end_date) : null;
                        if ($end && $end->diffInDays(now()) <= 3 && $record->is_active) {
                            return 'danger';
                        }
                        return null;
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y'),
            ]);
    }
}
