<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\LeadResource\Pages;
use App\Filament\Resources\CRM\LeadResource\RelationManagers;
use App\Filament\Resources\CRM\LeadResource\RelationManagers\DealsRelationManager;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Lead;
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

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Manajemen CRM';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'crm/leads';

    protected static ?string $pluralModelLabel = 'Lead';

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
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Individu/Perusahaan')
                                    ->required()
                                    ->maxLength(150)
                                    ->prefixIcon('heroicon-o-user'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(150)
                                    ->prefixIcon('heroicon-o-envelope'),

                                Forms\Components\TextInput::make('phone')
                                    ->label('No. WhatsApp')
                                    ->tel()
                                    ->maxLength(30)
                                    ->prefixIcon('heroicon-o-device-phone-mobile'),

                                Forms\Components\Select::make('customer_type')
                                    ->label('Tipe')
                                    ->options([
                                        'individual' => 'Individual (Perorangan)',
                                        'company' => 'Company (Perusahaan)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-identification'),

                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat Domisili/Kantor')
                                    ->rows(3)
                                    ->columnSpanFull(),
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
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\Select::make('converted_customer_id')
                                    ->label('Pilih Customer Terkonversi')
                                    ->relationship('convertedCustomer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn(Forms\Get $get) => $get('status') === 'converted')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\RichEditor::make('notes')
                            ->label('Catatan Sales')
                            ->toolbarButtons(['bold', 'bulletList', 'orderedList', 'link'])
                            ->placeholder('Tulis kebutuhan spesifik klien...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Daftar Deal Terkait')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Forms\Components\Placeholder::make('deals_list')
                            ->hiddenLabel()
                            ->content(function ($record) {
                                $deals = $record->deals()->withTrashed()->with('stage')->latest()->get();

                                if ($deals->isEmpty()) {
                                    return new HtmlString(
                                        '<p class="text-sm italic text-gray-500">Belum ada deal yang dibuat.</p>'
                                    );
                                }

                                $html = '<div class="grid w-full grid-cols-1 gap-4 card-repeater md:grid-cols-2">';

                                foreach ($deals as $deal) {
                                    $isDeleted = $deal->trashed();

                                    if ($isDeleted) {
                                        $containerClass = 'bg-danger-50 ring-danger-600/30 dark:bg-danger-900/20 dark:ring-danger-500/30 border-danger-200';
                                        $badgeClass = 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger';
                                        $textClass = 'text-gray-700 dark:text-white';
                                        $statusLabel = 'Terhapus';
                                    } else {
                                        $containerClass = match ($deal->status) {
                                            'won' => 'fi-color-success bg-success-100/40 text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                                            'lost' => 'fi-color-danger bg-danger-100/40 text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                                            default => 'fi-color-warning bg-warning-100/40 text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30 fi-color-warning',
                                        };

                                        $badgeClass = match ($deal->status) {
                                            'won' => 'fi-color-success bg-white text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                                            'lost' => 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                                            default => 'fi-color-warning bg-white text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30 fi-color-warning',
                                        };

                                        $textClass = 'text-gray-700 dark:text-white';
                                        $statusLabel = ucfirst($deal->status);
                                    }

                                    $stageName = $deal->stage->name ?? '-';
                                    $dealNumber = $deal->deal_number;
                                    $value = 'IDR ' . number_format($deal->estimated_value, 0, ',', '.');
                                    $date = $deal->created_at->format('d M Y');
                                    $deletedDate = $isDeleted ? '<br><span class="text-xs font-semibold text-danger-600 dark:text-danger-400">Dihapus: ' . $deal->deleted_at->format('d M Y') . '</span>' : '';

                                    $html .= "
                                    <div class='flex flex-col justify-between p-4 rounded-lg ring-1 ring-inset shadow-sm {$containerClass} transition duration-150 ease-in-out'>
                                        <div class='flex items-start justify-between mb-2'>
                                            <div>
                                                <span class='font-bold text-sm block {$textClass}'>{$dealNumber}</span>
                                                <span class='text-xs opacity-75 {$textClass}'>{$date}</span>
                                                {$deletedDate}
                                            </div>
                                            <span class='inline-flex items-center rounded-md px-2 py-1 text-xs ring-1 ring-inset shadow-sm capitalize {$badgeClass}'>
                                                {$statusLabel}
                                            </span>
                                        </div>
                                        
                                        <div class='flex items-end justify-between pt-3 mt-3 border-t border-black/20 dark:border-white/20'>
                                            <div class='text-xs {$textClass}'>
                                                <p class='opacity-70 uppercase tracking-wider text-[10px]'>Stage</p>
                                                <p class='text-sm font-semibold'>{$stageName}</p>
                                            </div>
                                            <div class='text-xs text-right {$textClass}'>
                                                <p class='opacity-70 uppercase tracking-wider text-[10px]'>Est. Value</p>
                                                <p class='text-sm font-semibold'>{$value}</p>
                                            </div>
                                        </div>
                                    </div>";
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ])
                    ->visible(fn($record) => $record && $record->deals()->withTrashed()->exists())
                    ->columnSpanFull(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lead')
                    ->state(function (Lead $record) {
                        $record->withTrashed()->first();

                        if ($record) {
                            $status = $record->trashed() ? ' (Dihapus)' : '';
                            return $record->name . $status;
                        }

                        return '-';
                    })
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color(function (Lead $record) {
                        $record->withTrashed()->first();
                        if ($record && $record->trashed())
                            return 'danger';
                        return '';
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->description(fn(Lead $record) => $record->email)
                    ->searchable(['phone', 'email'])
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->searchable(['lead.phone', 'lead.email'])
                    ->color(function (Lead $record) {
                        $record->withTrashed()->first();
                        return ($record && $record->trashed()) ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Tipe')
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Deal')
                    ->badge()
                    ->getStateUsing(function (Lead $record) {
                        $allDeals = $record->deals()->withTrashed()->get();

                        $activeDeals = $allDeals->whereNull('deleted_at');

                        if ($allDeals->isEmpty()) {
                            return match ($record->status) {
                                'new' => 'New',
                                'contacted' => 'Contacted',
                                'qualified' => 'Qualified',
                                default => ucfirst($record->status),
                            };
                        }

                        if ($activeDeals->isEmpty()) {
                            return 'All Deals Deleted';
                        }

                        if ($activeDeals->contains('status', 'open')) {
                            return 'Active Process';
                        }
                        if ($activeDeals->contains('status', 'won')) {
                            return 'Existing Customer';
                        }

                        return 'Lost Prospect';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Active Process' => 'info',
                        'Existing Customer' => 'success',
                        'Lost Prospect' => 'danger',
                        'All Deals Deleted' => 'danger',
                        'New' => 'primary',
                        'Contacted' => 'warning',
                        'Qualified' => 'success',

                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('deals_summary')
                    ->label('Ringkasan Deal')
                    ->state(function (Lead $record) {
                        $deals = $record->deals()->withTrashed()->with('stage')->latest()->get();

                        if ($deals->isEmpty())
                            return '-';

                        $badges = [];

                        foreach ($deals as $deal) {
                            if ($deal->trashed()) {
                                $color = 'fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30';
                                $label = 'Terhapus';
                            } else {
                                $color = match ($deal->status) {
                                    'won' => 'fi-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
                                    'lost' => 'fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
                                    default => 'fi-color-warning bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
                                };
                                $label = $deal->stage->name ?? 'Unknown';
                            }

                            $badges[] = "<span class='custom-badge inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {$color}'>{$label}</span>";
                        }

                        $display = array_slice($badges, 0, 2);

                        if (count($badges) > 2) {
                            $more = count($badges) - 2;
                            $display[] = "<span class='text-xs font-medium text-gray-500'>+{$more} deal lainnya</span>";
                        }

                        return new HtmlString(implode(' ', $display));
                    }),

                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deals_count')
                    ->label('Jumlah Deal')
                    ->badge()
                    ->state(function (Lead $record) {
                        return $record->deals()->withTrashed()->count();
                    })
                    ->color(function (Lead $record, int $state): string {
                        if ($state === 0)
                            return 'gray';

                        $hasTrashed = $record->deals()->onlyTrashed()->exists();

                        return $hasTrashed ? 'danger' : 'success';
                    })
                    ->sortable()
                    ->formatStateUsing(function ($state, Lead $record) {
                        $trashedCount = $record->deals()->onlyTrashed()->count();

                        if ($trashedCount > 0) {
                            return "{$trashedCount} Deal Terhapus";
                        }

                        return $state . ' Deal';
                    }),

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
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Tipe')
                    ->options([
                        'individual' => 'Individual',
                        'company' => 'Company',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'converted' => 'Converted',
                        'lost' => 'Lost',
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
