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
use Filament\Forms\Components\Hidden; // Tambahan Import
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
                        ->label('No. Penawaran (Opsional)')
                        ->relationship('quotation', 'quotation_number')
                        ->searchable()
                        ->placeholder('Pilih Penawaran untuk copy data otomatis')
                        ->reactive()
                        ->disabled(fn ($record) => $record && $record->status !== 'draft')
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) return;

                            $quotation = Quotation::with('items', 'promoCode')->find($state);
                            if (! $quotation) return;

                            // 1. Copy Header
                            $set('nx_customer_id', $quotation->nx_customer_id);

                            // 2. Copy Promo Code (Jika ada)
                            if ($quotation->promo_code_id) {
                                $set('promo_code_id', $quotation->promo_code_id);
                                $set('promo_code_input', $quotation->promoCode?->code);

                                // Set parameter temp untuk perhitungan
                                if ($quotation->promoCode) {
                                    $set('temp_discount_type', $quotation->promoCode->type);
                                    $set('temp_discount_value', $quotation->promoCode->value);
                                }
                            } else {
                                // Jika di SQ tidak ada promo, reset
                                $set('promo_code_id', null);
                                $set('promo_code_input', null);
                                $set('temp_discount_type', null);
                                $set('temp_discount_value', 0);
                            }

                            // 3. Copy Pajak (Persen)
                            $taxPercent = (float) ($quotation->tax ?? 0);
                            $set('tax', $taxPercent);

                            // 4. Copy Items
                            $items = $quotation->items->map(function ($item) {
                                $qty   = (float) $item->qty;
                                $price = (float) $item->unit_price;
                                return [
                                    // PENTING: Fallback ke 'product' jika item_type null
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

                            // 5. Trigger Update Total Akhir
                            self::updateTotals($get, $set);
                        }),
                ]),

                Grid::make(3)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required()
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
                    ->schema([
                        // --- FIX UTAMA: Gunakan Hidden component & dehydrated(true) ---
                        Hidden::make('item_type')
                            ->default('product')
                            ->dehydrated(true),

                        Hidden::make('item_id')->dehydrated(true),
                        Hidden::make('item_code')->dehydrated(true),
                        // -------------------------------------------------------------

                        TextInput::make('item_name')
                            ->label('Nama Item')
                            ->readOnly() // Jika Anda ingin user bisa edit nama manual, hapus readOnly()
                            ->dehydrated()
                            ->columnSpanFull(),

                        Grid::make(3)->schema([
                            TextInput::make('qty')
                                ->label('Jumlah')
                                ->numeric()
                                ->integer()
                                ->default(1)
                                ->minValue(1)
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

                    // === LOGIC PROMO CODE ===
                    TextInput::make('promo_code_input')
                        ->label('Kode Promo')
                        ->placeholder('Masukkan kode')
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => $record?->promoCode?->code)
                        ->suffixAction(
                            FormAction::make('apply_promo')
                                ->icon('heroicon-m-ticket')
                                ->color('success')
                                ->label('Apply')
                                ->action(function ($state, callable $set, callable $get) {
                                    // Reset jika input kosong
                                    if (empty($state)) {
                                        $set('promo_code_id', null);
                                        $set('temp_discount_type', null);
                                        $set('temp_discount_value', 0);
                                        self::updateTotals($get, $set);
                                        return;
                                    }

                                    // Cek Database
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

                    // Hidden Fields Promo
                    Hidden::make('promo_code_id'),
                    Hidden::make('temp_discount_type')->dehydrated(false),
                    Hidden::make('temp_discount_value')->dehydrated(false),

                    // Field Hasil Diskon
                    TextInput::make('discount_amount')
                        ->label('Potongan (Rp)')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('Rp'),

                    // Field Pajak (Persen)
                    TextInput::make('tax')
                        ->label('Pajak (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
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

    // --- LOGIC HITUNG ---

    public static function updateItemTotal(callable $get, callable $set): void
    {
        $qty = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);
    }

    public static function updateTotals(callable $get, callable $set): void
    {
        // 1. Hitung Subtotal Item
        $items = $get('items') ?? [];
        $subtotal = collect($items)->sum(
            fn ($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
        $set('subtotal', $subtotal);

        // 2. Logic Kalkulasi Promo
        $discountType = $get('temp_discount_type');
        $discountValue = (float) $get('temp_discount_value');

        if (!$discountType && $get('promo_code_id')) {
            $promo = PromoCode::find($get('promo_code_id'));
            if ($promo) {
                $discountType = $promo->type;
                $discountValue = $promo->value;
                $set('temp_discount_type', $discountType);
                $set('temp_discount_value', $discountValue);
                $set('promo_code_input', $promo->code);
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

        $set('discount_amount', $totalDiscount);

        // 3. Hitung Pajak & Grand Total
        $taxPercent = (float) ($get('tax') ?? 0);
        $afterDiscount = $subtotal - $totalDiscount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);

        $set('grand_total', $afterDiscount + $taxAmount);
    }

    // --- TABLE & PAGES ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Nomor SO')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer_po_number')->label('PO Customer')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('quotation.quotation_number')->label('Ref. SQ')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order_date')->label('Tgl Pesan')->date('d M Y'),

                Tables\Columns\TextColumn::make('promoCode.code')
                    ->label('Promo')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('grand_total')->label('Total')->money('IDR', true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
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
