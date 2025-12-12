<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\DeliveryOrderResource\Pages;
use App\Models\Sales\DeliveryOrder;
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

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?int $navigationSort = 7;
    protected static ?string $slug = 'sales/delivery-order';
    protected static ?string $pluralModelLabel = 'Surat Jalan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Surat Jalan')->schema([
                Grid::make(3)->schema([
                    TextInput::make('do_number')
                        ->label('Nomor DO')
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-o-hashtag'),

                    DatePicker::make('do_date')
                        ->label('Tanggal DO')
                        ->default(now())
                        ->required()
                        ->prefixIcon('heroicon-o-calendar-days'),

                    // === LOGIKA AUTO-FILL ===
                    Select::make('nx_sales_order_id')
                        ->label('No. Sales Order')
                        ->relationship('salesOrder', 'order_number')
                        ->searchable()
                        ->placeholder('Pilih Sales Order')
                        ->reactive() // Wajib aktif agar trigger afterStateUpdated
                        ->afterStateUpdated(function ($state, callable $set) {
                            // 1. Reset state items biar bersih
                            $set('items', []);
                            $set('nx_customer_id', null);

                            if (! $state) return;

                            // 2. Ambil data SO
                            $so = SalesOrder::with('items')->find($state);
                            if (! $so) return;

                            // 3. Set Customer
                            $set('nx_customer_id', $so->nx_customer_id);

                            // 4. Mapping Item
                            $items = $so->items->map(function ($item) {
                                // Pastikan Qty diambil sebagai float
                                $qty = floatval($item->qty ?? 0);

                                return [
                                    'item_type'       => $item->item_type,
                                    'item_id'         => $item->item_id,
                                    'item_code'       => (string) $item->item_code,
                                    'item_name'       => (string) $item->item_name,

                                    // Masukkan ke kolom baru (qty_ordered)
                                    'qty_ordered'     => $qty,

                                    // Default Kirim 0 (Masuk ke kolom 'qty')
                                    'qty'             => 0,

                                    // Default Sisa = Full (Masuk ke kolom baru)
                                    'qty_remaining'   => $qty,
                                ];
                            })->values()->toArray(); // Reset index array

                            // 5. Masukkan ke Repeater
                            $set('items', $items);
                        })
                        ->disabled(fn (?DeliveryOrder $record) => filled($record))
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required()
                        ->prefixIcon('heroicon-o-user-circle'),

                    Select::make('nx_employee_id')
                        ->label('Dibuat Oleh')
                        ->relationship('employee', 'full_name')
                        ->default(fn () => auth()->user()?->employee?->id)
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user'),
                ]),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'       => 'Draft',
                        'ready'       => 'Siap Kirim',
                        'on_delivery' => 'Dalam Pengiriman',
                        'delivered'   => 'Terkirim',
                        'cancelled'   => 'Dibatalkan',
                    ])
                    ->default('draft')
                    ->required()
                    ->prefixIcon('heroicon-o-adjustments-vertical'),

                Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->columnSpanFull(),
            ])->columns(1),

            Section::make('Item Surat Jalan')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        // Hidden IDs
                        TextInput::make('item_type')->hidden()->dehydrated(),
                        TextInput::make('item_id')->hidden()->dehydrated(),

                        // Readonly Info
                        TextInput::make('item_code')->label('Kode')->disabled()->dehydrated(),
                        TextInput::make('item_name')->label('Nama Item')->disabled()->dehydrated(),

                        // 1. QTY ORDER (Disimpan di kolom qty_ordered)
                        TextInput::make('qty_ordered')
                            ->label('Qty Order')
                            ->numeric()
                            ->default(0)
                            ->readOnly() // ReadOnly agar value tetap terkirim
                            ->dehydrated(),

                        // 2. QTY KIRIM (Disimpan di kolom qty)
                        TextInput::make('qty') // Nama sesuai kolom DB 'qty'
                            ->label('Qty Kirim')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->lte('qty_ordered') // Validasi: Kirim <= Order
                            ->required()
                            ->reactive() // Trigger hitung sisa
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $ordered = (float) ($get('qty_ordered') ?? 0);
                                $deliv   = (float) ($state ?? 0);
                                $set('qty_remaining', max($ordered - $deliv, 0));
                            }),

                        // 3. QTY SISA (Disimpan di kolom qty_remaining)
                        TextInput::make('qty_remaining')
                            ->label('Qty Sisa')
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->dehydrated(),
                    ])
                    ->columns(5)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('do_number')->label('Nomor DO')->sortable()->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('salesOrder.order_number')->label('Nomor SO')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('do_date')->label('Tanggal DO')->date('d M Y'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'       => 'gray',
                        'ready'       => 'warning',
                        'on_delivery' => 'info',
                        'delivered'   => 'success',
                        'cancelled'   => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'       => 'Draft',
                        'ready'       => 'Siap Kirim',
                        'on_delivery' => 'Dikirim',
                        'delivered'   => 'Sampai',
                        'cancelled'   => 'Batal',
                        default       => $state,
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // === ACTION CETAK: URL ke Route ===
                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (DeliveryOrder $record) => route('print.delivery-order', $record))
                    ->openUrlInNewTab(),

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
            'index'  => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            'view'   => Pages\ViewDeliveryOrder::route('/{record}'),
            'edit'   => Pages\EditDeliveryOrder::route('/{record}/edit'),
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
