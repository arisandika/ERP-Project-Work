<?php

namespace App\Filament\Pages\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Models\HR\Holiday;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class HolidayCalendar extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'hr';
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.hr.holiday-calendar';

    protected static ?string $slug = 'hr/calendar';

    protected static string $routePath = 'hr/calendar';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?string $navigationLabel = 'Kalender Libur';

    protected static ?string $title = 'Kalender Hari Libur 2026';

    public function viewHolidayAction(): Action
    {
        return Action::make('viewHoliday')
            ->modalHeading('Detail Hari Libur')
            ->modalSubmitAction(false) // Sembunyikan tombol submit
            ->modalCancelActionLabel('Tutup')
            ->record(function (array $arguments) {
                return Holiday::find($arguments['holiday_id']);
            })
            ->infolist([
                Section::make()->schema([
                    TextEntry::make('name')
                        ->label('Nama Hari Libur')
                        ->weight('bold')
                        ->color('danger'),
                        
                    TextEntry::make('date_range')
                        ->label('Tanggal')
                        ->getStateUsing(function (Holiday $record) {
                            $start = Carbon::parse($record->start_date)->translatedFormat('d F Y');
                            $end = Carbon::parse($record->end_date)->translatedFormat('d F Y');
                            return $start === $end ? $start : "$start - $end";
                        }),
                        
                    TextEntry::make('day_range')
                        ->label('Hari')
                        ->getStateUsing(function (Holiday $record) {
                            $startDay = Carbon::parse($record->start_date)->translatedFormat('l');
                            $endDay = Carbon::parse($record->end_date)->translatedFormat('l');
                            return $startDay === $endDay ? $startDay : "$startDay - $endDay";
                        }),

                    TextEntry::make('description')
                        ->label('Keterangan')
                        ->default('Tidak ada keterangan'),
                ])->columns(1)
            ]);
    }

    public function getViewData(): array
    {
        $year = 2026;

        // Ambil data holiday yang start_date atau end_date-nya ada di tahun 2026
        $holidayRecords = Holiday::whereYear('start_date', $year)
            ->orWhereYear('end_date', $year)
            ->orderBy('start_date', 'asc')
            ->get();

        // Bongkar rentang tanggal menjadi per-hari agar kalender bisa menandai semua harinya
        $holidays = [];
        foreach ($holidayRecords as $record) {
            $start = Carbon::parse($record->start_date);
            $end = Carbon::parse($record->end_date);

            // Looping dari start_date sampai end_date
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $holidays[$date->format('Y-m-d')] = $record->toArray();
            }
        }

        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $firstDay = Carbon::create($year, $m, 1);
            $daysInMonth = $firstDay->daysInMonth;

            $startDow = ($firstDay->dayOfWeek + 6) % 7; // 0=Mon, 6=Sun

            $months[] = [
                'name' => $firstDay->translatedFormat('F'),
                'month' => $m,
                'year' => $year,
                'daysInMonth' => $daysInMonth,
                'startDow' => $startDow,
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
            'holidays' => $holidays, // Array berdasarkan Y-m-d (Untuk Grid kalender)
            'holidayRecords' => $holidayRecords, // Array original (Untuk tabel list di bawah)
        ];
    }
}