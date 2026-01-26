<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\SalesOrderResource\Pages;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\Quotation;
use App\Models\Sales\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
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
                        // Ganti relationship() dengan options() manual agar query filter 100% jalan
                        ->getSearchResultsUsing(fn (string $search) => Quotation::query()
                            ->where('status', 'approved')
                            ->whereDoesntHave('salesOrder') // Filter yang belum punya SO
                            ->where('quotation_number', 'like', "%{$search}%") // Logic search manual
                            ->limit(50)
                            ->pluck('quotation_number', 'id'))
                        ->getOptionLabelUsing(fn ($value): ?string => Quotation::find($value)?->quotation_number)
                        // Options awal (saat belum search)
                        ->options(fn () => Quotation::query()
                            ->where('status', 'approved')
                            ->whereDoesntHave('salesOrder')
                            ->orderByDesc('created_at') // Biar yang baru muncul duluan
                            ->limit(50)
                            ->pluck('quotation_number', 'id'))

                        ->disabled(fn ($record) => $record && $record->exists)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) {
                                // Reset jika SQ dihapus user
                                $set('items', []);
                                return;
                            }

                            $quotation = Quotation::with('items', 'promoCode')->find($state);
                            if (! $quotation) return;

                            // 1. Copy Header
                            $set('nx_customer_id', $quotation->nx_customer_id);

                            // 2. Copy Promo Code
                            if ($quotation->promo_code_id) {
                                $set('promo_code_id', $quotation->promo_code_id);
                                $set('promo_code_input', $quotation->promoCode?->code);
                                if ($quotation->promoCode) {
                                    $set('temp_discount_type', $quotation->promoCode->type);
                                    $set('temp_discount_value', $quotation->promoCode->value);
                                }
                            } else {
                                $set('promo_code_id', null);
                                $set('promo_code_input', null);
                                $set('temp_discount_type', null);
                                $set('temp_discount_value', 0);
                            }

                            // 3. Copy Pajak
                            $taxPercent = (float) ($quotation->tax ?? 0);
                            $set('tax', $taxPercent);

                            // 4. Copy Items
                            $items = $quotation->items->map(function ($item) {
                                $qty   = (float) $item->qty;
                                $price = (float) $item->unit_price;
                                return [
                                    'item_type'  => $item->item_type ?? 'product',
                                    'item_id'    => $item->item_id,
                                    'item_code'  => $item->item_code,
                                    'item_name'  => $item->item_name,
                                    'qty'        => $qty,
                                    'unit_price' => $price,
                                    'line_total' => (float) $item->line_total ?: $qty * $price,
                                ];
                            })->toArray();

                            $set('items', $items);
                            self::updateTotals($get, $set);
                        }),
                ]),

                Grid::make(3)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required()
                        // REVISI 2: Disabled jika ambil dari SQ
                        ->disabled(fn (Forms\Get $get) => filled($get('nx_quotation_id')))
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
                    // REVISI 3: Kunci Tambah/Hapus item jika SQ terpilih
                    ->addable(fn (Forms\Get $get) => blank($get('nx_quotation_id')))
                    ->deletable(fn (Forms\Get $get) => blank($get('nx_quotation_id')))
                    ->reorderable(false)
                    ->schema([
                        Hidden::make('item_type')->default('product')->dehydrated(true),
                        Hidden::make('item_id')->dehydrated(true),
                        Hidden::make('item_code')->dehydrated(true),

                        TextInput::make('item_name')
                            ->label('Nama Item')
                            ->readOnly() // Nama item selalu readonly (sesuai kodingan awal lu)
                            ->dehydrated()
                            ->columnSpanFull(),

                        Grid::make(3)->schema([
                            TextInput::make('qty')
                                ->label('Jumlah')
                                ->numeric()
                                ->integer()
                                ->default(1)
                                ->minValue(1)
                                // REVISI 4: Kunci Qty jika dari SQ
                                ->readOnly(fn (Forms\Get $get) => filled($get('../../nx_quotation_id')))
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if ($state < 1) $set('qty', 1);
                                    self::updateItemTotal($get, $set);
                                    self::updateTotals($get, $set);
                                }),

                            TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                // REVISI 5: Kunci Harga jika dari SQ
                                ->readOnly(fn (Forms\Get $get) => filled($get('../../nx_quotation_id')))
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    self::updateItemTotal($get, $set);
                                    self::updateTotals($get, $set);
                                }),

                            TextInput::make('line_total')
                                ->label('Subtotal')
                                ->numeric()
                                ->dehydrated()
                                ->readOnly()
                                ->prefix('Rp'),
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
                        ->prefix('Rp'),

                    TextInput::make('promo_code_input')
                        ->label('Kode Promo')
                        ->placeholder('Masukkan kode')
                        ->dehydrated(false)
                        // REVISI 6: Kunci Promo jika dari SQ
                        ->disabled(fn (Forms\Get $get) => filled($get('nx_quotation_id')))
                        ->formatStateUsing(fn ($record) => $record?->promoCode?->code)
                        ->suffixAction(
                            FormAction::make('apply_promo')
                                ->icon('heroicon-m-ticket')
                                ->color('success')
                                ->label('Apply')
                                ->action(function ($state, callable $set, callable $get) {
                                    // ... logic apply promo (sama persis kaya punya lu)
                                    if (empty($state)) {
                                        $set('promo_code_id', null);
                                        $set('temp_discount_type', null);
                                        $set('temp_discount_value', 0);
                                        self::updateTotals($get, $set);
                                        return;
                                    }
                                    $promo = PromoCode::where('code', $state)->where('status', 'active')->first();
                                    if (!$promo) {
                                        Notification::make()->title('Kode tidak valid / expired!')->danger()->send();
                                        $set('promo_code_id', null);
                                        $set('temp_discount_type', null);
                                        $set('temp_discount_value', 0);
                                    } else {
                                        Notification::make()->title("Promo '{$promo->code}' berhasil diterapkan!")->success()->send();
                                        $set('promo_code_id', $promo->id);
                                        $set('temp_discount_type', $promo->type);
                                        $set('temp_discount_value', $promo->value);
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
                        ->prefix('Rp'),

                    TextInput::make('tax')
                        ->label('Pajak (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        // REVISI 7: Kunci Pajak jika dari SQ
                        ->readOnly(fn (Forms\Get $get) => filled($get('nx_quotation_id')))
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state < 0) $set('tax', 0);
                            self::updateTotals($get, $set);
                        })
                        ->prefixIcon('heroicon-o-receipt-percent'),

                    TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('Rp'),
                ]),
            ]),
        ]);
    }

    // --- LOGIC HITUNG (Sama Persis punya lu) ---
    public static function updateItemTotal(callable $get, callable $set): void
    {
        $qty = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);
    }

    public static function updateTotals(callable $get, callable $set): void
    {
        // Support path prefix agar jalan dari dalam repeater atau luar
        $items = $get('items') ?? $get('../../items') ?? [];
        $pathPrefix = is_null($get('subtotal')) ? '../../' : '';

        $subtotal = collect($items)->sum(
            fn ($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
        $set($pathPrefix.'subtotal', $subtotal);

        $discountType = $get($pathPrefix.'temp_discount_type');
        $discountValue = (float) $get($pathPrefix.'temp_discount_value');

        // Logic promo code ID (existing)
        if (!$discountType && $get($pathPrefix.'promo_code_id')) {
            $promo = PromoCode::find($get($pathPrefix.'promo_code_id'));
            if ($promo) {
                $discountType = $promo->type;
                $discountValue = $promo->value;
                $set($pathPrefix.'temp_discount_type', $discountType);
                $set($pathPrefix.'temp_discount_value', $discountValue);
                $set($pathPrefix.'promo_code_input', $promo->code);
            }
        }

        $totalDiscount = 0;
        if ($discountType === 'percentage') {
            $totalDiscount = $subtotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $totalDiscount = $discountValue;
        }

        if ($totalDiscount > $subtotal) $totalDiscount = $subtotal;

        $set($pathPrefix.'discount_amount', $totalDiscount);

        $taxPercent = (float) ($get($pathPrefix.'tax') ?? 0);
        $afterDiscount = $subtotal - $totalDiscount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);

        $set($pathPrefix.'grand_total', $afterDiscount + $taxAmount);
    }

    // --- TABLE & PAGES (Sama Persis punya lu) ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Nomor SO')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer_po_number')->label('PO Customer')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('quotation.quotation_number')->label('Ref. SQ')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order_date')->label('Tgl Pesan')->date('d M Y'),
                Tables\Columns\TextColumn::make('promoCode.code')->label('Promo')->badge()->color('info')->placeholder('-'),
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
