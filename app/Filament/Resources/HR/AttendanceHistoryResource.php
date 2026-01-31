<?php
namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\AttendanceHistoryResource\Pages;
use App\Models\HR\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class AttendanceHistoryResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Manajemen Presensi';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'hr/attendance-history';

    protected static ?string $pluralModelLabel = 'Riwayat Presensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Nama Karyawan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Shift')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d F Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('clock_in')
                    ->label('Jam Masuk')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('clock_out')
                    ->label('Jam Keluar')
                    ->time('H:i'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'hadir',
                        'warning' => 'izin',
                        'info'    => 'sakit',
                        'danger'  => 'alfa',
                    ])
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder =>
                                $query->whereDate('date', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder =>
                                $query->whereDate('date', '<=', $date)
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

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Presensi')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin'  => 'Izin',
                        'sakit' => 'Sakit',
                        'alfa'  => 'Tanpa Keterangan',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Presensi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')->label('Nama Karyawan'),
                        TextEntry::make('shift.name')->label('Shift'),
                        TextEntry::make('date')->label('Tanggal')->date('d F Y'),
                        TextEntry::make('clock_in')->label('Jam Masuk')->time('H:i'),
                        TextEntry::make('clock_out')->label('Jam Keluar')->time('H:i')->placeholder('-'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('note')->label('Catatan')->columnSpanFull()
                        ->placeholder('-'),
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
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceHistories::route('/'),
            'view'  => Pages\ViewAttendanceHistory::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        $user = auth()->user();

        // Jika bukan super_admin, hanya tampilkan data cutinya sendiri
        if (! $user->hasRole('super_admin')) {
            $employee = $user->employee;
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                // Jika user belum punya relasi employee, kosongkan query
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
