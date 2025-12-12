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
            Section::make('Informasi Pesanan')->schema([
                // Baris 1: Order No, Tanggal, PO Customer
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

                    // === KOLOM BARU: PO CUSTOMER ===
                    TextInput::make('customer_po_number')
                        ->label('No. PO Customer')
                        ->placeholder('Contoh: PO-ABC-001')
                        ->helperText('Opsional, isi jika ada Purchase Order dari customer.')
                        ->maxLength(50),
                ]),

                // Baris 2: Quotation (Logic Copy Data)
                Grid::make(1)->schema([
                    Select::make('nx_quotation_id')
                        ->label('No. Penawaran (Opsional)')
                        ->relationship('quotation', 'quotation_number')
                        ->searchable()
                        ->placeholder('Pilih Penawaran untuk copy data otomatis')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) return;

                            $quotation = Quotation::with('items')->find($state);
                            if (! $quotation) return;

                            // Copy Header
                            $set('nx_customer_id', $quotation->nx_customer_id);
                            $set('discount', (float) ($quotation->discount ?? 0));
                            $set('tax', (float) ($quotation->tax ?? 0));

                            // Copy Items
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

                            // Hitung ulang total
                            \App\Filament\Resources\Sales\QuotationResource::updateTotals($get, $set);
                        })
                        ->disabled(fn (?SalesOrder $record) => filled($record)),
                ]),

                // Baris 3: Customer, Dibuat Oleh, Status
                Grid::make(3)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required()
                        ->prefixIcon('heroicon-o-user-circle'),

                    // === EDIT: OTOMATIS LOGIN USER ===
                    Select::make('nx_employee_id')
                        ->label('Dibuat Oleh')
                        ->relationship('employee', 'full_name')
                        ->default(fn () => auth()->user()->employee?->id) // Auto Fill
                        ->disabled() // User cannot change
                        ->dehydrated() // Save to DB
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

            // ITEM REPEATER
            Section::make('Daftar Item Pesanan')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('item_type')->disabled()->dehydrated(),
                        TextInput::make('item_id')->disabled()->dehydrated(),

                        TextInput::make('item_code')->label('Kode')->readOnly()->dehydrated(),
                        TextInput::make('item_name')->label('Nama Item')->readOnly()->dehydrated(),

                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->numeric()->required()->reactive(),

                        TextInput::make('unit_price')
                            ->label('Harga Satuan')
                            ->numeric()->required()->prefix('Rp')->reactive(),

                        TextInput::make('line_total')
                            ->label('Subtotal Item')
                            ->numeric()->dehydrated()->disabled()->prefix('Rp'),
                    ])
                    // Logic hitung total dipanggil setiap ada perubahan
                    ->reactive()
                    ->afterStateUpdated(fn (callable $get, callable $set) => \App\Filament\Resources\Sales\QuotationResource::updateTotals($get, $set))
                    ->columns(2)
                    ->createItemButtonLabel('Tambah Item'),
            ])->collapsed(),

            // TOTALS
            Section::make('Perhitungan Akhir')->schema([
                Grid::make(4)->schema([
                    TextInput::make('subtotal')->label('Subtotal')->disabled()->dehydrated()->prefix('Rp'),

                    TextInput::make('discount')->label('Diskon (%)')->numeric()->default(0)->reactive()
                        ->afterStateUpdated(fn (callable $get, callable $set) => \App\Filament\Resources\Sales\QuotationResource::updateTotals($get, $set))
                        ->prefixIcon('heroicon-o-tag'),

                    TextInput::make('tax')->label('Pajak (%)')->numeric()->default(0)->reactive()
                        ->afterStateUpdated(fn (callable $get, callable $set) => \App\Filament\Resources\Sales\QuotationResource::updateTotals($get, $set))
                        ->prefixIcon('heroicon-o-receipt-percent'),

                    TextInput::make('grand_total')->label('Grand Total')->disabled()->dehydrated()->prefix('Rp'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Nomor SO')->sortable()->searchable()->weight('bold'),

                // Tambahan kolom PO di Tabel (Opsional, agar mudah dicari)
                Tables\Columns\TextColumn::make('customer_po_number')
                    ->label('PO Customer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Hidden by default biar gak penuh

                Tables\Columns\TextColumn::make('quotation.quotation_number')->label('No. Penawaran')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order_date')->label('Tgl Pesan')->date('d M Y'),
                Tables\Columns\TextColumn::make('grand_total')->label('Total')->money('IDR', true),

                // Filament 3 Badge Syntax
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'confirmed' => 'primary',
                        'shipped' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'processing' => 'Diproses',
                        'confirmed' => 'Dikonfirmasi',
                        'shipped' => 'Dikirim',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

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
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
