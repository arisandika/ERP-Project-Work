<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\DeliveryOrderResource\Pages;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Inventory\Product;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Closure;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'sales/delivery-orders';
    protected static ?string $pluralModelLabel = 'Surat Jalan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Surat Jalan')->schema([
                Grid::make(3)->schema([
                    TextInput::make('do_number')
                        ->label('No. Surat Jalan')
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-o-hashtag'),

                    DatePicker::make('do_date')
                        ->label('Tanggal Surat Jalan')
                        ->default(now())
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->required()
                        ->displayFormat('d M Y')
                        ->native(false),

                    Select::make('nx_sales_order_id')
                        ->label('No. Sales Order (Ref)')
                        ->relationship(
                            'salesOrder',
                            'order_number',
                            modifyQueryUsing: fn(Builder $query) =>
                            $query->whereIn('status', ['confirmed', 'processing', 'shipped'])
                                ->orderByDesc('created_at')
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->placeholder('Pilih Sales Order')
                        ->disabled(fn($record) => $record && $record->exists)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) return;

                            $so = SalesOrder::with('items')->find($state);
                            if (!$so) return;

                            $set('nx_customer_id', $so->nx_customer_id);

                            $existingDOs = DeliveryOrder::with('items')
                                ->where('nx_sales_order_id', $state)
                                ->where('status', '!=', 'cancelled')
                                ->get();

                            $items = $so->items->map(function ($item) use ($existingDOs) {
                                $qtyOrder = floatval($item->qty ?? 0);
                                $qtyShipped = $existingDOs->flatMap->items
                                    ->where('item_id', $item->item_id)
                                    ->sum('qty');

                                $qtyRemainingQuota = max($qtyOrder - $qtyShipped, 0);

                                $isSerialized = false;
                                if ($item->item_type === 'product') {
                                    $product = Product::find($item->item_id);
                                    $isSerialized = $product ? $product->is_serialized : false;
                                }

                                return [
                                    'item_type' => $item->item_type,
                                    'item_id' => $item->item_id,
                                    'item_code' => (string) $item->item_code,
                                    'item_name' => (string) $item->item_name,
                                    'qty_ordered' => $qtyRemainingQuota,
                                    'qty' => 0,
                                    'qty_remaining' => $qtyRemainingQuota,
                                    'is_serialized' => $isSerialized,
                                ];
                            })->values()->toArray();

                            $set('items', $items);
                        })
                        ->required()
                        ->prefixIcon('heroicon-o-hashtag'),
                ]),

                Grid::make(2)->schema([
                    Select::make('nx_customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user-circle'),

                    Select::make('nx_employee_id')
                        ->label('Ditugaskan Kepada')
                        ->relationship('employee', 'full_name')
                        ->default(fn() => auth()->user()->employee?->id)
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user'),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'ready' => 'Siap Kirim',
                            'on_delivery' => 'Dalam Pengiriman',
                            'delivered' => 'Terkirim',
                            'cancelled' => 'Dibatalkan',
                        ])
                        ->default('draft')
                        ->required()
                        ->prefixIcon('heroicon-o-adjustments-vertical'),

                    Textarea::make('notes')
                        ->label('Catatan Tambahan')
                        ->rows(3),
                ]),
            ]),

            Section::make('Daftar Item Surat Jalan')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Hidden::make('item_type')->default('product'),
                        Hidden::make('item_id'),
                        Hidden::make('is_serialized'),

                        Grid::make(2)->schema([
                            TextInput::make('item_code')
                                ->label('Kode Product')
                                ->disabled()
                                ->dehydrated(true),

                            TextInput::make('item_name')
                                ->label('Nama Product')
                                ->disabled()
                                ->dehydrated(true),

                            TextInput::make('qty_ordered')
                                ->label('Sisa Jatah')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('qty')
                                ->label('Kirim Sekarang')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->lte('qty_ordered')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                    $quota = (float) ($get('qty_ordered') ?? 0);
                                    $kirim = (float) ($state ?? 0);
                                    $set('qty_remaining', max($quota - $kirim, 0));
                                }),

                            TextInput::make('qty_remaining')
                                ->label('Sisa Nanti')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),

                            Textarea::make('scanned_sns')
                                ->label('Scan Serial Number')
                                ->rows(5)
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => $get('is_serialized') === true && (float) $get('qty') > 0)
                                ->required(fn (Get $get): bool => $get('is_serialized') === true && (float) $get('qty') > 0)
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        if (!$get('is_serialized') || (float) $get('qty') <= 0) return;
                                        $sns = array_filter(array_map('trim', explode("\n", $value)));
                                        $qtyKirim = (int) $get('qty');
                                        if (count($sns) !== $qtyKirim) {
                                            $fail("ERROR: Scan " . count($sns) . " SN, tapi kirim {$qtyKirim} unit.");
                                            return;
                                        }
                                        if (count($sns) !== count(array_unique($sns))) {
                                            $fail("ERROR: Ada SN Duplikat!");
                                            return;
                                        }
                                        foreach ($sns as $sn) {
                                            $snRecord = SerialNumber::where('serial_number', $sn)->first();
                                            if (!$snRecord) $fail("SN {$sn} tidak terdaftar.");
                                            elseif ($snRecord->status !== 'AVAILABLE') $fail("SN {$sn} status {$snRecord->status}.");
                                        }
                                    },
                                ]),
                        ]),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('do_number')->label('No. Surat Jalan')->sortable()->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('salesOrder.order_number')->label('Ref. Pesanan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('do_date')->label('Tanggal')->date('d M Y H:i'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray', 'ready' => 'warning', 'on_delivery' => 'info', 'delivered' => 'success', 'cancelled' => 'danger', default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft', 'ready' => 'Siap Kirim', 'on_delivery' => 'Dalam Pengiriman', 'delivered' => 'Diterima', 'cancelled' => 'Batal', default => $state,
                    }),
            ])
            ->actions([
                Action::make('kirim_barang')
                ->label('Kirim Barang')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn(DeliveryOrder $record) => in_array($record->status, ['draft', 'ready']))
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pengiriman')
                ->modalDescription('Apakah Anda yakin barang sudah dimuat ke kendaraan? Stok Reserved akan dipindahkan ke On Delivery.')
                ->action(function (DeliveryOrder $record): void {
                    DB::transaction(function () use ($record) {
                        $warehouseUtamaId = Warehouse::where('warehouse_name', 'Gudang Utama')->value('id') ?? 1;

                        StockTransaction::$autoUpdateStock = false; // Matikan observer

                        foreach ($record->items as $item) {
                            if ($item->item_type === 'product' && $item->qty > 0) {
                                $stockUtama = ProductStock::where('product_id', $item->item_id)
                                    ->where('warehouse_id', $warehouseUtamaId)
                                    ->lockForUpdate()
                                    ->first();

                                if (!$stockUtama || $stockUtama->qty_reserved < $item->qty) {
                                    throw new \Exception("Stok Reserved untuk {$item->item_name} tidak mencukupi!");
                                }

                                // A. PINDAH STOK DARI RESERVED KE ON DELIVERY
                                $stockReservedBefore = $stockUtama->qty_reserved;
                                $stockUtama->decrement('qty_reserved', $item->qty);
                                $stockUtama->increment('qty_on_delivery', $item->qty); // TAMBAHKAN INI

                                // B. CATAT HISTORY TRANSAKSI (KELUAR)
                                StockTransaction::create([
                                    'transaction_code' => "ST-OUT/" . rand(100,999) . "/" . now()->format('Ymd'),
                                    'transaction_date' => now(),
                                    'product_id'       => $item->item_id,
                                    'warehouse_id'     => $warehouseUtamaId,
                                    'mutation_type'    => 'delivery',
                                    'type'             => 'keluar',
                                    'quantity'         => $item->qty,
                                    'stock_before'     => $stockReservedBefore,
                                    'stock_after'      => $stockReservedBefore - $item->qty,
                                    'price'            => 0,
                                    'total_price'      => 0,
                                    'reference_id'     => $record->id,
                                    'reference_type'   => DeliveryOrder::class,
                                    'no_reference'     => $record->do_number,
                                    'notes'            => 'Pengiriman Fisik Keluar Gudang (via Table Action)',
                                    'created_by'       => auth()->id(),
                                ]);
                            }
                        }

                        StockTransaction::$autoUpdateStock = true;

                        // Update status dokumen
                        $record->update(['status' => 'on_delivery']);
                        if ($record->salesOrder) {
                            $record->salesOrder->update(['status' => 'shipped']);
                        }
                    });

                    Notification::make()->title('Barang resmi dikirim!')->success()->send();
                }),

                Action::make('upload_proof')
                    ->label('Upload Bukti')
                    ->icon('heroicon-o-camera')
                    ->color('info')
                    ->visible(fn(DeliveryOrder $record) => in_array($record->status, ['on_delivery', 'delivered']))
                    ->form([
                        FileUpload::make('proof_image')->label('Foto Penerimaan')->image()->directory('delivery-proofs')->required(),
                        Textarea::make('proof_notes')->label('Catatan Penerima')->rows(2),
                    ])
                    ->action(function (DeliveryOrder $record, array $data): void {
                        $record->update([
                            'proof_image' => $data['proof_image'],
                            'proof_notes' => $data['proof_notes'],
                            'status' => 'delivered',
                        ]);
                        if ($record->salesOrder) $record->salesOrder->update(['status' => 'completed']);
                        Notification::make()->title('Delivered!')->success()->send();
                    }),

                Action::make('print')->label('Cetak')->icon('heroicon-o-printer')->color('success')->url(fn(DeliveryOrder $record) => route('print.delivery-order', $record))->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            'edit' => Pages\EditDeliveryOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
