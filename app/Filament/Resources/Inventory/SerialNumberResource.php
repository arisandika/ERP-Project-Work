<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\Inventory\SerialNumberResource\Pages;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\StockTransaction;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Facades\DB;

class SerialNumberResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'inventory';
    protected static ?string $model = SerialNumber::class;
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'Daftar Serial Number';
    protected static ?string $pluralModelLabel = 'Daftar Serial Number';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    protected static function generateTransactionCode(string $code, $now): string
    {
        $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $monthRoman = $romanMonths[$now->month - 1];
        $year = $now->year;
        $company = 'NEX';

        $prefixLike = "%/{$code}/{$company}/{$monthRoman}/{$year}";

        $last = StockTransaction::query()
            ->where('transaction_code', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('transaction_code');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$monthRoman}/{$year}";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial Number (SN)')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->copyable()
                    ->copyMessage('SN berhasil disalin')
                    ->icon('heroicon-o-qr-code')
                    ->width('220px')
                    ->extraAttributes([
                        'class' => 'sticky-column',
                    ]),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->description(fn($record) => $record->product->product_code ?? '-')
                    ->width('250px'),
                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Lokasi Gudang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-building-storefront')
                    ->width('180px'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Unit')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        SerialNumber::STATUS_AVAILABLE => 'success',
                        SerialNumber::STATUS_RESERVED => 'warning',
                        SerialNumber::STATUS_ON_DELIVERY => 'info',
                        SerialNumber::STATUS_SOLD => 'gray',
                        SerialNumber::STATUS_DEFECTIVE,
                        SerialNumber::STATUS_LOST => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => str_replace('_', ' ', $state))
                    ->width('150px'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier Asal')
                    ->searchable()
                    ->toggleable()
                    ->width('200px'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Klien / Pembeli')
                    ->searchable()
                    ->toggleable()
                    ->width('200px'),
                Tables\Columns\TextColumn::make('warranty_expired_at')
                    ->label('Garansi Habis')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable()
                    ->width('150px'),
                Tables\Columns\TextColumn::make('inbound_date')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable()
                    ->width('150px'),
                Tables\Columns\TextColumn::make('outbound_date')
                    ->label('Tgl Keluar')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('Belum Keluar')
                    ->toggleable()
                    ->width('150px'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Filter Produk')
                    ->relationship('product', 'product_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Filter Gudang')
                    ->relationship('warehouse', 'warehouse_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status SN')
                    ->options(SerialNumber::getAllStatuses()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-s-eye')
                    ->iconButton()
                    ->tooltip('Lihat Riwayat & Detail'),
                Tables\Actions\Action::make('mark_as_defective')
                    ->label('Lapor Rusak')
                    ->icon('heroicon-s-exclamation-triangle')
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Laporkan Barang Rusak')
                    ->visible(fn($record) => $record->status === SerialNumber::STATUS_AVAILABLE)
                    ->requiresConfirmation()
                    ->modalHeading('Laporkan Barang Rusak / Afkir')
                    ->modalDescription('Barang akan dikeluarkan dari stok siap jual dan dicatat ke audit transaksi.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Kerusakan')
                            ->placeholder('Contoh: Jatuh saat diangkat dari rak')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $now = now();

                            $record->update([
                                'status' => SerialNumber::STATUS_DEFECTIVE,
                                'outbound_date' => $now->toDateString(),
                            ]);

                            StockTransaction::create([
                                'product_id' => $record->product_id,
                                'warehouse_id' => $record->warehouse_id,
                                'serial_number_id' => $record->id,
                                'transaction_code' => static::generateTransactionCode('ST-OUT', $now),
                                'reference_number' => 'Pelaporan Kerusakan SN',
                                'mutation_type' => 'adjustment_out',
                                'transaction_date' => $now,
                                'quantity' => 1,
                                'price' => 0,
                                'total_price' => 0,
                                'notes' => "Dilaporkan rusak (SN: {$record->serial_number}). Alasan: {$data['reason']}",
                                'created_by' => auth()->id() ?? 1,
                            ]);
                        });

                        Notification::make()
                            ->title('Berhasil!')
                            ->body("SN {$record->serial_number} dilaporkan rusak dan tercatat di audit transaksi.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('mark_as_lost')
                    ->label('Tandai Hilang')
                    ->icon('heroicon-s-x-circle')
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Tandai Barang Hilang')
                    ->visible(fn($record) => in_array($record->status, [
                        SerialNumber::STATUS_AVAILABLE,
                        SerialNumber::STATUS_RESERVED,
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Barang Hilang')
                    ->modalDescription('Barang akan ditandai hilang dan dicatat ke audit transaksi.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan / Keterangan')
                            ->placeholder('Contoh: Tidak ditemukan saat stock opname')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $now = now();

                            $record->update([
                                'status' => SerialNumber::STATUS_LOST,
                                'outbound_date' => $now->toDateString(),
                            ]);

                            StockTransaction::create([
                                'product_id' => $record->product_id,
                                'warehouse_id' => $record->warehouse_id,
                                'serial_number_id' => $record->id,
                                'transaction_code' => static::generateTransactionCode('ST-OUT', $now),
                                'reference_number' => 'Pelaporan Kehilangan SN',
                                'mutation_type' => 'adjustment_out',
                                'transaction_date' => $now,
                                'quantity' => 1,
                                'price' => 0,
                                'total_price' => 0,
                                'notes' => "Ditandai hilang (SN: {$record->serial_number}). Keterangan: {$data['reason']}",
                                'created_by' => auth()->id() ?? 1,
                            ]);
                        });

                        Notification::make()
                            ->title('Berhasil!')
                            ->body("SN {$record->serial_number} ditandai hilang dan tercatat di audit transaksi.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(['default' => 1, 'sm' => 3])->schema([
                    // BLOK 1: INFORMASI UNIT UTAMA
                    Section::make('Informasi Unit')
                        ->icon('heroicon-o-cube')
                        ->columnSpan(['default' => 'full', 'sm' => 1])
                        ->schema([
                            TextEntry::make('serial_number')
                                ->label('Serial Number')
                                ->weight('bold')
                                ->copyable(),
                            TextEntry::make('status')
                                ->label('Status Terkini')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    SerialNumber::STATUS_AVAILABLE => 'success',
                                    SerialNumber::STATUS_RESERVED => 'warning',
                                    SerialNumber::STATUS_ON_DELIVERY => 'info',
                                    SerialNumber::STATUS_SOLD => 'gray',
                                    SerialNumber::STATUS_DEFECTIVE,
                                    SerialNumber::STATUS_LOST => 'danger',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn(string $state): string => str_replace('_', ' ', $state)),
                            TextEntry::make('product.product_name')
                                ->label('Nama Product'),
                            TextEntry::make('product.product_code')
                                ->label('Kode Product'),
                            TextEntry::make('warehouse.warehouse_name')
                                ->label('Posisi Gudang')
                                ->icon('heroicon-o-building-storefront'),
                        ]),
                    // BLOK 2: RIWAYAT MASUK (INBOUND)
                    Section::make('Riwayat Masuk (Hulu)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->columnSpan(['default' => 'full', 'sm' => 1])
                        ->schema([
                            TextEntry::make('inbound_date')
                                ->label('Tanggal Masuk (Inbound)')
                                ->date('d F Y')
                                ->placeholder('-'),
                            TextEntry::make('supplier.name')
                                ->label('Dari Supplier')
                                ->placeholder('Tidak diketahui / Saldo Awal'),
                            TextEntry::make('purchaseOrder.po_number')
                                ->label('Berdasarkan Nomor PO')
                                ->placeholder('Tidak ada referensi PO'),
                        ]),
                    // BLOK 3: RIWAYAT KELUAR (OUTBOUND) & GARANSI
                    Section::make('Riwayat Keluar (Hilir)')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->columnSpan(['default' => 'full', 'sm' => 1])
                        ->schema([
                            TextEntry::make('outbound_date')
                                ->label('Tanggal Keluar (Outbound)')
                                ->date('d F Y')
                                ->placeholder('Belum Keluar / Masih di Gudang'),
                            TextEntry::make('customer.name')
                                ->label('Terjual ke Klien')
                                ->placeholder('Belum dialokasikan ke Klien'),
                            TextEntry::make('warranty_expired_at')
                                ->label('Masa Berlaku Garansi')
                                ->date('d F Y')
                                ->placeholder('Tidak ada data garansi')
                                ->badge()
                                ->color(fn($state) => \Carbon\Carbon::parse($state)->isPast() ? 'danger' : 'success'),
                        ]),
                ]),
                Section::make('Audit Trail Sistem')
                    ->collapsed()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Data Dibuat')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y H:i:s'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSerialNumbers::route('/'),
            'view' => Pages\ViewSerialNumber::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
