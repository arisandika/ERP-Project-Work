<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\QuotationResource\Pages;
use App\Models\Inventory\Package;
use App\Models\Inventory\Product;
use App\Models\Inventory\Service;
use App\Models\Marketing\PromoCode;
use App\Models\Sales\Quotation;
use App\Models\Sales\SalesPerson;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\Sales\QuotationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use App\Filament\Concerns\BelongsToModule;

class QuotationResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'Sales';
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Manajemen Sales';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'sales/quotations';

    protected static ?string $pluralModelLabel = 'Penawaran';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Informasi Penawaran')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('quotation_number')
                                    ->label('No. Penawaran')
                                    ->disabled()
                                    ->dehydrated()
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-hashtag'),

                                Select::make('nx_deal_id')
                                    ->label('No. Deal (Ref)')
                                    ->relationship(
                                        'deal',
                                        'deal_number',
                                        function (Builder $query) {
                                            return $query
                                                ->leftJoin('nx_customers', 'nx_deals.nx_customer_id', '=', 'nx_customers.id')
                                                ->leftJoin('nx_leads', 'nx_deals.nx_lead_id', '=', 'nx_leads.id')
                                                ->select('nx_deals.*', 'nx_customers.name as customer_name', 'nx_leads.name as lead_name')
                                                ->withTrashed();
                                        }
                                    )
                                    ->searchable(['deal_number', 'nx_customers.name', 'nx_leads.name'])
                                    ->preload()
                                    ->required()
                                    ->default(fn() => request()->query('nx_deal_id'))
                                    ->disabled(fn($record) => $record !== null || request()->has('nx_deal_id'))
                                    ->dehydrated()
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        $lead = $record->lead()->withTrashed()->first();
                                        $clientName = $record->customer?->name ?? $lead?->name ?? 'Tanpa Klien';
                                        $label = "{$record->deal_number} - {$clientName}";
                                        if ($record->trashed())
                                            return "{$label} (Deal Terhapus)";
                                        if ($lead && $lead->trashed())
                                            return "{$label} (Lead Terhapus)";
                                        return $label;
                                    }),

                                DatePicker::make('quotation_date')
                                    ->label('Tanggal Penawaran')
                                    ->default(now())
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->required()
                                    ->displayFormat('d M Y')
                                    ->native(false),

                                DatePicker::make('valid_until')
                                    ->label('Berlaku Hingga')
                                    ->default(now()->addDays(7))
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->required()
                                    ->displayFormat('d M Y')
                                    ->native(false),

                                Select::make('internal_pic_id')
                                    ->label('PIC (Internal Sales)')
                                    ->relationship('internalPic', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(function () {
                                        return auth()->user()?->employee?->id;
                                    })
                                    ->prefixIcon('heroicon-o-user'),

                                Select::make('field_staff_pic_id')
                                    ->label('PIC (External/Field Staff)')
                                    ->relationship('fieldStaffPic', 'full_name', function ($query) {
                                        return $query->where('type', 'external');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-users'),

                                Select::make('created_by')
                                    ->label('Dibuat Oleh')
                                    ->relationship('createdBy', 'full_name')
                                    ->disabled(),

                                Select::make('status')
                                    ->options(function (string $operation): array {
                                        $allStatuses = [
                                            'new' => 'Baru',
                                            'sent' => 'Terkirim',
                                            'negotiation' => 'Negosiasi',
                                            'accepted' => 'Diterima',
                                            'rejected' => 'Ditolak',
                                            'expired' => 'Expired',
                                        ];

                                        if ($operation === 'create') {
                                            return ['new' => 'Baru'];
                                        }

                                        return $allStatuses;
                                    })
                                    ->default('new')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-adjustments-vertical'),
                            ]),
                    ]),

                Section::make('Daftar Item Penawaran')
                    ->schema([
                        Repeater::make('items')
                            ->schema(self::getQuotationItemsSchema())
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
            ])->columnSpan(['lg' => 2]),

            Group::make()->schema([
                Section::make('Ringkasan Harga')
                    ->schema([
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
                            )
                            ->formatStateUsing(function ($state) {
                                return strtoupper($state ?? '');
                            })
                            ->afterStateUpdated(fn(Set $set, $state) => $set('promo_code_input', strtoupper($state ?? ''))),

                        Hidden::make('promo_code_id'),
                        Hidden::make('temp_discount_type')->dehydrated(false),
                        Hidden::make('temp_discount_value')->dehydrated(false),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('IDR')
                            ->required()
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn($state) => (int) $state),

                        TextInput::make('discount_amount')
                            ->label('Potongan / Diskon')
                            ->numeric()
                            ->prefix('IDR')
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn($state) => (int) $state),

                        TextInput::make('tax')
                            ->label('Pajak PPN (%)')
                            ->numeric()
                            ->default(11)
                            ->minValue(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn($state, Set $set, Get $get) => self::updateTotals($get, $set))
                            ->formatStateUsing(fn($state) => (float) $state)
                            ->prefixIcon('heroicon-o-receipt-percent'),

                        TextInput::make('grand_total')
                            ->label('Grand Total')
                            ->numeric()
                            ->prefix('IDR')
                            ->required()
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated()
                            ->extraInputAttributes(['style' => 'font-size: 1rem; font-weight: bold; color: green;'])
                            ->formatStateUsing(fn($state) => (int) $state),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan Tambahan / Syarat Ketentuan')
                            ->rows(5),
                    ]),

            ])->columnSpan(['lg' => 1]), // Menempati 1 dari 3 kolom grid utama

        ])->columns(3); // Container utama dibagi menjadi 3 kolom
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->label('No. Penawaran')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Lead')
                    ->state(function (Quotation $record) {
                        $deal = $record->deal()->withTrashed()->first();

                        if (!$deal)
                            return '-';

                        if ($deal->nx_customer_id) {
                            return $deal->customer?->name . ' (Customer)';
                        }

                        $lead = $deal->lead()->withTrashed()->first();

                        if ($lead) {
                            return $lead->name . ' (Lead)';
                        }

                        return '-';
                    })
                    ->description(function (Quotation $record) {
                        $deal = $record->deal()->withTrashed()->first();
                        if (!$deal)
                            return '-';

                        $lead = $deal->lead()->withTrashed()->first();

                        $infoParts = [];
                        $infoParts[] = "Deal: {$deal->deal_number}";

                        if ($deal->trashed()) {
                            $infoParts[] = "<span class='inline-flex items-center px-2 py-1 mt-2 text-xs font-medium capitalize bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-danger text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30'>Deal Terhapus</span>";
                        }
                        if ($lead && $lead->trashed()) {
                            $infoParts[] = "<span class='inline-flex items-center px-2 py-1 mt-2 text-xs font-medium capitalize bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-danger text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30'>Lead Terhapus</span>";
                        }

                        if ($deal->status === 'lost') {
                            $infoParts[] = '<span class="inline-flex items-center px-2 py-1 mt-2 text-xs font-medium capitalize bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-danger text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">Status Deal: Lost</span>';
                        } elseif ($deal->status === 'won') {
                            $infoParts[] = '<span class="inline-flex items-center px-2 py-1 mt-2 text-xs font-medium capitalize bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-success text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">Status Deal: Won</span>';
                        } else {
                            $infoParts[] = '<span class="inline-flex items-center px-2 py-1 mt-2 text-xs font-medium capitalize bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-warning text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30">Status Deal: Open</span>';
                        }

                        return new HtmlString(implode(' <br> ', $infoParts));
                    })
                    ->searchable(['deal.customer.name', 'deal.lead.name'])
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-building-office')
                    ->color(function (Quotation $record) {
                        $deal = $record->deal()->withTrashed()->first();
                        $lead = $deal?->lead()->withTrashed()->first();

                        if (($deal && $deal->trashed()) || ($lead && $lead->trashed())) {
                            return 'danger';
                        }
                        return '';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Penawaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'gray',
                        'sent' => 'warning',
                        'negotiation' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'danger',

                        default => 'gray'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'new' => 'Baru',
                        'sent' => 'Terkirim',
                        'negotiation' => 'Negosiasi',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                        'expired' => 'Expired',

                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('internalPic.full_name')
                    ->label('PIC Internal')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->weight('semibold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('fieldStaffPic.full_name')
                    ->label('Field Staff')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-users')
                    ->weight('semibold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('quotation_date')
                    ->label('Tanggal Penawaran')
                    ->date('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Berlaku Hingga')
                    ->date('d M Y')
                    ->sortable()
                    ->color(function (Quotation $record) {
                        if (in_array($record->status, ['accepted', 'rejected'])) {
                            return 'gray';
                        }
                        if (\Carbon\Carbon::parse($record->valid_until)->isPast()) {
                            return 'danger';
                        }
                        if (\Carbon\Carbon::parse($record->valid_until)->diffInDays(now()) <= 3) {
                            return 'warning';
                        }
                        return 'success';
                    })
                    ->description(function (Quotation $record) {
                        if (in_array($record->status, ['accepted', 'rejected']))
                            return null;

                        $days = now()->diffInDays(\Carbon\Carbon::parse($record->valid_until), false);
                        if ($days < 0)
                            return 'Expired ' . abs(intval($days)) . ' Hari lalu';
                        if ($days == 0)
                            return 'Hari ini terakhir';
                        return 'Sisa ' . intval($days) . ' Hari';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'gray',
                        'sent' => 'warning',
                        'negotiation' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'danger',

                        default => 'gray'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'new' => 'Baru',
                        'sent' => 'Terkirim',
                        'negotiation' => 'Negosiasi',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                        'expired' => 'Expired',

                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'success' : 'warning')
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'new' => 'Baru',
                        'sent' => 'Terkirim',
                        'negotiation' => 'Negosiasi',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                        'expired' => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('internal_pic_id')
                    ->label('PIC (Internal Sales)')
                    ->relationship('internalPic', 'full_name')
                    ->searchable()
                    ->preload(),
                    // ->default(function () {
                    //     return auth()->user()?->employee?->id;
                    // }),

                Tables\Filters\SelectFilter::make('field_staff_pic_id')
                    ->label('PIC (External/Field Staff)')
                    ->relationship('fieldStaffPic', 'full_name', function ($query) {
                        return $query->where('type', 'external');
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_expired')
                    ->label('Status Kedaluwarsa')
                    ->placeholder('Semua Penawaran')
                    ->trueLabel('Sudah Expired')
                    ->falseLabel('Masih Berlaku')
                    ->queries(
                        true: fn(Builder $query) => $query->whereDate('valid_until', '<', now())->whereIn('status', ['new', 'sent']),
                        false: fn(Builder $query) => $query->whereDate('valid_until', '>=', now())->orWhereNotIn('status', ['new', 'sent']),
                    ),

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
                    ->action(function (Quotation $record, QuotationService $service) {
                        $isSent = $service->sendQuotationEmail($record);

                        if ($isSent) {
                            Notification::make()->title('Terkirim!')->success()->send();
                        } else {
                            Notification::make()->title('Gagal: Email Klien tidak tersedia!')->danger()->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->before(function (Tables\Actions\RestoreAction $action, Quotation $record) {
                        $deal = $record->deal()->withTrashed()->first();
                        $lead = $deal ? $deal->lead()->withTrashed()->first() : null;

                        if ($lead && $lead->trashed()) {
                            Notification::make()
                                ->warning()
                                ->title('Gagal Restore Penawaran')
                                ->body('Silakan restore Lead terkait terlebih dahulu!')
                                ->send();

                            $action->cancel();
                        } elseif ($deal && $deal->trashed()) {
                            Notification::make()
                                ->warning()
                                ->title('Gagal Restore Penawaran')
                                ->body('Silakan restore Deal terkait terlebih dahulu!')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->before(function (Tables\Actions\RestoreBulkAction $action, Collection $records) {
                            foreach ($records as $record) {
                                $deal = $record->deal()->withTrashed()->first();
                                $lead = $deal ? $deal->lead()->withTrashed()->first() : null;

                                if (($lead && $lead->trashed()) || ($deal && $deal->trashed())) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Gagal Restore Bulk')
                                        ->body("Satu atau lebih Penawaran tidak dapat di-restore karena Deal/Lead terkait masih terhapus.")
                                        ->send();

                                    $action->cancel();
                                }
                            }
                        }),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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

                    if ($type === 'App\\Models\\Inventory\\Product' || $type === Product::class)
                        $type = 'product';
                    if ($type === 'App\\Models\\Inventory\\Service' || $type === Service::class)
                        $type = 'service';
                    if ($type === 'App\\Models\\Inventory\\Package' || $type === Package::class)
                        $type = 'package';

                    return match ($type) {
                        'product' => Product::query()->pluck('product_name', 'id'),
                        'service' => Service::query()->pluck('service_name', 'id'),
                        'package' => Package::query()->pluck('package_name', 'id'),
                        default => [],
                    };
                })
                ->getOptionLabelUsing(function ($value, Get $get) {
                    $type = $get('item_type');

                    if ($type === 'App\\Models\\Inventory\\Product' || $type === Product::class)
                        $type = 'product';
                    if ($type === 'App\\Models\\Inventory\\Service' || $type === Service::class)
                        $type = 'service';
                    if ($type === 'App\\Models\\Inventory\\Package' || $type === Package::class)
                        $type = 'package';

                    $modelClass = match ($type) {
                        'product' => Product::class,
                        'service' => Service::class,
                        'package' => Package::class,
                        default => null
                    };

                    if (!$modelClass || !$value)
                        return null;

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

                    if ($type === 'App\\Models\\Inventory\\Product' || $type === Product::class)
                        $type = 'product';
                    if ($type === 'App\\Models\\Inventory\\Service' || $type === Service::class)
                        $type = 'service';
                    if ($type === 'App\\Models\\Inventory\\Package' || $type === Package::class)
                        $type = 'package';

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
                ->label('Kode Item')
                ->disabled()
                ->dehydrated()
                ->required(),

            TextInput::make('item_name')
                ->label('Nama Item')
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
                ->label('Harga Jual Satuan')
                ->numeric()
                ->prefix('IDR')
                ->required()
                ->minValue(0)
                ->disabled()
                ->dehydrated()
                ->formatStateUsing(fn($state) => (int) $state)
                ->reactive()
                ->afterStateUpdated(fn(Set $set, Get $get) => self::updateItemTotal($get, $set)),

            TextInput::make('line_total')
                ->label('Subtotal')
                ->numeric()
                ->prefix('IDR')
                ->required()
                ->minValue(0)
                ->disabled()
                ->dehydrated()
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

        $service = app(\App\Services\Sales\QuotationService::class);
        $promo = $service->validatePromoCode($code);

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
}
