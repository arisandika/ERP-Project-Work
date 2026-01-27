<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\SalesOrderResource\Pages;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\Quotation;
use App\Models\Sales\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'sales/sales-order';
    protected static ?string $pluralModelLabel = 'Pesanan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // --- SECTION 1: HEADER ---
            Section::make('Informasi Pesanan')->schema([
                Grid::make(3)->schema([
                    TextInput::make('order_number')
                        ->label('Nomor Pesanan')
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-o-hashtag'),

                    DatePicker::make('order_date')
                        ->label('Tanggal Pesanan')
                        ->default(now())
                        ->required()
                        ->prefixIcon('heroicon-o-calendar-days'),

                    TextInput::make('customer_po_number')
                        ->label('No. PO Customer')
                        ->placeholder('Contoh: PO-ABC-001')
                        ->helperText('Opsional')
                        ->maxLength(50),
                ]),

                // --- LOGIC COPY QUOTATION ---
                Grid::make(1)->schema([
                    Select::make('nx_quotation_id')
                        ->label('No. Penawaran (Ref)')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->getSearchResultsUsing(fn (string $search) => Quotation::query()
                            ->where('status', 'accepted')
                            ->whereDoesntHave('salesOrder')
                            ->where('quotation_number', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('quotation_number', 'id'))
                        ->getOptionLabelUsing(fn ($value): ?string => Quotation::find($value)?->quotation_number)
                        ->options(fn () => Quotation::query()
                            ->where('status', 'accepted')
                            ->whereDoesntHave('salesOrder')
                            ->orderByDesc('created_at')
                            ->limit(50)
                            ->pluck('quotation_number', 'id'))
                        ->disabled(fn ($record) => $record && $record->exists)
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if (! $state) {
                                // Reset semua jika dihapus
                                $set('items', []);
                                $set('subtotal', 0);
                                $set('tax', 0);
                                $set('discount_amount', 0);
                                $set('grand_total', 0);
                                $set('promo_code_id', null);
                                $set('promo_code_input', null);
                                return;
                            }

                            $quotation = Quotation::with('items', 'promoCode')->find($state);
                            if (! $quotation) return;

                            // 1. Copy Header
                            $set('nx_customer_id', $quotation->nx_customer_id);

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
                                $qty   = (float) $item->qty;
                                $price = (float) $item->unit_price;
                                $lineTotal = (float) $item->line_total ?: $qty * $price;

                                $calculatedSubtotal += $lineTotal;

                                return [
                                    'item_type'  => $item->item_type ?? 'product',
                                    'item_id'    => $item->item_id,
                                    'item_code'  => $item->item_code,
                                    'item_name'  => $item->item_name,
                                    'qty'        => $qty,
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
                            $taxAmount     = $afterDiscount * ($taxPercent / 100);
                            $grandTotal    = $afterDiscount + $taxAmount;

                            $set('grand_total', $grandTotal);
                        }),
                ]),

                Grid::make(3)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required()
                        ->disabled(fn (Get $get) => filled($get('nx_quotation_id')))
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-user-circle'),

                    Select::make('nx_employee_id')
                        ->label('Dibuat Oleh')
                        ->relationship('employee', 'full_name')
                        ->default(fn () => auth()->user()->employee?->id)
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user'),

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
                        ->prefixIcon('heroicon-o-adjustments-vertical'),
                ]),

                Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->columnSpanFull(),
            ])->columns(1),

            // --- SECTION 2: ITEMS ---
            Section::make('Daftar Item Pesanan')->schema([
                Repeater::make('items')
                    ->relationship()
                    // KUNCI: Tidak bisa tambah/hapus kalau dari SQ
                    ->addable(fn (Get $get) => blank($get('nx_quotation_id')))
                    ->deletable(fn (Get $get) => blank($get('nx_quotation_id')))
                    ->reorderable(false)
                    ->schema([
                        Hidden::make('item_type')->default('product')->dehydrated(true),
                        Hidden::make('item_id')->dehydrated(true),
                        Hidden::make('item_code')->dehydrated(true),

                        TextInput::make('item_name')
                            ->label('Nama Item')
                            ->readOnly()
                            ->dehydrated()
                            ->columnSpanFull(),

                        Grid::make(3)->schema([
                            TextInput::make('qty')
                                ->label('Jumlah')
                                ->numeric()
                                ->integer()
                                ->default(1)
                                ->minValue(1)
                                // KUNCI: Readonly kalau dari SQ
                                ->readOnly(fn (Get $get) => filled($get('../../nx_quotation_id')))
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if ($state < 1) $set('qty', 1);
                                    self::updateItemTotal($get, $set);
                                    self::updateTotals($get, $set);
                                }),

                            TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                // UPDATE: Format tampilan integer
                                ->formatStateUsing(fn ($state) => (int) $state)
                                ->readOnly(fn (Get $get) => filled($get('../../nx_quotation_id')))
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    self::updateItemTotal($get, $set);
                                    self::updateTotals($get, $set);
                                }),

                            TextInput::make('line_total')
                                ->label('Subtotal')
                                ->numeric()
                                ->dehydrated()
                                ->readOnly()
                                ->prefix('Rp')
                                // UPDATE: Format tampilan integer
                                ->formatStateUsing(fn ($state) => (int) $state),
                        ]),
                    ])
                    ->reactive()
                    ->columns(1)
                    ->createItemButtonLabel('Tambah Item Manual'),
            ])->collapsed(),

            // --- SECTION 3: TOTALS ---
            Section::make('Perhitungan Akhir')->schema([
                Grid::make(4)->schema([
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('Rp')
                        // UPDATE: Format tampilan integer
                        ->formatStateUsing(fn ($state) => (int) $state),

                    TextInput::make('promo_code_input')
                        ->label('Kode Promo')
                        ->placeholder('Masukkan kode')
                        ->dehydrated(false)
                        ->disabled(fn (Get $get) => filled($get('nx_quotation_id')))
                        ->formatStateUsing(fn ($record) => $record?->promoCode?->code)
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
                        ->label('Potongan (Rp)')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('Rp')
                        // UPDATE: Format tampilan integer
                        ->formatStateUsing(fn ($state) => (int) $state),

                    TextInput::make('tax')
                        ->label('Pajak (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->readOnly(fn (Get $get) => filled($get('nx_quotation_id')))
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if ($state < 0) $set('tax', 0);
                            self::updateTotals($get, $set);
                        })
                        // UPDATE: Pajak boleh float
                        ->formatStateUsing(fn ($state) => (float) $state)
                        ->prefixIcon('heroicon-o-receipt-percent'),

                    TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('Rp')
                        ->extraInputAttributes(['style' => 'font-weight: bold; color: #16a34a;'])
                        // UPDATE: Format tampilan integer
                        ->formatStateUsing(fn ($state) => (int) $state),
                ]),
            ]),
        ]);
    }

    // --- LOGIC HITUNG ---
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
            fn ($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0)
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

        if ($totalDiscount > $subtotal) $totalDiscount = $subtotal;
        $set($pathPrefix . 'discount_amount', $totalDiscount);

        $taxPercent = (float) ($get($pathPrefix . 'tax') ?? 0);
        $afterDiscount = $subtotal - $totalDiscount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);

        $set($pathPrefix . 'grand_total', $afterDiscount + $taxAmount);
    }

    // --- TABLE ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Nomor SO')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer_po_number')->label('PO Customer')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('quotation.quotation_number')->label('Ref. SQ')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order_date')->label('Tgl Pesan')->date('d M Y'),

                // FIX: Pastikan relasi promoCode ada di Model SalesOrder
                Tables\Columns\TextColumn::make('promoCode.code')
                    ->label('Promo')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('grand_total')->label('Total')->money('IDR', true),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray', 'processing' => 'warning', 'confirmed' => 'primary', 'shipped' => 'info', 'completed' => 'success', 'cancelled' => 'danger', default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([Tables\Filters\TrashedFilter::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }
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
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
