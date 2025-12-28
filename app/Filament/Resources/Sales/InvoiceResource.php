<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\InvoiceResource\Pages;
use App\Filament\Resources\Sales\InvoiceResource\RelationManagers;
use App\Models\Sales\Invoice;
use App\Models\Sales\SalesOrder;
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
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use App\Mail\InvoiceSent;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?int $navigationSort = 8;
    protected static ?string $slug = 'sales/invoices';
    protected static ?string $pluralModelLabel = 'Invoice';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Invoice')->schema([
                Grid::make(3)->schema([
                    TextInput::make('invoice_number')
                        ->label('Nomor Invoice')
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-o-hashtag'),

                    DatePicker::make('invoice_date')
                        ->label('Tanggal Invoice')
                        ->default(now())
                        ->required()
                        ->prefixIcon('heroicon-o-calendar-days'),

                    DatePicker::make('due_date')
                        ->label('Jatuh Tempo')
                        ->required()
                        ->prefixIcon('heroicon-o-calendar-days'),
                ]),

                Grid::make(3)->schema([
                    Select::make('nx_sales_order_id')
                        ->label('No. Sales Order')
                        ->relationship('salesOrder', 'order_number')
                        ->searchable()
                        ->placeholder('Pilih Sales Order')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (!$state) return;

                            $so = SalesOrder::with('items')->find($state);
                            if (!$so) return;

                            $set('customer_po_number', $so->customer_po_number);
                            $set('nx_customer_id', $so->nx_customer_id);
                            $set('subtotal', (float) ($so->subtotal ?? 0));
                            $set('discount', (float) ($so->discount ?? 0));
                            $set('tax', (float) ($so->tax ?? 0));
                            $set('grand_total', (float) ($so->grand_total ?? 0));

                            $items = $so->items->map(function ($item) {
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
                            self::updateTotals($get, $set);
                        })
                        ->disabled(fn (?Invoice $record) => filled($record))
                        ->required(),

                    TextInput::make('customer_po_number')
                        ->label('No. PO Customer')
                        ->placeholder('Contoh: PO-ABC-001')
                        ->maxLength(50),

                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required()
                        ->prefixIcon('heroicon-o-user-circle'),
                ]),

                Grid::make(2)->schema([
                    Select::make('nx_employee_id')
                        ->label('Dibuat Oleh')
                        ->relationship('employee', 'full_name')
                        ->default(fn () => auth()->user()?->employee?->id)
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user'),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft'     => 'Draft',
                            'sent'      => 'Terkirim',
                            'partial'   => 'Terbayar Sebagian',
                            'paid'      => 'Lunas',
                            'cancelled' => 'Dibatalkan',
                        ])
                        ->default('draft')
                        ->required()
                        ->live()
                        ->prefixIcon('heroicon-o-adjustments-vertical'),
                ]),

                Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->columnSpanFull(),
            ])->columns(1),

            Section::make('Item Invoice')->schema([
                Repeater::make('items')
                    ->schema([
                        Forms\Components\Hidden::make('id'),

                        TextInput::make('item_type')->hidden()->dehydrated(),
                        TextInput::make('item_id')->hidden()->dehydrated(),

                        TextInput::make('item_code')
                            ->label('Kode')
                            ->readOnly()
                            ->dehydrated(),

                        TextInput::make('item_name')
                            ->label('Nama Item')
                            ->readOnly()
                            ->dehydrated(),

                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                self::updateItemTotal($get, $set)
                            ),

                        TextInput::make('unit_price')
                            ->label('Harga Satuan')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                self::updateItemTotal($get, $set)
                            ),

                        TextInput::make('line_total')
                            ->label('Total')
                            ->numeric()
                            ->dehydrated()
                            ->disabled()
                            ->prefix('Rp'),
                    ])
                    ->columns(5)
                    ->reactive()
                    ->afterStateUpdated(fn (callable $get, callable $set) =>
                        self::updateTotals($get, $set)
                    )
                    ->createItemButtonLabel('Tambah Item')
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->collapsible(),
            ])->collapsed(),

            Section::make('Perhitungan Akhir')->schema([
                Grid::make(4)->schema([
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->disabled()
                        ->dehydrated()
                        ->prefix('Rp')
                        ->numeric(),

                    TextInput::make('discount')
                        ->label('Diskon (%)')
                        ->numeric()
                        ->default(0)
                        ->reactive()
                        ->afterStateUpdated(fn (callable $get, callable $set) =>
                            self::updateTotals($get, $set)
                        )
                        ->prefixIcon('heroicon-o-tag')
                        ->minValue(0)
                        ->maxValue(100),

                    TextInput::make('tax')
                        ->label('Pajak (%)')
                        ->numeric()
                        ->default(0)
                        ->reactive()
                        ->afterStateUpdated(fn (callable $get, callable $set) =>
                            self::updateTotals($get, $set)
                        )
                        ->prefixIcon('heroicon-o-receipt-percent')
                        ->minValue(0)
                        ->maxValue(100),

                    TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->disabled()
                        ->dehydrated()
                        ->prefix('Rp')
                        ->numeric(),
                ]),

                // --- VISUAL PAYMENT STATUS (NEW) ---
                Grid::make(2)->schema([
                    Forms\Components\Placeholder::make('total_paid_view')
                        ->label('Sudah Dibayar')
                        ->content(fn ($record) => 'Rp ' . number_format($record?->total_paid ?? 0, 0, ',', '.'))
                        ->extraAttributes(['class' => 'text-success-600 font-bold text-lg']),

                    Forms\Components\Placeholder::make('remaining_balance_view')
                        ->label('Sisa Tagihan')
                        ->content(fn ($record) => 'Rp ' . number_format($record?->remaining_balance ?? 0, 0, ',', '.'))
                        ->extraAttributes(fn ($record) => [
                            'class' => ($record?->remaining_balance > 0)
                                ? 'text-danger-600 font-bold text-lg'
                                : 'text-gray-500 font-bold text-lg'
                        ]),
                ])->visible(fn ($record) => $record !== null),
            ]),
        ]);
    }

    public static function updateItemTotal(callable $get, callable $set): void
    {
        $qty   = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);
    }

    public static function updateTotals(callable $get, callable $set): void
    {
        $items = $get('items') ?? [];
        $subtotal = collect($items)->sum(fn ($item) =>
            (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );

        $discount = (float) ($get('discount') ?? 0);
        $tax      = (float) ($get('tax') ?? 0);

        $afterDiscount = $subtotal * (1 - ($discount / 100));
        $grandTotal    = $afterDiscount * (1 + ($tax / 100));

        $set('subtotal', $subtotal);
        $set('grand_total', $grandTotal);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Tagihan')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Sisa')
                    ->money('IDR', true)
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'sent'      => 'warning',
                        'partial'   => 'info',
                        'paid'      => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'     => 'Draft',
                        'sent'      => 'Terkirim',
                        'partial'   => 'Parsial',
                        'paid'      => 'Lunas',
                        'cancelled' => 'Batal',
                        default     => $state,
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => 'Lunas',
                        'partial' => 'Belum Lunas (Partial)',
                        'sent' => 'Belum Bayar (Sent)',
                    ]),
            ])
            ->actions([
                Action::make('download_pdf')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function (Invoice $record) {
                        $validationUrl = route('invoice.verify', $record->invoice_number);
                        $qrCode = new QrCode(data: $validationUrl, encoding: new Encoding('UTF-8'), size: 200, margin: 10);
                        $writer = new PngWriter();
                        $qrBase64 = base64_encode($writer->write($qrCode)->getString());

                        $generator = new BarcodeGeneratorPNG();
                        $barBase64 = base64_encode($generator->getBarcode($record->invoice_number, $generator::TYPE_CODE_128));

                        $pdf = Pdf::loadView('pdf.invoice', [
                            'invoice' => $record,
                            'qrCode'  => $qrBase64,
                            'barcode' => $barBase64,
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'Invoice-' . Str::slug($record->invoice_number) . '.pdf');
                    }),

                Action::make('sendEmail')
                    ->label('Email')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => !empty($record->customer->email))
                    ->action(function (Invoice $record) {
                        try {
                            Mail::to($record->customer->email)->send(new InvoiceSent($record));
                            if ($record->status === 'draft') $record->update(['status' => 'sent']);
                            Notification::make()->title('Email Terkirim')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view'   => Pages\ViewInvoice::route('/{record}'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
