<?php
namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\LeaveApprovalResource\Pages;
use App\Models\HR\LeaveRequest;
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

class LeaveApprovalResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'leave-approvals';

    protected static ?string $pluralModelLabel = 'Persetujuan Cuti';

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
                        }),

                    Forms\Components\Textarea::make('approval_note')
                        ->label('Catatan Admin')
                        ->nullable(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('leave.leave_type')
                    ->label('Jenis Cuti')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Durasi (Hari)')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucwords($state),
                    }),

                Tables\Columns\TextColumn::make('approver.full_name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->label('Status'),

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
                Tables\Actions\EditAction::make()
                    ->label('Review')
                    ->visible(fn(LeaveRequest $record) => $record->status === 'pending'),
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
                            ->label('Nama Karyawan'),

                        TextEntry::make('leave.leave_type')
                            ->label('Jenis Cuti'),

                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->date('d M Y'),

                        TextEntry::make('end_date')
                            ->label('Tanggal Selesai')
                            ->date('d M Y'),

                        TextEntry::make('total_days')
                            ->label('Durasi (Hari Kerja)')
                            ->numeric(),

                        TextEntry::make('reason')
                            ->label('Alasan Cuti')
                            ->columnSpanFull()
                            ->placeholder('-'),
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
                                'rejected' => 'danger',
                                default => 'secondary',
                            }),

                        TextEntry::make('approver.full_name')
                            ->label('Disetujui Oleh')
                            ->placeholder('-'),

                        TextEntry::make('approved_at')
                            ->label('Waktu Persetujuan')
                            ->dateTime('d M Y H:i')
                            ->visible(fn($record) => $record->approved_at !== null),

                        TextEntry::make('approval_note')
                            ->label('Catatan Admin')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Diajukan Pada')
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
            'view' => Pages\ViewLeaveApproval::route('/{record}'),
            'edit' => Pages\EditLeaveApproval::route('/{record}/edit'),
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
