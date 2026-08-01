<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\LeadResource\Pages;
use App\Filament\Resources\CRM\LeadResource\RelationManagers;
use App\Filament\Resources\CRM\LeadResource\RelationManagers\DealsRelationManager;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Deal;
use App\Models\CRM\Lead;
use App\Models\HR\Employee;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use App\Filament\Concerns\BelongsToModule;


class LeadResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'crm';

    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Manajemen CRM';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'crm/leads';

    protected static ?string $pluralModelLabel = 'Lead';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Lead')
                    ->description('Lengkapi data untuk lead baru')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('customer_type')
                                    ->label('Tipe')
                                    ->options([
                                        'individual' => 'Perorangan (B2C)',
                                        'company' => 'Perusahaan (B2B)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-identification')
                                    ->live(),

                                Forms\Components\TextInput::make('name')
                                    ->label(fn(Forms\Get $get) => $get('customer_type') === 'company' ? 'Nama Perusahaan' : 'Nama Individu')
                                    ->required()
                                    ->maxLength(150)
                                    ->prefixIcon('heroicon-o-building-office'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(100)
                                    ->required()
                                    ->prefixIcon('heroicon-o-envelope'),

                                Forms\Components\TextInput::make('phone')
                                    ->label('No. WhatsApp')
                                    ->tel()
                                    ->maxLength(14)
                                    ->required()
                                    ->prefixIcon('heroicon-o-device-phone-mobile'),

                                Forms\Components\Textarea::make('address')
                                    ->label(fn(Forms\Get $get) => $get('customer_type') === 'company' ? 'Alamat Kantor' : 'Alamat Domisili')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Forms\Components\Section::make('Informasi PIC (Person In Charge)')
                    ->description('Data narahubung dari pihak Lead/Perusahaan')
                    ->collapsible()
                    ->visible(fn(Forms\Get $get) => $get('customer_type') === 'company')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('pic_name')
                                    ->label('Nama PIC')
                                    ->required()
                                    ->prefixIcon('heroicon-o-user-circle'),

                                Forms\Components\TextInput::make('pic_email')
                                    ->label('Email PIC')
                                    ->email()
                                    ->required()
                                    ->prefixIcon('heroicon-o-envelope'),

                                Forms\Components\TextInput::make('pic_phone')
                                    ->label('No. WhatsApp PIC')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-device-phone-mobile')
                                    ->required(),

                                Forms\Components\TextInput::make('pic_position')
                                    ->label('Jabatan PIC')
                                    ->prefixIcon('heroicon-o-briefcase'),
                            ]),
                    ]),

                Forms\Components\Section::make('Status & Klasifikasi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('source')
                                    ->label('Sumber dari')
                                    ->options([
                                        'manual' => 'Manual',
                                        'website' => 'Website/Form',
                                        'social_media' => 'Social Media (IG/FB/Tiktok)',
                                        'referral' => 'Referral/Rekomendasi',
                                        'cold_call' => 'Cold Call/Canvas',
                                        'ads' => 'Iklan Berbayar',
                                        'other' => 'Lainnya',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\Select::make('status')
                                    ->label('Status Lead')
                                    ->options([
                                        Lead::STATUS_NEW => 'New',
                                        Lead::STATUS_CONTACTED => 'Contacted',
                                        Lead::STATUS_QUALIFIED => 'Qualified',
                                        Lead::STATUS_UNQUALIFIED => 'Unqualified',
                                        Lead::STATUS_CONVERTED => 'Converted',
                                        Lead::STATUS_DEAD => 'Dead',
                                    ])
                                    ->default(Lead::STATUS_NEW)
                                    ->disabled(fn(?Lead $record) => $record === null)
                                    ->dehydrated()
                                    ->required()
                                    ->native(false)
                                    ->live(),

                                Forms\Components\Select::make('created_by')
                                    ->label('Dibuat Oleh (Sales)')
                                    ->relationship('createdBy', 'full_name')
                                    ->default(fn() => Employee::where('user_id', auth()->id())->value('id'))
                                    ->disabled()
                                    ->dehydrated() // Wajib agar default valuenya tersimpan
                                    ->prefixIcon('heroicon-o-user'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->visible(fn(Forms\Get $get) => $get('status') === Lead::STATUS_CONVERTED)
                            ->schema([
                                Forms\Components\Select::make('converted_customer_id')
                                    ->label('Customer Terkonversi')
                                    ->relationship('convertedCustomer', 'name')
                                    ->disabled()
                                    ->dehydrated(false),

                                // PERBAIKAN: Ubah 'name' menjadi 'full_name' karena ini tabel Employee
                                Forms\Components\Select::make('converted_by')
                                    ->label('Dikonversi Oleh')
                                    ->relationship('convertedBy', 'full_name')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\DateTimePicker::make('converted_at')
                                    ->label('Waktu Konversi')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\RichEditor::make('notes')
                            ->label('Catatan Sales')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('attachments/lead-notes')
                            ->fileAttachmentsVisibility('public')
                            ->placeholder('Tulis kebutuhan spesifik klien...'),
                    ]),

                // Forms\Components\Section::make('Daftar Deal Terkait')
                //     ->icon('heroicon-o-briefcase')
                //     ->schema([
                //         Forms\Components\Placeholder::make('deals_list')
                //             ->hiddenLabel()
                //             ->content(function ($record) {
                //                 $deals = $record->deals()->withTrashed()->with('stage')->latest()->get();

                //                 if ($deals->isEmpty()) {
                //                     return new HtmlString(
                //                         '<p class="text-sm italic text-gray-500">Belum ada deal yang dibuat.</p>'
                //                     );
                //                 }

                //                 $html = '<div class="grid w-full grid-cols-1 gap-4 card-repeater md:grid-cols-2">';

                //                 foreach ($deals as $deal) {
                //                     $isDeleted = $deal->trashed();

                //                     if ($isDeleted) {
                //                         $containerClass = 'bg-danger-50 ring-danger-600/30 dark:bg-danger-900/20 dark:ring-danger-500/30 border-danger-200';
                //                         $badgeClass = 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger';
                //                         $textClass = 'text-gray-700 dark:text-white';
                //                         $statusLabel = 'Terhapus';
                //                     } else {
                //                         $containerClass = match ($deal->status) {
                //                             'won' => 'fi-color-success bg-success-100/40 text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                //                             'lost' => 'fi-color-danger bg-danger-100/40 text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                //                             default => 'fi-color-warning bg-warning-100/40 text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30 fi-color-warning',
                //                         };

                //                         $badgeClass = match ($deal->status) {
                //                             'won' => 'fi-color-success bg-white text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                //                             'lost' => 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                //                             default => 'fi-color-warning bg-white text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30 fi-color-warning',
                //                         };

                //                         $textClass = 'text-gray-700 dark:text-white';
                //                         $statusLabel = ucfirst($deal->status);
                //                     }

                //                     $stageName = $deal->stage->name ?? '-';
                //                     $dealNumber = $deal->deal_number;
                //                     $value = 'IDR ' . number_format($deal->estimated_value, 0, ',', '.');
                //                     $date = $deal->created_at->format('d M Y');
                //                     $deletedDate = $isDeleted ? '<br><span class="text-xs font-semibold text-danger-600 dark:text-danger-400">Dihapus: ' . $deal->deleted_at->format('d M Y') . '</span>' : '';

                //                     $html .= "
                //                     <div class='flex flex-col justify-between p-4 rounded-lg ring-1 ring-inset shadow-sm {$containerClass} transition duration-150 ease-in-out'>
                //                         <div class='flex items-start justify-between mb-2'>
                //                             <div>
                //                                 <span class='font-bold text-sm block {$textClass}'>{$dealNumber}</span>
                //                                 <span class='text-xs opacity-75 {$textClass}'>{$date}</span>
                //                                 {$deletedDate}
                //                             </div>
                //                             <span class='inline-flex items-center rounded-md px-2 py-1 text-xs ring-1 ring-inset shadow-sm capitalize {$badgeClass}'>
                //                                 {$statusLabel}
                //                             </span>
                //                         </div>

                //                         <div class='flex items-end justify-between pt-3 mt-3 border-t border-black/20 dark:border-white/20'>
                //                             <div class='text-xs {$textClass}'>
                //                                 <p class='opacity-70 uppercase tracking-wider text-[10px]'>Stage</p>
                //                                 <p class='text-sm font-semibold'>{$stageName}</p>
                //                             </div>
                //                             <div class='text-xs text-right {$textClass}'>
                //                                 <p class='opacity-70 uppercase tracking-wider text-[10px]'>Est. Value</p>
                //                                 <p class='text-sm font-semibold'>{$value}</p>
                //                             </div>
                //                         </div>
                //                     </div>";
                //                 }

                //                 $html .= '</div>';

                //                 return new HtmlString($html);
                //             }),
                //     ])
                //     ->visible(fn($record) => $record && $record->deals()->withTrashed()->exists())
                //     ->columnSpanFull(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['deals', 'deals.stage', 'convertedCustomer'])) // Eager load untuk mencegah N+1
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lead')
                    ->state(function (Lead $record) {
                        // PERBAIKAN: Cukup gunakan $record->trashed(), tidak perlu query ulang
                        $status = $record->trashed() ? ' (Dihapus)' : '';
                        return $record->name . $status;
                    })
                    ->description(fn(Lead $record) => $record->customer_type === 'company' ? 'Perusahaan' : 'Individu')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-building-office')
                    ->color(fn(Lead $record) => $record->trashed() ? 'danger' : ''),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->description(fn(Lead $record) => $record->email)
                    ->searchable(['phone', 'email']) // PERBAIKAN: hapus 'lead.phone' karena ini model lead itu sendiri
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->color(fn(Lead $record) => $record->trashed() ? 'danger' : 'success'),

                // PENAMBAHAN: Kolom Info PIC
                Tables\Columns\TextColumn::make('pic_name')
                    ->label('Info PIC')
                    ->state(fn(Lead $record) => $record->customer_type === 'company' ? $record->pic_name : '-')
                    ->description(fn(Lead $record) => $record->customer_type === 'company' ? ($record->pic_phone ?: $record->pic_email) : null)
                    ->searchable(['pic_name', 'pic_phone', 'pic_email'])
                    ->toggleable(),

                // PENAMBAHAN: Status Asli Lead (New, Contacted, dll)
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Lead')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Lead::STATUS_NEW => 'info',
                        Lead::STATUS_CONTACTED => 'warning',
                        Lead::STATUS_QUALIFIED => 'success',
                        Lead::STATUS_UNQUALIFIED => 'danger',
                        Lead::STATUS_CONVERTED => 'success',
                        Lead::STATUS_DEAD => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                // Kolom Deal Progress (Dari kode Anda, diubah labelnya agar tidak ambigu dengan status lead)
                Tables\Columns\TextColumn::make('deal_progress')
                    ->label('Progress Deal')
                    ->badge()
                    ->getStateUsing(function (Lead $record) {
                        $allDeals = $record->deals; // Sudah di eager load di modifyQueryUsing
                        $activeDeals = $allDeals->whereNull('deleted_at');

                        if ($allDeals->isEmpty())
                            return 'Belum Ada Deal';
                        if ($activeDeals->isEmpty())
                            return 'Semua Deal Terhapus';
                        if ($activeDeals->contains('status', 'open'))
                            return 'Sedang Aktif Proses';
                        if ($activeDeals->contains('status', 'won'))
                            return 'Terkonversi';

                        return 'Prospect Hilang';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Sedang Aktif Proses' => 'info',
                        'Terkonversi' => 'success',
                        'Prospect Hilang' => 'danger',
                        'Semua Deal Terhapus' => 'danger',
                        'Belum Ada Deal' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deals_summary')
                    ->label('Ringkasan Deal')
                    ->state(function (Lead $record) {
                        $deals = $record->deals; // Gunakan relasi yang sudah diload
                        if ($deals->isEmpty())
                            return [];

                        return $deals->map(function ($deal) {
                            if ($deal->trashed()) {
                                $color = 'danger';
                                $label = 'Terhapus';
                            } else {
                                $color = match ($deal->status) {
                                    'won' => 'success',
                                    'lost' => 'danger',
                                    default => 'warning',
                                };
                                $label = $deal->stage->name ?? 'Unknown';
                            }
                            return "{$color}::{$label}";
                        })->toArray();
                    })
                    ->badge()
                    ->color(fn(string $state): string => explode('::', $state)[0] ?? 'gray')
                    ->formatStateUsing(fn(string $state): string => explode('::', $state)[1] ?? $state)
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('createdBy.full_name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->placeholder('—'),

                // PENAMBAHAN: Info Konversi (Hidden by default)
                Tables\Columns\TextColumn::make('convertedCustomer.name')
                    ->label('Dikonversi Ke')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Tipe')
                    ->options([
                        'individual' => 'Perorangan (B2C)',
                        'company' => 'Perusahaan (B2B)',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Lead::STATUS_NEW => 'New',
                        Lead::STATUS_CONTACTED => 'Contacted',
                        Lead::STATUS_QUALIFIED => 'Qualified',
                        Lead::STATUS_UNQUALIFIED => 'Unqualified',
                        Lead::STATUS_CONVERTED => 'Converted',
                        Lead::STATUS_DEAD => 'Dead',
                    ]),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([
                        'manual' => 'Manual',
                        'website' => 'Website',
                        'social_media' => 'Social Media',
                        'referral' => 'Referral',
                        'ads' => 'Iklan',
                    ]),

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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            DealsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
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
