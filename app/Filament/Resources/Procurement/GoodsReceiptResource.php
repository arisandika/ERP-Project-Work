<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\GoodsReceiptResource\Pages;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\PurchaseOrder;
use App\Services\Procurement\GoodsReceiptService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?int $navigationSort = 5;
    protected static ?string $pluralModelLabel = 'Goods Receipts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Informasi Surat Jalan Penerimaan')
                        ->schema([
                            Forms\Components\TextInput::make('gr_number')
                                ->label('No. Penerimaan (GR)')
                                ->default('AUTO-GENERATED')
                                ->disabled(),

                            // 1. Pilih PO
                            Forms\Components\Select::make('purchase_order_id')
                                ->label('Dari Purchase Order (PO)')
                                ->options(PurchaseOrder::whereIn('status', ['sent', 'partial'])->pluck('po_number', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if (!$state) return;

                                    $po = PurchaseOrder::with(['items.product', 'supplier'])->find($state);
                                    if (!$po) return;

                                    $set('supplier_id', $po->supplier_id);
                                    $set('supplier_name', $po->supplier->name);

                                    $grItems = [];
                                    foreach ($po->items as $item) {
                                        $sisa = $item->quantity - $item->quantity_received;
                                        if ($sisa > 0) {
                                            $grItems[] = [
                                                'purchase_order_item_id' => $item->id,
                                                'product_id'             => $item->product_id,
                                                'product_name'           => $item->product->product_name,
                                                'is_serialized'          => $item->product->is_serialized ?? false,
                                                'sisa_qty'               => $sisa,
                                                'quantity_received'      => $sisa, // Default terima full sisa
                                                'unit_price'             => $item->unit_price, // Disembunyikan (untuk finance)
                                            ];
                                        }
                                    }
                                    $set('items', $grItems);
                                })
                                ->disabled(fn (string $operation): bool => $operation === 'edit'),

                            Forms\Components\Hidden::make('supplier_id'),

                            Forms\Components\TextInput::make('supplier_name')
                                ->label('Supplier')
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\Select::make('warehouse_id')
                                ->label('Masuk ke Gudang')
                                ->options(\App\Models\Inventory\Warehouse::where('is_active', true)->pluck('warehouse_name', 'id'))
                                ->required(),

                            Forms\Components\DatePicker::make('receipt_date')
                                ->label('Tanggal Diterima')
                                ->default(now())
                                ->required(),

                            Forms\Components\TextInput::make('delivery_note_number')
                                ->label('No. Surat Jalan Supplier')
                                ->placeholder('Misal: SJ-12345'),
                        ])->columns(2),

                    Forms\Components\Section::make('Ceklis Barang Fisik & Scan SN')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Hidden::make('purchase_order_item_id'),
                                    Forms\Components\Hidden::make('product_id'),
                                    Forms\Components\Hidden::make('is_serialized'),
                                    Forms\Components\Hidden::make('unit_price'), // Rahasia HPP, hanya untuk di-passing ke Service

                                    Forms\Components\TextInput::make('product_name')
                                        ->label('Nama Barang')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('sisa_qty')
                                        ->label('Kekurangan (PO)')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->numeric(),

                                    Forms\Components\TextInput::make('quantity_received')
                                        ->label('Fisik Diterima')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0)
                                        ->maxValue(fn (Forms\Get $get) => $get('sisa_qty'))
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                            // Jika kuantitas diubah, reset scanner agar validasinya pas
                                            $set('scanned_sns', null);
                                        }),

                                    // --- FITUR SCANNER DARI DO (DI-REUSE) ---
                                    Forms\Components\ViewField::make('camera_sn')
                                        ->label('Scanner Barcode/QR')
                                        ->view('filament.forms.components.camera-scanner')
                                        ->visible(fn (Forms\Get $get) => $get('is_serialized'))
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, Forms\Set $set, Forms\Get $get) {
                                            if (blank($state)) return;

                                            $scannedSn = trim($state);
                                            $currentText = $get('scanned_sns') ?? '';
                                            $currentArray = array_filter(array_map('trim', explode("\n", $currentText)));
                                            $targetQty = (int) $get('quantity_received');

                                            if (count($currentArray) >= $targetQty) {
                                                Notification::make()->title("Gagal! Anda hanya menginput Qty: {$targetQty}.")->warning()->send();
                                                $set('camera_sn', null);
                                                return;
                                            }

                                            if (!in_array($scannedSn, $currentArray)) {
                                                // Validasi Barang Masuk: Pastikan SN belum pernah ada di gudang kita!
                                                $isDuplicate = \App\Models\Inventory\SerialNumber::where('serial_number', $scannedSn)
                                                    ->where('product_id', $get('product_id'))
                                                    ->exists();

                                                if ($isDuplicate) {
                                                    Notification::make()->title("SN {$scannedSn} DITOLAK! Sudah ada di database gudang.")->danger()->send();
                                                } else {
                                                    $currentArray[] = $scannedSn;
                                                    $set('scanned_sns', implode("\n", $currentArray));
                                                    Notification::make()->title("SN {$scannedSn} berhasil di-scan.")->success()->send();
                                                }
                                            } else {
                                                Notification::make()->title("SN {$scannedSn} sudah di-scan di baris ini!")->warning()->send();
                                            }
                                            $set('camera_sn', null); // Reset otomatis untuk scan berikutnya
                                        }),

                                    Forms\Components\Textarea::make('scanned_sns')
                                        ->label('Daftar Serial Number')
                                        ->visible(fn (Forms\Get $get) => $get('is_serialized'))
                                        ->required(fn (Forms\Get $get) => $get('is_serialized') && (int) $get('quantity_received') > 0)
                                        ->rows(4)
                                        ->helperText('Jumlah SN harus persis sama dengan Qty Fisik Diterima.')
                                        ->rules([
                                            function (Forms\Get $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $qty = (int) $get('quantity_received');
                                                    if ($qty === 0) return;

                                                    $sns = array_filter(array_map('trim', explode("\n", $value)));
                                                    if (count($sns) !== $qty) {
                                                        $fail("Qty Fisik: {$qty}, tapi Anda men-scan " . count($sns) . " SN.");
                                                    }
                                                };
                                            },
                                        ]),
                                ])->columns(4)
                                ->addable(false) // Item ditarik dari PO, tidak boleh nambah item liar
                                ->deletable(false),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gr_number')->label('No. GR')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')->label('Ref. PO')->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Supplier'),
                Tables\Columns\TextColumn::make('receipt_date')->label('Tgl Diterima')->date('d M Y'),
                Tables\Columns\TextColumn::make('receiver.name')->label('Penerima (Gudang)'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    })->formatStateUsing(fn($state) => strtoupper($state)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'view' => Pages\ViewGoodsReceipt::route('/{record}'),
        ];
    }
}
