<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\PurchaseOrderResource\Pages;
use App\Models\Inventory\Product;
use App\Models\Procurement\PurchaseOrder;
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

    protected static ?int $navigationSort = 2;

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

                Tables\Actions\Action::make('receive_goods')
                    ->label('Terima Barang')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['sent', 'partial']))
                    ->form(function (PurchaseOrder $record) {
                        return [
                            Forms\Components\Select::make('warehouse_id')
                                ->label('Masuk ke Gudang Mana?')
                                ->options(\App\Models\Inventory\Warehouse::where('is_active', true)->pluck('warehouse_name', 'id'))
                                ->required(),

                            Forms\Components\DatePicker::make('receipt_date')
                                ->label('Tanggal Diterima')
                                ->default(now())
                                ->required(),

                            Forms\Components\Section::make('Ceklis Barang Fisik & Input SN')
                                ->description('Masukkan kuantitas. Jika barang memiliki SN, wajib scan SN sesuai jumlah kuantitas.')
                                ->schema(
                                    $record->items->map(function ($item) {
                                        $sisa = $item->quantity - $item->quantity_received;
                                        $isSerialized = $item->product->is_serialized ?? false;

                                        return Forms\Components\Group::make()->schema([
                                            Forms\Components\TextInput::make("qty_{$item->id}")
                                                ->label($item->product->product_name . ' (Sisa: '.$sisa.')')
                                                ->numeric()
                                                ->default($sisa > 0 ? $sisa : 0)
                                                ->maxValue($sisa)
                                                ->minValue(0)
                                                ->disabled($sisa == 0)
                                                ->live(debounce: 500),

                                            Forms\Components\Textarea::make("sn_{$item->id}")
                                                ->label('Scan Serial Number (Pisahkan dengan Enter)')
                                                ->visible($isSerialized)
                                                ->required(fn (Forms\Get $get) => $isSerialized && (int) $get("qty_{$item->id}") > 0)
                                                ->disabled($sisa == 0)
                                                ->rows(3)
                                                ->helperText('Jumlah SN harus sama dengan Qty.')
                                                ->rules([
                                                    function (Forms\Get $get) use ($item) {
                                                        return function (string $attribute, $value, \Closure $fail) use ($get, $item) {
                                                            $qty = (int) $get("qty_{$item->id}");
                                                            if ($qty === 0) return;

                                                            $sns = array_filter(array_map('trim', explode("\n", $value)));

                                                            if (count($sns) !== $qty) {
                                                                $fail("Anda menginput {$qty} barang, tapi scan ".count($sns)." SN. Harus seimbang!");
                                                            }

                                                            if (count($sns) !== count(array_unique($sns))) {
                                                                $fail("Terdapat duplikat Serial Number dalam inputan Anda.");
                                                            }
                                                        };
                                                    },
                                                ]),
                                        ])->columns($isSerialized ? 2 : 1);
                                    })->toArray()
                                )->columns(1),
                        ];
                    })
                    ->action(function (array $data, PurchaseOrder $record) {
                        try {
                            $service = app(PurchaseOrderReceiptService::class);
                            $service->processReceipt($record, $data, auth()->id());

                            $record->refresh();

                            if ($record->status === 'completed') {
                                $journalExists = FinancialRecord::where('reference_type', PurchaseOrder::class)
                                    ->where('reference_id', $record->id)
                                    ->exists();

                                if (!$journalExists) {
                                    FinancialRecord::create([
                                        'transaction_date' => now(),
                                        'type'             => 'pengeluaran',
                                        'amount'           => $record->grand_total,
                                        'category'         => 'Purchase Order',
                                        'description'      => 'Pelunasan Pembelian Stok (PO) dari Supplier: ' . ($record->supplier->name ?? '-'),
                                        'reference_number' => $record->po_number,
                                        'reference_type'   => PurchaseOrder::class,
                                        'reference_id'     => $record->id,
                                        'created_by'       => auth()->id() ?? 1,
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Barang Diterima & Masuk Gudang!')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Log::error('Error pada Goods Receipt: ' . $e->getMessage(), [
                                'po_id' => $record->id,
                                'user_id' => auth()->id()
                            ]);

                            Notification::make()
                                ->title('Gagal memproses penerimaan barang')
                                ->body('Terjadi kesalahan sistem, silakan coba lagi atau hubungi IT.')
                                ->danger()
                                ->send();
                        }
                    })
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
}
