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
use App\Models\Procurement\PurchaseOrder;

class FinancialRecordResource extends Resource
{
    protected static ?string $model = FinancialRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Manajemen Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'finance/financial-records';

    protected static ?string $pluralModelLabel = 'Catatan Operasional & Jurnal'; // Update Label

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Input Transaksi')
                    ->description('Catat pengeluaran kecil atau pemasukan non-penjualan.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // --- REVISI 1: Tambahkan Input No. Transaksi di paling atas Form ---
                                Forms\Components\TextInput::make('transaction_code')
                                    ->label('No. Transaksi')
                                    ->placeholder('Akan di-generate otomatis saat disimpan')
                                    ->disabled()
                                    ->dehydrated(false),

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
                                    ->numeric()
                                    ->prefix('IDR')
                                    ->required()
                                    ->minValue(0),

                                Forms\Components\Textarea::make('description')
                                    ->label('Keterangan')
                                    ->placeholder('Tuliskan keterangan transaksi...')
                                    ->rows(3)
                                    ->maxLength(500),

                                Forms\Components\FileUpload::make('receipt')
                                    ->label('Upload Bukti')
                                    ->image()
                                    ->required()
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
                // --- REVISI 2: Tambahkan Kolom No. Transaksi di paling kiri Tabel ---
                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->placeholder('Sedang diproses...'), // Fallback jika data lama belum punya nomor

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    // Membuat link ke halaman dokumen asli (jika referensi adalah PO)
                    ->url(function ($record) {
                        if ($record->reference_type === PurchaseOrder::class) {
                            // Link ke halaman View PO
                            return \App\Filament\Resources\Procurement\PurchaseOrderResource::getUrl('view', ['record' => $record->reference_id]);
                        }
                        return null;
                    })
                    ->description(function ($record) {
                        // Mengubah nama namespace model (App\Models\Procurement\PurchaseOrder) menjadi nama simpel (PurchaseOrder)
                        if ($record->reference_type) {
                            return class_basename($record->reference_type);
                        }
                        return $record->reimburse_id ? 'Reimbursement' : 'Catatan Manual';
                    })
                    ->placeholder('Catatan Manual'),

                Tables\Columns\TextColumn::make('creator')
                    ->label('Dibuat Oleh')
                    ->state(
                        fn($record) =>
                        $record->reimbursement?->employee?->full_name
                        ?? $record->employee?->full_name
                        ?? 'Sistem Otomatis'
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
                        default => 'gray'
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                        default => ucwords(
                            str_replace('_', ' ', $state)
                        ),
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('transaction_category')
                    ->label('Kategori')
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
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->description), // Hover untuk lihat full teks

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
                        'manual' => 'Manual (Kas Kecil)',
                        'reimburse' => 'Reimburse Karyawan',
                        'purchase_order' => 'Pembelian (Purchase Order)',
                        // Tambahkan 'invoice' => 'Penjualan (Invoice)' nanti kalau modul Invoice dihubungkan
                    ])
                    ->query(function ($query, $data) {
                        return match ($data['value'] ?? null) {
                            'manual' => $query->whereNull('reimburse_id')->whereNull('reference_id'),
                            'reimburse' => $query->whereNotNull('reimburse_id'),
                            'purchase_order' => $query->where('reference_type', PurchaseOrder::class),
                            default => $query,
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Transaksi Keuangan')
                    ->description('Detail transaksi pemasukan atau pengeluaran.')
                    ->columns(2)
                    ->schema([
                        // --- REVISI 3: Tambahkan Infolist No. Transaksi di paling atas Detail ---
                        TextEntry::make('transaction_code')
                            ->label('No. Transaksi')
                            ->weight('bold')
                            ->color('primary')
                            ->icon('heroicon-o-hashtag')
                            ->placeholder('—'),

                        TextEntry::make('employee.full_name')
                            ->label('Dibuat Oleh')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->state(fn ($record) => $record->employee?->full_name ?? 'Sistem Otomatis')
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
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),

                Section::make('Informasi Dokumen Referensi Sistem')
                    ->description('Transaksi ini dicatat secara otomatis dari modul lain di ERP.')
                    ->columns(2)
                    ->visible(fn($record) => filled($record->reference_id))
                    ->schema([
                        TextEntry::make('reference_number')
                            ->label('Nomor Referensi (ID)')
                            ->weight('semibold')
                            ->color('primary')
                            ->icon('heroicon-o-document-text'),

                        TextEntry::make('reference_type')
                            ->label('Modul Asal')
                            ->formatStateUsing(fn ($state) => class_basename($state)),
                    ]),

                Section::make('Informasi Reimburse')
                    ->description('Transaksi ini berasal dari pengajuan reimburse.')
                    ->columns(2)
                    ->visible(fn($record) => filled($record->reimburse_id))
                    ->schema([
                        TextEntry::make('reimbursement.employee.full_name')
                            ->label('Pemilik Reimburse')
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

                        ImageEntry::make('reimbursement.receipt')
                            ->label('Bukti Reimburse')
                            ->placeholder('—')
                            ->extraImgAttributes([
                                'style' => 'width: 100%; height: auto; object-fit: cover;',
                                'class' => 'w-full rounded-2xl'
                            ]),

                        TextEntry::make('reimbursement.approver.full_name')
                            ->label('Disetujui Oleh')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                    ]),

                Section::make('Bukti Transaksi Manual')
                    ->description('Transaksi ini dibuat langsung di catatan operasional.')
                    ->visible(fn($record) => empty($record->reimburse_id) && empty($record->reference_id))
                    ->schema([
                        ImageEntry::make('receipt')
                            ->label('Bukti Transaksi')
                            ->placeholder('—')
                            ->extraImgAttributes([
                                'style' => 'width: 100%; height: auto; object-fit: cover;',
                                'class' => 'w-full rounded-2xl'
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialRecords::route('/'),
            'create' => Pages\CreateFinancialRecord::route('/create'),
            'view' => Pages\ViewFinancialRecord::route('/{record}'),
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
