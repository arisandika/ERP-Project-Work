<?php

namespace App\Filament\Resources\CRM\LeadResource\RelationManagers;

use App\Filament\Resources\CRM\DealResource;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Customer;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use App\Models\CRM\Lead;
use App\Models\HR\Employee;
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
use Illuminate\Support\HtmlString;
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
                                    ->prefixIcon('heroicon-o-hashtag'),

                                Forms\Components\TextInput::make('title')
                                    ->label('Nama / Judul Deal')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\Select::make('nx_lead_id')
                                    ->label('Lead')
                                    ->relationship('lead', 'name', function ($query) {
                                        return $query->withTrashed();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn() => request()->query('nx_lead_id'))
                                    ->disabled(fn(string $context) => $context === 'edit' || filled(request()->query('nx_lead_id')))
                                    ->dehydrated()
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        if (!$record)
                                            return 'Tanpa Lead';
                                        return $record->trashed() ? "{$record->name} (Terhapus)" : $record->name;
                                    })
                                    ->prefixIcon('heroicon-o-funnel')

                                    ->createOptionForm(fn(Form $form) => LeadResource::form($form)->getComponents())
                                    ->createOptionAction(fn(\Filament\Forms\Components\Actions\Action $action) => $action->modalWidth('4xl'))
                                    ->createOptionUsing(function (array $data) {
                                        // Otomatis isi created_by untuk lead baru
                                        if (empty($data['created_by'])) {
                                            $data['created_by'] = Employee::where('user_id', auth()->id())->value('id');
                                        }

                                        // Simpan Lead baru ke database
                                        $lead = Lead::create($data);

                                        // Kembalikan ID lead yang baru dibuat agar terpilih di select
                                        return $lead->id;
                                    })
                                    //-------------------------------

                                    ->default(fn() => request()->query('nx_lead_id'))
                                    ->disabled(fn(string $context) => $context === 'edit' || filled(request()->query('nx_lead_id')))
                                    ->dehydrated()
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        if (!$record)
                                            return 'Tanpa Lead';
                                        return $record->trashed() ? "{$record->name} (Terhapus)" : $record->name;
                                    })
                                    ->prefixIcon('heroicon-o-funnel'),

                                Forms\Components\Select::make('nx_deal_stage_id')
                                    ->label('Stage Deal')
                                    ->options(function (string $operation, ?Deal $record) {
                                        $query = DealStage::query();

                                        if ($operation === 'create') {
                                            $query->where(function ($q) {
                                                $q->where('name', 'LIKE', '%Lead Baru%')
                                                    ->orWhere('sort_order', 1);
                                            });
                                        } else {
                                            // CEK FAKTA PENAWARAN
                                            $hasWon = $record && $record->quotations()->where('is_primary', true)->exists();
                                            $hasQuotations = $record && $record->quotations()->exists();
                                            // Dianggap Lost JIKA punya penawaran DAN tidak ada satupun yang statusnya selain 'rejected'
                                            $allRejected = $hasQuotations && $record->quotations()->where('status', '!=', 'rejected')->count() === 0;

                                            if ($hasWon) {
                                                $query->whereRaw('LOWER(name) LIKE ?', ['%won%']);
                                            } elseif ($allRejected) {
                                                $query->whereRaw('LOWER(name) LIKE ?', ['%lost%']);
                                            } else {
                                                // Sembunyikan Won dan Lost karena faktanya belum terpenuhi
                                                $query->whereRaw('LOWER(name) NOT LIKE ?', ['%won%'])
                                                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%lost%'])
                                                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%closed%']);
                                            }
                                        }

                                        return $query->orderBy('sort_order', 'asc')
                                            ->get()
                                            ->mapWithKeys(fn($stage) => [$stage->id => "Stage {$stage->sort_order} - {$stage->name}"]);
                                    })
                                    ->disabled(function (?Deal $record) {
                                        if (!$record)
                                            return false;
                                        // DISABLE input jika sudah Won atau semua Quotation Rejected
                                        $hasWon = $record->quotations()->where('is_primary', true)->exists();
                                        $hasQuotations = $record->quotations()->exists();
                                        $allRejected = $hasQuotations && $record->quotations()->where('status', '!=', 'rejected')->count() === 0;

                                        return $hasWon || $allRejected;
                                    })
                                    ->dehydrated() // Wajib agar state yang disabled tetap terkirim saat save
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(function () {
                                        return DealStage::where(function ($q) {
                                            $q->where('name', 'LIKE', '%Lead Baru%')
                                                ->orWhere('sort_order', 1);
                                        })->orderBy('sort_order', 'asc')->first()?->id;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, $old, callable $set, callable $get, ?Deal $record) {
                                        if (!$state)
                                            return;

                                        $targetStage = DealStage::find($state);
                                        if (!$targetStage)
                                            return;

                                        $hasQuotation = $record ? $record->quotations()->exists() : false;
                                        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first();
                                        $penawaranProbability = $penawaranStage?->probability ?? 0;

                                        // Validasi Stage Lanjutan jika belum punya penawaran
                                        if (!$hasQuotation && $targetStage->probability >= $penawaranProbability) {
                                            Notification::make()->title('Gagal')->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran atau di atasnya.')->danger()->send();
                                            $set('nx_deal_stage_id', $old);
                                            return;
                                        }
                                    }),

                                Forms\Components\Select::make('created_by')
                                    ->label('Dibuat Oleh')
                                    ->relationship('createdBy', 'full_name')
                                    ->default(fn() => Employee::where('user_id', auth()->id())->value('id'))
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-user'),

                                Forms\Components\Select::make('nx_customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-user-group')
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
                                    ->required()
                                    ->minValue(0),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        Deal::STATUS_OPEN => 'Open',
                                        Deal::STATUS_CLOSED_WON => 'Won',
                                        Deal::STATUS_CLOSED_LOST => 'Lost',
                                        Deal::STATUS_ON_HOLD => 'On Hold',
                                    ])
                                    ->required()
                                    ->default(Deal::STATUS_OPEN)
                                    ->selectablePlaceholder(false)
                                    ->native(false)
                                    ->disableOptionWhen(function (string $value, ?Deal $record) {
                                        if (!$record) {
                                            return in_array($value, [Deal::STATUS_CLOSED_WON, Deal::STATUS_CLOSED_LOST]);
                                        }

                                        $hasWon = $record->quotations()->where('is_primary', true)->exists();
                                        $hasQuotations = $record->quotations()->exists();
                                        $allRejected = $hasQuotations && $record->quotations()->where('status', '!=', 'rejected')->count() === 0;

                                        // Sembunyikan dan disable Won / Lost jika bukan faktanya
                                        if ($hasWon) {
                                            return $value !== Deal::STATUS_CLOSED_WON;
                                        } elseif ($allRejected) {
                                            return $value !== Deal::STATUS_CLOSED_LOST;
                                        } else {
                                            return in_array($value, [Deal::STATUS_CLOSED_WON, Deal::STATUS_CLOSED_LOST]);
                                        }
                                    })
                                    ->disabled(function (?Deal $record) {
                                        if (!$record)
                                            return false;
                                        // DISABLE input jika sudah Won atau semua Quotation Rejected
                                        $hasWon = $record->quotations()->where('is_primary', true)->exists();
                                        $hasQuotations = $record->quotations()->exists();
                                        $allRejected = $hasQuotations && $record->quotations()->where('status', '!=', 'rejected')->count() === 0;

                                        return $hasWon || $allRejected;
                                    })
                                    ->dehydrated() // Wajib agar state yang disabled tetap terkirim saat save
                                    ->live()
                                    ->afterStateUpdated(function ($state, $old, callable $set, callable $get, ?Deal $record) {
                                        if ($state === Deal::STATUS_OPEN) {
                                            $set('close_date', null);
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
                                    ->helperText('Diisi otomatis ketika status menjadi Lost/Won.')
                                    ->dehydrateStateUsing(fn($state) => $state ?? null)
                                    ->disabled(fn(Forms\Get $get) => in_array($get('status'), [Deal::STATUS_OPEN, Deal::STATUS_ON_HOLD]))
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
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Deal')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('customer_or_lead')
                    ->label('Lead / Customer')
                    ->state(function (Deal $record) {
                        if ($record->nx_customer_id) {
                            return $record->customer?->name . ' (Customer)';
                        }

                        $lead = $record->lead()->withTrashed()->first();
                        if ($lead) {
                            $status = $lead->trashed() ? ' (Dihapus)' : '';
                            return $lead->name . $status;
                        }

                        return '-';
                    })
                    ->description(function (Deal $record) {
                        $infoParts = [];

                        // 1. Tampilkan Email di baris pertama
                        $email = $record->nx_customer_id ? $record->customer?->email : $record->lead()->withTrashed()->first()?->email;
                        if ($email) {
                            $infoParts[] = "<span class='text-gray-500'>{$email}</span>";
                        }

                        $badges = [];
                        $lead = $record->lead()->withTrashed()->first();

                        // 2. Badge Status Penghapusan Lead
                        if ($lead && $lead->trashed()) {
                            $badges[] = "<span class='inline-flex items-center px-2 py-0.5 text-xs font-medium bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-danger text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30'>Lead Terhapus</span>";
                        }

                        // 3. Badge Status Konversi
                        if ($record->nx_customer_id) {
                            $badges[] = "<span class='inline-flex items-center px-2 py-0.5 text-xs font-medium bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-success text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30'>Customer Aktif</span>";
                        } elseif ($lead && $lead->converted_customer_id) {
                            $badges[] = "<span class='inline-flex items-center px-2 py-0.5 text-xs font-medium bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-info text-info-600 ring-info-600/30 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30'>Lead Terkonversi</span>";
                        } else {
                            $badges[] = "<span class='inline-flex items-center px-2 py-0.5 text-xs font-medium bg-white rounded-md shadow-sm ring-1 ring-inset fi-color-warning text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30'>Prospek Lead</span>";
                        }

                        // Gabungkan semua badge dengan spasi, lalu taruh di bawah email dengan <br>
                        if (!empty($badges)) {
                            $infoParts[] = "<div class='flex flex-wrap gap-1 mt-1'>" . implode(' ', $badges) . "</div>";
                        }

                        return new HtmlString(implode('', $infoParts));
                    })
                    ->searchable(['customer.name', 'lead.name'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Mengambil nama tabel secara dinamis dari Model
                        $customerTable = (new Customer())->getTable();
                        $leadTable = (new Lead())->getTable();

                        // Menggunakan COALESCE: Jika Customer Name null, urutkan berdasarkan Lead Name
                        return $query->orderByRaw("
                            COALESCE(
                                (SELECT name FROM {$customerTable} WHERE {$customerTable}.id = nx_deals.nx_customer_id),
                                (SELECT name FROM {$leadTable} WHERE {$leadTable}.id = nx_deals.nx_lead_id)
                            ) {$direction}
                        ");
                    })
                    ->weight('semibold')
                    ->icon('heroicon-o-building-office')
                    ->color(function (Deal $record) {
                        $lead = $record->lead()->withTrashed()->first();
                        if ($lead && $lead->trashed())
                            return 'danger';
                        return '';
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
                    ->searchable(['lead.phone', 'lead.email'])
                    ->color(function (Deal $record) {
                        $lead = $record->lead()->withTrashed()->first();
                        return ($lead && $lead->trashed()) ? 'danger' : 'success';
                    }),

                Tables\Columns\SelectColumn::make('nx_deal_stage_id')
                    ->label('Stage Deal')
                    ->options(function ($record) {
                        $query = DealStage::query();

                        // CEK FAKTA PENAWARAN
                        $hasWon = $record->quotations()->where('is_primary', true)->exists();
                        $hasQuotations = $record->quotations()->exists();
                        $allRejected = $hasQuotations && $record->quotations()->where('status', '!=', 'rejected')->count() === 0;

                        if ($hasWon) {
                            $query->whereRaw('LOWER(name) LIKE ?', ['%won%']);
                        } elseif ($allRejected) {
                            $query->whereRaw('LOWER(name) LIKE ?', ['%lost%']);
                        } else {
                            // Sembunyikan Won dan Lost karena faktanya belum terpenuhi
                            $query->whereRaw('LOWER(name) NOT LIKE ?', ['%won%'])
                                ->whereRaw('LOWER(name) NOT LIKE ?', ['%lost%'])
                                ->whereRaw('LOWER(name) NOT LIKE ?', ['%closed%']);
                        }

                        return $query->orderBy('sort_order', 'asc')
                            ->pluck('name', 'id')
                            ->map(fn($name, $id) => $name); // Anda bisa memformat label di sini jika perlu
                    })
                    ->disabled(function ($record) {
                        // DISABLE jika sudah Won atau semua Quotation Rejected (Fakta sudah mengunci)
                        $hasWon = $record->quotations()->where('is_primary', true)->exists();
                        $hasQuotations = $record->quotations()->exists();
                        $allRejected = $hasQuotations && $record->quotations()->where('status', '!=', 'rejected')->count() === 0;

                        return $hasWon || $allRejected;
                    })
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->beforeStateUpdated(function ($record, $state) {
                        $targetStage = DealStage::find($state);
                        if (!$targetStage)
                            return;

                        $hasQuotation = $record->quotations()->exists();
                        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first();
                        $penawaranProbability = $penawaranStage?->probability ?? 0;

                        // Validasi Stage Lanjutan jika belum punya penawaran (mencegah bypass lewat UI)
                        if (!$hasQuotation && $targetStage->probability >= $penawaranProbability) {
                            Notification::make()
                                ->title('Gagal Memperbarui Stage')
                                ->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran atau di atasnya.')
                                ->danger()
                                ->send();

                            throw ValidationException::withMessages(['nx_deal_stage_id' => 'Validasi gagal.']);
                        }
                    })
                    ->afterStateUpdated(function ($record, $state) {
                        Notification::make()
                            ->title('Stage Diperbarui')
                            ->success()
                            ->send();
                    }),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options(function ($record) {
                        $hasWon = $record->quotations()->where('is_primary', true)->exists();
                        $hasQuotations = $record->quotations()->exists();
                        $allRejected = $hasQuotations && $record->quotations()->where('status', '!=', 'rejected')->count() === 0;

                        if ($hasWon) {
                            return [Deal::STATUS_CLOSED_WON => 'Won'];
                        } elseif ($allRejected) {
                            return [Deal::STATUS_CLOSED_LOST => 'Lost'];
                        } else {
                            return [
                                Deal::STATUS_OPEN => 'Open',
                                Deal::STATUS_ON_HOLD => 'On Hold',
                            ];
                        }
                    })
                    ->disabled(function ($record) {
                        $hasWon = $record->quotations()->where('is_primary', true)->exists();
                        $hasQuotations = $record->quotations()->exists();
                        $allRejected = $hasQuotations && $record->quotations()->where('status', '!=', 'rejected')->count() === 0;

                        return $hasWon || $allRejected;
                    })
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->afterStateUpdated(function ($record, $state) {
                        // Jika status dirubah ke Open, pastikan close_date dikosongkan
                        if ($state === Deal::STATUS_OPEN) {
                            $record->update(['close_date' => null]);
                        }

                        Notification::make()
                            ->title('Status Diperbarui')
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Deal')
                    ->date('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quotations_count')
                    ->label('Jumlah Penawaran')
                    ->badge()
                    ->state(function (Deal $record) {
                        return $record->quotations()->withTrashed()->count();
                    })
                    ->color(function (Deal $record, int $state): string {
                        if ($state === 0)
                            return 'gray';

                        $hasTrashed = $record->quotations()->onlyTrashed()->exists();

                        return $hasTrashed ? 'danger' : 'info';
                    })
                    ->formatStateUsing(function ($state, Deal $record) {
                        $trashedCount = $record->quotations()->onlyTrashed()->count();

                        if ($trashedCount > 0) {
                            return "{$trashedCount} Penawaran Terhapus";
                        }

                        return $state . ' Penawaran';
                    })
                    ->sortable(),

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
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Deal')
                    ->mutateFormDataUsing(function (array $data) {
                        $leadId = $data['nx_lead_id'] ?? null;

                        if ($leadId) {
                            // Cari Lead (termasuk yang soft deleted)
                            $lead = Lead::withTrashed()->find($leadId);

                            // Jika Lead ditemukan dan statusnya terhapus
                            if ($lead && $lead->trashed()) {

                                Notification::make()
                                    ->title('Gagal Membuat Deal')
                                    ->body("Lead '{$lead->name}' sedang terhapus. Silakan restore Lead terlebih dahulu.")
                                    ->danger()
                                    ->send();

                                // Hentikan proses simpan dan munculkan error di field form
                                throw ValidationException::withMessages([
                                    'nx_lead_id' => 'Lead ini sedang dalam kondisi terhapus (Trash).',
                                ]);
                            }
                        }

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

                            throw \Illuminate\Validation\ValidationException::withMessages([
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

                            throw \Illuminate\Validation\ValidationException::withMessages([
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

    public function isReadOnly(): bool
    {
        return false;
    }
}
