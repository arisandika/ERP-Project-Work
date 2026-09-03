<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\InvoiceResource\Pages;
use App\Filament\Resources\Sales\InvoiceResource\RelationManagers;
use App\Mail\InvoiceSent;
use App\Models\Finance\FinancialRecord;
use App\Models\Sales\Invoice;
use App\Models\Sales\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Throwable;
use App\Filament\Concerns\BelongsToModule;

class InvoiceResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'sales';
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'sales/invoices';
    protected static ?string $pluralModelLabel = 'Invoice';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(['default' => 1, 'sm' => 3])->schema([
                Group::make()->schema([
                    Section::make('Informasi Invoice')->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('invoice_number')
                                ->label('No. Invoice')
                                ->disabled()
                                ->dehydrated()
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-o-hashtag'),

                            Select::make('nx_sales_order_id')
                                ->label('No. Sales Order (Ref)')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->relationship(
                                    name: 'salesOrder',
                                    titleAttribute: 'order_number',
                                    modifyQueryUsing: fn ($query) => $query->orderByDesc('created_at')
                                )
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if (! $state) {
                                        return;
                                    }

                                    $so = SalesOrder::with([
                                        'items' => fn ($query) => $query->orderBy('created_at', 'asc'),
                                    ])->find($state);

                                    if (! $so) {
                                        return;
                                    }

                                    $set('customer_po_number', $so->customer_po_number);
                                    $set('nx_customer_id', $so->nx_customer_id);
                                    $set('subtotal', (float) ($so->subtotal ?? 0));
                                    $set('discount', (float) ($so->discount_amount ?? 0));
                                    $set('tax', (float) ($so->tax ?? 0));

                                    $items = $so->items->map(function ($item) {
                                        $qty = (float) ($item->qty ?? 0);
                                        $price = (float) ($item->unit_price ?? 0);

                                        return [
                                            'item_type' => $item->item_type,
                                            'item_id' => $item->item_id,
                                            'item_code' => $item->item_code,
                                            'item_name' => $item->item_name,
                                            'qty' => $qty,
                                            'unit_price' => $price,
                                            'line_total' => (float) ($item->line_total ?? ($qty * $price)),
                                        ];
                                    })->toArray();

                                    $set('items', $items);
                                    self::updateTotals($get, $set);
                                })
                                ->disabled(fn (?Invoice $record) => filled($record))
                                ->required(),

                            DatePicker::make('invoice_date')
                                ->label('Tanggal Invoice')
                                ->default(now())
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->required()
                                ->displayFormat('d M Y')
                                ->native(false),

                            DatePicker::make('due_date')
                                ->label('Jatuh Tempo')
                                ->default(now()->addDays(7))
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->required()
                                ->displayFormat('d M Y')
                                ->native(false),

                            TextInput::make('customer_po_number')
                                ->label('No. PO Customer')
                                ->placeholder('Contoh: PO-ABC-001')
                                ->maxLength(50),

                            Select::make('nx_customer_id')
                                ->label('Customer')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->required()
                                ->prefixIcon('heroicon-o-user-circle'),

                            Select::make('nx_employee_id')
                                ->label('Ditugaskan Kepada')
                                ->relationship('employee', 'full_name')
                                ->default(fn () => auth()->user()?->employee?->id)
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->prefixIcon('heroicon-o-user'),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'draft' => 'Draft',
                                    'sent' => 'Terkirim',
                                    'partial' => 'Terbayar Sebagian',
                                    'paid' => 'Lunas',
                                    'cancelled' => 'Dibatalkan',
                                ])
                                ->default('draft')
                                ->required()
                                ->live()
                                ->prefixIcon('heroicon-o-adjustments-vertical'),
                        ]),

                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->rows(3),
                    ]),

                    Section::make('Daftar Item Invoice')->schema([
                            Repeater::make('items')
                                ->schema([
                                    Forms\Components\Hidden::make('id'),
                                    Forms\Components\Hidden::make('item_type'),
                                    Forms\Components\Hidden::make('item_id'),

                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        TextInput::make('item_code')
                                            ->label('Kode Product')
                                            ->readOnly()
                                            ->dehydrated(),

                                        TextInput::make('item_name')
                                            ->label('Nama Item')
                                            ->readOnly()
                                            ->dehydrated(),

                                        TextInput::make('qty')
                                            ->label('Qty')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateItemTotal($get, $set)),

                                        TextInput::make('unit_price')
                                            ->label('Harga Satuan')
                                            ->numeric()
                                            ->prefix('IDR')
                                            ->required()
                                            ->minValue(0)
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateItemTotal($get, $set)),

                                        TextInput::make('line_total')
                                            ->label('Subtotal')
                                            ->numeric()
                                            ->prefix('IDR')
                                            ->required()
                                            ->minValue(0)
                                            ->dehydrated()
                                            ->disabled()
                                            ->extraInputAttributes(['style' => 'font-weight: bold;']),
                                    ]),
                                ])
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->reactive()
                                ->columns(1),
                        ])->collapsed(),
                ])->columnSpan(['lg' => 2]),

                Group::make()->schema([
                    Section::make('Perhitungan Akhir')->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('IDR')
                            ->required()
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('discount')
                            ->label('Diskon')
                            ->numeric()
                            ->default(0)
                            ->reactive()
                            ->dehydrated()
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotals($get, $set))
                            ->prefixIcon('heroicon-o-tag')
                            ->minValue(0),

                        TextInput::make('tax')
                            ->label('Pajak (%)')
                            ->numeric()
                            ->default(0)
                            ->reactive()
                            ->dehydrated()
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotals($get, $set))
                            ->prefixIcon('heroicon-o-receipt-percent')
                            ->minValue(0)
                            ->maxValue(100),

                        TextInput::make('grand_total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('IDR')
                            ->required()
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated()
                            ->extraInputAttributes([
                                'style' => 'font-weight: bold; font-size: 1.1em; color: #10b981;',
                            ]),

                        Forms\Components\Placeholder::make('total_paid_view')
                            ->label('Sudah Dibayar')
                            ->content(fn ($record) => 'IDR ' . number_format((float) ($record?->total_paid ?? 0), 0, ',', '.'))
                            ->extraAttributes(['class' => 'text-success-600 font-bold text-lg'])
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\Placeholder::make('remaining_balance_view')
                            ->label('Sisa Tagihan')
                            ->content(fn ($record) => 'IDR ' . number_format((float) ($record?->remaining_balance ?? 0), 0, ',', '.'))
                            ->extraAttributes(fn ($record) => [
                                'class' => (($record?->remaining_balance ?? 0) > 0)
                                    ? 'text-danger-600 font-bold text-lg'
                                    : 'text-gray-500 font-bold text-lg'
                            ])
                            ->visible(fn ($record) => $record !== null),
                    ]),
                ])->columnSpan(['lg' => 1]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Tanggal')
                    ->date('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Tagihan')
                    ->money('IDR', true)
                    ->color(fn ($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Sisa')
                    ->money('IDR', true)
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'partial' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'partial' => 'Parsial',
                        'paid' => 'Lunas',
                        'cancelled' => 'Batal',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => 'Lunas',
                        'partial' => 'Belum Lunas (Partial)',
                        'sent' => 'Belum Bayar (Sent)',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Status')
                    ->native(false),
            ])
            ->actions([
                Action::make('download_pdf')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function (Invoice $record) {
                        $validationUrl = route('invoice.verify.form', ['number' => $record->invoice_number]);

                        $qrCode = new QrCode(
                            data: $validationUrl,
                            encoding: new Encoding('UTF-8'),
                            size: 200,
                            margin: 10
                        );

                        $writer = new PngWriter();
                        $qrBase64 = base64_encode($writer->write($qrCode)->getString());

                        $generator = new BarcodeGeneratorPNG();
                        $barBase64 = base64_encode(
                            $generator->getBarcode($record->invoice_number, $generator::TYPE_CODE_128)
                        );

                        $pdf = Pdf::loadView('pdf.invoice', [
                            'invoice' => $record,
                            'qrCode' => $qrBase64,
                            'barcode' => $barBase64,
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'Invoice-' . Str::slug($record->invoice_number) . '.pdf');
                    }),

                Action::make('sendEmail')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => ! empty($record->customer->email))
                    ->action(function (Invoice $record) {
                        try {
                            DB::transaction(function () use ($record) {
                                Mail::to($record->customer->email)->queue(new InvoiceSent($record));

                                if ($record->status === 'draft') {
                                    $record->update(['status' => 'sent']);
                                }

                                $existingReceivable = FinancialRecord::query()
                                    ->where('type', 'piutang')
                                    ->where('reference_type', Invoice::class)
                                    ->where('reference_id', $record->id)
                                    ->exists();

                                if (! $existingReceivable) {
                                    FinancialRecord::create([
                                        'transaction_date' => $record->invoice_date,
                                        'type' => 'piutang',
                                        'amount' => (float) $record->grand_total,
                                        'category' => 'Accounts Receivable',
                                        'description' => 'Piutang customer dari invoice ' . $record->invoice_number,
                                        'reference_number' => $record->invoice_number,
                                        'reference_type' => Invoice::class,
                                        'reference_id' => $record->id,
                                        'created_by' => auth()->user()?->employee?->id,
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Invoice berhasil dikirim dan piutang berhasil dibuat')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Gagal kirim invoice')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function updateItemTotal(callable $get, callable $set): void
    {
        $qty = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);

        $set('line_total', round($qty * $price, 2));

        $items = $get('../../items') ?? $get('items') ?? null;
        if ($items !== null) {
            self::updateTotals($get, $set);
        }
    }

    public static function updateTotals(callable $get, callable $set): void
    {
        $items = $get('items') ?? [];

        $subtotal = collect($items)->sum(function ($item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);

            return $qty * $price;
        });

        $discount = (float) ($get('discount') ?? 0);
        $discount = min($discount, $subtotal);

        $tax = (float) ($get('tax') ?? 0);
        $tax = max(0, min($tax, 100));

        $afterDiscount = $subtotal - $discount;
        $grandTotal = $afterDiscount + ($afterDiscount * ($tax / 100));

        $set('subtotal', round($subtotal, 2));
        $set('discount', round($discount, 2));
        $set('tax', round($tax, 2));
        $set('grand_total', round($grandTotal, 2));
    }
}
