<?php

namespace App\Filament\Resources\Sales;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Mail\QuotationSent;
use App\Models\Sales\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Sales\QuotationResource\Pages;

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
                            if (empty($values)) {
                                return [];
                            }
                            return \App\Models\CRM\Customer::whereIn('id', $values)->pluck('name', 'id')->toArray();
                        })
                        ->required(),

                    Select::make('nx_employee_id')
                        ->label('Dibuat Oleh')
                        ->relationship('employee', 'full_name')
                        ->searchable()
                        ->default(fn () => auth()->user()?->employee?->id)
                        ->required(),
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

            Section::make('Perhitungan Akhir')->schema([
                Grid::make(4)->schema([
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->disabled()
                        ->dehydrated()
                        ->prefix('Rp'),
                    TextInput::make('discount')
                        ->label('Diskon (%)')
                        ->numeric()
                        ->default(0)
                        ->reactive()
                        ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotals($get, $set)),
                    TextInput::make('tax')
                        ->label('Pajak (%)')
                        ->numeric()
                        ->default(0)
                        ->reactive()
                        ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotals($get, $set)),
                    TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->disabled()
                        ->dehydrated()
                        ->prefix('Rp'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')->label('Nomor')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Dibuat Oleh')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('quotation_date')->label('Tanggal')->date(),
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
                    ->label('Status')
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
                Action::make('sendEmail')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->modalHeading('Kirim Penawaran via Email')
                    ->modalDescription(function (Quotation $record) {
                        return 'Anda yakin ingin mengirim penawaran ' . $record->quotation_number . ' ke ' . $record->customer->name . ' (' . $record->customer->email . ')?';
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Quotation $record) => ! empty($record->customer->email))
                    ->action(function (Quotation $record) {
                        try {
                            Mail::to($record->customer->email)
                                ->queue(new QuotationSent($record));

                            Notification::make()
                                ->title('Email sedang dalam antrian pengiriman')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mengirim email')
                                ->body('Terjadi masalah pada sistem. Hubungi administrator.')
                                ->danger()
                                ->send();

                            \Log::error('Gagal kirim email quotation ' . $record->id . ': ' . $e->getMessage());
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

    public static function getQuotationItemsSchema(): array
    {
        return [
            Select::make('item_type')
                ->label('Tipe Item')
                ->options([
                    'product' => 'Product',
                    'service' => 'Service',
                    'package' => 'Package',
                ])
                ->reactive()
                ->required(),

            Select::make('item_id')
                ->label('Pilih Item')
                ->required()
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
                    if (! $state) {
                        return;
                    }

                    $model = match ($get('item_type')) {
                        'product' => \App\Models\Inventory\Product::find($state),
                        'service' => \App\Models\Inventory\Service::find($state),
                        'package' => \App\Models\Inventory\Package::find($state),
                    };

                    if ($model) {
                        $set('item_code', $model->product_code ?? $model->service_code ?? $model->package_code ?? null);
                        $set('item_name', $model->product_name ?? $model->service_name ?? $model->package_name ?? null);
                        $set('unit_price', $model->price ?? $model->total_price ?? 0);
                        self::updateItemTotal($get, $set);
                    }
                }),

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
                ->integer()
                ->required()
                ->default(1)
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateItemTotal($get, $set)),

            TextInput::make('unit_price')
                ->label('Harga Satuan')
                ->numeric()
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateItemTotal($get, $set))
                ->prefix('Rp'),

            // nilai mentah yang disimpan
            TextInput::make('line_total')
                ->label('Subtotal Item')
                ->numeric()
                ->dehydrated()
                ->disabled()
                ->prefix('Rp'),
        ];
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

        $subtotal = collect($items)->sum(
            fn ($item) => (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );

        $discount = (float) ($get('discount') ?? 0);
        $tax      = (float) ($get('tax') ?? 0);

        $grandTotal = ($subtotal * (1 - $discount / 100)) * (1 + $tax / 100);

        $set('subtotal', $subtotal);
        $set('grand_total', $grandTotal);
    }
}
