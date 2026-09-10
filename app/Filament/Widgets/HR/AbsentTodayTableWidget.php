<?php
namespace App\Filament\Widgets\HR;

use App\Filament\Resources\HR\EmployeeResource;
use App\Models\HR\Employee;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class AbsentTodayTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Karyawan Belum Presensi Hari Ini';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->forAttendanceReporting()
                    ->where('status', 'active')
                    ->whereDoesntHave('attendances', function ($query) {
                        $query->whereDate('date', now());
                    })
            )
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(url('/assets/placeholder.jpg')),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Karyawan')
                    ->weight('semibold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('position')
                    ->label('Jabatan'),
                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Jam Kerja')
                    ->formatStateUsing(fn($record) => $record->shift
                        ? $record->shift->name . ' (' . $record->shift->start_time . ' - ' . $record->shift->end_time . ')'
                        : '—'),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Kontak')
                    ->icon('heroicon-o-phone'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat Profil')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Employee $record) => EmployeeResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('Semua karyawan sudah presensi hari ini')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
