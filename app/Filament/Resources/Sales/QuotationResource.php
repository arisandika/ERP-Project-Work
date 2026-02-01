<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\QuotationResource\Pages;
use App\Mail\QuotationSent;
use App\Models\Inventory\Package;
use App\Models\Inventory\Product;
use App\Models\Inventory\Service;
use App\Models\Sales\PromoCode;
use App\Models\Sales\Quotation;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'sales/quotation';
    protected static ?string $pluralModelLabel = 'Penawaran';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Penawaran')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('quotation_number')
                                ->label('No. Penawaran')
                                ->disabled()
                                ->dehydrated()
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-o-hashtag'),

                            DatePicker::make('quotation_date')
                                ->label('Tanggal Penawaran')
                                ->default(now())
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->required()
                                ->displayFormat('d M Y')
                                ->native(false),

                            DatePicker::make('valid_until')
                                ->label('Berlaku Hingga')
                                ->default(now()
                                    ->addDays(7))
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->required()
                                ->displayFormat('d M Y')
                                ->native(false),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Select::make('nx_customer_id')
                                ->label('Pelanggan')
                                ->searchable()
                                ->preload()
                                ->relationship('customer', 'name')
                                ->required()
                                ->prefixIcon('heroicon-o-user-circle'),

                            Select::make('nx_employee_id')
                                ->label('Ditugaskan Kepada')
                                ->relationship('employee', 'full_name')
                                ->default(fn() => auth()
                                    ->user()?->employee?->id)
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->prefixIcon('heroicon-o-user'),

                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'sent' => 'Terkirim',
                                    'accepted' => 'Diterima',
                                    'rejected' => 'Ditolak',
                                ])
                                ->default('draft')
                                ->required()
                                ->prefixIcon('heroicon-o-adjustments-vertical'),

                            Textarea::make('notes')
                                ->label('Catatan Tambahan')
                                ->rows(3),
                        ]),
                ]),

            Section::make('Daftar Item Penawaran')
                ->schema([
                    Repeater::make('items')
                        ->schema(self::getQuotationItemsSchema())
                        ->relationship()
                        ->columns(2)
                        ->live()
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set))
                        ->createItemButtonLabel('Tambah Item')
                        ->defaultItems(1)
                        ->deletable(true)
                        ->addable(true)
                        ->reorderable(false),
                ])
                ->collapsible(),

            Section::make('Perhitungan Akhir')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            // 1. SUBTOTAL
                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->disabled()
                                ->dehydrated()
                                ->prefix('IDR')
                                ->numeric()
                                ->formatStateUsing(fn($state) => (int) $state),

                            // 2. PROMO LOGIC
                            TextInput::make('promo_code_input')
                                ->label('Kode Promo')
                                ->placeholder('Masukkan kode promo')
                                ->dehydrated(false)
                                ->formatStateUsing(fn($record) => $record?->promoCode?->code)
                                ->suffixAction(
                                    FormAction::make('apply_promo')
                                        ->icon('heroicon-m-ticket')
                                        ->color('success')
                                        ->label('Apply')
                                        ->action(fn($state, Set $set, Get $get) => self::applyPromo($state, $set, $get))
                                ),

                            Hidden::make('promo_code_id'),

                            Hidden::make('temp_discount_type')
                                ->dehydrated(false),

                            Hidden::make('temp_discount_value')
                                ->dehydrated(false),

                            // 3. DISCOUNT
                            TextInput::make('discount_amount')
                                ->label('Potongan')
                                ->disabled()
                                ->dehydrated()
                                ->prefix('IDR')
                                ->numeric()
                                ->formatStateUsing(fn($state) => (int) $state),

                            // 4. TAX
                            TextInput::make('tax')
                                ->label('Pajak (%)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn($state, Set $set, Get $get) => self::updateTotals($get, $set))
                                ->formatStateUsing(fn($state) => (float) $state)
                                ->prefixIcon('heroicon-o-receipt-percent'),

                            // 5. GRAND TOTAL
                            TextInput::make('grand_total')
                                ->label('Total')
                                ->disabled()
                                ->dehydrated()
                                ->prefix('IDR')
                                ->extraInputAttributes(['style' => 'font-weight: bold;'])
                                ->numeric()
                                ->formatStateUsing(fn($state) => (int) $state),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->label('No. Penawaran')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('quotation_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR', true)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Created From')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false),

                        DatePicker::make('created_until')
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
                Tables\Actions\Action::make('send')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        if (!$record->customer || !$record->customer->email) {
                            Notification::make()
                                ->title('Email customer tidak tersedia!')
                                ->danger()
                                ->send();
                            return;
                        }
                        Mail::to($record->customer->email)
                            ->send(new QuotationSent($record));
                        $record->update(['status' => 'sent']);
                        Notification::make()
                            ->title('Penawaran berhasil dikirim')
                            ->success()
                            ->send();
                    }),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }

    public static function getQuotationItemsSchema(): array
    {
        return [
            Select::make('item_type')
                ->label('Tipe')
                ->options(['product' => 'Product', 'service' => 'Service', 'package' => 'Package'])
                ->default('product')
                ->reactive()
                ->required()
                ->afterStateUpdated(function (Set $set, Get $get) {
                    $set('item_id', null);
                    $set('item_code', null);
                    $set('item_code_display', null);
                    $set('item_name', null);
                    $set('unit_price', 0);
                    $set('cost_price', 0);
                    $set('line_total', 0);
                    $set('qty', 1);
                    self::updateTotals($get, $set);
                }),

            Select::make('item_id')
                ->label('Pilih Item')
                ->options(function (Get $get) {
                    $type = $get('item_type');

                    if ($type === 'App\\Models\\Inventory\\Product' || $type === Product::class) $type = 'product';
                    if ($type === 'App\\Models\\Inventory\\Service' || $type === Service::class) $type = 'service';
                    if ($type === 'App\\Models\\Inventory\\Package' || $type === Package::class) $type = 'package';

                    return match ($type) {
                        'product' => Product::query()->pluck('product_name', 'id'),
                        'service' => Service::query()->pluck('service_name', 'id'),
                        'package' => Package::query()->pluck('package_name', 'id'),
                        default => [],
                    };
                })
                ->getOptionLabelUsing(function ($value, Get $get) {
                    $type = $get('item_type');

                    if ($type === 'App\\Models\\Inventory\\Product' || $type === Product::class) $type = 'product';
                    if ($type === 'App\\Models\\Inventory\\Service' || $type === Service::class) $type = 'service';
                    if ($type === 'App\\Models\\Inventory\\Package' || $type === Package::class) $type = 'package';

                    $modelClass = match ($type) {
                        'product' => Product::class,
                        'service' => Service::class,
                        'package' => Package::class,
                        default => null
                    };

                    if (!$modelClass || !$value) return null;

                    $record = $modelClass::find($value);

                    return $record?->product_name
                        ?? $record?->service_name
                        ?? $record?->package_name
                        ?? $record?->name;
                })
                ->visible(fn(Get $get) => !empty($get('item_type')))
                ->searchable()
                ->preload()
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if (!$state) {
                        return;
                    }

                    $type = $get('item_type');

                    if ($type === 'App\\Models\\Inventory\\Product' || $type === Product::class) $type = 'product';
                    if ($type === 'App\\Models\\Inventory\\Service' || $type === Service::class) $type = 'service';
                    if ($type === 'App\\Models\\Inventory\\Package' || $type === Package::class) $type = 'package';

                    $model = match ($type) {
                        'product' => Product::find($state),
                        'service' => Service::find($state),
                        'package' => Package::find($state),
                        default => null
                    };

                    if ($model) {
                        $name = $model->product_name ?? $model->service_name ?? $model->package_name ?? $model->name;
                        $code = $model->product_code ?? $model->service_code ?? $model->package_code ?? $model->code ?? 'CODE-' . $state;

                        $sellPrice = match ($type) {
                            'product' => (float) ($model->selling_price ?? $model->price ?? 0),
                            'service' => (float) ($model->price ?? 0),
                            'package' => (float) ($model->total_price ?? 0),
                            default => 0
                        };
                        $buyPrice = match ($type) {
                            'product' => (float) ($model->purchase_price ?? 0),
                            default => 0
                        };

                        $set('item_name', $name);
                        $set('item_code', $code);
                        $set('item_code_display', $code);
                        $set('unit_price', $sellPrice);
                        $set('cost_price', $buyPrice);

                        self::updateItemTotal($get, $set);
                    }
                }),

            Hidden::make('cost_price')
                ->dehydrated(true),

            TextInput::make('item_code')
                ->label('Kode Produk')
                ->disabled()
                ->dehydrated()
                ->required(),

            TextInput::make('item_name')
                ->label('Nama Produk')
                ->disabled()
                ->dehydrated(true),

            TextInput::make('qty')
                ->label('Qty')
                ->numeric()
                ->integer()
                ->default(1)
                ->minValue(1)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if ($state < 1) {
                        $set('qty', 1);
                    }

                    self::updateItemTotal($get, $set);
                }),

            TextInput::make('unit_price')
                ->label('Harga Satuan')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->prefix('IDR')
                ->formatStateUsing(fn($state) => (int) $state)
                ->reactive()
                ->afterStateUpdated(fn(Set $set, Get $get) => self::updateItemTotal($get, $set)),

            TextInput::make('line_total')
                ->label('Subtotal')
                ->disabled()
                ->dehydrated()
                ->numeric()
                ->prefix('IDR')
                ->extraInputAttributes(['style' => 'font-weight: bold;'])
                ->formatStateUsing(fn($state) => (int) $state),
        ];
    }

    public static function updateItemTotal(Get $get, Set $set): void
    {
        $qty = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);

        self::updateTotals($get, $set);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items');
        $pathPrefix = '';

        if ($items === null) {
            $items = $get('../../items');
            $pathPrefix = '../../';
        }

        $items = $items ?? [];

        $subtotal = collect($items)
            ->sum(fn($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0));

        $set($pathPrefix . 'subtotal', $subtotal);

        $discountType = $get($pathPrefix . 'temp_discount_type');
        $discountValue = (float) $get($pathPrefix . 'temp_discount_value');

        if (!$discountType && $promoId = $get($pathPrefix . 'promo_code_id')) {
            $promo = PromoCode::find($promoId);
            if ($promo) {
                $discountType = $promo->type;
                $discountValue = (float) $promo->value;
                $set($pathPrefix . 'temp_discount_type', $discountType);
                $set($pathPrefix . 'temp_discount_value', $discountValue);
                $set($pathPrefix . 'promo_code_input', $promo->code);
            }
        }

        $totalDiscount = 0;
        if ($discountType === 'percentage') {
            $totalDiscount = $subtotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $totalDiscount = $discountValue;
        }

        $totalDiscount = min($totalDiscount, $subtotal);
        $set($pathPrefix . 'discount_amount', $totalDiscount);

        $taxPercent = (float) ($get($pathPrefix . 'tax') ?? 0);
        $afterDiscount = $subtotal - $totalDiscount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);

        $set($pathPrefix . 'grand_total', $afterDiscount + $taxAmount);
    }

    public static function applyPromo($code, Set $set, Get $get): void
    {
        if (empty($code)) {
            $set('promo_code_id', null);
            $set('temp_discount_type', null);
            $set('temp_discount_value', 0);
            self::updateTotals($get, $set);
            return;
        }

        $promo = PromoCode::where('code', $code)
            ->where('is_active', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        if (!$promo) {
            Notification::make()
                ->title('Kode tidak valid atau kadaluwarsa!')
                ->danger()
                ->send();
            $set('promo_code_id', null);
            $set('temp_discount_type', null);
            $set('temp_discount_value', 0);
        } else {
            Notification::make()
                ->title("Promo Applied!")
                ->success()
                ->send();
            $set('promo_code_id', $promo->id);
            $set('temp_discount_type', $promo->type);
            $set('temp_discount_value', $promo->value);
        }
        self::updateTotals($get, $set);
    }
}
