<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\DeliveryOrderResource\Pages;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

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
                        ->label('No. Surat Jalan')
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-o-hashtag'),

                    DatePicker::make('do_date')
                        ->label('Tanggal Surat Jalan')
                        ->default(now())
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->required()
                        ->displayFormat('d M Y')
                        ->native(false),

                    Select::make('nx_sales_order_id')
                        ->label('No. Sales Order (Ref)')
                        ->relationship(
                            'salesOrder',
                            'order_number',
                            modifyQueryUsing: fn(Builder $query) =>
                            $query->whereIn('status', ['confirmed', 'processing', 'shipped'])
                                ->orderByDesc('created_at')
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->placeholder('Pilih Sales Order')
                        ->disabled(fn($record) => $record && $record->exists)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('items', []);
                            $set('nx_customer_id', null);

                            if (!$state)
                                return;

                            $so = SalesOrder::with('items')->find($state);
                            if (!$so)
                                return;

                            $set('nx_customer_id', $so->nx_customer_id);

                            $existingDOs = DeliveryOrder::with('items')
                                ->where('nx_sales_order_id', $state)
                                ->where('status', '!=', 'cancelled')
                                ->get();

                            $items = $so->items->map(function ($item) use ($existingDOs) {
                                $qtyOrder = floatval($item->qty ?? 0);

                                // Hitung total qty item ini yang SUDAH dikirim di DO lain
                                $qtyShipped = $existingDOs->flatMap->items
                                    ->where('item_id', $item->item_id)
                                    ->sum('qty');

                                // Sisa jatah yang BOLEH dikirim sekarang
                                $qtyRemainingQuota = max($qtyOrder - $qtyShipped, 0);

                                return [
                                    'item_type' => $item->item_type,
                                    'item_id' => $item->item_id,
                                    'item_code' => (string) $item->item_code,
                                    'item_name' => (string) $item->item_name,
                                    'qty_ordered' => $qtyRemainingQuota,
                                    'qty' => 0,
                                    'qty_remaining' => $qtyRemainingQuota,
                                ];
                            })->values()->toArray();

                            $set('items', $items);
                        })
                        ->required()
                        ->prefixIcon('heroicon-o-hashtag'),
                ]),

                Grid::make(2)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user-circle'),

                    Select::make('nx_employee_id')
                        ->label('Ditugaskan Kepada')
                        ->relationship('employee', 'full_name')
                        ->default(fn() => auth()->user()->employee?->id)
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->prefixIcon('heroicon-o-user'),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'ready' => 'Siap Kirim',
                            'on_delivery' => 'Dalam Pengiriman',
                            'delivered' => 'Terkirim',
                            'cancelled' => 'Dibatalkan',
                        ])
                        ->default('draft')
                        ->required()
                        ->prefixIcon('heroicon-o-adjustments-vertical'),

                    Textarea::make('notes')
                        ->label('Catatan Tambahan')
                        ->rows(3),
                ]),
            ]),

            Section::make('Daftar Item Surat Jalan')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Hidden::make('item_type')->default('product'),
                        Hidden::make('item_id'),
                        Hidden::make('item_code'),
                        
                        Grid::make(2)->schema([
                            TextInput::make('item_code')
                                ->label('Kode Produk')
                                ->disabled()
                                ->dehydrated(false),
    
                            TextInput::make('item_name')
                                ->label('Nama Produk')
                                ->disabled()
                                ->dehydrated(true),

                            TextInput::make('qty_ordered')
                                ->label('Sisa Jatah')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('qty')
                                ->label('Kirim Sekarang')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->lte('qty_ordered')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                    $quota = (float) ($get('qty_ordered') ?? 0);
                                    $kirim = (float) ($state ?? 0);
                                    $set('qty_remaining', max($quota - $kirim, 0));
                                }),

                            TextInput::make('qty_remaining')
                                ->label('Sisa Nanti')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated(),
                        ]),
                    ])
                    ->columns(1)
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
                Tables\Columns\TextColumn::make('do_number')
                    ->label('No. Surat Jalan')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('salesOrder.order_number')
                    ->label('Ref. Pesanan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('do_date')
                    ->label('Tanggal')
                    ->date('d M Y H:i'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'ready' => 'warning',
                        'on_delivery' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'ready' => 'Siap Kirim',
                        'on_delivery' => 'Dalam Pengiriman',
                        'delivered' => 'Diterima',
                        'cancelled' => 'Batal',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
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
                Action::make('upload_proof')
                    ->label('Upload Bukti')
                    ->icon('heroicon-o-camera')
                    ->color('info')
                    ->visible(fn(DeliveryOrder $record) => in_array($record->status, ['on_delivery', 'delivered']))
                    ->form([
                        FileUpload::make('proof_image')
                            ->label('Foto Penerimaan')
                            ->image()
                            ->imageEditor()
                            ->directory('delivery-proofs')
                            ->required(),
                        Textarea::make('proof_notes')
                            ->label('Catatan Penerima')
                            ->placeholder('Diterima oleh siapa? Keterangan barang?')
                            ->rows(2),
                    ])
                    ->action(function (DeliveryOrder $record, array $data): void {
                        $record->update([
                            'proof_image' => $data['proof_image'],
                            'proof_notes' => $data['proof_notes'],
                            'status' => 'delivered',
                        ]);
                        Notification::make()->title('Berhasil')->body('Bukti foto tersimpan.')->success()->send();
                    })
                    ->modalHeading('Upload Bukti Barang Sampai')
                    ->modalSubmitActionLabel('Simpan Bukti')
                    ->modalWidth('md'),

                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn(DeliveryOrder $record) => route('print.delivery-order', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            'edit' => Pages\EditDeliveryOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
