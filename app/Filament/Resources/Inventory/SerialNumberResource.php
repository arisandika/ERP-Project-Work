<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\SerialNumberResource\Pages;
use App\Models\Inventory\SerialNumber;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SerialNumberResource extends Resource
{
    protected static ?string $model = SerialNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Pelacakan SN';

    protected static ?string $pluralModelLabel = 'Pelacakan Serial Number';

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
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('SN berhasil disalin')
                    ->icon('heroicon-o-qr-code'),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->description(fn($record) => $record->product->product_code ?? ''),

                // UBAHAN 1: Menampilkan lokasi gudang spesifik untuk SN ini
                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Lokasi Gudang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-building-storefront'),

                // UBAHAN 2: Menyesuaikan status dengan logika Mutasi Engine kita
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Unit')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'AVAILABLE' => 'success',
                        'RESERVED' => 'warning',
                        'ON_DELIVERY' => 'info',
                        'SOLD_OR_OUT', 'SOLD' => 'danger',
                        'DAMAGED' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', $state)),

                Tables\Columns\TextColumn::make('inbound_date')
                    ->label('Tgl Masuk (Suplai)')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                // UBAHAN 3: Menampilkan tanggal keluar untuk patokan Garansi
                Tables\Columns\TextColumn::make('outbound_date')
                    ->label('Tgl Keluar (Terjual)')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('Belum Keluar')
                    ->toggleable(),
            ])
            ->filters([
                // UBAHAN 4: Menambahkan filter kompleks agar pencarian lebih cepat
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
                    ->options([
                        'AVAILABLE' => 'Tersedia di Gudang',
                        'RESERVED' => 'Dipesan (Reserved)',
                        'ON_DELIVERY' => 'Dalam Pengiriman',
                        'SOLD_OR_OUT' => 'Terjual / Keluar',
                        'DAMAGED' => 'Rusak / Afkir',
                    ]),
            ])
            ->actions([
                // Hanya butuh View, karena datanya dikontrol penuh oleh sistem mutasi
                Tables\Actions\ViewAction::make(),
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

    // PROTEKSI AUDIT: Data SN mutlak dikendalikan oleh Sistem Mutasi Transaksi
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
}
