<?php

namespace App\Filament\Pages\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Models\HR\Holiday;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;

class HolidayCalendar extends Page
{
    use BelongsToModule;

    protected static ?string $module = 'hr';
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.hr.holiday-calendar';

    protected static ?string $slug = 'hr/calendar';

    protected static string $routePath = 'hr/calendar';

    protected static ?string $navigationGroup = 'Manajemen HR ✅';

    protected static ?string $navigationLabel = 'Kalender Libur';

    protected static ?string $title = 'Kalender Hari Libur 2026';

    public function viewHolidayAction(): Action
    {
        return Action::make('viewHoliday')
            ->modalHeading('Detail Hari Libur')
            ->modalSubmitAction(false) // Sembunyikan tombol submit (karena hanya view)
            ->modalCancelActionLabel('Tutup')
            ->record(function (array $arguments) {
                // Ambil data holiday berdasarkan ID yang dikirim dari view
                return Holiday::find($arguments['holiday_id']);
            })
            ->infolist([
                Section::make()->schema([
                    TextEntry::make('name')
                        ->label('Nama Hari Libur')
                        ->weight('bold')
                        ->color('danger'),
                    TextEntry::make('date')
                        ->label('Tanggal')
                        ->date('d F Y'),
                    TextEntry::make('description')
                        ->label('Keterangan')
                        ->default('Tidak ada keterangan'),
                ])->columns(1)
            ]);
    }

    public function getViewData(): array
    {
        $year = 2026;

        // Ambil semua holiday tahun 2026, index by date string
        $holidays = Holiday::whereYear('date', $year)
            ->get()
            ->keyBy(fn($h) => $h->date->format('Y-m-d'))
            ->toArray();

        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $firstDay = \Carbon\Carbon::create($year, $m, 1);
            $daysInMonth = $firstDay->daysInMonth;

            // 0=Sun,1=Mon,...,6=Sat — geser agar Senin jadi awal minggu
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
            'holidays' => $holidays,
        ];
    }
}
