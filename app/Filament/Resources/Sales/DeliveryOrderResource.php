<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\DeliveryOrderResource\Pages;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
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
use Illuminate\Support\Facades\DB;
use Closure;
use Throwable;
use App\Filament\Concerns\BelongsToModule;

class DeliveryOrderResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'sales';
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
                Grid::make(['default' => 1, 'sm' => 3])->schema([
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
                            modifyQueryUsing: fn (Builder $query) => $query
                                ->whereIn('status', ['confirmed', 'processing', 'shipped'])
                                ->orderByDesc('created_at')
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->placeholder('Pilih Sales Order')
                        ->disabled(fn ($record) => $record && $record->exists)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) {
                                return;
                            }

                            $so = SalesOrder::with('items')->find($state);

                            if (!$so) {
                                return;
                            }

                            $set('nx_customer_id', $so->nx_customer_id);

                            $existingDOs = DeliveryOrder::with('items')
                                ->where('nx_sales_order_id', $state)
                                ->where('status', '!=', 'cancelled')
                                ->get();

                            $items = $so->items->map(function ($item) use ($existingDOs) {
                                $qtyOrder = (float) ($item->qty ?? 0);
                                $qtyShipped = $existingDOs->flatMap->items
                                    ->where('item_id', $item->item_id)
                                    ->sum('qty');

                                $qtyRemainingQuota = max($qtyOrder - $qtyShipped, 0);

                                return [
                                    'item_type'      => $item->item_type,
                                    'item_id'        => $item->item_id,
                                    'item_code'      => (string) $item->item_code,
                                    'item_name'      => (string) $item->item_name,
                                    'qty_ordered'    => $qtyRemainingQuota,
                                    'qty'            => 0,
                                    'qty_remaining'  => $qtyRemainingQuota,
                                ];
                            })->values()->toArray();

                            $set('items', $items);
                        })
                        ->required()
                        ->prefixIcon('heroicon-o-hashtag'),
                ]),

                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    Select::make('nx_customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user-circle'),

                    Select::make('nx_employee_id')
                        ->label('Ditugaskan Kepada')
                        ->relationship('employee', 'full_name')
                        ->default(fn () => auth()->user()->employee?->id)
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user'),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft'       => 'Draft',
                            'ready'       => 'Siap Kirim',
                            'on_delivery' => 'Dalam Pengiriman',
                            'delivered'   => 'Terkirim',
                            'cancelled'   => 'Dibatalkan',
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

                        Grid::make(['default' => 1, 'sm' => 2])->schema([
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
                Tables\Columns\TextColumn::make('do_number')
                    ->label('No. Surat Jalan')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('salesOrder.order_number')
                    ->label('Ref. Pesanan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('do_date')
                    ->label('Tanggal')
                    ->date('d M Y H:i'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'       => 'gray',
                        'ready'       => 'warning',
                        'on_delivery' => 'info',
                        'delivered'   => 'success',
                        'cancelled'   => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'       => 'Draft',
                        'ready'       => 'Siap Kirim',
                        'on_delivery' => 'Dalam Pengiriman',
                        'delivered'   => 'Diterima',
                        'cancelled'   => 'Batal',
                        default       => $state,
                    }),
            ])
            ->actions([
                Action::make('proses_dan_cetak')
                    ->label('Proses & Cetak DO')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->visible(fn (DeliveryOrder $record) => in_array($record->status, ['draft', 'ready']))
                    ->modalHeading('Scan Barang & Cetak Surat Jalan')
                    ->modalDescription('Lakukan scan untuk barang berserial. Setelah dikonfirmasi, stok akan dipotong dan PDF akan otomatis terbuka.')
                    ->form(fn (DeliveryOrder $record) => static::buildProcessFormSchema($record))
                    ->action(function (DeliveryOrder $record, array $data) {
                        try {
                            static::processDeliveryOrder($record, $data);

                            Notification::make()
                                ->title('Surat Jalan berhasil diproses.')
                                ->success()
                                ->send();

                            return redirect()->route('print.delivery-order', $record);
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Gagal memproses Surat Jalan')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            return null;
                        }
                    }),

                Action::make('upload_proof')
                    ->label('Upload Bukti')
                    ->icon('heroicon-o-camera')
                    ->color('info')
                    ->visible(fn (DeliveryOrder $record) => in_array($record->status, ['on_delivery', 'delivered']))
                    ->form([
                        FileUpload::make('proof_image')
                            ->label('Foto Penerimaan')
                            ->image()
                            ->directory('delivery-proofs')
                            ->required(),

                        Textarea::make('proof_notes')
                            ->label('Catatan Penerima')
                            ->rows(2),
                    ])
                    ->action(function (DeliveryOrder $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'proof_image' => $data['proof_image'],
                                'proof_notes' => $data['proof_notes'],
                                'status'      => 'delivered',
                            ]);

                            if ($record->salesOrder) {
                                $record->salesOrder->update([
                                    'status' => 'completed',
                                ]);
                            }

                            SerialNumber::where('customer_id', $record->nx_customer_id)
                                ->where('status', SerialNumber::STATUS_ON_DELIVERY)
                                ->whereIn('product_id', $record->items->pluck('item_id'))
                                ->update([
                                    'status' => SerialNumber::STATUS_SOLD,
                                ]);
                        });

                        Notification::make()
                            ->title('Delivered!')
                            ->success()
                            ->send();
                    }),

                Action::make('print')
                    ->label('Print Ulang')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->visible(fn (DeliveryOrder $record) => in_array($record->status, ['on_delivery', 'delivered']))
                    ->url(fn (DeliveryOrder $record) => route('print.delivery-order', $record))
                    ->openUrlInNewTab(),

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

    protected static function buildProcessFormSchema(DeliveryOrder $record): array
    {
        $schema = [];

        foreach ($record->items as $item) {
            if ($item->item_type !== 'product') {
                continue;
            }

            $product = \App\Models\Inventory\Product::find($item->item_id);
            $isSerialized = $product ? (bool) $product->is_serialized : false;
            $qtyKirim = (float) $item->qty;

            if (!$isSerialized || $qtyKirim <= 0) {
                continue;
            }

            $schema[] = Forms\Components\Section::make("Scan: {$item->item_name} (Butuh {$qtyKirim} Unit)")
                ->schema([
                    Forms\Components\ViewField::make("camera_sn_{$item->id}")
                        ->label('Scanner Kamera')
                        ->view('filament.forms.components.camera-scanner')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Forms\Set $set, Get $get) use ($item) {
                            if (blank($state)) {
                                return;
                            }

                            $scannedSn = trim($state);
                            $currentText = $get("scanned_sns_{$item->id}") ?? '';
                            $currentArray = array_filter(array_map('trim', explode("\n", $currentText)));

                            if (in_array($scannedSn, $currentArray)) {
                                Notification::make()
                                    ->title("SN {$scannedSn} sudah di-scan.")
                                    ->warning()
                                    ->send();

                                $set("camera_sn_{$item->id}", null);
                                return;
                            }

                            $isValid = SerialNumber::where('serial_number', $scannedSn)
                                ->where('status', SerialNumber::STATUS_AVAILABLE)
                                ->where('product_id', $item->item_id)
                                ->exists();

                            if (!$isValid) {
                                Notification::make()
                                    ->title("SN {$scannedSn} tidak tersedia di gudang.")
                                    ->danger()
                                    ->send();

                                $set("camera_sn_{$item->id}", null);
                                return;
                            }

                            $currentArray[] = $scannedSn;
                            $set("scanned_sns_{$item->id}", implode("\n", $currentArray));

                            Notification::make()
                                ->title("SN {$scannedSn} valid.")
                                ->success()
                                ->send();

                            $set("camera_sn_{$item->id}", null);
                        }),

                    Textarea::make("scanned_sns_{$item->id}")
                        ->label('Daftar SN Terkumpul')
                        ->rows(4)
                        ->required()
                        ->rules([
                            fn () => function (string $attribute, $value, Closure $fail) use ($qtyKirim) {
                                $sns = array_filter(array_map('trim', explode("\n", $value)));

                                if (count($sns) !== (int) $qtyKirim) {
                                    $fail("Barang ini butuh {$qtyKirim} SN, tapi Anda baru men-scan " . count($sns) . " SN.");
                                }
                            },
                        ]),
                ]);
        }

        if (empty($schema)) {
            $schema[] = Forms\Components\Placeholder::make('info')
                ->content('Tidak ada barang berserial yang dikirim pada Surat Jalan ini. Anda bisa langsung klik tombol Submit di bawah.');
        }

        return $schema;
    }

    protected static function processDeliveryOrder(DeliveryOrder $record, array $data): void
    {
        if (!in_array($record->status, ['draft', 'ready'])) {
            throw new \Exception('Status Surat Jalan tidak valid untuk diproses.');
        }

        DB::beginTransaction();

        try {
            $now = now();

            StockTransaction::$autoUpdateStock = false;

            foreach ($record->items as $item) {
                if ($item->item_type !== 'product' || (float) $item->qty <= 0) {
                    continue;
                }

                static::processDeliveryItem($record, $item, $data, $now);
            }

            $record->update([
                'status' => 'on_delivery',
            ]);

            if ($record->salesOrder) {
                $record->salesOrder->update([
                    'status' => 'shipped',
                ]);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            StockTransaction::$autoUpdateStock = true;
        }
    }

    protected static function processDeliveryItem(
        DeliveryOrder $record,
        $item,
        array $data,
        $now
    ): void {
        $stocks = ProductStock::where('product_id', $item->item_id)
            ->where('qty_reserved', '>', 0)
            ->orderBy('warehouse_id')
            ->lockForUpdate()
            ->get();

        $qtyKirim = (float) $item->qty;

        if ($qtyKirim <= 0) {
            return;
        }

        $totalReserved = (float) $stocks->sum('qty_reserved');

        if ($totalReserved < $qtyKirim) {
            throw new \Exception("Stok reserved tidak mencukupi untuk {$item->item_name}");
        }

        // ===== HANDLE SN =====
        $snsArray = static::handleSerializedProduct($record, $item, $data, $now);

        $remaining = $qtyKirim;
        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $qtyDelivered = min((float) $stock->qty_reserved, $remaining);
            $stockBefore = (float) $stock->qty_reserved;

            $stock->decrement('qty_reserved', $qtyDelivered);
            $stock->increment('sold_stock', $qtyDelivered);

            StockTransaction::create([
                'product_id'       => $item->item_id,
                'warehouse_id'     => $stock->warehouse_id,
                'transaction_code' => static::generateStockTransactionCode($now),
                'mutation_type'    => 'delivery',
                'transaction_date' => $now,
                'quantity'         => $qtyDelivered,
                'stock_before'     => $stockBefore,
                'stock_after'      => $stockBefore - $qtyDelivered,
                'type'             => 'keluar',
                'reference_id'     => $record->id,
                'reference_type'   => DeliveryOrder::class,
                'reference_number' => $record->do_number,
                'notes'            => "DO {$record->do_number} - Reserved ke Sold",
                'created_by'       => auth()->id(),
            ]);

            $remaining -= $qtyDelivered;
        }

        if ($remaining > 0) {
            throw new \Exception("Stok reserved tidak mencukupi untuk {$item->item_name}");
        }
    }

    protected static function handleSerializedProduct(
        DeliveryOrder $record,
        $item,
        array $data,
        $now
    ): array {
        $product = \App\Models\Inventory\Product::find($item->item_id);

        if (!$product || !$product->is_serialized) {
            return [];
        }

        $fieldName = "scanned_sns_{$item->id}";

        if (!isset($data[$fieldName])) {
            throw new \Exception("SN wajib diinput untuk {$item->item_name}");
        }

        $snsArray = array_values(array_unique(array_filter(
            array_map('trim', explode("\n", $data[$fieldName]))
        )));

        if (count($snsArray) !== (int) $item->qty) {
            throw new \Exception("Jumlah SN tidak sesuai untuk {$item->item_name}");
        }

        $snModels = SerialNumber::whereIn('serial_number', $snsArray)
            ->where('product_id', $item->item_id)
            ->lockForUpdate()
            ->get();

        if ($snModels->count() !== count($snsArray)) {
            throw new \Exception("Ada SN yang tidak valid.");
        }

        foreach ($snModels as $sn) {
            if ($sn->status !== SerialNumber::STATUS_AVAILABLE) {
                throw new \Exception("SN {$sn->serial_number} tidak tersedia.");
            }

            $sn->update([
                'status'        => SerialNumber::STATUS_ON_DELIVERY,
                'customer_id'   => $record->nx_customer_id,
                'outbound_date' => $now->toDateString(),
            ]);
        }

        return $snsArray;
    }

    protected static function generateStockTransactionCode($now): string
    {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][$now->month - 1];
        $prefix = "%/ST-OUT/NEX/{$roman}/" . $now->year;

        $last = StockTransaction::where('transaction_code', 'like', $prefix)
            ->orderByDesc('id')
            ->value('transaction_code');

        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;

        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/ST-OUT/NEX/{$roman}/" . $now->year;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            'edit'   => Pages\EditDeliveryOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
