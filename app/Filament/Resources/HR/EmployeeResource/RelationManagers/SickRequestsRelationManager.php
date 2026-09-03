<?php
namespace App\Filament\Resources\HR\EmployeeResource\RelationManagers;

use App\Models\HR\SickRequest;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SickRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'sickRequests';

    protected static ?string $recordTitleAttribute = 'start_date';

    protected static ?string $title = 'Riwayat Sakit';

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
                    ->color(function (SickRequest $record) {
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

                        default => ucwords(
                            str_replace('_', ' ', $state)
                        ),
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
                    ->color(function (SickRequest $record) {
                        $record->withTrashed()->first();
                        if ($record && $record->trashed()) {
                            return 'danger';
                        }

                        return '';
                    })
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
                Tables\Actions\ViewAction::make(),
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
                Section::make('Informasi Pengajuan Sakit')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nama Karyawan')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),
                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->date('D, d M Y')
                            ->placeholder('—'),
                        TextEntry::make('end_date')
                            ->label('Tanggal Selesai')
                            ->date('D, d M Y')
                            ->placeholder('—'),
                        TextEntry::make('total_days')
                            ->label('Durasi (Hari)')
                            ->numeric()
                            ->formatStateUsing(fn($state) => $state . ' Hari')
                            ->placeholder('—'),
                        TextEntry::make('reason')
                            ->label('Keterangan Sakit')
                            ->placeholder('—'),
                        ImageEntry::make('sick_proof')
                            ->label('Bukti/Surat Dokter')
                            ->placeholder('—')
                            ->extraImgAttributes([
                                'style' => 'width: 100%; height: auto; object-fit: cover;',
                                'class' => 'w-full rounded-2xl',
                            ]),
                    ]),
                Section::make('Status Persetujuan')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state) => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',

                                default => 'danger',
                            })
                            ->formatStateUsing(function (string $state): string {
                                return match ($state) {
                                    'pending' => 'Menunggu',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                    'cancelled' => 'Dibatalkan',
                                    'expired' => 'Kadaluwarsa',

                                    default => ucwords(
                                        str_replace('_', ' ', $state)
                                    ),
                                };
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
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Pengelolaan Data')
                    ->columns(['default' => 1, 'md' => 2])
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
