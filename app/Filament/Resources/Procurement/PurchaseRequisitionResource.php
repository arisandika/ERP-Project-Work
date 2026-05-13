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

        if (static::canApproveAny()) {
            $count = $query->where('status', PurchaseRequisition::STATUS_PENDING)->count();
        } else {
            $count = $query->where('requested_by', $user->id)
                ->whereIn('status', [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_PENDING])
                ->count();
        }
        return $count > 0 ? (string) $count : null;
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
            ->extraAttributes(['style' => 'border-radius: 0px !important'])
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Nama / Judul Permintaan')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: bold; border:none; border-bottom: 1px solid #ccc; outline:none; box-shadow:none; border-radius: 0px !important;']),

                Forms\Components\TextInput::make('pr_number_preview')
                    ->label('No. PR')
                    ->disabled()
                    ->dehydrated(false)
                    ->prefixIcon('heroicon-o-hashtag')
                    ->afterStateHydrated(fn ($component, $record) => $component->state($record?->pr_number ?? PurchaseRequisition::generatePRNumber()))
                    ->extraInputAttributes(['style' => 'border-radius: 0px !important']),

                Forms\Components\DatePicker::make('request_date')
                    ->label('Tanggal Permintaan')
                    ->default(today())->required()->native(false)
                    ->prefixIcon('heroicon-o-calendar-days')
                    ->extraAttributes(['style' => '--c-radius: 0px !important']),

                Forms\Components\DatePicker::make('required_date')
                    ->label('Tanggal Dibutuhkan')
                    ->required()->native(false)
                    ->prefixIcon('heroicon-o-calendar')
                    ->minDate(fn (Get $get) => $get('request_date') ?: today())
                    ->rule('after_or_equal:request_date')
                    ->extraAttributes(['style' => '--c-radius: 0px !important']),

                Forms\Components\Textarea::make('purpose')
                    ->label('Tujuan / Alasan Pembelian')
                    ->required()->rows(3)->columnSpanFull()
                    ->extraInputAttributes(['style' => 'border-radius: 0px !important']),

                Forms\Components\Placeholder::make('status_preview')
                    ->label('Status Saat Ini')
                    ->content(fn (?PurchaseRequisition $record) => strtoupper($record?->status ?? 'DRAFT')),
            ])
            ->columns(2);
    }

    protected static function itemsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Daftar Barang')
            ->icon('heroicon-o-queue-list')
            ->extraAttributes(['style' => 'border-radius: 0px !important'])
            ->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->label('Item PR')
                    ->live()
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Barang')
                                    ->options(fn () => Product::pluck('product_name', 'id'))
                                    ->searchable()->preload()->required()->columnSpan(2)
                                    ->extraAttributes(['style' => '--c-radius: 0px !important']),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Kuantitas')->numeric()->required()->default(1)
                                    ->extraInputAttributes(['style' => 'border-radius: 0px !important']),

                                Forms\Components\TextInput::make('estimated_price')
                                    ->label('Harga Estimasi')->numeric()->prefix('Rp')->required()
                                    ->extraInputAttributes(['style' => 'border-radius: 0px !important']),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->addActionLabel('Tambah Item')
                    ->extraAttributes(['style' => 'border-radius: 0px !important']),

                Forms\Components\Placeholder::make('total_preview')
                    ->label('Total Estimasi')
                    ->content(function (Get $get) {
                        $items = $get('items') ?? [];
                        $total = 0;
                        foreach ($items as $item) {
                            $total += (floatval($item['quantity'] ?? 0) * floatval($item['estimated_price'] ?? 0));
                        }
                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->extraAttributes(['class' => 'text-right text-xl font-bold text-primary-600']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pr_number')->label('No. PR')->weight(FontWeight::Bold)->searchable(),
                Tables\Columns\TextColumn::make('title')->label('Nama Permintaan')->searchable(),
                Tables\Columns\TextColumn::make('requester.name')->label('Peminta'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->extraAttributes(['style' => 'border-radius: 0px !important'])
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('request_date')->label('Tgl Minta')->date('d M Y'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),

                // Tombol AJUKAN (Cuma muncul buat Staff yang buat & status Draft)
                Tables\Actions\Action::make('submit_action')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->button()
                    ->visible(fn ($record) => $record->status === 'draft' && (int)$record->requested_by === auth()->id())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'pending']);
                        Notification::make()->title('PR Berhasil Diajukan')->success()->send();
                    }),

                // Tombol SETUJU (Cuma muncul buat Admin & status Pending)
                Tables\Actions\Action::make('approve_button')
                    ->label('Setuju')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn ($record) => $record->status === 'pending' && static::canApproveAny())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()->title('PR Telah Disetujui')->success()->send();
                    }),

                // Tombol TIDAK SETUJU (Cuma muncul buat Admin & status Pending)
                Tables\Actions\Action::make('reject_button')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->button()
                    ->visible(fn ($record) => $record->status === 'pending' && static::canApproveAny())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()->title('PR Telah Ditolak')->danger()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // --- POLICIES (Tetap) ---
    public static function canViewAny(): bool {
        return Auth::user()?->hasAnyRole(['Super Admin', 'admin']) || Auth::user()?->can('view_any_procurement::purchase::requisition');
    }

    public static function canCreate(): bool {
        return Auth::user()?->hasAnyRole(['Super Admin', 'admin']) || Auth::user()?->can('create_procurement::purchase::requisition');
    }

    protected static function canApproveAny(): bool {
        return Auth::user()?->hasAnyRole(['Super Admin', 'admin', 'Manager']) || Auth::user()?->can('approve_procurement::purchase::requisition');
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
