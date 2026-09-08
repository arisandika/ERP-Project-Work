<?php
namespace App\Filament\Resources\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\HR\OvertimeMonitoringResource\Pages;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class OvertimeMonitoringResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'hr';

    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'hr/overtime-monitoring';

    protected static ?string $pluralModelLabel = 'Monitoring Overtime';

    protected static ?string $modelLabel = 'Overtime';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('employee.department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Shift')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('D, d M Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('clock_in')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('clock_out')
                    ->label('Jam Keluar')
                    ->time('H:i')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_duration')
                    ->label('Durasi Lembur')
                    ->getStateUsing(fn(Attendance $record) => $record->overtime_duration)
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-clock')
                    ->sortable(false)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('overtime_minutes')
                    ->label('Menit Lembur')
                    ->getStateUsing(fn(Attendance $record) => $record->overtime_minutes . ' mnt')
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period')
                    ->label('Periode')
                    ->options([
                        'today' => 'Hari Ini',
                        'this_week' => 'Minggu Ini',
                        'this_month' => 'Bulan Ini',
                        'last_month' => 'Bulan Lalu',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'today' => $query->whereDate('date', Carbon::today()),
                            'this_week' => $query->whereBetween('date', [
                                Carbon::now()->startOfWeek()->toDateString(),
                                Carbon::now()->endOfWeek()->toDateString(),
                            ]),
                            'this_month' => $query->whereBetween('date', [
                                Carbon::now()->startOfMonth()->toDateString(),
                                Carbon::now()->endOfMonth()->toDateString(),
                            ]),
                            'last_month' => $query->whereBetween('date', [
                                Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                                Carbon::now()->subMonth()->endOfMonth()->toDateString(),
                            ]),
                            default => $query,
                        };
                    }),
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Karyawan')
                    ->options(fn() => Employee::where('status', 'active')->pluck('full_name', 'id'))
                    ->searchable()
                    ->native(false),
                Tables\Filters\SelectFilter::make('department')
                    ->label('Departemen')
                    ->relationship('employee.department', 'name')
                    ->native(false),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date) => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date) => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari ' . Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai ' . Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detail Lembur'),
            ])
            ->bulkActions([])
            ->defaultSort('date', 'desc')
            ->poll('60s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Data Karyawan')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextEntry::make('employee.full_name')
                        ->label('Nama Karyawan')
                        ->weight('semibold')
                        ->icon('heroicon-o-user'),
                    TextEntry::make('employee.department.name')
                        ->label('Departemen')
                        ->badge()
                        ->color('gray'),
                    TextEntry::make('shift.name')
                        ->label('Shift')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('date')
                        ->label('Tanggal Presensi')
                        ->date('D, d M Y'),
                ]),
            Section::make('Detail Jam Kerja & Lembur')
                ->columns(['default' => 2, 'md' => 4])
                ->schema([
                    TextEntry::make('clock_in')
                        ->label('Jam Masuk')
                        ->time('H:i')
                        ->placeholder('—'),
                    TextEntry::make('shift.start_time')
                        ->label('Mulai Shift')
                        ->time('H:i')
                        ->placeholder('—'),
                    TextEntry::make('clock_out')
                        ->label('Jam Keluar')
                        ->time('H:i')
                        ->placeholder('—'),
                    TextEntry::make('overtime_duration')
                        ->label('Durasi Lembur')
                        ->getStateUsing(fn(Attendance $record) => $record->overtime_duration ?? '—')
                        ->badge()
                        ->color(fn(Attendance $record) => $record->overtime_minutes > 0 ? 'warning' : 'gray')
                        ->icon('heroicon-m-clock')
                        ->columnSpan(['default' => 'full', 'sm' => 2]),
                    TextEntry::make('overtime_minutes')
                        ->label('Total Menit Lembur')
                        ->getStateUsing(fn(Attendance $record) => $record->overtime_minutes . ' menit')
                        ->columnSpan(['default' => 'full', 'sm' => 2]),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOvertimeMonitoring::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->whereNotNull('shift_id')
            ->join('nx_shifts', 'nx_shifts.id', '=', 'nx_attendances.shift_id')
            ->with(['shift', 'employee.department'])
            ->whereHas('shift')
            ->whereRaw('
                TIMESTAMPDIFF(
                    MINUTE,
                    CASE
                        WHEN TIME_TO_SEC(nx_shifts.end_time) <= TIME_TO_SEC(nx_shifts.start_time)
                        THEN DATE_ADD(TIMESTAMP(DATE(clock_in), nx_shifts.end_time), INTERVAL 1 DAY)
                        ELSE TIMESTAMP(DATE(clock_in), nx_shifts.end_time)
                    END,
                    clock_out
                ) > 30
                AND TIMESTAMPDIFF(
                    MINUTE,
                    CASE
                        WHEN TIME_TO_SEC(nx_shifts.end_time) <= TIME_TO_SEC(nx_shifts.start_time)
                        THEN DATE_ADD(TIMESTAMP(DATE(clock_in), nx_shifts.end_time), INTERVAL 1 DAY)
                        ELSE TIMESTAMP(DATE(clock_in), nx_shifts.end_time)
                    END,
                    clock_out
                ) <= 720
            ');
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
