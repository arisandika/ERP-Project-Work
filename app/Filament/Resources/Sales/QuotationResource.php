<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\QuotationResource\Pages;
use App\Mail\QuotationSent;
use App\Models\Sales\PromoCode;
use App\Models\Sales\Quotation;
use App\Models\Inventory\Product;
use App\Models\Inventory\Service;
use App\Models\Inventory\Package; // Pastikan namespace ini benar
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
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
            // --- SECTION 1: HEADER INFORMASI ---
            Section::make('Informasi Penawaran')->schema([
                Grid::make(3)->schema([
                    TextInput::make('quotation_number')
                        ->label('Nomor Penawaran')
                        ->default('SQ-' . strtoupper(uniqid())) // Auto-generate dummy
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true),
                    DatePicker::make('quotation_date')
                        ->label('Tanggal Penawaran')
                        ->default(now())
                        ->required(),
                    DatePicker::make('valid_until')
                        ->label('Berlaku Hingga')
                        ->default(now()->addDays(7)) // Default 7 hari
                        ->required(),
                ]),
                Grid::make(2)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->searchable()
                        ->preload() // Gunakan preload jika data customer < 1000
                        ->relationship('customer', 'name')
                        ->required(),

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
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('draft')
                    ->required(),
                Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->columnSpanFull(),
            ]),

            // --- SECTION 2: ITEMS ---
            Section::make('Daftar Item Penawaran')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema(self::getQuotationItemsSchema())
                        ->columns(2)
                        ->reactive()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                        ->createItemButtonLabel('Tambah Item')
                        ->defaultItems(1),
                ])->collapsible(),

            // --- SECTION 3: PERHITUNGAN & PROMO ---
            Section::make('Perhitungan Akhir')->schema([
                Grid::make(4)->schema([
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('Rp'),

                    // === LOGIC PROMO CODE ===
                    TextInput::make('promo_code_input')
                        ->label('Kode Promo')
                        ->placeholder('Masukkan kode')
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => $record?->promoCode?->code)
                        ->suffixAction(
                            FormAction::make('apply_promo')
                                ->icon('heroicon-m-ticket')
                                ->color('success')
                                ->label('Apply')
                                ->action(function ($state, Set $set, Get $get) {
                                    self::applyPromo($state, $set, $get);
                                })
                        ),

                    // Hidden Fields Promo
                    Hidden::make('promo_code_id'),
                    Hidden::make('temp_discount_type')->dehydrated(false),
                    Hidden::make('temp_discount_value')->dehydrated(false),

                    TextInput::make('discount_amount')
                        ->label('Potongan')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('Rp'),

                    TextInput::make('tax')
                        ->label('Pajak (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->reactive()
                        ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::updateTotals($get, $set)),

                    TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->readOnly()
                        ->dehydrated()
                        ->prefix('Rp')
                        ->extraInputAttributes(['style' => 'font-weight: bold; color: #16a34a;']),
                ]),
            ]),
        ]);
    }

    // --- HELPER ITEMS SCHEMA ---
    public static function getQuotationItemsSchema(): array
    {
        return [
            Select::make('item_type')
                ->label('Tipe')
                ->options([
                    'product' => 'Product',
                    'service' => 'Service',
                    'package' => 'Package',
                ])
                ->default('product')
                ->reactive()
                ->required()
                ->afterStateUpdated(function (Set $set) {
                    // Reset field saat ganti tipe agar data bersih
                    $set('item_id', null);
                    $set('item_code', null);
                    $set('item_name', null);
                    $set('unit_price', 0);
                    $set('line_total', 0);
                }),

            Select::make('item_id')
                ->label('Pilih Item')
                ->options(function (Get $get) {
                    $type = $get('item_type');
                    // Pastikan class Model valid & method pluck benar
                    return match ($type) {
                        'product' => Product::query()->pluck('product_name', 'id'), // Cek nama kolom di DB mu, apakah 'name' atau 'product_name'?
                        'service' => Service::query()->pluck('service_name', 'id'),
                        'package' => Package::query()->pluck('package_name', 'id'),
                        default => [],
                    };
                })
                ->visible(fn (Get $get) => ! empty($get('item_type')))
                ->searchable()
                ->preload()
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if (!$state) return;

                    $type = $get('item_type');

                    // Logic pengambilan data yang aman
                    $model = null;
                    if ($type === 'product') $model = Product::find($state);
                    elseif ($type === 'service') $model = Service::find($state);
                    elseif ($type === 'package') $model = Package::find($state);

                    if ($model) {
                        // ADJUST KOLOM DATABASE DISINI SESUAI TABEL KAMU
                        $name  = $model->product_name ?? $model->service_name ?? $model->package_name ?? $model->name;
                        $code  = $model->product_code ?? $model->service_code ?? $model->package_code ?? $model->code ?? 'CODE-'.$state;
                        $price = $model->price ?? $model->total_price ?? 0;

                        $set('item_name', $name);
                        $set('item_code', $code); // <-- INI PENTING
                        $set('unit_price', $price);

                        self::updateItemTotal($get, $set);
                    }
                }),

            // [FIX] Gunakan Hidden + TextInput ReadOnly agar data Code benar-benar aman
            Hidden::make('item_code')->dehydrated(true),

            TextInput::make('item_code_display') // Field dummy buat display aja
                ->label('Kode Item')
                ->disabled()
                ->dehydrated(false) // Jangan kirim ke DB, DB ambil dari Hidden diatas
                ->formatStateUsing(fn (Get $get) => $get('item_code')), // Ambil value dari hidden field

            TextInput::make('item_name')
                ->label('Nama Item')
                ->readOnly()
                ->dehydrated()
                ->columnSpan(1),

            TextInput::make('qty')
                ->numeric()
                ->integer()
                ->default(1)
                ->minValue(1)
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if ($state < 1) $set('qty', 1);
                    self::updateItemTotal($get, $set);
                }),

            TextInput::make('unit_price')
                ->numeric()
                ->reactive()
                ->afterStateUpdated(fn (Set $set, Get $get) => self::updateItemTotal($get, $set))
                ->prefix('Rp'),

            TextInput::make('line_total')
                ->label('Total')
                ->readOnly()
                ->dehydrated()
                ->prefix('Rp'),
        ];
    }

    // --- HELPER LOGIC ---

    public static function updateItemTotal(Get $get, Set $set): void
    {
        $qty   = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);

        // Panggil updateTotals global setiap kali item berubah (opsional, tp aman)
        // self::updateTotals($get, $set); <--- Hati-hati infinite loop jika logicnya salah, lebih baik dipanggil di Repeater level
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        // 1. Hitung Subtotal
        $items = $get('items') ?? [];
        $subtotal = collect($items)->sum(fn ($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0));
        $set('subtotal', $subtotal);

        // 2. Promo Logic
        $discountType  = $get('temp_discount_type');
        $discountValue = (float) $get('temp_discount_value');

        // Restore promo on edit
        if (!$discountType && $promoId = $get('promo_code_id')) {
            $promo = PromoCode::find($promoId);
            if ($promo) {
                $discountType  = $promo->type;
                $discountValue = $promo->value;
                $set('temp_discount_type', $discountType);
                $set('temp_discount_value', $discountValue);
                $set('promo_code_input', $promo->code);
            }
        }

        $totalDiscount = 0;
        if ($discountType === 'percentage') {
            $totalDiscount = $subtotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $totalDiscount = $discountValue;
        }

        $totalDiscount = min($totalDiscount, $subtotal); // Safety check
        $set('discount_amount', $totalDiscount);

        // 3. Tax & Grand Total
        $taxPercent = (float) ($get('tax') ?? 0);
        $afterDiscount = $subtotal - $totalDiscount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);

        $set('grand_total', $afterDiscount + $taxAmount);
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

        // Pastikan pakai scope available() jika ada di model PromoCode
        $promo = PromoCode::where('code', $code)->where('status', 'active')->first(); // Fallback query manual jika scope error

        if (!$promo) {
            Notification::make()->title('Kode tidak valid!')->danger()->send();
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

    // ... sisa method table dan pages sama ...
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
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
