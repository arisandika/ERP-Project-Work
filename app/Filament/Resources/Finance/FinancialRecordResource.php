<?php

namespace App\Filament\Resources\Finance;

use App\Filament\Resources\Finance\FinancialRecordResource\Pages;
use App\Models\Finance\FinancialRecord;
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

class FinancialRecordResource extends Resource
{
    protected static ?string $model = FinancialRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Manajemen Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'finance/financial-records';

    protected static ?string $pluralModelLabel = 'Catatan Operasional';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Input Transaksi')
                    ->description('Catat pengeluaran kecil atau pemasukan non-penjualan.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('transaction_date')
                                    ->label('Tanggal Transaksi')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d M Y')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->prefixIcon('heroicon-o-calendar-days'),

                                Forms\Components\Select::make('type')
                                    ->label('Tipe Transaksi')
                                    ->required()
                                    ->options([
                                        'pemasukan' => 'Pemasukan (Uang Masuk)',
                                        'pengeluaran' => 'Pengeluaran (Uang Keluar)',
                                    ])
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-tag'),

                                Forms\Components\Select::make('category')
                                    ->label('Jenis Transaksi')
                                    ->required()
                                    ->options([
                                        'Bensin' => 'Bensin',
                                        'Makan' => 'Makan',
                                        'Transport' => 'Transport',
                                        'Parkir' => 'Parkir',
                                        'Hotel' => 'Hotel',
                                        'Listrik' => 'Listrik',
                                        'Air' => 'Air',
                                        'Lainnya' => 'Lainnya (Tulis di keterangan)',
                                    ])
                                    ->searchable()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-tag'),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Nominal')
                                    ->numeric()
                                    ->prefix('IDR')
                                    ->required(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Keterangan')
                                    ->placeholder('Tuliskan keterangan transaksi...')
                                    ->rows(3)
                                    ->maxLength(500),

                                Forms\Components\FileUpload::make('receipt')
                                    ->label('Upload Bukti')
                                    ->image()
                                    // ->required()
                                    ->directory('financials')
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
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator')
                    ->label('Dibuat Oleh')
                    ->state(
                        fn($record) =>
                        $record->reimbursement?->employee?->full_name
                        ?? $record->employee?->full_name
                    )
                    ->sortable()
                    ->weight('semibold')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Transaksi')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',

                        default => ucwords(
                            str_replace('_', ' ', $state)
                        ),
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->sortable()
                    ->state(
                        fn($record) =>
                        $record->reimbursement ? 'Reimburse' : 'Manual'
                    )
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('transaction_category')
                    ->label('Jenis Transaksi')
                    ->state(
                        fn($record) =>
                        $record->reimbursement?->type
                        ?? $record->category
                    )
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->state(
                        fn($record) =>
                        $record->reimbursement?->description
                        ?? $record->description
                    )
                    ->searchable()
                    ->placeholder('—')
                    ->limit(30),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->state(
                        fn($record) =>
                        $record->reimbursement?->date
                        ?? $record->transaction_date
                    )
                    ->date('D, d M Y')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->color(fn($record) => match ($record->type) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->weight('semibold')
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Transaksi')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Sumber Data')
                    ->options([
                        'manual' => 'Manual',
                        'reimburse' => 'Reimburse',
                    ])
                    ->query(function ($query, $data) {

                        return match ($data['value'] ?? null) {

                            'manual'
                            => $query->whereNull('reimburse_id'),

                            'reimburse'
                            => $query->whereNotNull('reimburse_id'),

                            default
                            => $query,
                        };
                    })
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
                // Tables\Actions\EditAction::make(),
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
                Section::make('Informasi Transaksi Keuangan')
                    ->description('Detail transaksi pemasukan atau pengeluaran operasional.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Dibuat Oleh')
                            ->color('primary')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                        TextEntry::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->date('D, d M Y')
                            ->placeholder('—'),

                        TextEntry::make('type')
                            ->label('Jenis Transaksi')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'pemasukan' => 'success',
                                'pengeluaran' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(
                                fn($state) => match ($state) {
                                    'pemasukan' => 'Pemasukan',
                                    'pengeluaran' => 'Pengeluaran',
                                    default => ucwords(str_replace('_', ' ', $state)),
                                }
                            )
                            ->placeholder('—'),

                        TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR')
                            ->weight('semibold')
                            ->color(fn($record) => match (strtolower($record->type)) {
                                'pemasukan' => 'success',
                                'pengeluaran' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('—'),

                        TextEntry::make('category')
                            ->label('Kategori')
                            ->placeholder('—'),

                        TextEntry::make('description')
                            ->label('Keterangan')
                            ->placeholder('—'),

                    ]),

                Section::make('Informasi Reimburse')
                    ->description('Transaksi ini berasal dari pengajuan reimburse.')
                    ->columns(2)
                    ->visible(fn($record) => filled($record->reimburse_id))
                    ->schema([

                        TextEntry::make('reimbursement.employee.full_name')
                            ->label('Pemilik Reimburse')
                            ->color('primary')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                        TextEntry::make('reimbursement.date')
                            ->label('Tanggal Reimburse')
                            ->date('D, d M Y')
                            ->placeholder('—'),

                        TextEntry::make('reimbursement.amount')
                            ->label('Nominal Reimburse')
                            ->money('IDR')
                            ->color('danger')
                            ->weight('semibold')
                            ->placeholder('—'),

                        TextEntry::make('reimbursement.status')
                            ->label('Status')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'cancelled' => 'gray',
                                default => 'danger',
                            })
                            ->formatStateUsing(
                                fn($state) => match ($state) {
                                    'pending' => 'Menunggu',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                    'cancelled' => 'Dibatalkan',
                                    default => ucwords(str_replace('_', ' ', $state)),
                                }
                            )
                            ->placeholder('—'),

                        ImageEntry::make('reimbursement.receipt')
                            ->label('Bukti Reimburse')
                            ->placeholder('—')
                            ->extraImgAttributes(['style' => 'width: 100%; height: auto; object-fit: cover;']),

                        TextEntry::make('reimbursement.approver.full_name')
                            ->label('Disetujui Oleh')
                            ->color('primary')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                    ]),

                Section::make('Bukti Transaksi')
                    ->description('Transaksi ini dibuat langsung di catatan operasional.')
                    ->visible(fn($record) => empty($record->reimburse_id))
                    ->schema([

                        ImageEntry::make('receipt')
                            ->label('Bukti Transaksi')
                            ->extraImgAttributes([
                                'style' => 'max-width:400px;height:auto;object-fit:cover;',
                            ])
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialRecords::route('/'),
            'create' => Pages\CreateFinancialRecord::route('/create'),
            'view' => Pages\ViewFinancialRecord::route('/{record}'),
            // 'edit' => Pages\EditFinancialRecord::route('/{record}/edit'),
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
