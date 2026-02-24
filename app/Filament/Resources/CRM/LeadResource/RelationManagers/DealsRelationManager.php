<?php

namespace App\Filament\Resources\CRM\LeadResource\RelationManagers;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\Deal;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DealsRelationManager extends RelationManager
{
    protected static string $relationship = 'deals';

    protected static ?string $recordTitleAttribute = 'deal_number';

    protected static ?string $title = 'Daftar Deal';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->deals_count ?? $ownerRecord->deals()->count();
    }

    public function form(Form $form): Form
    {
        $leadId = $this->getOwnerRecord()->id;

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Utama Deal')
                    ->description('Pilih sumber Deal (Lead/Customer) dan tentukan Stage Deal.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('deal_number')
                                    ->label('No. Deal')
                                    ->disabled()
                                    ->dehydrated()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn() => $this->generateDealNumber())
                                    ->prefixIcon('heroicon-o-hashtag'),

                                Forms\Components\Select::make('nx_lead_id')
                                    ->label('Lead')
                                    ->relationship(
                                        name: 'lead',
                                        titleAttribute: 'name'
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default($leadId)
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-funnel'),

                                Forms\Components\Select::make('nx_deal_stage_id')
                                    ->label('Stage Deal')
                                    ->relationship(
                                        name: 'stage',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query->orderBy('order', 'asc'),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "Stage {$record->order} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-queue-list')
                                    ->helperText('Tahapan/Stage Deal saat ini.'),

                                Forms\Components\Select::make('nx_customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-user-group')
                                    ->helperText('Customer adalah lead yang sudah pernah deal')
                                    ->hidden(),
                            ]),
                    ]),

                Forms\Components\Section::make('Nilai Transaksi & Status Penutupan')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_value')
                                    ->label('Estimasi Nilai (IDR)')
                                    ->numeric()
                                    ->prefix('IDR'),

                                Forms\Components\Select::make('status')
                                    ->label('Status Deal')
                                    ->options([
                                        'open' => 'Open (Masih Negosiasi/Proses)',
                                        'won' => 'Won (Berhasil Closing)',
                                        'lost' => 'Lost (Gagal Closing)',
                                    ])
                                    ->default('open')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-adjustments-vertical'),

                                Forms\Components\DatePicker::make('deal_date')
                                    ->label('Tanggal Deal Dibuat')
                                    ->default(now())
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->prefixIcon('heroicon-o-calendar'),

                                Forms\Components\DatePicker::make('close_date')
                                    ->label('Tanggal Penutupan (Closing Date)')
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->helperText('Diisi ketika status menjadi Won atau Lost.')
                                    ->dehydrateStateUsing(fn($state) => $state ?? null)
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('clear')
                                            ->action(fn(Forms\Set $set) => $set('close_date', null))
                                            ->tooltip('Hapus Close Date')
                                            ->icon('heroicon-o-x-mark')
                                            ->label('Hapus')
                                    ),
                            ]),
                    ]),
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('deal_number')
            ->columns([
                Tables\Columns\TextColumn::make('deal_number')
                    ->label('No. Deal')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer_or_lead')
                    ->label('Lead')
                    ->state(function (Deal $record) {
                        if ($record->customer)
                            return $record->customer->name . ' (Customer)';
                        if ($record->lead)
                            return $record->lead->name . ' (Lead)';
                        return '-';
                    })
                    ->searchable(['customer.name', 'lead.name'])
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('lead.phone')
                    ->label('Kontak')
                    ->state(function (Deal $record) {
                        return $record->lead?->phone ?? '-';
                    })
                    ->description(fn(Deal $record) => $record->lead?->email ?? '-')
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->searchable(['lead.phone', 'lead.email'])
                    ->color('success'),

                Tables\Columns\TextColumn::make('stage.name')
                    ->label('Stage Deal')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Lead Baru' => 'gray',
                        'Penawaran' => 'primary',
                        'Closed Won' => 'success',
                        'Closed Lost' => 'red',
                        default => 'primary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('Est. Value')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open' => 'warning',
                        'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Deal')
                    ->date('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quotations_count')
                    ->label('Jumlah Penawaran')
                    ->counts('quotations')
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state . ' Penawaran'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ]),

                Tables\Filters\SelectFilter::make('nx_deal_stage_id')
                    ->label('Stage')
                    ->relationship('stage', 'name'),

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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Deal'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function generateDealNumber(): string
    {
        do {
            $dealNumber = 'DEAL-' . strtoupper(Str::random(4));
        } while (
            Deal::where('deal_number', $dealNumber)->exists()
        );

        return $dealNumber;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['deal_number'] = $this->generateDealNumber();

        return $data;
    }
}
