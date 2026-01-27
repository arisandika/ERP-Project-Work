<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\QuotationResource\Pages;
use App\Mail\QuotationSent;
use App\Models\Sales\PromoCode;
use App\Models\Sales\Quotation;
use App\Models\Inventory\Product;
use App\Models\Inventory\Service;
use App\Models\Inventory\Package;
use Filament\Forms;
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
            // --- SECTION 1: HEADER ---
            Section::make('Informasi Penawaran')->schema([
                Grid::make(3)->schema([
                    TextInput::make('quotation_number')
                        ->label('Nomor Penawaran')
                        ->default('SQ-' . strtoupper(uniqid()))
                        ->disabled()->dehydrated()->unique(ignoreRecord: true),
                    DatePicker::make('quotation_date')
                        ->label('Tanggal')->default(now())->required(),
                    DatePicker::make('valid_until')
                        ->label('Berlaku Hingga')->default(now()->addDays(7))->required(),
                ]),
                Grid::make(2)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->searchable()->preload()->relationship('customer', 'name')->required(),
                    Select::make('nx_employee_id')
                        ->label('Sales / PIC')
                        ->relationship('employee', 'full_name')
                        ->default(fn () => auth()->user()?->employee?->id)
                        ->disabled()->dehydrated()->required()->prefixIcon('heroicon-o-user'),
                ]),
                Select::make('status')
                    ->options(['draft' => 'Draft', 'sent' => 'Terkirim', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'])
                    ->default('draft')->required(),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            ]),

            // --- SECTION 2: ITEMS ---
            Section::make('Daftar Item Penawaran')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema(self::getQuotationItemsSchema())
                    ->columns(2)
                    ->live() // Live agar perubahan row terdeteksi
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                    ->createItemButtonLabel('Tambah Item')
                    ->defaultItems(1),
            ])->collapsible(),

            // --- SECTION 3: TOTALS ---
            Section::make('Perhitungan Akhir')->schema([
                Grid::make(4)->schema([
                    TextInput::make('subtotal')->label('Subtotal')
                        ->disabled()->dehydrated()->prefix('Rp'),

                    // Promo Logic
                    TextInput::make('promo_code_input')->label('Kode Promo')
                        ->placeholder('Kode...')
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => $record?->promoCode?->code)
                        ->suffixAction(
                            FormAction::make('apply_promo')->icon('heroicon-m-ticket')->color('success')->label('Apply')
                                ->action(fn ($state, Set $set, Get $get) => self::applyPromo($state, $set, $get))
                        ),

                    Hidden::make('promo_code_id'),
                    Hidden::make('temp_discount_type')->dehydrated(false),
                    Hidden::make('temp_discount_value')->dehydrated(false),

                    TextInput::make('discount_amount')->label('Potongan')
                        ->disabled()->dehydrated()->prefix('Rp'),

                    TextInput::make('tax')->label('Pajak (%)')
                        ->numeric()->default(0)->minValue(0)
                        ->live(debounce: 500) // Live update saat ngetik pajak
                        ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::updateTotals($get, $set)),

                    TextInput::make('grand_total')->label('Grand Total')
                        ->disabled()->dehydrated()->prefix('Rp')
                        ->extraInputAttributes(['style' => 'font-weight: bold; color: #16a34a;']),
                ]),
            ]),
        ]);
    }

    // --- ITEM SCHEMA ---
    public static function getQuotationItemsSchema(): array
    {
        return [
            Select::make('item_type')
                ->label('Tipe')
                ->options(['product' => 'Product', 'service' => 'Service', 'package' => 'Package'])
                ->default('product')
                ->reactive()->required()
                ->afterStateUpdated(function (Set $set, Get $get) {
                    $set('item_id', null); $set('item_code', null); $set('item_code_display', null);
                    $set('item_name', null); $set('unit_price', 0); $set('cost_price', 0);
                    $set('line_total', 0); $set('qty', 1);
                    self::updateTotals($get, $set);
                }),

            Select::make('item_id')
                ->label('Pilih Item')
                ->options(function (Get $get) {
                    $type = $get('item_type');
                    return match ($type) {
                        'product' => Product::query()->pluck('product_name', 'id'),
                        'service' => Service::query()->pluck('service_name', 'id'),
                        'package' => Package::query()->pluck('package_name', 'id'),
                        default => [],
                    };
                })
                ->visible(fn (Get $get) => ! empty($get('item_type')))
                ->searchable()->preload()->reactive()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if (!$state) return;
                    $type = $get('item_type');

                    $model = match ($type) {
                        'product' => Product::find($state),
                        'service' => Service::find($state),
                        'package' => Package::find($state),
                        default => null
                    };

                    if ($model) {
                        $name = $model->product_name ?? $model->service_name ?? $model->package_name ?? $model->name;
                        $code = $model->product_code ?? $model->service_code ?? $model->package_code ?? $model->code ?? 'CODE-'.$state;

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

            Hidden::make('item_code')->dehydrated(true),
            Hidden::make('cost_price')->dehydrated(true),

            TextInput::make('item_code_display')->label('Kode')->disabled()->dehydrated(false)->columnSpan(1),
            TextInput::make('item_name')->label('Nama')->disabled()->dehydrated(true)->columnSpan(1),

            TextInput::make('qty')
                ->label('Qty')->numeric()->integer()->default(1)->minValue(1)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if ($state < 1) $set('qty', 1);
                    self::updateItemTotal($get, $set);
                }),

            TextInput::make('unit_price')->label('Harga')->numeric()->disabled()->dehydrated()->prefix('Rp')
                ->reactive()->afterStateUpdated(fn (Set $set, Get $get) => self::updateItemTotal($get, $set)),

            TextInput::make('line_total')->label('Total')->disabled()->dehydrated()->numeric()->prefix('Rp'),
        ];
    }

    // --- CALCULATIONS ---

    public static function updateItemTotal(Get $get, Set $set): void
    {
        $qty   = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);

        self::updateTotals($get, $set);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        // 1. Ambil data items
        $items = $get('items');
        $pathPrefix = '';

        // DETEKSI SCOPE REPEATER (Apakah dipanggil dari dalam row repeater?)
        if ($items === null) {
            $items = $get('../../items');
            $pathPrefix = '../../';
        }

        $items = $items ?? [];

        // 2. Hitung Subtotal
        $subtotal = collect($items)->sum(fn ($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0));

        $set($pathPrefix . 'subtotal', $subtotal);

        // 3. Promo Logic
        $discountType  = $get($pathPrefix . 'temp_discount_type');
        $discountValue = (float) $get($pathPrefix . 'temp_discount_value');

        if (!$discountType && $promoId = $get($pathPrefix . 'promo_code_id')) {
            $promo = PromoCode::find($promoId);
            if ($promo) {
                $discountType  = $promo->type;
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

        // 4. Tax & Grand Total
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

        // --- LOGIC PROMO ---
        $promo = PromoCode::where('code', $code)
            ->where('is_active', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        if (!$promo) {
            Notification::make()->title('Kode tidak valid atau kadaluwarsa!')->danger()->send();
            $set('promo_code_id', null);
            $set('temp_discount_type', null);
            $set('temp_discount_value', 0);
        } else {
            Notification::make()->title("Promo Applied!")->success()->send();
            $set('promo_code_id', $promo->id);
            $set('temp_discount_type', $promo->type);
            $set('temp_discount_value', $promo->value);
        }
        self::updateTotals($get, $set);
    }

    // --- TABLE & ACTIONS ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')->label('Nomor')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('quotation_date')->label('Tanggal')->date(),
                Tables\Columns\TextColumn::make('grand_total')->label('Grand Total')->money('IDR', true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray', 'sent' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', default => 'gray'
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        if (!$record->customer || !$record->customer->email) {
                            Notification::make()->title('Email customer tidak tersedia!')->danger()->send();
                            return;
                        }
                        Mail::to($record->customer->email)->send(new QuotationSent($record));
                        $record->update(['status' => 'sent']);
                        Notification::make()->title('Penawaran berhasil dikirim')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
