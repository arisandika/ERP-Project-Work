<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\DealResource\Pages;
use App\Filament\Resources\CRM\DealResource\RelationManagers;
use App\Filament\Resources\DealResource\RelationManagers\QuotationsRelationManager;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Manajemen CRM';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'crm/deals';

    protected static ?string $pluralModelLabel = 'Deal';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
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
                                    ->prefixIcon('heroicon-o-hashtag'),

                                Forms\Components\Select::make('nx_lead_id')
                                    ->label('Lead')
                                    ->relationship('lead', 'name', function ($query) {
                                        // PENTING: Sertakan withTrashed agar Lead yang terhapus tetap muncul di opsi (saat edit)
                                        return $query->withTrashed();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn() => request()->query('nx_lead_id'))
                                    ->disabled(
                                        fn(string $context) =>
                                        $context === 'edit' || filled(request()->query('nx_lead_id'))
                                    )
                                    ->dehydrated()
                                    // Custom label untuk memberitahu user jika Lead tersebut sudah dihapus
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        if (!$record)
                                            return 'Tanpa Lead';
                                        return $record->trashed()
                                            ? "{$record->name} (Terhapus)"
                                            : $record->name;
                                    })
                                    ->prefixIcon('heroicon-o-funnel'),

                                Forms\Components\Select::make('nx_deal_stage_id')
                                    ->label('Stage Deal')
                                    ->relationship(
                                        name: 'stage',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn($query) => $query->orderBy('order', 'asc'),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "Stage {$record->order} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live() // Wajib
                                    ->afterStateUpdated(function ($state, $old, callable $set, callable $get, $record) {
                                        if (!$state)
                                            return;

                                        $targetStage = DealStage::find($state);
                                        if (!$targetStage)
                                            return;

                                        $stageName = strtolower($targetStage->name);
                                        $hasQuotation = $record ? $record->quotations()->exists() : false; // Cek kutipan aman untuk create/edit
                            
                                        $isWon = str_contains($stageName, 'closed won') || str_contains($stageName, 'won');
                                        $isLost = str_contains($stageName, 'closed lost') || str_contains($stageName, 'lost');

                                        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first();
                                        $penawaranProbability = $penawaranStage?->probability ?? 0;

                                        // LOGIC VALIDASI (Sama seperti sebelumnya)
                                        if (!$hasQuotation) {
                                            if ($targetStage->probability >= $penawaranProbability && !$isLost) {
                                                Notification::make()->title('Gagal')->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran/atasnya.')->danger()->send();
                                                $set('nx_deal_stage_id', $old);
                                                return;
                                            }
                                            if ($isWon) {
                                                Notification::make()->title('Gagal')->body('Deal tanpa Penawaran tidak bisa Closed Won.')->danger()->send();
                                                $set('nx_deal_stage_id', $old);
                                                return;
                                            }
                                        }
                                        if ($hasQuotation) {
                                            if ($targetStage->probability < $penawaranProbability && !$isLost) {
                                                Notification::make()->title('Gagal')->body('Deal tidak bisa kembali ke bawah stage Penawaran kecuali Lost.')->warning()->send();
                                                $set('nx_deal_stage_id', $old);
                                                return;
                                            }
                                        }

                                        // LOGIC UPDATE STATUS & CLOSE DATE
                                        $currentStatus = $get('status') ?? ($record?->status ?? 'open');
                                        $newStatus = 'open';

                                        if ($isWon) {
                                            $newStatus = 'won';
                                        } elseif ($isLost) {
                                            $newStatus = 'lost';
                                        }

                                        // Jika dari WON turun ke stage lain (bukan lost), status kembali OPEN
                                        if ($currentStatus === 'won' && !$isWon && !$isLost) {
                                            $newStatus = 'open';
                                        }

                                        $set('status', $newStatus);

                                        // LOGIC TANGGAL DISINI:
                                        if (in_array($newStatus, ['won', 'lost'])) {
                                            $set('close_date', now()->format('Y-m-d'));
                                        } else {
                                            $set('close_date', null);
                                        }
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
                                    ->label('Status')
                                    ->options([
                                        'open' => 'Open',
                                        'won' => 'Won',
                                        'lost' => 'Lost',
                                    ])
                                    ->required()
                                    ->selectablePlaceholder(false)
                                    ->live() // Wajib Live
                                    ->afterStateUpdated(function ($state, $old, callable $set, callable $get, $record) {
                                        if (!$state)
                                            return;

                                        $hasQuotation = $record ? $record->quotations()->exists() : false;

                                        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first();
                                        $kualifikasiStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%kualifikasi%'])->orderBy('probability')->first();
                                        $wonStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%won%'])->first();
                                        $lostStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%lost%'])->first();

                                        // WON TANPA PENAWARAN = GAGAL
                                        if ($state === 'won' && !$hasQuotation) {
                                            Notification::make()
                                                ->title('Gagal Memperbarui Status')
                                                ->body('Deal tanpa Penawaran tidak bisa menjadi WON. Status dikembalikan ke OPEN dan stage dipindahkan ke Kualifikasi.')
                                                ->danger()
                                                ->send();

                                            $set('status', 'open');
                                            $set('nx_deal_stage_id', $kualifikasiStage?->id);
                                            return;
                                        }

                                        // WON PUNYA PENAWARAN = BOLEH
                                        if ($state === 'won') {
                                            $set('nx_deal_stage_id', $wonStage?->id);
                                            // $set('close_date', now()->format('Y-m-d')); // Aktifkan jika ada field close_date
                                        }

                                        // LOST DENGAN DAN TANPA PENAWARAN = BOLEH
                                        elseif ($state === 'lost') {
                                            $set('nx_deal_stage_id', $lostStage?->id);
                                            // $set('close_date', now()->format('Y-m-d')); // Aktifkan jika ada field close_date
                                        }

                                        // OPEN
                                        elseif ($state === 'open') {
                                            if (!$hasQuotation) {
                                                Notification::make()
                                                    ->title('Info')
                                                    ->body('Status berubah ke OPEN dan stage dikembalikan ke Kualifikasi.')
                                                    ->info()
                                                    ->send();

                                                $set('nx_deal_stage_id', $kualifikasiStage?->id);
                                            } else {
                                                $set('nx_deal_stage_id', $penawaranStage?->id);
                                            }
                                            // $set('close_date', null); // Aktifkan jika ada field close_date
                                        }
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
                                    ->helperText('Diisi otomatis ketika status menjadi Won atau Lost.')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('deal_number')
                    ->label('No. Deal')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer_or_lead')
                    ->label('Lead / Customer')
                    ->state(function (Deal $record) {
                        // Cek jika ini Customer (biasanya jarang dihapus soft delete di case ini, tapi aman)
                        if ($record->nx_customer_id) {
                            return $record->customer?->name . ' (Customer)';
                        }

                        // AMBIL LEAD DENGAN WITH TRASHED
                        // Kita panggil query manual agar data yang terhapus tetap terambil
                        $lead = $record->lead()->withTrashed()->first();

                        if ($lead) {
                            // Opsional: Kasih tanda jika lead-nya sudah dihapus
                            $status = $lead->trashed() ? ' (Dihapus)' : '';
                            return $lead->name . ' (Lead)' . $status;
                        }

                        return '-';
                    })
                    ->description(function (Deal $record) {
                        // Lakukan hal yang sama untuk deskripsi email/phone
                        if ($record->nx_customer_id)
                            return $record->customer?->email;

                        $lead = $record->lead()->withTrashed()->first();
                        if ($lead) {
                            $desc = $lead->email ?? '-';
                            // Jika lead terhapus, kita bisa kasih warning warna merah di description
                            if ($lead->trashed()) {
                                return new HtmlString("<span class='text-danger-600 font-bold'>Lead Terhapus</span> • {$desc}");
                            }
                            return $desc;
                        }
                        return '-';
                    })
                    ->searchable(['customer.name', 'lead.name']) // Search mungkin agak tricky jika deleted, tapi display aman
                    ->sortable()
                    ->weight('semibold')
                    ->icon(fn($record) => $record->nx_customer_id ? 'heroicon-o-user-group' : 'heroicon-o-user')
                    ->color(function (Deal $record) {
                        // Warnanya bisa dibedakan jika lead terhapus
                        $lead = $record->lead()->withTrashed()->first();
                        if ($lead && $lead->trashed())
                            return 'danger'; // Merah jika lead dihapus
                        return 'primary';
                    }),

                Tables\Columns\TextColumn::make('lead.phone')
                    ->label('Kontak')
                    ->state(function (Deal $record) {
                        $lead = $record->lead()->withTrashed()->first();
                        return $lead?->phone ?? '-';
                    })
                    ->description(function (Deal $record) {
                        $lead = $record->lead()->withTrashed()->first();
                        return $lead?->email ?? '-';
                    })
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    // Searchable tetap mengacu ke relasi standar, tapi display sudah aman
                    ->searchable(['lead.phone', 'lead.email'])
                    ->color(function (Deal $record) {
                        $lead = $record->lead()->withTrashed()->first();
                        // Jika lead terhapus, kita beri warna danger sebagai warning
                        return ($lead && $lead->trashed()) ? 'danger' : 'success';
                    }),

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

                        $isWon = str_contains($stageName, 'closed won');
                        $isLost = str_contains($stageName, 'closed lost');

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

                        // dd($stageName);
            
                        $newStatus = 'open';

                        if (str_contains($stageName, 'closed won')) {
                            $newStatus = 'won';
                        } elseif (str_contains($stageName, 'closed lost')) {
                            $newStatus = 'lost';
                        }

                        // Jika dari WON turun ke stage lain (KECUALI pindah ke LOST) -> kembali OPEN
                        if ($deal->status === 'won' && !str_contains($stageName, 'closed won') && !str_contains($stageName, 'closed lost')) {
                            $newStatus = 'open';
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
                    ->url(fn(Deal $record): string => QuotationResource::getUrl('create', ['nx_deal_id' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn(Deal $record) => !$record->trashed()),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->before(function (Tables\Actions\RestoreAction $action, Deal $record) {
                        // Ambil parent lead-nya (meskipun lead sedang soft-deleted)
                        $lead = $record->lead()->withTrashed()->first();

                        if ($lead && $lead->trashed()) {
                            Notification::make()
                                ->warning()
                                ->title('Gagal Restore Deal')
                                ->body('Silakan restore Lead terkait terlebih dahulu!')
                                ->send();

                            $action->cancel(); // Batalkan proses restore
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->before(function (Tables\Actions\RestoreBulkAction $action, Collection $records) {
                            foreach ($records as $record) {
                                $lead = $record->lead()->withTrashed()->first();
                                if ($lead && $lead->trashed()) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Gagal Restore Bulk')
                                        ->body("Deal {$record->deal_number} tidak dapat di-restore karena Lead terkait masih terhapus.")
                                        ->send();

                                    $action->cancel();
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            QuotationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeals::route('/'),
            'create' => Pages\CreateDeal::route('/create'),
            'view' => Pages\ViewDeal::route('/{record}'),
            'edit' => Pages\EditDeal::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
