<?php
namespace App\Filament\Resources\Sales;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\Sales\SalesOrderResource\Pages;
use App\Models\Inventory\Package;
use App\Models\Inventory\Product;
use App\Models\Inventory\Service;
use App\Models\Sales\Quotation;
use App\Models\Sales\SalesOrder;
use App\Services\Sales\QuotationService;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module           = 'sales';
    protected static ?string $model            = SalesOrder::class;
    protected static ?string $navigationIcon   = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup  = 'Manajemen Sales';
    protected static ?int $navigationSort      = 3;
    protected static ?string $slug             = 'sales/sales-orders';
    protected static ?string $pluralModelLabel = 'Pesanan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Informasi Pesanan')->schema([
                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                        TextInput::make('order_number')
                            ->label('No. Pesanan')
                            ->disabled()
                            ->dehydrated()
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-o-hashtag'),
                        DatePicker::make('order_date')
                            ->label('Tanggal Pesanan')
                            ->default(now())
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false),
                        TextInput::make('customer_po_number')
                            ->label('No. PO Customer')
                            ->placeholder('Contoh: PO-ABC-001')
                            ->maxLength(50)
                            ->required(fn(Get $get) => blank($get('nx_quotation_id')))
                            ->dehydrated(),
                        Select::make('nx_quotation_id')
                            ->label('No. Penawaran (Ref)')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn(Get $get) => blank($get('customer_po_number')))
                            ->options(function (?SalesOrder $record) {
                                return Quotation::query()
                                    ->where('status', 'accepted')
                                    ->where(function ($query) use ($record) {
                                        $query->whereDoesntHave('salesOrder');
                                        if ($record && $record->nx_quotation_id) {
                                            $query->orWhere('id', $record->nx_quotation_id);
                                        }
                                    })
                                    ->pluck('quotation_number', 'id');
                            })
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (! $state) {
                                    return;
                                }

                                $quotation = Quotation::with('items', 'promoCode')->find($state);
                                if (! $quotation) {
                                    return;
                                }

                                $set('nx_customer_id', $quotation->nx_customer_id ?? $quotation->deal?->nx_customer_id);
                                $set('tax', (float) $quotation->tax);

                                // Mapping items agar otomatis muncul dari penawaran
                                $items = $quotation->items->map(fn($item) => [
                                    'item_id'    => $item->item_id,
                                    'item_code'  => $item->item_code,
                                    'item_name'  => $item->item_name,
                                    'qty'        => $item->qty,
                                    'unit_price' => $item->unit_price,
                                    'line_total' => $item->line_total,
                                    'item_type'  => $item->item_type ?? 'product',
                                ])->toArray();

                                $set('items', $items);
                                self::updateTotals($get, $set);
                            }),
                        Select::make('nx_customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->required()
                            ->live()
                            ->disabled(fn(Get $get) => filled($get('nx_quotation_id')))
                            ->dehydrated(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft'      => 'Draft',
                                'confirmed'  => 'Dikonfirmasi',
                                'processing' => 'Diproses',
                                'shipped'    => 'Dikirim',
                                'completed'  => 'Selesai',
                                'cancelled'  => 'Dibatalkan',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),
                    ]),
                ]),
                Section::make('Daftar Item Pesanan')->schema([
                    Repeater::make('items')
                    // NOTE: Kita hapus ->relationship() agar bisa handle saving manual di Service
                    // Ini memecahkan masalah data tidak tersimpan saat dimanipulasi di backend
                        ->schema(self::getOrderItemsSchema())
                        ->live()
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set))
                        ->addable(fn(Get $get) => blank($get('nx_quotation_id')))
                        ->deletable(fn(Get $get) => blank($get('nx_quotation_id')))
                        ->reorderable(false),
                ])->collapsible(),
            ])->columnSpan(['lg' => 2]),

            Group::make()
                ->columns(['default' => 2]) // langsung di sini
                ->schema([
                    Section::make('Catatan')
                        ->columnSpan(1)
                        ->schema([
                            Textarea::make('notes')->label('Catatan Pesanan')->rows(3),
                        ]),
                    Section::make('Ringkasan Harga')
                        ->columnSpan(1)
                        ->schema([
                            TextInput::make('promo_code_input')
                                ->label('Kode Promo')
                                ->placeholder('Masukkan kode')
                                ->disabled(fn(Get $get) => filled($get('nx_quotation_id')))
                                ->suffixAction(
                                    FormAction::make('apply_promo')
                                        ->icon('heroicon-m-ticket')
                                        ->color('success')
                                        ->action(fn($state, Set $set, Get $get) => self::applyPromo($state, $set, $get))
                                ),
                            Hidden::make('promo_code_id'),
                            Hidden::make('temp_discount_type'),
                            Hidden::make('temp_discount_value'),
                            TextInput::make('subtotal')->label('Subtotal')->disabled()->dehydrated()->prefix('IDR'),
                            TextInput::make('discount_amount')->label('Potongan')->disabled()->dehydrated()->prefix('IDR'),
                            TextInput::make('tax')
                                ->label('Pajak PPN (%)')
                                ->numeric()
                                ->default(0)
                                ->live()
                                ->disabled(fn(Get $get) => filled($get('nx_quotation_id')))
                                ->dehydrated()
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                            TextInput::make('grand_total')
                                ->label('Total Akhir')
                                ->disabled()
                                ->dehydrated()
                                ->prefix('IDR')
                                ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: bold; color: green;']),
                        ]),
                ])->columnSpan(['lg' => 2]),
        ])->columns(['default' => 1, 'md' => 3]);
    }

    public static function getOrderItemsSchema(): array
    {
        return [
            Grid::make(['default' => 1, 'sm' => 4])->schema([
                Select::make('item_type')
                    ->label('Tipe')
                    ->options(['product' => 'Product', 'service' => 'Service', 'package' => 'Package'])
                    ->default('product')
                    ->reactive()
                    ->required()
                    ->disabled(fn(Get $get) => filled($get('../../nx_quotation_id')))
                    ->dehydrated()
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('item_id', null);
                        $set('item_code', null);
                        $set('item_name', null);
                        $set('unit_price', 0);
                        $set('line_total', 0);
                        $set('qty', 1);
                        self::updateTotals($get, $set);
                    }),
                Select::make('item_id')
                    ->label('Pilih Item')
                    ->options(function (Get $get) {
                        $type = $get('item_type');
                        return match ($type) {
                            'product' => Product::query()->pluck('product_name', 'id'),
                            'service' => Service::query()->pluck('service_name', 'id'),
                            'package' => Package::query()->pluck('package_name', 'id'),
                            default   => [],
                        };
                    })
                    ->getOptionLabelUsing(function ($value, Get $get) {
                        $type       = $get('item_type');
                        $modelClass = match ($type) {
                            'product' => Product::class,
                            'service' => Service::class,
                            'package' => Package::class,
                            default   => null
                        };
                        if (! $modelClass || ! $value) {
                            return null;
                        }

                        $record = $modelClass::find($value);
                        return $record?->product_name ?? $record?->service_name ?? $record?->package_name ?? $record?->name;
                    })
                    ->visible(fn(Get $get) => ! empty($get('item_type')))
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->disabled(fn(Get $get) => filled($get('../../nx_quotation_id')))
                    ->dehydrated()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (! $state) {
                            return;
                        }

                        $type  = $get('item_type');
                        $model = match ($type) {
                            'product' => Product::find($state),
                            'service' => Service::find($state),
                            'package' => Package::find($state),
                            default   => null
                        };
                        if ($model) {
                            $name      = $model->product_name ?? $model->service_name ?? $model->package_name ?? $model->name;
                            $code      = $model->product_code ?? $model->service_code ?? $model->package_code ?? $model->code ?? 'CODE-' . $state;
                            $sellPrice = match ($type) {
                                'product' => (float) ($model->selling_price ?? $model->price ?? 0),
                                'service' => (float) ($model->price ?? 0),
                                'package' => (float) ($model->total_price ?? 0),
                                default   => 0
                            };
                            $set('item_name', $name);
                            $set('item_code', $code);
                            $set('unit_price', $sellPrice);
                            self::updateItemTotal($get, $set);
                        }
                    }),
                TextInput::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->integer()
                    ->default(1)
                    ->minValue(1)
                    ->live(onBlur: true)
                    ->readOnly(fn(Get $get) => filled($get('../../nx_quotation_id')))
                    ->helperText(function (Get $get) {
                        $type   = $get('item_type');
                        $itemId = $get('item_id');
                        if ($type !== 'product' || ! $itemId) {
                            return null;
                        }

                        $warehouseId = \App\Models\Inventory\Warehouse::where('warehouse_name', 'Gudang Utama')->value('id') ?? 1;
                        $stock       = \App\Models\Inventory\ProductStock::where('product_id', $itemId)
                            ->where('warehouse_id', $warehouseId)
                            ->first();

                        if (! $stock) {
                            return '⚠️ Stok tidak ditemukan';
                        }

                        $avail = (float) $stock->qty_available;
                        return "Stok tersedia: {$avail}";
                    })
                    ->maxValue(function (Get $get) {
                        $type   = $get('item_type');
                        $itemId = $get('item_id');
                        if ($type !== 'product' || ! $itemId) {
                            return null;
                        }

                        $warehouseId = \App\Models\Inventory\Warehouse::where('warehouse_name', 'Gudang Utama')->value('id') ?? 1;
                        $stock       = \App\Models\Inventory\ProductStock::where('product_id', $itemId)
                            ->where('warehouse_id', $warehouseId)
                            ->value('qty_available');

                        return $stock ? (int) $stock : null;
                    })
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if ($state < 1) {
                            $set('qty', 1);
                        }

                        self::updateItemTotal($get, $set);
                    }),
                TextInput::make('unit_price')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->prefix('IDR')
                    ->required()
                    ->minValue(0)
                    ->readOnly(fn(Get $get) => filled($get('../../nx_quotation_id')))
                    ->formatStateUsing(fn($state) => (int) $state)
                    ->reactive()
                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateItemTotal($get, $set)),
            ]),
            Grid::make(['default' => 1, 'sm' => 3])->schema([
                TextInput::make('item_code')
                    ->label('Kode Item')
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                TextInput::make('item_name')
                    ->label('Nama Item')
                    ->disabled()
                    ->dehydrated(true),
                TextInput::make('line_total')
                    ->label('Subtotal Baris')
                    ->numeric()
                    ->prefix('IDR')
                    ->required()
                    ->minValue(0)
                    ->disabled()
                    ->dehydrated()
                    ->extraInputAttributes(['style' => 'font-weight: bold;'])
                    ->formatStateUsing(fn($state) => (int) $state),
            ]),
        ];
    }

    public static function updateItemTotal(Get $get, Set $set): void
    {
        $qty   = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);
        self::updateTotals($get, $set);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items      = $get('items');
        $pathPrefix = '';

        if ($items === null) {
            $pathPrefix = '../../';
            $items      = $get('../../items') ?? [];
        }

        $subtotal = collect($items)->sum(fn($i) => (float) ($i['qty'] ?? 0) * (float) ($i['unit_price'] ?? 0));
        $set($pathPrefix . 'subtotal', $subtotal);

        $discountType  = $get($pathPrefix . 'temp_discount_type');
        $discountValue = (float) $get($pathPrefix . 'temp_discount_value');

        $totalDiscount = $discountType === 'percentage' ? $subtotal * ($discountValue / 100) : $discountValue;
        $totalDiscount = min($totalDiscount, $subtotal);
        $set($pathPrefix . 'discount_amount', $totalDiscount);

        $taxPercent    = (float) ($get($pathPrefix . 'tax') ?? 0);
        $afterDiscount = $subtotal - $totalDiscount;
        $set($pathPrefix . 'grand_total', $afterDiscount + ($afterDiscount * ($taxPercent / 100)));
    }

    public static function applyPromo($code, Set $set, Get $get): void
    {
        if (empty($code)) {
            $set('promo_code_id', null);
            $set('temp_discount_type', null);
            $set('temp_discount_value', 0);
            self::updateTotals($get, $set);
            return;
        }

        $service = app(QuotationService::class);
        $promo   = $service->validatePromoCode($code);

        if (! $promo) {
            Notification::make()->title('Kode tidak valid!')->danger()->send();
            $set('promo_code_id', null);
        } else {
            Notification::make()->title('Promo Berhasil!')->success()->send();
            $set('promo_code_id', $promo->id);
            $set('temp_discount_type', $promo->type);
            $set('temp_discount_value', $promo->value);
        }
        self::updateTotals($get, $set);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('No. Pesanan')->sortable()->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('reference_display')
                    ->label('Referensi')
                    ->getStateUsing(function (SalesOrder $record): string {
                        $refs = [];

                        if ($record->quotation?->quotation_number) {
                            $refs[] = '' . $record->quotation->quotation_number;
                        }

                        if ($record->customer_po_number) {
                            $refs[] = '' . $record->customer_po_number;
                        }

                        return ! empty($refs) ? implode(' | ', $refs) : '-';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('customer_po_number', 'like', "%{$search}%")
                            ->orWhereHas('quotation', function (Builder $q) use ($search) {
                                $q->where('quotation_number', 'like', "%{$search}%");
                            });
                    }),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order_date')->label('Tanggal')->dateTime('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('grand_total')->label('Total')->money('IDR', true)->weight('semibold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft'      => 'gray',
                        'processing' => 'warning',
                        'confirmed'  => 'primary',
                        'shipped'    => 'info',
                        'completed'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'view'   => Pages\ViewSalesOrder::route('/{record}'),
            'edit'   => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
