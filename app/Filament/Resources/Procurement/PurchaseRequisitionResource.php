<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\PurchaseRequisitionResource\Pages;
use App\Models\Inventory\Product;
use App\Models\Procurement\PurchaseRequisition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\BelongsToModule;
use Filament\Support\Enums\FontWeight;

class PurchaseRequisitionResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'procurement';
    protected static ?string $model = PurchaseRequisition::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Purchase Requisition';
    protected static ?string $pluralModelLabel = 'Purchase Requisitions';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if (! $user) return null;

        $query = static::getModel()::query();

        $count = static::canApproveAny()
            ? $query->where('status', 'pending')->count()
            : $query->where('requested_by', $user->id)
                ->whereIn('status', ['draft', 'pending'])
                ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            return $query->where('requested_by', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    static::requestInformationSection(),
                    static::itemsSection(),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static function requestInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Informasi Permintaan')
            ->description('Lengkapi informasi utama pengajuan pembelian.')
            ->icon('heroicon-o-document-text')
            ->schema([
                Forms\Components\TextInput::make('pr_number')
                    ->label('No. PR')
                    ->disabled()
                    ->dehydrated(false)
                    ->prefixIcon('heroicon-o-hashtag')
                    ->default(fn () => PurchaseRequisition::generatePRNumber())
                    ->columnSpan(1),

                Forms\Components\TextInput::make('title')
                    ->label('Nama / Judul Permintaan')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2)
                    ->extraInputAttributes(['class' => 'text-xl font-bold border-t-0 border-l-0 border-r-0 border-b-2 border-gray-300 focus:ring-0 px-0 bg-transparent']),

                Forms\Components\DatePicker::make('request_date')
                    ->label('Tanggal Permintaan')
                    ->default(today())
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-o-calendar-days')
                    ->columnSpan(1),

                Forms\Components\DatePicker::make('required_date')
                    ->label('Tanggal Dibutuhkan')
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-o-calendar')
                    ->minDate(fn (Get $get) => $get('request_date') ?: today())
                    ->columnSpan(1),

                Forms\Components\Placeholder::make('status_preview')
                    ->label('Status Saat Ini')
                    ->content(fn (?PurchaseRequisition $record) => strtoupper($record?->status ?? 'draft')),

                Forms\Components\Textarea::make('purpose')
                    ->label('Tujuan / Alasan Pembelian')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    protected static function itemsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Daftar Barang')
            ->icon('heroicon-o-queue-list')
            ->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->label('Item PR')
                    ->live(debounce: 500)
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Barang')
                            ->options(fn () => Product::pluck('product_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Kuantitas')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('estimated_price')
                            ->label('Harga Estimasi')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->addActionLabel('Tambah Item')
                    ->defaultItems(1),

                Forms\Components\Placeholder::make('total_preview')
                    ->label('Total Estimasi')
                    ->content(function (Get $get) {
                        $total = collect($get('items'))->reduce(function ($carry, $item) {
                            return $carry + (floatval($item['quantity'] ?? 0) * floatval($item['estimated_price'] ?? 0));
                        }, 0);

                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->extraAttributes(['class' => 'text-right text-xl font-bold text-primary-600']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pr_number')
                    ->label('No. PR')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Permintaan')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Peminta')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('request_date')
                    ->label('Tgl Minta')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Aksi Edit (Menggunakan ikon solid agar konsisten)
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-s-pencil-square')
                    ->iconButton()
                    ->visible(fn ($record) => strtolower($record->status) === 'draft'),

                // TOMBOL SUBMIT (Solid Icon + Tooltip)
                Tables\Actions\Action::make('submit_action')
                    ->label('Submit')
                    ->tooltip('Submit PR')
                    ->icon('heroicon-s-paper-airplane') // Menggunakan Solid Icon
                    ->color('info')
                    ->iconButton()
                    ->visible(fn ($record) => strtolower($record->status) === 'draft')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'pending']);
                        Notification::make()->title('PR Submitted Successfully')->success()->send();
                    }),

                // TOMBOL APPROVE (Solid Icon + Tooltip)
                Tables\Actions\Action::make('approve_button')
                    ->label('Approve')
                    ->tooltip('Approve PR')
                    ->icon('heroicon-s-check-circle') // Menggunakan Solid Icon
                    ->color('success')
                    ->iconButton()
                    ->visible(fn ($record) =>
                        strtolower($record->status) === 'pending' &&
                        static::canApproveAny() &&
                        $record->requested_by != auth()->id()
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()->title('PR Approved')->success()->send();
                    }),

                // TOMBOL REJECT (Solid Icon + Tooltip)
                Tables\Actions\Action::make('reject_button')
                    ->label('Reject')
                    ->tooltip('Reject PR')
                    ->icon('heroicon-s-x-circle') // Menggunakan Solid Icon
                    ->color('danger')
                    ->iconButton()
                    ->visible(fn ($record) =>
                        strtolower($record->status) === 'pending' &&
                        static::canApproveAny() &&
                        $record->requested_by != auth()->id()
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()->title('PR Rejected')->danger()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool {
        return Auth::user()?->hasAnyRole(['super_admin', 'admin']) || Auth::user()?->can('view_any_procurement::purchase::requisition');
    }

    public static function canCreate(): bool {
        return Auth::user()?->hasAnyRole(['super_admin', 'admin']) || Auth::user()?->can('create_procurement::purchase::requisition');
    }

    protected static function canApproveAny(): bool {
        return Auth::user()?->hasAnyRole(['super_admin', 'admin', 'manager']) || Auth::user()?->can('approve_procurement::purchase::requisition');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequisitions::route('/'),
            'create' => Pages\CreatePurchaseRequisition::route('/create'),
            'edit' => Pages\EditPurchaseRequisition::route('/{record}/edit'),
        ];
    }
}
