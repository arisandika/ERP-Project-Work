<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\QuotationResource\Pages;
use App\Mail\QuotationSent;
use App\Models\Sales\PromoCode;
use App\Models\Sales\Quotation;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true),
                    DatePicker::make('quotation_date')
                        ->label('Tanggal Penawaran')
                        ->default(now())
                        ->required(),
                    DatePicker::make('valid_until')
                        ->label('Berlaku Hingga')
                        ->required(),
                ]),
                Grid::make(2)->schema([
                    Select::make('nx_customer_id')
                        ->label('Pelanggan')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => \App\Models\CRM\Customer::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                        ->getOptionLabelsUsing(function (array $values): array {
                            if (empty($values)) return [];
                            return \App\Models\CRM\Customer::whereIn('id', $values)->pluck('name', 'id')->toArray();
                        })
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
                        ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotals($get, $set))
                        ->createItemButtonLabel('Tambah Item')
                        ->defaultItems(1),
                ])->collapsible(),

            // --- SECTION 3: PERHITUNGAN & PROMO ---
            Section::make('Perhitungan Akhir')->schema([
                Grid::make(4)->schema([
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->disabled()
                        ->dehydrated()
                        ->prefix('Rp'),

                    // === LOGIC PROMO CODE ===
                    TextInput::make('promo_code_input')
                        ->label('Kode Promo')
                        ->placeholder('Masukkan kode')
                        ->formatStateUsing(fn ($record) => $record?->promoCode?->code)
                        ->suffixAction(
                            FormAction::make('apply_promo')
                                ->icon('heroicon-m-ticket')
                                ->color('success')
                                ->label('Apply')
                                ->action(function ($state, callable $set, callable $get) {
                                    // Reset jika input kosong
                                    if (empty($state)) {
                                        $set('promo_code_id', null);
                                        $set('temp_discount_type', null);
                                        $set('temp_discount_value', 0);
                                        self::updateTotals($get, $set);
                                        return;
                                    }

                                    // Cek Database via Scope Available
                                    $promo = PromoCode::where('code', $state)->available()->first();

                                    if (!$promo) {
                                        Notification::make()->title('Kode tidak valid / expired!')->danger()->send();
                                        $set('promo_code_id', null);
                                        $set('temp_discount_type', null);
                                        $set('temp_discount_value', 0);
                                    } else {
                                        Notification::make()->title("Promo '{$promo->code}' diterapkan!")->success()->send();
                                        // Set Hidden Fields
                                        $set('promo_code_id', $promo->id);
                                        $set('temp_discount_type', $promo->type); // 'fixed' or 'percentage'
                                        $set('temp_discount_value', $promo->value);
                                    }

                                    // Trigger hitung ulang
                                    self::updateTotals($get, $set);
                                })
                        ),

                    // Hidden Fields Promo
                    TextInput::make('promo_code_id')->hidden()->dehydrated(),
                    TextInput::make('temp_discount_type')->hidden()->dehydrated(false),
                    TextInput::make('temp_discount_value')->hidden()->dehydrated(false),

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
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state < 0) {
                                $set('tax', 0);
                            }

                            self::updateTotals($get, $set);
                        }),
                        
                    TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->disabled()
                        ->dehydrated()
                        ->prefix('Rp'),
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
                ->reactive()
                ->required(),

            Select::make('item_id')
                ->label('Pilih Item')
                ->options(function (callable $get) {
                    return match ($get('item_type')) {
                        'product' => \App\Models\Inventory\Product::pluck('product_name', 'id')->toArray(),
                        'service' => \App\Models\Inventory\Service::pluck('service_name', 'id')->toArray(),
                        'package' => \App\Models\Inventory\Package::pluck('package_name', 'id')->toArray(),
                        default => [],
                    };
                })
                ->visible(fn (callable $get) => ! empty($get('item_type')))
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    if (!$state) return;

                    // Ambil data Model
                    $model = match ($get('item_type')) {
                        'product' => \App\Models\Inventory\Product::find($state),
                        'service' => \App\Models\Inventory\Service::find($state),
                        'package' => \App\Models\Inventory\Package::find($state),
                    };

                    if ($model) {
                        $name = $model->product_name ?? $model->service_name ?? $model->package_name;
                        $code = $model->product_code ?? $model->service_code ?? $model->package_code;
                        $price = $model->price ?? $model->total_price ?? 0;

                        $set('item_name', $name);
                        $set('item_code', $code);
                        $set('unit_price', $price);

                        // Hitung total baris otomatis
                        self::updateItemTotal($get, $set);
                    }
                }),

            TextInput::make('qty')
                ->numeric()
                ->integer()
                ->default(1)
                ->minValue(1)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    if ($state < 1) {
                        $set('qty', 1);
                    }

                    self::updateItemTotal($get, $set);
                }),


            TextInput::make('unit_price')
                ->numeric()
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateItemTotal($get, $set))
                ->prefix('Rp'),

            TextInput::make('line_total')
                ->label('Total')
                ->disabled()
                ->dehydrated()
                ->prefix('Rp'),

            TextInput::make('item_name')
                ->label('Nama Item')
                ->readOnly()
                ->dehydrated()
                ->columnSpanFull(),

            TextInput::make('item_code')->hidden()->dehydrated(),
        ];
    }

    public static function updateItemTotal(callable $get, callable $set): void
    {
        $qty = (float) ($get('qty') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $set('line_total', $qty * $price);
    }

    public static function updateTotals(callable $get, callable $set): void
    {
        // 1. Hitung Subtotal Item
        $items = $get('items') ?? [];
        $subtotal = collect($items)->sum(
            fn ($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
        $set('subtotal', $subtotal);

        // 2. Logic Kalkulasi Promo
        $discountType = $get('temp_discount_type');
        $discountValue = (float) $get('temp_discount_value');

        if (!$discountType && $get('promo_code_id')) {
            $promo = PromoCode::find($get('promo_code_id'));
            if ($promo) {
                $discountType = $promo->type;
                $discountValue = $promo->value;
                $set('temp_discount_type', $discountType);
                $set('temp_discount_value', $discountValue);
            }
        }

        $totalDiscount = 0;
        if ($discountType === 'percentage') {
            $totalDiscount = $subtotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $totalDiscount = $discountValue;
        }

        if ($totalDiscount > $subtotal) {
            $totalDiscount = $subtotal;
        }

        $set('discount_amount', $totalDiscount);

        // 3. Hitung Pajak & Grand Total
        $taxPercent = (float) ($get('tax') ?? 0);
        $afterDiscount = $subtotal - $totalDiscount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);

        $set('grand_total', $afterDiscount + $taxAmount);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')->label('Nomor')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Dibuat Oleh')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('quotation_date')->label('Tanggal')->date(),

                Tables\Columns\TextColumn::make('promoCode.code')
                    ->label('Promo')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('grand_total')->label('Grand Total')->money('IDR', true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                TableAction::make('sendEmail')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Quotation $record) => ! empty($record->customer->email))
                    ->action(function (Quotation $record) {
                        try {
                            Mail::to($record->customer->email)->queue(new QuotationSent($record));
                            Notification::make()->title('Email antri dikirim')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal kirim')->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
