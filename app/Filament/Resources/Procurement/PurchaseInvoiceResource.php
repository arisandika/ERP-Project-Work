<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\PurchaseInvoiceResource\Pages;
use App\Models\Procurement\PurchaseInvoice;
use App\Models\Procurement\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\BelongsToModule;

class PurchaseInvoiceResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'Procurement';
    protected static ?string $model = PurchaseInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Manajemen Finance'; // Pindah ke wilayah Finance!
    protected static ?int $navigationSort = 1;
    protected static ?string $pluralModelLabel = 'Purchase Invoices (Tagihan)';

    public static function updateTotals(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('../../items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (float) ($item['total_price'] ?? 0);
        }

        $discount = (float) ($get('../../discount_amount') ?? 0);
        $taxRate = (float) ($get('../../tax_rate') ?? 0);
        $taxAmount = ($subtotal - $discount) * ($taxRate / 100);

        $set('../../subtotal', $subtotal);
        $set('../../tax_amount', $taxAmount);
        $set('../../grand_total', $subtotal + $taxAmount - $discount);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Informasi Tagihan Supplier')
                        ->schema([
                            Forms\Components\TextInput::make('invoice_number')
                                ->label('No. Internal PI')
                                ->default('AUTO-GENERATED')
                                ->disabled(),

                            Forms\Components\TextInput::make('vendor_invoice_number')
                                ->label('No. Tagihan (Dari Supplier)')
                                ->required()
                                ->placeholder('Contoh: INV-SUP-001'),

                            // THREE-WAY MATCHING: Pilih PO yang sudah ada barang masuknya
                            Forms\Components\Select::make('purchase_order_id')
                                ->label('Berdasarkan PO')
                                ->options(PurchaseOrder::whereIn('status', ['partial', 'completed'])->pluck('po_number', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if (!$state) return;

                                    $po = PurchaseOrder::with(['items.product', 'supplier'])->find($state);
                                    if (!$po) return;

                                    $set('supplier_id', $po->supplier_id);

                                    $piItems = [];
                                    $subtotal = 0;

                                    foreach ($po->items as $item) {
                                        // HANYA MUNCULKAN BARANG YANG SUDAH DITERIMA GUDANG
                                        if ($item->quantity_received > 0) {
                                            $lineTotal = $item->quantity_received * $item->unit_price;

                                            $piItems[] = [
                                                'purchase_order_item_id' => $item->id,
                                                'product_id'             => $item->product_id,
                                                'product_name'           => $item->product->product_name,
                                                'max_qty'                => $item->quantity_received,
                                                'quantity_billed'        => $item->quantity_received, // Default tagih semua yang diterima
                                                'unit_price'             => $item->unit_price,
                                                'total_price'            => $lineTotal,
                                            ];
                                            $subtotal += $lineTotal;
                                        }
                                    }
                                    $set('items', $piItems);
                                    $set('subtotal', $subtotal);

                                    // Set ulang total
                                    $taxRate = (float) ($get('tax_rate') ?? 0);
                                    $taxAmount = $subtotal * ($taxRate / 100);
                                    $set('tax_amount', $taxAmount);
                                    $set('grand_total', $subtotal + $taxAmount);
                                })
                                ->disabled(fn (string $operation): bool => $operation === 'edit'),

                            Forms\Components\Hidden::make('supplier_id'),

                            Forms\Components\DatePicker::make('invoice_date')
                                ->label('Tanggal Tagihan')
                                ->default(now())
                                ->required(),

                            Forms\Components\DatePicker::make('due_date')
                                ->label('Jatuh Tempo')
                                ->default(now()->addDays(14))
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Rincian Tagihan')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Hidden::make('purchase_order_item_id'),
                                    Forms\Components\Hidden::make('product_id'),

                                    Forms\Components\TextInput::make('product_name')
                                        ->label('Nama Barang')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('max_qty')
                                        ->label('Max (Sesuai GR Gudang)')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->numeric(),

                                    Forms\Components\TextInput::make('quantity_billed')
                                        ->label('Qty Ditagihkan')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        // PENGUNCIAN AUDIT: Tagihan tidak boleh melebihi fisik di gudang
                                        ->maxValue(fn (Forms\Get $get) => $get('max_qty'))
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $price = (float) ($get('unit_price') ?? 0);
                                            $set('total_price', $price * (int) $state);
                                            self::updateTotals($get, $set);
                                        }),

                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('Harga Satuan')
                                        ->numeric()
                                        ->required()
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $qty = (int) ($get('quantity_billed') ?? 1);
                                            $set('total_price', (float) $state * $qty);
                                            self::updateTotals($get, $set);
                                        }),

                                    Forms\Components\TextInput::make('total_price')
                                        ->label('Total Baris')
                                        ->numeric()
                                        ->required()
                                        ->disabled()
                                        ->dehydrated(),
                                ])
                                ->columns(3)
                                ->addable(false)
                                ->deletable(false),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Kalkulasi Pembayaran')
                        ->schema([
                            Forms\Components\TextInput::make('subtotal')->disabled()->dehydrated()->prefix('Rp')->default(0),
                            Forms\Components\TextInput::make('tax_rate')->label('PPN (%)')->numeric()->default(11)->live(debounce: 500)
                                ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::updateTotals($get, $set))->suffix('%')->dehydrated(false),
                            Forms\Components\Hidden::make('tax_amount'),
                            Forms\Components\TextInput::make('discount_amount')->label('Diskon')->numeric()->default(0)->live(debounce: 500)
                                ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::updateTotals($get, $set))->prefix('Rp'),
                            Forms\Components\TextInput::make('grand_total')->label('Grand Total Tagihan')->disabled()->dehydrated()->prefix('Rp')
                                ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: bold; color: #dc2626;']), // Merah karena ini hutang
                        ]),
                ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('No. Internal')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('vendor_invoice_number')->label('No. Vendor')->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Supplier'),
                Tables\Columns\TextColumn::make('due_date')->label('Jatuh Tempo')->date('d M Y')->color(fn ($record) => $record->due_date < now() && $record->status !== 'paid' ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('grand_total')->label('Total Tagihan')->money('IDR', true)->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'gray',
                    })->formatStateUsing(fn($state) => strtoupper($state)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn ($record) => $record->status === 'unpaid'),
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseInvoices::route('/'),
            'create' => Pages\CreatePurchaseInvoice::route('/create'),
            'edit' => Pages\EditPurchaseInvoice::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Procurement\PurchaseInvoiceResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }
}
