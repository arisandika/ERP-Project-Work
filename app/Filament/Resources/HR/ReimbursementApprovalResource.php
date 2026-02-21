<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\ReimbursementApprovalResource\Pages;
use App\Filament\Resources\HR\ReimbursementApprovalResource\RelationManagers;
use App\Models\HR\ReimbursementRequest;
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

class ReimbursementApprovalResource extends Resource
{
    protected static ?string $model = ReimbursementRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'hr/reimburse-approvals';

    protected static ?string $pluralModelLabel = 'Persetujuan Reimburse';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pengajuan')
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Nama Karyawan')
                        ->relationship('employee', 'full_name')
                        ->disabled()
                        ->searchable()
                        ->preload()
                        ->prefixIcon('heroicon-o-user'),

                    Forms\Components\DatePicker::make('date')
                        ->label('Tanggal Transaksi')
                        ->disabled()
                        ->required()
                        ->default(now())
                        ->displayFormat('d M Y')
                        ->native(false)
                        ->prefixIcon('heroicon-o-calendar-days'),

                    Forms\Components\TextInput::make('type')
                        ->label('Jenis Reimburse')
                        ->disabled()
                        ->required()
                        ->placeholder('Contoh: Bensin, Makan, Transport, Parkir')
                        ->prefixIcon('heroicon-o-tag'),

                    Forms\Components\TextInput::make('amount')
                        ->label('Nominal')
                        ->disabled()
                        ->numeric()
                        ->required()
                        ->prefix('IDR'),

                    Forms\Components\Textarea::make('description')
                        ->label('Keterangan')
                        ->disabled()
                        ->placeholder('Tuliskan keterangan reimburse...')
                        ->rows(3)
                        ->maxLength(500),

                    Forms\Components\FileUpload::make('receipt')
                        ->label('Upload Bukti')
                        ->disabled()
                        ->required()
                        ->image()
                        ->directory('reimbursements')
                        ->imageEditor(),
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
                                $set(
                                    'approved_by',
                                    auth()->user()->employee->id ?? null
                                );
                            }

                        }),
                ])
                ->columns(2)

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

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Review')
                    ->visible(fn(ReimbursementRequest $record) => $record->status === 'pending'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {

        return $infolist

            ->schema([
                Section::make('Informasi Pengajuan Reimburse')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nama Karyawan'),

                        TextEntry::make('date')
                            ->label('Tanggal Transaksi')
                            ->date('d M Y'),

                        TextEntry::make('type')
                            ->label('Jenis Reimburse')
                            ->badge(),

                        TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR'),

                        TextEntry::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->placeholder('-'),

                        ImageEntry::make('receipt')
                            ->label('Bukti Transaksi')
                            ->columnSpanFull()
                            ->width('500px')
                            ->height('auto'),
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
            'index' => Pages\ListReimbursementApprovals::route('/'),
            'create' => Pages\CreateReimbursementApproval::route('/create'),
            'view' => Pages\ViewReimbursementApproval::route('/{record}'),
            'edit' => Pages\EditReimbursementApproval::route('/{record}/edit'),
        ];
    }
}
