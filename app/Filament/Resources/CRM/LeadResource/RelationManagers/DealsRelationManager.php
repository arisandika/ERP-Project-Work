<?php

namespace App\Filament\Resources\CRM\LeadResource\RelationManagers;

use App\Filament\Resources\CRM\DealResource;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
                                    ->relationship('lead', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default($leadId)
                                    ->disabled(
                                        fn(string $context) =>
                                        $context === 'edit' || filled($leadId)
                                    )
                                    ->dehydrated()
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) => $record->name ?? 'Tanpa Lead'
                                    )
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
                                    ->live()
                                    ->prefixIcon('heroicon-o-queue-list')
                                    ->helperText('Tahapan/Stage deal saat ini.')
                                    ->afterStateUpdated(function ($state, callable $set, $record) {
                                        if (!$record)
                                            return;

                                        $deal = Deal::with('stage', 'quotations')->find($record->id);
                                        $targetStage = DealStage::find($state);

                                        if (!$deal || !$targetStage)
                                            return;

                                        $stageName = strtolower($targetStage->name);
                                        $hasQuotation = $deal->quotations()->exists();

                                        $isWon = str_contains($stageName, 'won');
                                        $isLost = str_contains($stageName, 'lost');

                                        $penawaranStage = DealStage::whereRaw(
                                            'LOWER(name) LIKE ?',
                                            ['%penawaran%']
                                        )->first();

                                        $penawaranProbability = $penawaranStage?->probability ?? 0;

                                        // TIDAK PUNYA PENAWARAN
                                        if (!$hasQuotation) {

                                            if ($targetStage->probability >= $penawaranProbability && !$isLost) {

                                                Notification::make()
                                                    ->title('Gagal Memperbarui Deal')
                                                    ->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran atau di atasnya.')
                                                    ->danger()
                                                    ->send();

                                                $set('nx_deal_stage_id', $deal->nx_deal_stage_id);
                                                return;
                                            }

                                            if ($isWon) {
                                                Notification::make()
                                                    ->title('Gagal Memperbarui Deal')
                                                    ->body('Deal tanpa Penawaran tidak bisa Closed Won.')
                                                    ->danger()
                                                    ->send();

                                                $set('nx_deal_stage_id', $deal->nx_deal_stage_id);
                                                return;
                                            }
                                        }

                                        // PUNYA PENAWARAN
                                        if ($hasQuotation) {

                                            if (
                                                $targetStage->probability < $penawaranProbability
                                                && !$isLost
                                            ) {
                                                Notification::make()
                                                    ->title('Gagal Memperbarui Deal')
                                                    ->body('Deal tidak bisa kembali ke bawah stage Penawaran kecuali Closed Lost.')
                                                    ->warning()
                                                    ->send();

                                                $set('nx_deal_stage_id', $deal->nx_deal_stage_id);
                                                return;
                                            }
                                        }

                                        // AUTO SYNC STATUS
                                        $newStatus = 'open';

                                        if ($isWon) {
                                            $newStatus = 'won';
                                        } elseif ($isLost) {
                                            $newStatus = 'lost';
                                        }

                                        if ($deal->status === 'won' && !$isWon) {
                                            $newStatus = 'open';
                                        }

                                        $set('status', $newStatus);

                                        // PUNYA QUOTATION + STATUS LOST = Tidak boleh turun ke bawah Penawaran
                                        if ($hasQuotation && $deal->status === 'lost') {

                                            if ($targetStage->probability < $penawaranProbability) {

                                                Notification::make()
                                                    ->title('Gagal Memperbarui Deal')
                                                    ->body('Deal yang sudah Closed Lost tidak bisa kembali ke stage sebelum Penawaran.')
                                                    ->warning()
                                                    ->send();

                                                $set('nx_deal_stage_id', $deal->nx_deal_stage_id);
                                                return;
                                            }
                                        }

                                        // TANPA QUOTATION + STATUS LOST = Tidak boleh naik ke Penawaran atau atasnya
                                        if (!$hasQuotation && $deal->status === 'lost') {

                                            if ($targetStage->probability >= $penawaranProbability) {

                                                Notification::make()
                                                    ->title('Gagal Memperbarui Deal')
                                                    ->body('Deal tanpa Penawaran yang sudah Closed Lost tidak bisa ke stage Penawaran atau di atasnya.')
                                                    ->danger()
                                                    ->send();

                                                $set('nx_deal_stage_id', $deal->nx_deal_stage_id);
                                                return;
                                            }
                                        }

                                        Notification::make()
                                            ->title('Berhasil Memperbarui Deal')
                                            ->body("Stage berhasil diperbarui menjadi {$targetStage->name}.")
                                            ->success()
                                            ->send();
                                    }),

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
                                    ->label('Estimasi Nilai')
                                    ->numeric()
                                    ->prefix('IDR')
                                    ->required(),

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
                                    ->live()
                                    ->prefixIcon('heroicon-o-adjustments-vertical')
                                    ->afterStateUpdated(function ($state, callable $set, $record) {

                                        if (!$record)
                                            return;

                                        $deal = Deal::with('quotations')->find($record->id);
                                        $hasQuotation = $deal->quotations()->exists();

                                        $penawaranStage = DealStage::whereRaw(
                                            'LOWER(name) LIKE ?',
                                            ['%penawaran%']
                                        )->first();

                                        // WON TANPA PENAWARAN = GAGAL
                                        if ($state === 'won' && !$hasQuotation) {

                                            $kualifikasiStage = DealStage::whereRaw(
                                                'LOWER(name) LIKE ?',
                                                ['%kualifikasi%']
                                            )->orderBy('probability')
                                                ->first();

                                            $set('status', 'open');
                                            $set('nx_deal_stage_id', $kualifikasiStage?->id);

                                            Notification::make()
                                                ->title('Gagal Memperbarui Deal')
                                                ->body('Deal tanpa Penawaran tidak bisa menjadi WON. Status dikembalikan ke OPEN dan stage dipindahkan ke Kualifikasi.')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        // WON PUNYA PENAWARAN
                                        if ($state === 'won') {

                                            $wonStage = DealStage::whereRaw(
                                                'LOWER(name) LIKE ?',
                                                ['%won%']
                                            )->first();

                                            $set('nx_deal_stage_id', $wonStage?->id);
                                        }

                                        // LOST
                                        elseif ($state === 'lost') {

                                            $lostStage = DealStage::whereRaw(
                                                'LOWER(name) LIKE ?',
                                                ['%lost%']
                                            )->first();

                                            $set('nx_deal_stage_id', $lostStage?->id);
                                        }

                                        // OPEN
                                        elseif ($state === 'open') {

                                            if (!$hasQuotation) {

                                                $kualifikasiStage = DealStage::whereRaw(
                                                    'LOWER(name) LIKE ?',
                                                    ['%kualifikasi%']
                                                )->orderBy('probability')
                                                    ->first();

                                                $set('nx_deal_stage_id', $kualifikasiStage?->id);

                                                Notification::make()
                                                    ->title('Berhasil Memperbarui Deal')
                                                    ->body('Status dikembalikan ke OPEN dan stage dipindahkan ke Kualifikasi.')
                                                    ->success()
                                                    ->send();

                                                return;
                                            }

                                            $set('nx_deal_stage_id', $penawaranStage?->id);
                                        }

                                        Notification::make()
                                            ->title('Berhasil Memperbarui Deal')
                                            ->body('Status deal berhasil diperbarui.')
                                            ->success()
                                            ->send();
                                    }),

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
            ]);
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

                Tables\Columns\SelectColumn::make('nx_deal_stage_id')
                    ->label('Stage Deal')
                    ->options(DealStage::orderBy('order')->pluck('name', 'id'))
                    ->searchable()
                    ->rules(['required'])
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->beforeStateUpdated(function ($record, $state) {
                        $deal = Deal::with('stage', 'quotations')->findOrFail($record->id);
                        $targetStage = DealStage::findOrFail($state);

                        $stageName = strtolower($targetStage->name);
                        $hasQuotation = $deal->quotations()->exists();

                        $isWon = str_contains($stageName, 'won');
                        $isLost = str_contains($stageName, 'lost');

                        $penawaranStage = DealStage::whereRaw(
                            'LOWER(name) LIKE ?',
                            ['%penawaran%']
                        )->first();

                        $penawaranProbability = $penawaranStage?->probability ?? 0;

                        // TIDAK PUNYA PENAWARAN
                        if (!$hasQuotation) {

                            // Tidak boleh ke Penawaran atau di atasnya
                            if ($targetStage->probability >= $penawaranProbability && !$isLost) {

                                Notification::make()
                                    ->title('Gagal Memperbarui Deal')
                                    ->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran atau di atasnya.')
                                    ->danger()
                                    ->send();

                                throw ValidationException::withMessages([
                                    'nx_deal_stage_id' => 'Tidak memiliki Penawaran.'
                                ]);
                            }

                            // Tidak boleh ke WON
                            if ($isWon) {
                                Notification::make()
                                    ->title('Gagal Memperbarui Deal')
                                    ->body('Deal tanpa Penawaran tidak bisa Closed Won.')
                                    ->danger()
                                    ->send();

                                throw ValidationException::withMessages([
                                    'nx_deal_stage_id' => 'Tidak memiliki Penawaran.'
                                ]);
                            }
                        }

                        // PUNYA PENAWARAN
                        if ($hasQuotation) {

                            // Jika turun ke bawah Penawaran tapi bukan LOST → tolak
                            if (
                                $targetStage->probability < $penawaranProbability
                                && !$isLost
                            ) {
                                Notification::make()
                                    ->title('Gagal Memperbarui Deal')
                                    ->body('Deal tidak bisa kembali ke bawah stage Penawaran kecuali Closed Lost.')
                                    ->warning()
                                    ->send();

                                throw ValidationException::withMessages([
                                    'nx_deal_stage_id' => 'Tidak bisa turun ke stage awal.'
                                ]);
                            }
                        }
                    })
                    ->afterStateUpdated(function ($record, $state) {
                        $deal = Deal::find($record->id);
                        $targetStage = DealStage::find($state);

                        $stageName = strtolower($targetStage->name);

                        $newStatus = 'open';

                        if (str_contains($stageName, 'won')) {
                            $newStatus = 'won';
                        } elseif (str_contains($stageName, 'lost')) {
                            $newStatus = 'lost';
                        }

                        // Jika dari WON turun → kembali OPEN
                        if ($deal->status === 'won' && !str_contains($stageName, 'won') && !str_contains($stageName, 'lost')) {
                            $newStatus = 'lost';
                        }

                        $deal->update([
                            'status' => $newStatus,
                            'close_date' => in_array($newStatus, ['won', 'lost']) ? now() : null
                        ]);

                        Notification::make()
                            ->title('Berhasil Memperbarui Deal')
                            ->body("Stage berhasil diperbarui menjadi {$targetStage->name}.")
                            ->success()
                            ->send();
                    }),

                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('Est. Value')
                    ->money('IDR')
                    ->color(fn($record) => match ($record->status) {
                        'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ])
                    ->rules(['required'])
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->afterStateUpdated(function ($record, $state) {
                        $deal = Deal::with('quotations')->findOrFail($record->id);
                        $hasQuotation = $deal->quotations()->exists();

                        $penawaranStage = DealStage::whereRaw(
                            'LOWER(name) LIKE ?',
                            ['%penawaran%']
                        )->first();

                        // WON TANPA PENAWARAN = GAGAL
                        if ($state === 'won' && !$hasQuotation) {
                            $kualifikasiStage = DealStage::whereRaw(
                                'LOWER(name) LIKE ?',
                                ['%kualifikasi%']
                            )->orderBy('probability')
                                ->first();

                            $deal->update([
                                'status' => 'open',
                                'nx_deal_stage_id' => $kualifikasiStage?->id,
                                'close_date' => null
                            ]);

                            Notification::make()
                                ->title('Gagal Memperbarui Deal')
                                ->body('Deal tanpa Penawaran tidak bisa menjadi WON. Status dikembalikan ke OPEN dan stage dipindahkan ke Kualifikasi.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // WON PUNYA PENAWARAN = BOLEH
                        if ($state === 'won') {

                            $wonStage = DealStage::whereRaw(
                                'LOWER(name) LIKE ?',
                                ['%won%']
                            )->first();

                            $deal->update([
                                'nx_deal_stage_id' => $wonStage?->id,
                                'close_date' => now()
                            ]);
                        }

                        // LOST DENGAN DAN TANPA PENAWARAN = BOLEH
                        elseif ($state === 'lost') {

                            $lostStage = DealStage::whereRaw(
                                'LOWER(name) LIKE ?',
                                ['%lost%']
                            )->first();

                            $deal->update([
                                'nx_deal_stage_id' => $lostStage?->id,
                                'close_date' => now()
                            ]);
                        }

                        // OPEN JIKA TANPA PENAWARAN = STAGE DEAL KUALIFIKASI
                        elseif ($state === 'open') {

                            if (!$hasQuotation) {

                                // cari stage kualifikasi
                                $kualifikasiStage = DealStage::whereRaw(
                                    'LOWER(name) LIKE ?',
                                    ['%kualifikasi%']
                                )->orderBy('probability')
                                    ->first();

                                $deal->update([
                                    'nx_deal_stage_id' => $kualifikasiStage?->id,
                                    'close_date' => null
                                ]);

                                Notification::make()
                                    ->title('Berhasil Memperbarui Deal')
                                    ->body('Status dikembalikan ke OPEN dan stage dipindahkan ke Kualifikasi.')
                                    ->success()
                                    ->send();

                                return;
                            }

                            // JIKA PUNYA PENAWARAN = STAGE DEAL PENAWARAN
                            $deal->update([
                                'nx_deal_stage_id' => $penawaranStage?->id,
                                'close_date' => null
                            ]);
                        }

                        Notification::make()
                            ->title('Berhasil Memperbarui Deal')
                            ->body('Status deal berhasil diperbarui.')
                            ->success()
                            ->send();
                    }),

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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Status')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('create_quotation')
                    ->label('Buat Penawaran')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->url(fn(deal $record): string => QuotationResource::getUrl('create', ['nx_deal_id' => $record->id]))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Deal')
                    ->mutateFormDataUsing(function (array $data) {

                        $targetStage = DealStage::find($data['nx_deal_stage_id']);

                        if (!$targetStage) {
                            return $data;
                        }

                        $stageName = strtolower($targetStage->name);

                        $isWonStage = str_contains($stageName, 'won');

                        $penawaranStage = DealStage::whereRaw(
                            'LOWER(name) LIKE ?',
                            ['%penawaran%']
                        )->first();

                        $penawaranProbability = $penawaranStage?->probability ?? 0;

                        // VALIDASI STAGE
                        if ($targetStage->probability >= $penawaranProbability || $isWonStage) {

                            Notification::make()
                                ->title('Gagal Menambahkan Deal')
                                ->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran atau di atasnya.')
                                ->danger()
                                ->send();

                            throw ValidationException::withMessages([
                                'nx_deal_stage_id' => 'Stage tidak valid',
                            ]);
                        }

                        // VALIDASI STATUS
                        if (in_array($data['status'], ['won', 'lost'])) {

                            Notification::make()
                                ->title('Gagal Menambahkan Deal')
                                ->body('Deal baru tidak boleh langsung berstatus Won atau Lost.')
                                ->danger()
                                ->send();

                            throw ValidationException::withMessages([
                                'status' => 'Status tidak valid',
                            ]);
                        }

                        // DEFAULT VALUE
                        $data['deal_number'] = $this->generateDealNumber();
                        $data['status'] = 'open';

                        return $data;
                    }),
            ]);
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
}
