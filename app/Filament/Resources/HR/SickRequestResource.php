<?php
namespace App\Filament\Resources\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\HR\SickRequestResource\Pages;
use App\Models\HR\SickRequest;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class SickRequestResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'attendance';

    protected static ?string $model = SickRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Manajemen Presensi';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'sick-requests';

    protected static ?string $modelLabel = 'Pengajuan Sakit';

    protected static ?string $pluralModelLabel = 'Pengajuan Sakit';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        $query = static::getModel()::query()->where('status', 'pending');

        if (!$user->hasRole('super_admin')) {
            $employee = $user->employee;

            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                return '0';
            }
        }

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pengajuan sakit pending yang belum di-review';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Pengajuan Sakit')
                    ->description('Isi data berikut untuk mengajukan izin sakit.')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->closeOnDateSelection()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $get) {
                                self::calcTotalDays($get('start_date'), $get('end_date'), $set);
                            }),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->closeOnDateSelection()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, $get) {
                                self::calcTotalDays($get('start_date'), $state, $set);
                            }),
                        Forms\Components\TextInput::make('total_days')
                            ->label('Durasi Sakit (Hari)')
                            ->prefixIcon('heroicon-o-clock')
                            ->disabled()
                            ->dehydrated(false)
                            ->reactive(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Keterangan Sakit')
                            ->placeholder('Tuliskan keterangan/keluhan sakit...')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('sick_proof')
                            ->label('Bukti/Surat Dokter')
                            ->image()
                            ->directory('sick-proofs')
                            ->imageEditor()
                            ->previewable()
                            ->maxSize(2048)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/jpg',
                                'image/webp',
                            ])
                            ->helperText('Upload surat dokter atau dokumen pendukung (opsional)')
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 122, 'md' => 2]),
            ]);
    }

    private static function calcTotalDays($start, $end, callable $set): void
    {
        if ($start && $end) {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);

            if ($startDate->gt($endDate)) {
                $set('total_days', null);
                return;
            }

            // Sakit dihitung hari kalender (bukan hanya hari kerja)
            $set('total_days', $startDate->diffInDays($endDate) + 1);
        } else {
            $set('total_days', null);
        }
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
                    ->color(function (SickRequest $record) {
                        $record->withTrashed()->first();
                        return ($record && $record->trashed()) ? 'danger' : '';
                    })
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                        'expired' => 'Kadaluwarsa',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('total_days')
                    ->label('Durasi (Hari)')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state . ' Hari')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('approver.full_name')
                    ->label('Disetujui Oleh')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->placeholder('—'),
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
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                        'expired' => 'Kadaluwarsa',
                    ])
                    ->native(false),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn(Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn(Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date));
                    }),
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Status')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(SickRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $employeeId = auth()->user()?->employee?->id;

                        if (!$employeeId) {
                            Notification::make()->title('Data karyawan tidak ditemukan')->danger()->send();
                            return;
                        }

                        if ($record->employee_id !== $employeeId) {
                            Notification::make()->title('Anda tidak bisa membatalkan pengajuan orang lain')->danger()->send();
                            return;
                        }

                        if (!in_array($record->status, ['pending', 'approved'])) {
                            Notification::make()->title('Status pengajuan tidak bisa dibatalkan')->warning()->send();
                            return;
                        }

                        $record->update(['status' => 'cancelled']);

                        Notification::make()->title('Pengajuan berhasil dibatalkan')->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(SickRequest $record) => $record->status === 'pending'),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Pengajuan Sakit')
                    ->description('Kamu bisa edit pengajuan sakit ini jika masih berstatus pending.')
                    ->columns(['default' => 122, 'md' => 2])
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nama Karyawan')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),
                        TextEntry::make('total_days')
                            ->label('Durasi (Hari)')
                            ->formatStateUsing(fn($state) => $state . ' Hari')
                            ->placeholder('—'),
                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->date('D, d M Y')
                            ->placeholder('—'),
                        TextEntry::make('end_date')
                            ->label('Tanggal Selesai')
                            ->date('D, d M Y')
                            ->placeholder('—'),
                        TextEntry::make('reason')
                            ->label('Keterangan Sakit')
                            ->columnSpanFull()
                            ->placeholder('—'),
                        ImageEntry::make('sick_proof')
                            ->label('Bukti/Surat Dokter')
                            ->columnSpanFull()
                            ->placeholder('—')
                            ->extraImgAttributes([
                                'style' => 'width: 100%; height: auto; object-fit: cover;',
                                'class' => 'w-full rounded-2xl',
                            ]),
                    ]),
                Section::make('Status Persetujuan')
                    ->columns(['default' => 122, 'md' => 2])
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state) => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                default => 'danger',
                            })
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                'cancelled' => 'Dibatalkan',
                                'expired' => 'Kadaluwarsa',
                                default => ucwords(str_replace('_', ' ', $state)),
                            })
                            ->placeholder('—'),
                        TextEntry::make('approver.full_name')
                            ->label('Disetujui Oleh')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),
                        TextEntry::make('approved_at')
                            ->label('Waktu Persetujuan')
                            ->dateTime('d M Y H:i')
                            ->visible(fn($record) => $record->approved_at !== null)
                            ->placeholder('—'),
                        TextEntry::make('approval_note')
                            ->label('Catatan Admin')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),
                Section::make('Pengelolaan Data')
                    ->columns(['default' => 122, 'md' => 2])
                    ->schema([
                        TextEntry::make('created_at')->label('Dibuat Pada')->dateTime('d M Y H:i'),
                        TextEntry::make('updated_at')->label('Diperbarui Pada')->dateTime('d M Y H:i'),
                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d M Y H:i')
                            ->visible(fn($record) => $record->trashed()),
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
            'index' => Pages\ListSickRequests::route('/'),
            'create' => Pages\CreateSickRequest::route('/create'),
            'view' => Pages\ViewSickRequest::route('/{record}'),
            'edit' => Pages\EditSickRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        $user = auth()->user();

        if (!$user->hasRole('super_admin')) {
            $employee = $user->employee;
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }
}
