<?php

namespace App\Filament\Resources\Finance;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\Finance\FinancialRecordResource\Pages;
use App\Models\Finance\FinancialRecord;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Sales\Payment;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class FinancialRecordResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'finance';

    protected static ?string $model = FinancialRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Manajemen Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'finance/financial-records';

    protected static ?string $pluralModelLabel = 'Catatan Operasional & Jurnal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Input Transaksi')
                    ->description('Catat pemasukan atau pengeluaran operasional secara manual.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
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
                                        'hutang' => 'Hutang',
                                        'piutang' => 'Piutang',
                                    ])
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-tag'),
                                Forms\Components\Select::make('category')
                                    ->label('Jenis Transaksi')
                                    ->required()
                                    ->options([
                                        'Sales Revenue' => 'Sales Revenue',
                                        'Accounts Receivable' => 'Accounts Receivable',
                                        'Accounts Payable' => 'Accounts Payable',
                                        'Reimbursement' => 'Reimbursement',
                                        'Salary Expense' => 'Salary Expense',
                                        'Office Rent' => 'Office Rent',
                                        'Listrik' => 'Listrik',
                                        'Air' => 'Air',
                                        'Bensin' => 'Bensin',
                                        'Makan' => 'Makan',
                                        'Transport' => 'Transport',
                                        'Parkir' => 'Parkir',
                                        'Hotel' => 'Hotel',
                                        'Lainnya' => 'Lainnya',
                                    ])
                                    ->searchable()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-tag'),
                                Forms\Components\Select::make('account_type')
                                    ->label('Klasifikasi Akun')
                                    ->options([
                                        'revenue' => 'Revenue / Pendapatan',
                                        'cogs' => 'COGS / HPP',
                                        'operating_expense' => 'Operating Expense / Biaya Operasional',
                                        'other_income' => 'Other Income / Pendapatan Lain-lain',
                                        'other_expense' => 'Other Expense / Beban Lain-lain',
                                        'asset' => 'Asset / Aset',
                                        'liability' => 'Liability / Kewajiban',
                                        'equity' => 'Equity / Modal',
                                    ])
                                    ->searchable()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-rectangle-stack')
                                    ->helperText('Dipakai untuk Laba Rugi dan Neraca.'),
                                Forms\Components\Select::make('cash_flow_activity')
                                    ->label('Aktivitas Arus Kas')
                                    ->options([
                                        'operating' => 'Operating / Operasional',
                                        'investing' => 'Investing / Investasi',
                                        'financing' => 'Financing / Pendanaan',
                                    ])
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-arrows-right-left')
                                    ->helperText('Dipakai untuk laporan Arus Kas.'),
                                Forms\Components\Select::make('normal_balance')
                                    ->label('Saldo Normal')
                                    ->options([
                                        'debit' => 'Debit',
                                        'credit' => 'Credit',
                                    ])
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-scale')
                                    ->helperText('Opsional. Bisa dikosongkan, sistem akan mengisi otomatis.'),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Nominal')
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
                                    ->directory('financials')
                                    ->imageEditor()
                                    ->previewable()
                                    ->maxSize(2048)
                                    ->acceptedFileTypes([
                                        'image/jpeg',
                                        'image/png',
                                        'image/jpg',
                                        'image/webp',
                                    ])
                                    ->helperText('Upload bukti seperti struk (maks 2MB)')
                                    ->visible(fn(Forms\Get $get) => empty($get('reference_id'))),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->placeholder('Sedang diproses...'),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->url(function ($record) {
                        if ($record->reference_type === PurchaseOrder::class) {
                            return \App\Filament\Resources\Procurement\PurchaseOrderResource::getUrl('view', [
                                'record' => $record->reference_id,
                            ]);
                        }

                        return null;
                    })
                    ->description(function ($record) {
                        if ($record->reference_type) {
                            return class_basename($record->reference_type);
                        }

                        return $record->reimburse_id ? 'Reimbursement' : 'Catatan Manual';
                    })
                    ->placeholder('Catatan Manual'),
                Tables\Columns\TextColumn::make('creator_name')
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
                        'hutang' => 'warning',
                        'piutang' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                        'hutang' => 'Hutang',
                        'piutang' => 'Piutang',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('transaction_category')
                    ->label('Kategori')
                    ->state(fn($record) => $record->reimbursement?->type ?? $record->category)
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('account_type')
                    ->label('Klasifikasi')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'revenue' => 'Revenue',
                        'cogs' => 'HPP',
                        'operating_expense' => 'Opex',
                        'other_income' => 'Other Income',
                        'other_expense' => 'Other Expense',
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        default => 'Belum Diklasifikasi',
                    })
                    ->color(fn(?string $state) => match ($state) {
                        'revenue' => 'success',
                        'cogs' => 'danger',
                        'operating_expense' => 'warning',
                        'other_income' => 'info',
                        'other_expense' => 'gray',
                        'asset' => 'primary',
                        'liability' => 'danger',
                        'equity' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->state(fn($record) => $record->reimbursement?->description ?? $record->description)
                    ->searchable()
                    ->placeholder('—')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->description),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->state(fn($record) => $record->reimbursement?->date ?? $record->transaction_date)
                    ->date('D, d M Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->color(fn($record) => match ($record->type) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger',
                        'hutang' => 'warning',
                        'piutang' => 'info',
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
                        'hutang' => 'Hutang',
                        'piutang' => 'Piutang',
                    ])
                    ->native(false),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Sumber Data')
                    ->options([
                        'manual' => 'Manual (Kas Kecil)',
                        'reimburse' => 'Reimburse Karyawan',
                        'goods_receipt' => 'Goods Receipt',
                        'purchase_order' => 'Purchase Order',
                        'invoice_payment' => 'Pembayaran Invoice',
                    ])
                    ->query(function ($query, $data) {
                        return match ($data['value'] ?? null) {
                            'manual' => $query->whereNull('reimburse_id')->whereNull('reference_id'),
                            'reimburse' => $query->whereNotNull('reimburse_id'),
                            'goods_receipt' => $query->where('reference_type', GoodsReceipt::class),
                            'purchase_order' => $query->where('reference_type', PurchaseOrder::class),
                            'invoice_payment' => $query->where('reference_type', Payment::class),
                            default => $query,
                        };
                    })
                    ->native(false),
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Tanggal Dari')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Tanggal Hingga')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'Tanggal dari ' . Carbon::parse($data['date_from'])->toFormattedDateString();
                        }

                        if ($data['date_until'] ?? null) {
                            $indicators[] = 'Tanggal hingga ' . Carbon::parse($data['date_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('account_type')
                    ->label('Klasifikasi Akun')
                    ->options([
                        'revenue' => 'Revenue',
                        'cogs' => 'HPP',
                        'operating_expense' => 'Operating Expense',
                        'other_income' => 'Other Income',
                        'other_expense' => 'Other Expense',
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                    ])
                    ->native(false),
                Tables\Filters\SelectFilter::make('cash_flow_activity')
                    ->label('Aktivitas Arus Kas')
                    ->options([
                        'operating' => 'Operating',
                        'investing' => 'Investing',
                        'financing' => 'Financing',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Transaksi Keuangan')
                    ->description('Detail transaksi pemasukan, pengeluaran, hutang, atau piutang.')
                    ->columns(['default' => 12, 'md' => 2])
                    ->schema([
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
                            ->state(fn($record) => $record->employee?->full_name ?? 'Sistem Otomatis')
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
                                'hutang' => 'warning',
                                'piutang' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn($state) => match ($state) {
                                'pemasukan' => 'Pemasukan',
                                'pengeluaran' => 'Pengeluaran',
                                'hutang' => 'Hutang',
                                'piutang' => 'Piutang',
                                default => ucwords(str_replace('_', ' ', $state)),
                            })
                            ->placeholder('—'),
                        TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR')
                            ->weight('semibold')
                            ->color(fn($record) => match (strtolower($record->type)) {
                                'pemasukan' => 'success',
                                'pengeluaran' => 'danger',
                                'hutang' => 'warning',
                                'piutang' => 'info',
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
                    ->description('Transaksi ini dicatat otomatis dari modul lain di ERP.')
                    ->columns(['default' => 12, 'md' => 2])
                    ->visible(fn($record) => filled($record->reference_id))
                    ->schema([
                        TextEntry::make('reference_number')
                            ->label('Nomor Referensi')
                            ->weight('semibold')
                            ->color('primary')
                            ->icon('heroicon-o-document-text'),
                        TextEntry::make('reference_type')
                            ->label('Modul Asal')
                            ->formatStateUsing(fn($state) => class_basename($state)),
                    ]),
                Section::make('Informasi Reimburse')
                    ->description('Transaksi ini berasal dari pengajuan reimburse.')
                    ->columns(['default' => 12, 'md' => 2])
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
                                default => ucwords(str_replace('_', ' ', $state)),
                            })
                            ->placeholder('—'),
                        ImageEntry::make('reimbursement.receipt')
                            ->label('Bukti Reimburse')
                            ->placeholder('—')
                            ->extraImgAttributes([
                                'style' => 'width: 100%; height: auto; object-fit: cover;',
                                'class' => 'w-full rounded-2xl',
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
                                'class' => 'w-full rounded-2xl',
                            ]),
                    ])
                    ->columns(['default' => 12, 'md' => 2]),
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
