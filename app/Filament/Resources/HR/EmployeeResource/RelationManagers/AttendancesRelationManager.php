<?php
namespace App\Filament\Resources\HR\EmployeeResource\RelationManagers;

use App\Models\HR\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $recordTitleAttribute = 'date';

    protected static ?string $title = 'Riwayat Presensi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
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
                        if ($record && $record->trashed())
                            return 'danger';
                        return '';
                    })
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'absen' => 'danger',
                        'izin' => 'yellow',
                        'cuti' => 'info',

                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'belum_presensi' => 'Belum Presensi',
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'absen' => 'Absen',
                        'cuti' => 'Cuti',
                        'izin' => 'Izin',
                        'no_checkout' => 'Tidak Presensi Keluar',

                        default => ucwords(
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
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'absen' => 'Absen',
                        'cuti' => 'Cuti',
                        'izin' => 'Izin',
                        'no_checkout' => 'Tidak Presensi Keluar',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
                            ->required()
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
                //
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
                                'hadir' => 'success',
                                'terlambat' => 'warning',
                                'absen' => 'danger',
                                'izin' => 'yellow',
                                'cuti' => 'info',

                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                            ->placeholder('—'),

                        TextEntry::make('note')
                            ->label('Catatan')
                            ->placeholder('—'),

                        ImageEntry::make('face_snapshot_in')
                            ->label('Foto Presensi Masuk')
                            ->placeholder('—')
                            ->extraImgAttributes(['style' => 'width: 100%; height: auto; object-fit: cover;']),

                        ImageEntry::make('face_snapshot_out')
                            ->label('Foto Presensi Keluar')
                            ->placeholder('—')
                            ->extraImgAttributes(['style' => 'width: 100%; height: auto; object-fit: cover;']),
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
