<?php
namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\SalesOrderResource\Pages;
use App\Models\Sales\PromoCode;
use App\Models\Sales\Quotation;
use App\Models\Sales\SalesOrder;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Manajemen Sales';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'sales/sales-orders';

    protected static ?string $pluralModelLabel = 'Pesanan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Pesanan')->schema([
                Grid::make(3)->schema([
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
                        ->helperText('Opsional')
                        ->maxLength(50),
                ]),

                Grid::make(2)->schema([
                    Select::make('nx_quotation_id')
                        ->label('No. Penawaran (Ref)')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->getSearchResultsUsing(fn(string $search) => Quotation::query()
                            ->where('status', 'accepted')
                            ->whereDoesntHave('salesOrder')
                            ->where('quotation_number', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('quotation_number', 'id'))
                        ->getOptionLabelUsing(fn($value): ?string => Quotation::find($value)?->quotation_number)
                        ->options(fn() => Quotation::query()
                            ->where('status', 'accepted')
                            ->whereDoesntHave('salesOrder')
                            ->orderByDesc('created_at')
                            ->limit(50)
                            ->pluck('quotation_number', 'id'))
                        ->disabled(fn($record) => $record && $record->exists)
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if (!$state) {
                                $set('nx_customer_id', null);
                                $set('items', []);
                                $set('subtotal', 0);
                                $set('tax', 0);
                                $set('discount_amount', 0);
                                $set('grand_total', 0);
                                $set('promo_code_id', null);
                                $set('promo_code_input', null);
                                return;
                            }

                            $quotation = Quotation::with('items', 'promoCode', 'deal')->find($state);
                            if (!$quotation) {
                                return;
                            }

                            // 1. Copy Header
                            $customerId = $quotation->nx_customer_id ?? $quotation->deal?->nx_customer_id;
                            $set('nx_customer_id', $customerId);

                            // 2. Copy Pajak
                            $taxPercent = (float) ($quotation->tax ?? 0);
                            $set('tax', $taxPercent);

                            // 3. Setup Variabel Kalkulasi
                            $calculatedSubtotal = 0;

                            // Setup Promo (Data Only)
                            $discountType = null;
                            $discountValue = 0;

                            if ($quotation->promo_code_id) {
                                $set('promo_code_id', $quotation->promo_code_id);
                                $set('promo_code_input', $quotation->promoCode?->code);
                                if ($quotation->promoCode) {
                                    $discountType = $quotation->promoCode->type;
                                    $discountValue = (float) $quotation->promoCode->value;
                                    $set('temp_discount_type', $discountType);
                                    $set('temp_discount_value', $discountValue);
                                }
                            } else {
                                $set('promo_code_id', null);
                                $set('promo_code_input', null);
                                $set('temp_discount_type', null);
                                $set('temp_discount_value', 0);
                            }

                            // 4. Map Items & Hitung Subtotal Manual
                            $items = $quotation->items->map(function ($item) use (&$calculatedSubtotal) {
                                $qty = (float) $item->qty;
                                $price = (float) $item->unit_price;
                                $lineTotal = (float) $item->line_total ?: $qty * $price;

                                $calculatedSubtotal += $lineTotal;

                                return [
                                    'item_type' => $item->item_type ?? 'product',
                                    'item_id' => $item->item_id,
                                    'item_code' => $item->item_code,
                                    'item_name' => $item->item_name,
                                    'qty' => $qty,
                                    'unit_price' => $price,
                                    'line_total' => $lineTotal,
                                ];
                            })->toArray();

                            $set('items', $items);
                            $set('subtotal', $calculatedSubtotal);

                            // 5. Hitung Diskon Manual
                            $calculatedDiscount = 0;
                            if ($discountType === 'percentage') {
                                $calculatedDiscount = $calculatedSubtotal * ($discountValue / 100);
                            } elseif ($discountType === 'fixed') {
                                $calculatedDiscount = $discountValue;
                            }
                            $calculatedDiscount = min($calculatedDiscount, $calculatedSubtotal);
                            $set('discount_amount', $calculatedDiscount);

                            // 6. Hitung Grand Total Manual
                            $afterDiscount = $calculatedSubtotal - $calculatedDiscount;
                            $taxAmount = $afterDiscount * ($taxPercent / 100);
                            $grandTotal = $afterDiscount + $taxAmount;

                            $set('grand_total', $grandTotal);
                        })
                        ->prefixIcon('heroicon-o-hashtag'),

                    Select::make('nx_customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required()
                        ->live()
                        ->disabled(fn(Get $get) => filled($get('nx_quotation_id')))
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-user-circle'),
                ]),

                Grid::make(2)->schema([
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
                            'confirmed' => 'Dikonfirmasi',
                            'processing' => 'Diproses',
                            'shipped' => 'Dikirim',
                            'completed' => 'Selesai',
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

            Section::make('Daftar Item Pesanan')->schema([
                Repeater::make('items')
                    ->schema([
                        Hidden::make('item_type')->default('product')->dehydrated(true),
                        Hidden::make('item_id')->dehydrated(true),

                        Grid::make(2)->schema([
                            TextInput::make('item_code')
                                ->label('Kode Product')
                                ->disabled()
                                ->dehydrated(true),

                            TextInput::make('item_name')
                                ->label('Nama Product')
                                ->disabled()
                                ->dehydrated(true),

                            TextInput::make('qty')
                                ->label('Qty')
                                ->numeric()
                                ->integer()
                                ->default(1)
                                ->minValue(1)
                                ->readOnly(fn(Get $get) => filled($get('../../nx_quotation_id')))
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if ($state < 1) {
                                        $set('qty', 1);
                                    }

                                    self::updateItemTotal($get, $set);
                                    self::updateTotals($get, $set);
                                }),

                            TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->required()
                                ->prefix('IDR')
                                ->formatStateUsing(fn($state) => (int) $state)
                                ->readOnly(fn(Get $get) => filled($get('../../nx_quotation_id')))
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    self::updateItemTotal($get, $set);
                                    self::updateTotals($get, $set);
                                }),

                            TextInput::make('line_total')
                                ->label('Subtotal')
                                ->disabled()
                                ->dehydrated()
                                ->numeric()
                                ->prefix('IDR')
                                ->extraInputAttributes(['style' => 'font-weight: bold;'])
                                ->formatStateUsing(fn($state) => (int) $state),
                        ]),
                    ])
                    ->relationship()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->relationship()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
            ])->collapsed(),

            Section::make('Perhitungan Akhir')->schema([
                Grid::make(4)->schema([
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('IDR')
                        ->formatStateUsing(fn($state) => (int) $state),

                    TextInput::make('promo_code_input')
                        ->label('Kode Promo')
                        ->placeholder('Masukkan kode promo')
                        ->dehydrated(false)
                        ->disabled(fn(Get $get) => filled($get('nx_quotation_id')))
                        ->formatStateUsing(fn($record) => $record?->promoCode?->code)
                        ->suffixAction(
                            FormAction::make('apply_promo')
                                ->icon('heroicon-m-ticket')
                                ->color('success')
                                ->label('Apply')
                                ->action(function ($state, Set $set, Get $get) {
                                    if (empty($state)) {
                                        $set('promo_code_id', null);
                                        $set('temp_discount_type', null);
                                        $set('temp_discount_value', 0);
                                        self::updateTotals($get, $set);
                                        return;
                                    }

                                    // UPDATE LOGIC PROMO (Sesuai perbaikan sebelumnya)
                                    $promo = PromoCode::where('code', $state)
                                        ->where('is_active', 1)
                                        ->whereDate('start_date', '<=', now())
                                        ->whereDate('end_date', '>=', now())
                                        ->first();

                                    if (!$promo) {
                                        Notification::make()->title('Kode tidak valid / expired!')->danger()->send();
                                        $set('promo_code_id', null);
                                        $set('temp_discount_type', null);
                                        $set('temp_discount_value', 0);
                                    } else {
                                        Notification::make()->title("Promo '{$promo->code}' berhasil diterapkan!")->success()->send();
                                        $set('promo_code_id', $promo->id);
                                        $set('temp_discount_type', $promo->type);
                                        $set('temp_discount_value', (float) $promo->value);
                                    }
                                    self::updateTotals($get, $set);
                                })
                        ),

                    Hidden::make('promo_code_id'),
                    Hidden::make('temp_discount_type')->dehydrated(false),
                    Hidden::make('temp_discount_value')->dehydrated(false),

                    TextInput::make('discount_amount')
                        ->label('Potongan')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('IDR')
                        ->formatStateUsing(fn($state) => (int) $state),

                    TextInput::make('tax')
                        ->label('Pajak (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->readOnly(fn(Get $get) => filled($get('nx_quotation_id')))
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if ($state < 0) {
                                $set('tax', 0);
                            }

                            self::updateTotals($get, $set);
                        })
                        // UPDATE: Pajak boleh float
                        ->formatStateUsing(fn($state) => (float) $state)
                        ->prefixIcon('heroicon-o-receipt-percent'),

                    TextInput::make('grand_total')
                        ->label('Total')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('IDR')
                        ->extraInputAttributes(['style' => 'font-weight: bold;'])
                        ->formatStateUsing(fn($state) => (int) $state),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('No. Pesanan')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer_po_number')
                    ->label('PO Customer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('quotation.quotation_number')
                    ->label('Ref. Penawaran')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                // FIX: Pastikan relasi promoCode ada di Model SalesOrder
                Tables\Columns\TextColumn::make('promoCode.code')
                    ->label('Promo')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR', true)
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'confirmed' => 'primary',
                        'shipped' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'processing' => 'Sedang Diproses',
                        'confirmed' => 'Dikonfirmasi',
                        'shipped' => 'Dalam Pengiriman',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        DatePicker::make('created_until')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'view' => Pages\ViewSalesOrder::route('/{record}'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function updateItemTotal(Get $get, Set $set): void
    {
        $qty = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items');

        if (is_array($items)) {
            $pathPrefix = '';
        } else {
            $pathPrefix = '../../';
            $items = $get($pathPrefix . 'items') ?? [];
        }

        $subtotal = collect($items)->sum(
            fn($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
        $set($pathPrefix . 'subtotal', $subtotal);

        $discountType = $get($pathPrefix . 'temp_discount_type');
        $discountValue = (float) $get($pathPrefix . 'temp_discount_value');

        if (!$discountType && $get($pathPrefix . 'promo_code_id')) {
            $promo = PromoCode::find($get($pathPrefix . 'promo_code_id'));
            if ($promo) {
                $discountType = $promo->type;
                $discountValue = (float) $promo->value;
                $set($pathPrefix . 'temp_discount_type', $discountType);
                $set($pathPrefix . 'temp_discount_value', $discountValue);
                $set($pathPrefix . 'promo_code_input', $promo->code);
            }
        }

        $totalDiscount = 0;
        if ($discountType === 'percentage') {
            $totalDiscount = $subtotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $totalDiscount = $discountValue;
        }

        if ($totalDiscount > $subtotal) {
            $totalDiscount = $subtotal;
        }

        $set($pathPrefix . 'discount_amount', $totalDiscount);

        $taxPercent = (float) ($get($pathPrefix . 'tax') ?? 0);
        $afterDiscount = $subtotal - $totalDiscount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);

        $set($pathPrefix . 'grand_total', $afterDiscount + $taxAmount);
    }
}
