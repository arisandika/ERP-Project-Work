<?php
namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\LeaveApprovalResource\Pages;
use App\Models\HR\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class LeaveApprovalResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'hr/leave-approvals';

    protected static ?string $pluralModelLabel = 'Persetujuan Cuti';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pengajuan cuti pending yang perlu di-review';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pengajuan')
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Nama Karyawan')
                        ->relationship('employee', 'full_name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->prefixIcon('heroicon-o-user'),

                    Forms\Components\Select::make('leave_id')
                        ->label('Jenis Cuti')
                        ->relationship('leave', 'leave_type')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->prefixIcon('heroicon-o-briefcase'),

                    Forms\Components\TextInput::make('start_date')
                        ->label('Tanggal Mulai')
                        ->disabled()
                        ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d M Y'))
                        ->prefixIcon('heroicon-o-calendar'),

                    Forms\Components\TextInput::make('end_date')
                        ->label('Tanggal Selesai')
                        ->disabled()
                        ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d M Y'))
                        ->prefixIcon('heroicon-o-calendar'),

                    Forms\Components\TextInput::make('total_days')
                        ->label('Durasi Cuti (Hari Kerja)')
                        ->disabled()
                        ->prefixIcon('heroicon-o-clock'),

                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Cuti')
                        ->disabled(),

                    Forms\Components\Placeholder::make('leave_proof_preview')
                        ->label('Bukti Cuti/Sakit')
                        ->content(
                            fn($record) => $record?->leave_proof
                            ? new HtmlString('<img src="/storage/' . $record->leave_proof . '" class="w-full rounded-2xl">')
                            : 'Tidak menyertakan bukti cuti'
                        )
                ])
                ->columns(2),

            Forms\Components\Section::make('Persetujuan')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status Persetujuan')
                        ->options([
                            'pending' => 'Menunggu Persetujuan',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === 'approved') {
                                $set('approved_at', now());
                                $set('approved_by', auth()->user()->employee->id ?? null);
                            }
                        })
                        ->native(false),

                    Forms\Components\Textarea::make('approval_note')
                        ->label('Catatan Admin')
                        ->nullable(),
                ])
                ->columns(2),
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
                    ->color(function (LeaveRequest $record) {
                        $record->withTrashed()->first();
                        if ($record && $record->trashed())
                            return 'danger';
                        return '';
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('leave.leave_type')
                    ->label('Jenis Cuti')
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
                    ->color(function (LeaveRequest $record) {
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
                Tables\Actions\EditAction::make()
                    ->label('Review')
                    ->color('warning')
                    ->visible(fn(LeaveRequest $record) => $record->status === 'pending'),

                Tables\Actions\ViewAction::make(),
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
                Section::make('Informasi Pengajuan Cuti')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nama Karyawan')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                        TextEntry::make('leave.leave_type')
                            ->label('Jenis Cuti')
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
                            ->label('Durasi (Hari Kerja)')
                            ->numeric()
                            ->formatStateUsing(fn($state) => $state . ' Hari')
                            ->placeholder('—'),

                        TextEntry::make('reason')
                            ->label('Alasan Cuti')
                            ->placeholder('—'),

                        ImageEntry::make('leave_proof')
                            ->label('Bukti Cuti/Sakit')
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
            'index' => Pages\ListLeaveApprovals::route('/'),
            'edit' => Pages\EditLeaveApproval::route('/{record}/edit'),
            'view' => Pages\ViewLeaveApproval::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
