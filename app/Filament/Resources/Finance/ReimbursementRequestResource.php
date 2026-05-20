<?php

namespace App\Filament\Resources\Finance;

use App\Filament\Resources\Finance\ReimbursementRequestResource\Pages;
use App\Filament\Resources\Finance\ReimbursementRequestResource\RelationManagers;
use App\Models\Finance\ReimbursementRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use App\Filament\Concerns\BelongsToModule;

class ReimbursementRequestResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'attendance';

    protected static ?string $model = ReimbursementRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Manajemen Presensi';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'reimburse-requests';

    protected static ?string $pluralModelLabel = 'Pengajuan Reimburse';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        $query = static::getModel()::query()
            ->where('status', 'pending');

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
        return 'Pengajuan reimburse pending yang belum di-review';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pengajuan Reimburse')
                ->description('Isi data berikut untuk mengajukan reimburse.')
                ->schema([
                    Forms\Components\DatePicker::make('date')
                        ->label('Tanggal Transaksi')
                        ->required()
                        ->default(now())
                        ->displayFormat('d M Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->prefixIcon('heroicon-o-calendar-days'),

                    Forms\Components\Select::make('type')
                        ->label('Jenis Reimburse')
                        ->required()
                        ->options([
                            'Bensin' => 'Bensin',
                            'Makan' => 'Makan',
                            'Transport' => 'Transport',
                            'Parkir' => 'Parkir',
                            'Hotel' => 'Hotel',
                            'Lainnya' => 'Lainnya (Tulis di keterangan)',
                        ])
                        ->searchable()
                        ->native(false)
                        ->prefixIcon('heroicon-o-tag'),

                    Forms\Components\TextInput::make('amount')
                        ->label('Nominal')
                        ->numeric()
                        ->prefix('IDR')
                        ->required()
                        ->minValue(0),

                    Forms\Components\Textarea::make('description')
                        ->label('Keterangan')
                        ->placeholder('Tuliskan keterangan reimburse...')
                        ->rows(3)
                        ->maxLength(500),

                    Forms\Components\FileUpload::make('receipt')
                        ->label('Upload Bukti')
                        ->image()
                        ->required()
                        ->directory('reimbursements')
                        ->imageEditor()
                        ->previewable()
                        ->maxSize(2048) // 2MB
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/webp'
                        ])
                        ->helperText('Upload bukti seperti struk (2MB)'),
                ])
                ->columns(2)
        ]);
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
                    ->color(function (ReimbursementRequest $record) {
                        $record->withTrashed()->first();
                        if ($record && $record->trashed())
                            return 'danger';
                        return '';
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis Reimburse')
                    ->sortable()
                    ->searchable()
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

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'success' : 'danger')
                    ->sortable()
                    ->weight('semibold')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('approver.full_name')
                    ->label('Disetujui Oleh')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color(function (ReimbursementRequest $record) {
                        $record->withTrashed()->first();
                        if ($record && $record->trashed())
                            return 'danger';
                        return '';
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
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
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('created_at')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
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
                    ->visible(
                        fn(ReimbursementRequest $record) => $record->status === 'pending'
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $employeeId = auth()->user()?->employee?->id;

                        // Employee tidak ditemukan
                        if (!$employeeId) {
                            Notification::make()
                                ->title('Data karyawan tidak ditemukan')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Bukan milik sendiri
                        if ($record->employee_id !== $employeeId) {
                            Notification::make()
                                ->title('Anda tidak bisa membatalkan pengajuan orang lain')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Status tidak valid
                        if (!in_array($record->status, ['pending', 'approved'])) {
                            Notification::make()
                                ->title('Status pengajuan tidak bisa dibatalkan')
                                ->warning()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'cancelled',
                        ]);

                        Notification::make()
                            ->title('Pengajuan berhasil dibatalkan')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn(ReimbursementRequest $record) => $record->status === 'pending'),

                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ForceDeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Pengajuan Reimburse')
                    ->description('Kamu bisa edit pengajuan reimburse ini jika masih berstatus pending atau menunggu persetujuan.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nama Karyawan')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                        TextEntry::make('type')
                            ->label('Jenis Reimburse')
                            ->placeholder('—'),

                        TextEntry::make('date')
                            ->label('Tanggal Transaksi')
                            ->date('D, d M Y')
                            ->placeholder('—'),

                        TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR')
                            ->color('danger')
                            ->weight('semibold')
                            ->placeholder('—'),

                        TextEntry::make('description')
                            ->label('Keterangan')
                            ->placeholder('—'),

                        ImageEntry::make('receipt')
                            ->label('Bukti Transaksi')
                            ->placeholder('—')
                            ->extraImgAttributes([
                                'style' => 'width: 100%; height: auto; object-fit: cover;',
                                'class' => 'w-full rounded-2xl'
                            ]),
                    ]),

                Section::make('Status Persetujuan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
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

                                default => ucwords(
                                    str_replace('_', ' ', $state)
                                ),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReimbursementRequests::route('/'),
            'create' => Pages\CreateReimbursementRequest::route('/create'),
            'view' => Pages\ViewReimbursementRequest::route('/{record}'),
            'edit' => Pages\EditReimbursementRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        $user = auth()->user();

        // Jika bukan super_admin, hanya tampilkan data reimbursenya sendiri
        if (!$user->hasRole('super_admin')) {
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
}
