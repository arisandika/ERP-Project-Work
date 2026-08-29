<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\GoodsReceiptResource\Pages;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\Warehouse;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\BelongsToModule;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class GoodsReceiptResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'procurement';
    protected static ?string $model = GoodsReceipt::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?int $navigationSort = 5;
    protected static ?string $pluralModelLabel = 'Goods Receipts';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Informasi Surat Jalan Penerimaan')
                        ->schema([
                            // Baris 1: No GR (1 kolom) & Judul GR (2 kolom)
                            Forms\Components\TextInput::make('gr_number')
                                ->label('No. Penerimaan (GR)')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(function (Forms\Components\TextInput $component, ?GoodsReceipt $record) {
                                    $component->state($record?->gr_number ?? GoodsReceipt::generateGRNumber());
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('title')
                                ->label('Nama / Judul Penerimaan')
                                ->placeholder('Contoh: Penerimaan Laptop Batch 1')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2) // Agar inputan membentang
                                ->extraInputAttributes(['class' => 'text-xl font-normal border-t-0 border-l-0 border-r-0 border-b-2 border-gray-300 focus:ring-0 px-0 bg-transparent']),

                            // Baris 2: Pemilihan PO, Supplier, & Gudang
                            Forms\Components\Select::make('purchase_order_id')
                                ->label('Dari Purchase Order (PO)')
                                // Modifikasi agar menampilkan No PO dan Nama PO
                                ->options(
                                    PurchaseOrder::whereIn('status', ['sent', 'partial'])
                                        ->get()
                                        ->mapWithKeys(fn ($po) => [$po->id => $po->po_number . ' - ' . $po->title])
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if (! $state) {
                                        $set('supplier_id', null);
                                        $set('supplier_name', null);
                                        $set('items', []);
                                        return;
                                    }

                                    $po = PurchaseOrder::with(['items.product', 'supplier'])->find($state);

                                    if (! $po) {
                                        $set('supplier_id', null);
                                        $set('supplier_name', null);
                                        $set('items', []);
                                        return;
                                    }

                                    $set('supplier_id', $po->supplier_id);
                                    $set('supplier_name', $po->supplier->name ?? '-');

                                    $grItems = [];

                                    foreach ($po->items as $item) {
                                        $qtyPo = (int) ($item->quantity ?? 0);
                                        $qtyReceivedBefore = (int) ($item->quantity_received ?? 0);
                                        $remainingBefore = max($qtyPo - $qtyReceivedBefore, 0);

                                        if ($remainingBefore <= 0) {
                                            continue;
                                        }

                                        $grItems[] = [
                                            'purchase_order_item_id' => $item->id,
                                            'product_id' => $item->product_id,
                                            'product_name' => $item->product->product_name ?? '-',
                                            'is_serialized' => (bool) ($item->product->is_serialized ?? false),
                                            'qty_po' => $qtyPo,
                                            'qty_sudah_diterima' => $qtyReceivedBefore,
                                            'sisa_qty' => $remainingBefore,
                                            'quantity_received' => $remainingBefore,
                                            'unit_price' => $item->unit_price,
                                            'scanned_sns' => null,
                                        ];
                                    }

                                    $set('items', $grItems);
                                })
                                ->disabled(fn (string $operation): bool => $operation === 'edit')
                                ->columnSpan(1),

                            Forms\Components\Hidden::make('supplier_id'),

                            Forms\Components\TextInput::make('supplier_name')
                                ->label('Supplier')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),

                            Forms\Components\Select::make('warehouse_id')
                                ->label('Masuk ke Gudang')
                                ->options(
                                    Warehouse::query()
                                        ->where('is_active', true)
                                        ->pluck('warehouse_name', 'id')
                                )
                                ->required()
                                ->columnSpan(1),

                            // Baris 3: Tanggal & Surat Jalan
                            Forms\Components\DatePicker::make('receipt_date')
                                ->label('Tanggal Diterima')
                                ->default(now())
                                ->required()
                                ->native(false)
                                ->displayFormat('d M Y')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('delivery_note_number')
                                ->label('No. Surat Jalan Supplier')
                                ->placeholder('Misal: SJ-12345')
                                ->columnSpan(2), // Mengisi sisa kolom agar rapi
                        ])
                        ->columns(['default' => 1, 'md' => 3]), // <-- Diubah menjadi 3 kolom

                    Forms\Components\Section::make('Ceklis Barang Fisik & Scan SN')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->live()
                                ->schema([
                                    Forms\Components\Hidden::make('purchase_order_item_id'),
                                    Forms\Components\Hidden::make('product_id'),
                                    Forms\Components\Hidden::make('is_serialized'),
                                    Forms\Components\Hidden::make('unit_price'),
                                    Forms\Components\Hidden::make('qty_po'),
                                    Forms\Components\Hidden::make('qty_sudah_diterima'),
                                    Forms\Components\Hidden::make('sisa_qty'),

                                    Forms\Components\TextInput::make('product_name')
                                        ->label('Nama Barang')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpanFull()
                                        ->helperText(function (Forms\Get $get) {
                                            $qtyPo = (int) ($get('qty_po') ?? 0);
                                            $sudahDiterima = (int) ($get('qty_sudah_diterima') ?? 0);
                                            $sisa = (int) ($get('sisa_qty') ?? 0);
                                            $diterimaSekarang = (int) ($get('quantity_received') ?? 0);
                                            $sisaSetelah = max($sisa - $diterimaSekarang, 0);

                                            return "Qty PO: {$qtyPo} • Sudah diterima: {$sudahDiterima} • Sisa saat ini: {$sisa} • Sisa setelah input: {$sisaSetelah}";
                                        }),

                                    Forms\Components\TextInput::make('quantity_received')
                                        ->label('Diterima Sekarang')
                                        ->numeric()
                                        ->required()
                                        ->default(fn (Forms\Get $get) => (int) ($get('sisa_qty') ?? 0))
                                        ->minValue(0)
                                        ->maxValue(fn (Forms\Get $get) => (int) ($get('sisa_qty') ?? 0))
                                        ->live(debounce: 300)
                                        ->suffix('pcs')
                                        ->afterStateUpdated(function (Forms\Set $set) {
                                            $set('scanned_sns', null);
                                        }),

                                    Forms\Components\ViewField::make('camera_sn')
                                        ->label('Scanner Barcode/QR')
                                        ->view('filament.forms.components.camera-scanner')
                                        ->visible(fn (Forms\Get $get) => (bool) $get('is_serialized'))
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, Forms\Set $set, Forms\Get $get) {
                                            if (blank($state)) {
                                                return;
                                            }

                                            $scannedSn = trim($state);
                                            $currentText = $get('scanned_sns') ?? '';
                                            $currentArray = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $currentText))));
                                            $targetQty = (int) ($get('quantity_received') ?? 0);

                                            if ($targetQty <= 0) {
                                                Notification::make()
                                                    ->title('Qty diterima harus lebih dari 0 sebelum scan serial number.')
                                                    ->warning()
                                                    ->send();

                                                $set('camera_sn', null);
                                                return;
                                            }

                                            if (count($currentArray) >= $targetQty) {
                                                Notification::make()
                                                    ->title("Qty diterima hanya {$targetQty}.")
                                                    ->warning()
                                                    ->send();

                                                $set('camera_sn', null);
                                                return;
                                            }

                                            if (in_array($scannedSn, $currentArray, true)) {
                                                Notification::make()
                                                    ->title("SN {$scannedSn} sudah di-scan.")
                                                    ->warning()
                                                    ->send();

                                                $set('camera_sn', null);
                                                return;
                                            }

                                            $isDuplicate = SerialNumber::query()
                                                ->where('serial_number', $scannedSn)
                                                ->where('product_id', $get('product_id'))
                                                ->exists();

                                            if ($isDuplicate) {
                                                Notification::make()
                                                    ->title("SN {$scannedSn} ditolak. Sudah ada di database gudang.")
                                                    ->danger()
                                                    ->send();

                                                $set('camera_sn', null);
                                                return;
                                            }

                                            $currentArray[] = $scannedSn;

                                            $set('scanned_sns', implode("\n", $currentArray));
                                            $set('camera_sn', null);

                                            Notification::make()
                                                ->title("SN {$scannedSn} berhasil di-scan.")
                                                ->success()
                                                ->send();
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('scanned_sns')
                                        ->label('Daftar Serial Number')
                                        ->visible(fn (Forms\Get $get) => (bool) $get('is_serialized'))
                                        ->required(fn (Forms\Get $get) => (bool) $get('is_serialized') && (int) ($get('quantity_received') ?? 0) > 0)
                                        ->rows(4)
                                        ->helperText('Jumlah serial number harus sama persis dengan qty yang diterima sekarang.')
                                        ->rules([
                                            function (Forms\Get $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $qty = (int) ($get('quantity_received') ?? 0);

                                                    if ($qty <= 0) {
                                                        return;
                                                    }

                                                    $sns = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));

                                                    if (count($sns) !== $qty) {
                                                        $fail("Qty diterima: {$qty}, tapi jumlah serial number yang diinput: " . count($sns) . '.');
                                                    }

                                                    if (count($sns) !== count(array_unique($sns))) {
                                                        $fail('Ada serial number duplikat di daftar scan.');
                                                    }
                                                };
                                            },
                                        ])
                                        ->columnSpanFull(),
                                ])
                                ->columns(['default' => 1, 'md' => 2])
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Surat Jalan Penerimaan')
                    ->schema([
                        Infolists\Components\TextEntry::make('gr_number')
                            ->label('No. Penerimaan (GR)')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('title')
                            ->label('Nama / Judul Penerimaan'),
                        Infolists\Components\TextEntry::make('purchaseOrder.po_number')
                            ->label('Ref. PO')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('supplier.name')
                            ->label('Supplier')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('warehouse.warehouse_name')
                            ->label('Gudang')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('receipt_date')
                            ->label('Tanggal Diterima')
                            ->date('d M Y'),
                        Infolists\Components\TextEntry::make('delivery_note_number')
                            ->label('No. Surat Jalan Supplier')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match (strtolower($state)) {
                                'draft' => 'gray',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => strtoupper($state)),
                        Infolists\Components\TextEntry::make('receiver.name')
                            ->label('Penerima (Gudang)')
                            ->placeholder('-'),
                    ])
                    ->columns(['default' => 1, 'md' => 3]),

                Infolists\Components\Section::make('Detail Barang yang Diterima')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('product.product_name')
                                    ->label('Nama Barang'),
                                Infolists\Components\TextEntry::make('quantity_received')
                                    ->label('Qty Diterima')
                                    ->suffix(' pcs'),
                                Infolists\Components\TextEntry::make('scanned_sns')
                                    ->label('Serial Number')
                                    ->placeholder('-')
                                    ->formatStateUsing(fn($state) =>
                                        is_array($state)
                                            ? implode(', ', $state)
                                            : (is_string($state) ? preg_replace('/\s+/', ', ', trim($state)) : (string)$state)
                                    ),
                            ])
                            ->columns(['default' => 1, 'md' => 3]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gr_number')
                    ->label('No. GR')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Menampilkan Judul GR di Tabel
                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->limit(30),

                // Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                //     ->label('Ref. PO')
                //     ->searchable()
                //     // Menampilkan No PO sekaligus Judul PO-nya di baris tabel (jika ada)
                //     ->formatStateUsing(fn (string $state, $record): string =>
                //         $record->purchaseOrder ? $record->purchaseOrder->po_number . ' - ' . $record->purchaseOrder->title : $state
                //     ),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier'),

                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('Tgl Diterima')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('receiver.name')
                    ->label('Penerima (Gudang)'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => strtoupper($state)),
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
