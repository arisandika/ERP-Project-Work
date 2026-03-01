<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\PurchaseOrderResource\Pages;
use App\Models\Inventory\Product;
use App\Models\Procurement\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Manajemen Procurement';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'procurement/purchase-orders';

    protected static ?string $pluralModelLabel = 'Purchase Orders';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function updateTotals(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (float) ($item['total_price'] ?? 0);
        }

        $tax = (float) ($get('tax_amount') ?? 0);
        $discount = (float) ($get('discount_amount') ?? 0);

        $set('subtotal', $subtotal);
        $set('grand_total', $subtotal + $tax - $discount);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Informasi Dokumen PO')
                        ->schema([
                            Forms\Components\TextInput::make('po_number')
                                ->label('Nomor PO')
                                ->default(fn () => PurchaseOrder::generatePONumber())
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('supplier_id')
                                ->label('Supplier / Vendor')
                                ->relationship('supplier', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\DatePicker::make('order_date')
                                ->label('Tanggal Pemesanan')
                                ->default(now())
                                ->required(),

                            Forms\Components\DatePicker::make('expected_delivery_date')
                                ->label('Estimasi Tanggal Tiba'),

                            Forms\Components\Select::make('status')
                                ->label('Status PO')
                                ->options([
                                    'draft' => 'Draft (Belum Dikirim)',
                                    'sent' => 'Dikirim ke Supplier',
                                    'partial' => 'Diterima Sebagian',
                                    'completed' => 'Selesai (Masuk Gudang)',
                                    'cancelled' => 'Dibatalkan',
                                ])
                                ->default('draft')
                                ->required()
                                ->disabled(fn (string $operation): bool => $operation === 'create'),
                        ])->columns(2),

                    Forms\Components\Section::make('Daftar Barang (Order Items)')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Select::make('product_id')
                                        ->label('Pilih Produk')
                                        ->options(Product::query()->pluck('product_name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $product = Product::find($state);
                                            $price = $product?->purchase_price ?? 0;
                                            $qty = (int) ($get('quantity') ?? 1);

                                            $set('unit_price', $price);
                                            $set('total_price', $price * $qty);
                                        }),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Qty Pesan')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(1)
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $price = (float) ($get('unit_price') ?? 0);
                                            $set('total_price', $price * (int) $state);
                                        }),

                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('Harga Satuan')
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $qty = (int) ($get('quantity') ?? 1);
                                            $set('total_price', (float) $state * $qty);
                                        }),

                                    Forms\Components\TextInput::make('total_price')
                                        ->label('Total Baris')
                                        ->numeric()
                                        ->required()
                                        ->disabled()
                                        ->dehydrated(),
                                ])
                                ->columns(4)
                                ->addActionLabel('Tambah Barang')
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                    self::updateTotals($get, $set);
                                })
                        ]),
                ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Ringkasan Biaya')
                        ->schema([
                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated()
                                ->prefix('Rp'),

                            Forms\Components\TextInput::make('tax_amount')
                                ->label('Pajak (PPN)')
                                ->numeric()
                                ->default(0)
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                    self::updateTotals($get, $set);
                                })
                                ->prefix('Rp'),

                            Forms\Components\TextInput::make('discount_amount')
                                ->label('Diskon')
                                ->numeric()
                                ->default(0)
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                    self::updateTotals($get, $set);
                                })
                                ->prefix('Rp'),

                            Forms\Components\TextInput::make('grand_total')
                                ->label('Grand Total')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated()
                                ->prefix('Rp')
                                ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: bold; color: green;']),
                        ]),

                    Forms\Components\Section::make('Catatan Tambahan')
                        ->schema([
                            Forms\Components\Textarea::make('notes')
                                ->label('Catatan untuk Supplier')
                                ->rows(4),
                        ])
                ])->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Tgl Pesan')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'partial' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Dikirim ke Supplier',
                        'partial' => 'Diterima Sebagian',
                        'completed' => 'Selesai',
                        'cancelled' => 'Batal',
                    ]),
            ])
            ->actions([
                // 1. Tombol Edit (Hanya bisa jika masih Draft atau Sent)
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'sent'])),

                // 2. Tombol Kirim Dokumen (Ubah draf jadi Sent)
                Tables\Actions\Action::make('mark_as_sent')
                    ->label('Kirim ke Supplier')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $record) {
                        $record->update(['status' => 'sent']);
                        \Filament\Notifications\Notification::make()
                            ->title('PO Berhasil Dikirim')
                            ->success()
                            ->send();
                    }),

                // 3. Tombol Terima Barang (Goods Receipt)
                Tables\Actions\Action::make('receive_goods')
                    ->label('Terima Barang')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['sent', 'partial']))
                    ->form(function (PurchaseOrder $record) {
                        $schema = [
                            Forms\Components\Select::make('warehouse_id')
                                ->label('Masuk ke Gudang Mana?')
                                ->options(\App\Models\Inventory\Warehouse::where('is_active', true)->pluck('warehouse_name', 'id'))
                                ->required(),

                            Forms\Components\DatePicker::make('receipt_date')
                                ->label('Tanggal Diterima')
                                ->default(now())
                                ->required(),

                            Forms\Components\Section::make('Ceklis Barang Fisik')
                                ->description('Masukkan jumlah kuantitas yang benar-benar Anda terima dari kurir hari ini.')
                                ->schema(
                                    $record->items->map(function ($item) {
                                        $sisa = $item->quantity - $item->quantity_received;
                                        return Forms\Components\TextInput::make("item_{$item->id}")
                                            ->label($item->product->product_name . ' (Pesanan: '.$item->quantity.', Sisa: '.$sisa.')')
                                            ->numeric()
                                            ->default($sisa > 0 ? $sisa : 0)
                                            ->maxValue($sisa)
                                            ->minValue(0)
                                            ->disabled($sisa == 0);
                                    })->toArray()
                                )->columns(2),
                        ];
                        return $schema;
                    })
                    ->action(function (array $data, PurchaseOrder $record) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $record) {
                            $warehouseId = $data['warehouse_id'];
                            $receiptDate = $data['receipt_date'];
                            $allCompleted = true;

                            // --- LOGIKA Transaction Code ---
                            $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
                            $monthRoman = $romanMonths[now()->month - 1];
                            $year = now()->year;
                            $company = 'NEX';
                            $code = 'ST-IN'; // PO masuk gudang pasti ST-IN
                            $prefixLike = "%/{$code}/{$company}/{$monthRoman}/{$year}";

                            $last = \App\Models\Inventory\StockTransaction::where('transaction_code', 'like', $prefixLike)
                                ->orderByDesc('id')
                                ->value('transaction_code');

                            $seq = 1;
                            if ($last) {
                                $parts = explode('/', $last);
                                $seq = ((int) $parts[0]) + 1;
                            }
                            // --------------------------------------------------------

                            foreach ($record->items as $item) {
                                $inputKey = "item_{$item->id}";
                                $receivedNow = (int) ($data[$inputKey] ?? 0);

                                if ($receivedNow > 0) {
                                    $item->quantity_received += $receivedNow;
                                    $item->save();

                                    // Merangkai nomor: 001/ST-IN/NEX/III/2026
                                    $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                                    $txCode = "{$seqStr}/{$code}/{$company}/{$monthRoman}/{$year}";

                                    \App\Models\Inventory\StockTransaction::create([
                                        'product_id' => $item->product_id,
                                        'warehouse_id' => $warehouseId,
                                        'transaction_code' => $txCode,
                                        'reference_number' => $record->po_number,
                                        'mutation_type' => 'stock_in',
                                        'transaction_date' => $receiptDate,
                                        'quantity' => $receivedNow,
                                        'price' => $item->unit_price,
                                        'notes' => 'Penerimaan otomatis dari ' . $record->po_number,
                                        'created_by' => auth()->id() ?? 1,
                                    ]);

                                    $seq++;
                                }

                                if ($item->quantity_received < $item->quantity) {
                                    $allCompleted = false;
                                }
                            }

                            $record->status = $allCompleted ? 'completed' : 'partial';
                            $record->save();
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Barang Diterima & Masuk Gudang!')
                            ->success()
                            ->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
