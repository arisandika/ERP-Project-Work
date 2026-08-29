<?php
namespace App\Filament\Resources\HR\EmployeeResource\RelationManagers;

use App\Exports\AttendancesExport;
use App\Infolists\Components\AttendanceMapEntry;
use App\Models\HR\Attendance;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $recordTitleAttribute = 'date';

    protected static ?string $title = 'Riwayat Presensi';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function exportAttendances(array $data): void
    {
        $selectedColumns = $data['columns'] ?? [];
        $startDate       = $data['start_date'] ?? null;
        $endDate         = $data['end_date'] ?? null;

        if (empty($selectedColumns)) {
            Notification::make()
                ->title('Export Gagal')
                ->body('Pilih minimal satu kolom untuk diekspor')
                ->danger()
                ->send();
            return;
        }

        // Scope ke employee yang sedang dibuka di relation manager
        $query = Attendance::with(['employee', 'shift'])
            ->where('employee_id', $this->getOwnerRecord()->id)
            ->orderBy('date', 'desc');

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $attendances = $query->get();

        if ($attendances->isEmpty()) {
            Notification::make()
                ->title('Export Gagal')
                ->body('Tidak ada data presensi pada rentang tanggal tersebut')
                ->warning()
                ->send();
            return;
        }

        try {
            $employeeName = str($this->getOwnerRecord()->full_name)
                ->slug('_')
                ->toString();

            $fileName = 'presensi_' . $employeeName . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            $export   = new AttendancesExport($attendances, $selectedColumns);

            Excel::store($export, 'exports/' . $fileName, 'public');

            $downloadUrl = asset('storage/exports/' . $fileName);

            $this->js("
                const a = document.createElement('a');
                a.href = '{$downloadUrl}';
                a.download = '{$fileName}';
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            ");

            Notification::make()
                ->title('Export Berhasil')
                ->body('File Excel presensi sedang diunduh')
                ->success()
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('Export Gagal')
                ->body('Terjadi kesalahan saat export: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color(function (Attendance $record) {
                        $record->withTrashed()->first();
                        if ($record && $record->trashed()) {
                            return 'danger';
                        }

                        return '';
                    })
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'hadir'       => 'success',
                        'terlambat'   => 'warning',
                        'absen'       => 'danger',
                        'izin'        => 'yellow',
                        'cuti'        => 'info',
                        'sakit'       => 'danger', // <-- tambahkan
                        'libur'       => 'gray',   // <-- tambahkan (biar konsisten, opsional)
                        'no_checkout' => 'orange', // <-- tambahkan (opsional)

                        default       => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'belum_presensi' => 'Belum Presensi',
                        'hadir'          => 'Hadir',
                        'terlambat'      => 'Terlambat',
                        'absen'          => 'Absen',
                        'cuti'           => 'Cuti',
                        'izin'           => 'Izin',
                        'sakit'          => 'Sakit', // <-- tambahkan
                        'libur'          => 'Libur', // <-- tambahkan
                        'no_checkout'    => 'Tidak Presensi Keluar',

                        default          => ucwords(
                            str_replace('_', ' ', $state)
                        ),
                    })
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Presensi')
                    ->options([
                        'belum_presensi' => 'Belum Presensi',
                        'hadir'          => 'Hadir',
                        'terlambat'      => 'Terlambat',
                        'absen'          => 'Absen',
                        'cuti'           => 'Cuti',
                        'izin'           => 'Izin',
                        'sakit'          => 'Sakit', // <-- tambahkan
                        'libur'          => 'Libur', // <-- tambahkan
                        'no_checkout'    => 'Tidak Presensi Keluar',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Dari ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Sampai ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Status')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Lihat Riwayat Presensi'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('export_attendances')
                    ->label('Export Excel')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->form([
                        FormSection::make('Filter Tanggal')
                            ->description('Pilih rentang waktu presensi yang ingin diekspor')
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Dari Tanggal')
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->required(),
                                DatePicker::make('end_date')
                                    ->label('Sampai Tanggal')
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->required(),
                            ])->columns(2),

                        FormSection::make('Pilih Kolom')
                            ->description('Pilih kolom yang ingin disertakan dalam file Excel')
                            ->schema([
                                CheckboxList::make('columns')
                                    ->label('Kolom')
                                    ->options([
                                        'employee_name' => 'Nama Karyawan',
                                        'date'          => 'Tanggal',
                                        'shift'         => 'Shift',
                                        'note'          => 'Catatan',
                                        'clock_in'      => 'Jam Masuk',
                                        'latitude_in'   => 'Latitude Masuk',
                                        'longitude_in'  => 'Longitude Masuk',
                                        'clock_out'     => 'Jam Keluar',
                                        'latitude_out'  => 'Latitude Keluar',
                                        'longitude_out' => 'Longitude Keluar',
                                        'status'        => 'Status Kehadiran',
                                        'created_at'    => 'Dibuat Pada',
                                    ])
                                    ->default([
                                        'employee_name',
                                        'date',
                                        'shift',
                                        'clock_in',
                                        'clock_out',
                                        'status',
                                    ])
                                    ->required()
                                    ->minItems(1)
                                    ->columns(2)
                                    ->gridDirection('row'),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $this->exportAttendances($data);
                    }),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Presensi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nama Karyawan')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                        TextEntry::make('date')
                            ->label('Tanggal')
                            ->date('D, d M Y')
                            ->placeholder('—'),

                        TextEntry::make('clock_in')
                            ->label('Jam Masuk')
                            ->time('H:i')
                            ->placeholder('—'),

                        TextEntry::make('clock_out')
                            ->label('Jam Keluar')
                            ->time('H:i')
                            ->placeholder('—'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'hadir'     => 'success',
                                'terlambat' => 'warning',
                                'absen'     => 'danger',
                                'izin'      => 'yellow',
                                'cuti'      => 'info',
                                'sakit'     => 'yellow', // <-- tambahkan
                                'libur'     => 'gray',   // <-- tambahkan

                                default     => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                            ->placeholder('—'),

                        TextEntry::make('note')
                            ->label('Catatan')
                            ->placeholder('—'),
                    ]),

                Section::make('Lokasi Presensi')
                    ->schema([
                        AttendanceMapEntry::make('map')
                            ->label('')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d M Y H:i')
                            ->visible(fn($record) => $record->trashed()),
                    ]),
            ]);
    }
}
