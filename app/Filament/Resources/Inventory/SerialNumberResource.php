<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\SerialNumberResource\Pages;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\StockTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class SerialNumberResource extends Resource
{
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
                    ->icon('heroicon-o-qr-code'),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->description(fn($record) => $record->product->product_code ?? ''),

                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Lokasi Gudang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Unit')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SerialNumber::STATUS_AVAILABLE => 'success',
                        SerialNumber::STATUS_RESERVED => 'warning',
                        SerialNumber::STATUS_ON_DELIVERY => 'info',
                        SerialNumber::STATUS_SOLD => 'gray', // Terjual bukan danger, lebih baik abu-abu (selesai)
                        SerialNumber::STATUS_DEFECTIVE, SerialNumber::STATUS_LOST => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', $state)),

                Tables\Columns\TextColumn::make('inbound_date')
                    ->label('Tgl Masuk (Suplai)')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('outbound_date')
                    ->label('Tgl Keluar (Terjual)')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('Belum Keluar')
                    ->toggleable(),
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

                // Filter status disesuaikan dengan Konstanta di Model
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status SN')
                    ->options(SerialNumber::getAllStatuses()),
            ])

            ->actions([
                Tables\Actions\ViewAction::make(),

                // === TAMBAHAN FITUR: QUICK ACTION LAPOR RUSAK ===
                Tables\Actions\Action::make('mark_as_defective')
                    ->label('Lapor Rusak')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === SerialNumber::STATUS_AVAILABLE)
                    ->requiresConfirmation()
                    ->modalHeading('Laporkan Barang Rusak / Afkir')
                    ->modalDescription('Apakah Anda yakin barang ini rusak? Tindakan ini akan mengeluarkan SN dari stok siap jual dan membuat Log Mutasi Gudang secara otomatis.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Kerusakan')
                            ->placeholder('Contoh: Jatuh saat diangkat dari rak')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $now = now();

                            // 1. Ubah status SN ini menjadi rusak & catat waktu keluarnya
                            $record->update([
                                'status' => SerialNumber::STATUS_DEFECTIVE,
                                'outbound_date' => $now->toDateString(),
                            ]);

                            // 2. LOGIKA GENERATE KODE TRANSAKSI STANDAR (ST-OUT)
                            $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
                            $monthRoman = $romanMonths[$now->month - 1];
                            $year = $now->year;
                            $company = 'NEX';
                            $code = 'ST-OUT'; // Karena ini mutasi pengeluaran (rusak)

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
                            $transactionCode = "{$seqStr}/{$code}/{$company}/{$monthRoman}/{$year}";

                            // 3. OTOMATIS Buat Catatan di Tabel Transaksi Stok (Audit Trail)
                            StockTransaction::create([
                                'product_id'       => $record->product_id,
                                'warehouse_id'     => $record->warehouse_id,
                                'transaction_code' => $transactionCode, // <-- Pakai kode yang sudah distandarisasi
                                'reference_number' => 'Pelaporan Kerusakan SN',
                                'mutation_type'    => 'adjustment_out',
                                'transaction_date' => $now,
                                'quantity'         => 1,
                                'notes'            => "Dilaporkan rusak (SN: {$record->serial_number}). Alasan: {$data['reason']}",
                                'created_by'       => auth()->id() ?? 1,
                            ]);
                        });

                        Notification::make()
                            ->title('Berhasil!')
                            ->body("SN {$record->serial_number} dilaporkan rusak dan tercatat di mutasi gudang.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSerialNumbers::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
}
