<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\PurchaseOrderResource\Pages;
use App\Models\Inventory\Product;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseRequisition; // DITAMBAHKAN
use App\Services\Procurement\PurchaseOrderReceiptService;
use App\Models\Finance\FinancialRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Procurement\PurchaseOrderMail;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Manajemen Procurement';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'procurement/purchase-orders';

    protected static ?string $pluralModelLabel = 'Purchase Orders';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function updateTotals(Forms\Get $get, Forms\Set $set): void
    {
        $isInsideRepeater = $get('items') === null;

        $prefix = $isInsideRepeater ? '../../' : '';

        $items = $get($prefix . 'items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (float) ($item['total_price'] ?? 0);
        }

        $discount = (float) ($get($prefix . 'discount_amount') ?? 0);

        $taxRate = (float) ($get($prefix . 'tax_rate') ?? 0);

        $taxAmount = ($subtotal - $discount) * ($taxRate / 100);

        $set($prefix . 'subtotal', $subtotal);
        $set($prefix . 'tax_amount', $taxAmount);
        $set($prefix . 'grand_total', $subtotal + $taxAmount - $discount);
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

                            // --- BLOK DITAMBAHKAN: Tarik data dari PR ---
                            Forms\Components\Select::make('purchase_requisition_id')
                                ->label('Berdasarkan PR (Opsional)')
                                ->options(PurchaseRequisition::where('status', 'approved')->pluck('pr_number', 'id'))
                                ->searchable()
                                ->preload()
                                ->live() // Jadikan AJAX reaktif
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if (!$state) return;

                                    // Eager load items dan relasi product
                                    $pr = PurchaseRequisition::with('items.product')->find($state);
                                    if (!$pr) return;

                                    $poItems = [];
                                    $subtotal = 0;

                                    foreach ($pr->items as $item) {
                                        $qty = $item->quantity;
                                        // Gunakan estimated_price dari PR. Jika 0, fallback ke master harga produk
                                        $price = $item->estimated_price > 0 ? $item->estimated_price : ($item->product->purchase_price ?? 0);
                                        $lineTotal = $qty * $price;

                                        $poItems[] = [
                                            'product_id'  => $item->product_id,
                                            'quantity'    => $qty,
                                            'unit_price'  => $price,
                                            'total_price' => $lineTotal,
                                        ];
                                        $subtotal += $lineTotal;
                                    }

                                    // Inject data ke form repeater 'items'
                                    $set('items', $poItems);

                                    // Trigger kalkulasi ulang
                                    $set('subtotal', $subtotal);
                                    self::updateTotals($get, $set);
                                })
                                ->disabled(fn (string $operation): bool => $operation === 'edit')
                                ->helperText('Memilih PR akan otomatis mengisi daftar barang di bawah.'),
                            // --- AKHIR BLOK ---

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
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $product = Product::find($state);
                                            $price = $product?->purchase_price ?? 0;
                                            $qty = (int) ($get('quantity') ?? 1);

                                            $set('unit_price', $price);
                                            $set('total_price', $price * $qty);

                                            self::updateTotals($get, $set);
                                        }),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Qty Pesan')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(1)
                                        ->required()
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
                                            $qty = (int) ($get('quantity') ?? 1);
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

                            Forms\Components\TextInput::make('tax_rate')
                                ->label('Pajak PPN (%)')
                                ->numeric()
                                ->default(11)
                                ->live(debounce: 500)
                                ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, Forms\Get $get) {
                                    $subtotal = (float) $get('subtotal');
                                    $taxAmount = (float) $get('tax_amount');
                                    if ($subtotal > 0 && $taxAmount > 0) {
                                        $component->state(round(($taxAmount / $subtotal) * 100, 2));
                                    }
                                })
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                    self::updateTotals($get, $set);
                                })
                                ->suffix('%')
                                ->dehydrated(false),

                            Forms\Components\Hidden::make('tax_amount'),

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
        // (Fungsi table tidak perlu diubah, biarkan seperti semula)
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
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
                    ->weight('semibold'),
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
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'sent'])),

                Tables\Actions\Action::make('mark_as_sent')
                    ->label('Kirim ke Supplier')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'sent']))
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $record) {
                        $supplierEmail = $record->supplier?->email ?? null;

                        if ($supplierEmail) {
                            try {
                                Mail::to($supplierEmail)->send(new PurchaseOrderMail($record));

                                $record->update(['status' => 'sent']);

                                Notification::make()
                                    ->title('PO Berhasil Dikirim ke Email Supplier')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Gagal mengirim email PO: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Email Gagal Dikirim')
                                    ->body('Terjadi kesalahan saat mengirim email ke supplier. Pastikan konfigurasi SMTP benar.')
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            Notification::make()
                                ->title('Email Tidak Dapat Dikirim')
                                ->body('Supplier tidak memiliki alamat email yang terdaftar.')
                                ->warning()
                                ->send();
                        }
                    }),
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

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Procurement\PurchaseOrderResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }
}
