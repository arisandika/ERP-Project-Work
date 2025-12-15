<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\SalesOrderResource\Pages;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\Quotation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
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

                // --- LOGIC PENTING: COPY QUOTATION ---
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

                            $quotation = Quotation::with('items')->find($state);
                            if (! $quotation) return;

                            // 1. Copy Header
                            $set('nx_customer_id', $quotation->nx_customer_id);

                            // 2. Hitung Diskon (Prioritas: Promo Code > Diskon Manual Lama)
                            $subtotalQ = (float) $quotation->subtotal;
                            $discountRupiah = 0;

                            if ($quotation->discount_amount > 0) {
                                // Ambil dari hasil promo code
                                $discountRupiah = (float) $quotation->discount_amount;
                            } elseif ($quotation->discount > 0) {
                                // Fallback: Konversi diskon % lama ke Rupiah
                                $discountRupiah = $subtotalQ * ($quotation->discount / 100);
                            }

                            // Set nilai ke field agar bisa diedit user
                            $set('discount', $discountRupiah);

                            // 3. Hitung Pajak (Konversi % ke Rupiah karena SO pakai Rupiah)
                            $taxPercent = (float) ($quotation->tax ?? 0);
                            // Pajak dihitung dari (Subtotal - Diskon)
                            $taxable = max($subtotalQ - $discountRupiah, 0);
                            $taxRupiah = $taxable * ($taxPercent / 100);

                            $set('tax', $taxRupiah);

                            // 4. Copy Items
                            $items = $quotation->items->map(function ($item) {
                                $qty   = (float) $item->qty;
                                $price = (float) $item->unit_price;
                                return [
                                    'item_type'  => $item->item_type,
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
                        TextInput::make('item_type')->hidden()->dehydrated(),
                        TextInput::make('item_id')->hidden()->dehydrated(),
                        TextInput::make('item_code')->hidden()->dehydrated(),

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

                    // Field Diskon Rupiah (Editable)
                    TextInput::make('discount')
                        ->label('Diskon (Rp)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->reactive() // Reactive biar kalau user edit manual, total berubah
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state < 0) $set('discount', 0);
                            self::updateTotals($get, $set);
                        })
                        ->prefixIcon('heroicon-o-tag'),

                    // Field Pajak Rupiah (Editable)
                    TextInput::make('tax')
                        ->label('Pajak (Rp)')
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
        // 1. Hitung Subtotal
        $items = $get('items') ?? [];
        $subtotal = collect($items)->sum(
            fn ($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
        $set('subtotal', $subtotal);

        // 2. Ambil Inputan User (Diskon & Pajak)
        // Karena field ini editable, kita ambil apapun yg ada di form sekarang
        $discountAmount = (float) ($get('discount') ?? 0);
        $taxAmount      = (float) ($get('tax') ?? 0);

        // 3. Validasi Logic (Diskon tidak boleh lebih besar dari subtotal)
        if ($discountAmount > $subtotal) {
            $discountAmount = $subtotal;
            $set('discount', $subtotal); // Auto koreksi di UI
        }

        // 4. Hitung Grand Total
        // Rumus: (Subtotal - Diskon) + Pajak
        $afterDiscount = max($subtotal - $discountAmount, 0);
        $grandTotal    = $afterDiscount + $taxAmount;

        $set('grand_total', $grandTotal);
    }

    // --- TABLE & PAGES ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Nomor SO')->sortable()->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('customer_po_number')->label('PO Customer')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('quotation.quotation_number')->label('Ref. SQ')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order_date')->label('Tgl Pesan')->date('d M Y'),
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
                Tables\Actions\ViewAction::make(),
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
