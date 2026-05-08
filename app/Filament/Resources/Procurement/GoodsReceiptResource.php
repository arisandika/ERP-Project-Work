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
                            Forms\Components\TextInput::make('gr_number')
                                ->label('No. Penerimaan (GR)')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(function (Forms\Components\TextInput $component, ?GoodsReceipt $record) {
                                    $component->state($record?->gr_number ?? GoodsReceipt::generateGRNumber());
                                }),

                            Forms\Components\Select::make('purchase_order_id')
                                ->label('Dari Purchase Order (PO)')
                                ->options(
                                    PurchaseOrder::query()
                                        ->whereIn('status', ['sent', 'partial'])
                                        ->pluck('po_number', 'id')
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
                                ->disabled(fn (string $operation): bool => $operation === 'edit'),

                            Forms\Components\Hidden::make('supplier_id'),

                            Forms\Components\TextInput::make('supplier_name')
                                ->label('Supplier')
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\Select::make('warehouse_id')
                                ->label('Masuk ke Gudang')
                                ->options(
                                    Warehouse::query()
                                        ->where('is_active', true)
                                        ->pluck('warehouse_name', 'id')
                                )
                                ->required(),

                            Forms\Components\DatePicker::make('receipt_date')
                                ->label('Tanggal Diterima')
                                ->default(now())
                                ->required()
                                ->native(false)
                                ->displayFormat('d M Y'),

                            Forms\Components\TextInput::make('delivery_note_number')
                                ->label('No. Surat Jalan Supplier')
                                ->placeholder('Misal: SJ-12345'),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Ceklis Barang Fisik & Scan SN')
                        ->schema([
                            Forms\Components\Repeater::make('items')
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
                                ->columns(2)
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false),
                        ]),
                ])
                ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('Ref. PO')
                    ->searchable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier'),

                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('Tgl Diterima')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('receiver.name')
                    ->label('Penerima (Gudang)'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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
