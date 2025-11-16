<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\LeaveApprovalResource\Pages;
use App\Filament\Resources\HR\LeaveApprovalResource\RelationManagers;
use App\Models\HR\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
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
                    ->dateTime('d F Y H:i')
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

                Tables\Filters\Filter::make('tanggal_pengajuan')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(
                        fn(Builder $query, array $data): Builder =>
                        $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('start_date', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('end_date', '<=', $date))
                    ),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until')
                            ->displayFormat('d/m/Y')
                            ->native(false),
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
