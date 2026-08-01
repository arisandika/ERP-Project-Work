<?php
namespace App\Filament\Widgets\HR;

use App\Models\HR\Holiday;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class UpcomingHolidaysWidget extends BaseWidget
{
    protected static ?string $heading = 'Hari Libur Mendatang';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Holiday::query()
                    ->where('end_date', '>=', now())
                    ->orderBy('start_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Hari Libur')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('date_range')
                    ->label('Tanggal')
                    ->getStateUsing(function (Holiday $record) {
                        $start = Carbon::parse($record->start_date)->translatedFormat('d M Y');
                        $end   = Carbon::parse($record->end_date)->translatedFormat('d M Y');
                        return $start === $end ? $start : "$start - $end";
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Sisa Hari')
                    ->getStateUsing(function (Holiday $record) {
                        $days = (int) round(now()->diffInDays(Carbon::parse($record->start_date), false));
                        return $days <= 0 ? 'Berlangsung' : $days . ' Hari Lagi';
                    })
                    ->badge()
                    ->color(fn($state) => str_contains($state, 'Berlangsung') ? 'success' : 'info'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada hari libur mendatang');
    }
}
