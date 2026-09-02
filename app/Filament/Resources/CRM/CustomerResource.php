<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\CRM\CustomerResource\Pages;
use App\Models\CRM\Customer;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class CustomerResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'crm';

    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Manajemen CRM';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'crm/customers';

    protected static ?string $pluralModelLabel = 'Customer';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Customer')
                    ->description('Lengkapi data untuk customer.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(fn(Forms\Get $get) => $get('customer_type') === 'company' ? 'Nama Perusahaan' : 'Nama Individu')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-building-office'),
                                Forms\Components\Select::make('customer_type')
                                    ->label('Tipe')
                                    ->options([
                                        'individual' => 'Perorangan (B2C)',
                                        'company' => 'Perusahaan (B2B)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->prefixIcon('heroicon-o-identification'),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-envelope'),
                                Forms\Components\TextInput::make('phone')
                                    ->label('No. WhatsApp')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-device-phone-mobile')
                                    ->required(),
                                Forms\Components\Textarea::make('address')
                                    ->label(fn(Forms\Get $get) => $get('customer_type') === 'company' ? 'Alamat Kantor' : 'Alamat Domisili')
                                    ->rows(3)
                                    ->maxLength(255)
                                    ->columnSpanFull(),  // KONSISTENSI: Layout seperti Lead
                            ]),
                    ]),
                Forms\Components\Section::make('Informasi Tambahan & Legalitas')
                    ->description('Lengkapi data legalitas seperti NIK atau NPWP.')
                    ->schema([
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK (KTP)')
                            ->numeric()
                            ->minLength(16)
                            ->maxLength(16)
                            ->prefixIcon('heroicon-o-identification')
                            ->required()
                            ->visible(fn(Forms\Get $get) => $get('customer_type') === 'individual'),
                        Forms\Components\TextInput::make('npwp')
                            ->label('NPWP Perusahaan')
                            ->prefixIcon('heroicon-o-document-text')
                            ->visible(fn(Forms\Get $get) => $get('customer_type') === 'company'),
                    ])
                    ->columns(['default' => 12, 'md' => 2]),
                Forms\Components\Section::make('Informasi PIC (Person In Charge)')
                    ->description('Data narahubung dari pihak Customer/Perusahaan')
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
                                        'lead_conversion' => 'Konversi dari Lead',
                                        'other' => 'Lainnya',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->native(false),
                                Forms\Components\Select::make('status')
                                    ->label('Status Customer')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'lost' => 'Lost',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false),
                            ]),
                    ]),
                Forms\Components\Section::make('Daftar Deal Terkait')
                    ->icon('heroicon-o-briefcase')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Placeholder::make('deals_list')
                            ->hiddenLabel()
                            ->content(function ($record) {
                                if (!$record)
                                    return new HtmlString('<p class="text-sm italic text-gray-500">Simpan data terlebih dahulu.</p>');

                                $deals = $record->deals()->withTrashed()->with('stage')->latest()->get();
                                if ($deals->isEmpty())
                                    return new HtmlString('<p class="text-sm italic text-gray-500">Belum ada deal.</p>');

                                $html = '<div class="grid w-full grid-cols-1 gap-4 card-repeater md:grid-cols-2">';
                                foreach ($deals as $deal) {
                                    $isDeleted = $deal->trashed();
                                    $containerClass = $isDeleted ? 'bg-danger-50 ring-danger-600/30 border-danger-200' : match ($deal->status) {
                                        'won' => 'bg-success-100/40 ring-success-600/30',
                                        'lost' => 'bg-danger-100/40 ring-danger-600/30',
                                        default => 'bg-warning-100/40 ring-warning-600/30',
                                    };
                                    $badgeClass = $isDeleted ? 'bg-white text-danger-600 ring-danger-600/30' : match ($deal->status) {
                                        'won' => 'bg-white text-success-600 ring-success-600/30',
                                        'lost' => 'bg-white text-danger-600 ring-danger-600/30',
                                        default => 'bg-white text-warning-600 ring-warning-600/30',
                                    };

                                    $val = 'IDR ' . number_format($deal->estimated_value, 0, ',', '.');
                                    $html .= "<div class='flex flex-col justify-between p-4 rounded-lg ring-1 ring-inset shadow-sm {$containerClass}'>
                                    <div class='flex items-start justify-between mb-2'>
                                        <div><span class='block text-sm font-bold'>{$deal->deal_number}</span><span class='text-xs opacity-75'>{$deal->created_at->format('d M Y')}</span></div>
                                        <span class='inline-flex items-center rounded-md px-2 py-1 text-xs ring-1 ring-inset shadow-sm capitalize {$badgeClass}'>" . ($isDeleted ? 'Terhapus' : $deal->status) . "</span>
                                    </div>
                                    <div class='flex items-end justify-between pt-3 mt-3 border-t border-black/10'>
                                        <div class='text-xs'><p class='opacity-70 text-[10px] uppercase'>Stage</p><p class='font-semibold'>" . ($deal->stage->name ?? '-') . "</p></div>
                                        <div class='text-xs text-right'><p class='opacity-70 text-[10px] uppercase'>Value</p><p class='font-semibold'>{$val}</p></div>
                                    </div>
                                </div>";
                                }
                                $html .= '</div>';
                                return new HtmlString($html);
                            }),
                    ])
                    ->visible(fn($record) => $record && $record->deals()->withTrashed()->exists()),
                Forms\Components\Section::make('Daftar Penawaran Terkait')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Placeholder::make('quotations_list')
                            ->hiddenLabel()
                            ->content(function ($record) {
                                if (!$record)
                                    return null;

                                $quotations = $record->quotations()->with('deal')->withTrashed()->latest()->get();
                                if ($quotations->isEmpty())
                                    return new HtmlString('<p class="text-sm italic text-gray-500">Belum ada penawaran.</p>');

                                $html = '<div class="grid w-full grid-cols-1 gap-4 card-repeater md:grid-cols-2">';
                                foreach ($quotations as $q) {
                                    $isDeleted = $q->trashed();
                                    $status = $isDeleted ? 'deleted' : $q->status;

                                    $containerClass = match ($status) {
                                        'accepted' => 'bg-success-100/40 ring-success-600/30',
                                        'rejected', 'deleted' => 'bg-danger-100/40 ring-danger-600/30',
                                        default => 'bg-warning-100/40 ring-warning-600/30',
                                    };
                                    $badgeClass = 'bg-white ring-1 ring-inset shadow-sm px-2 py-1 text-xs rounded-md capitalize';

                                    $total = 'IDR ' . number_format($q->grand_total, 0, ',', '.');
                                    $html .= "<div class='flex flex-col justify-between p-4 rounded-lg ring-1 ring-inset shadow-sm {$containerClass}'>
                                    <div class='flex items-start justify-between mb-2'>
                                        <div><span class='block text-sm font-bold'>{$q->quotation_number}</span><span class='text-xs opacity-75'>{$q->quotation_date->format('d M Y')}</span></div>
                                        <span class='{$badgeClass}'>" . ($isDeleted ? 'Dihapus' : $q->status) . "</span>
                                    </div>
                                    <div class='flex items-end justify-between pt-3 mt-3 border-t border-black/10'>
                                        <div class='text-xs'><p class='opacity-70 text-[10px] uppercase'>Deal Ref</p><p class='font-semibold'>" . ($q->deal?->deal_number ?? '-') . "</p></div>
                                        <div class='text-xs text-right'><p class='opacity-70 text-[10px] uppercase'>Grand Total</p><p class='font-semibold'>{$total}</p></div>
                                    </div>
                                </div>";
                                }
                                $html .= '</div>';
                                return new HtmlString($html);
                            }),
                    ])
                    ->visible(fn($record) => $record && $record->quotations()->withTrashed()->exists()),
            ])
            ->columns(1);  // DIUBAH: Layout utama menjadi 1 kolom untuk Section
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn(Customer $record) => $record->customer_type === 'company' ? 'PIC: ' . $record->pic_name : null),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->icon('heroicon-o-phone')
                    ->searchable(['phone', 'email'])
                    ->getStateUsing(fn(Customer $record) => $record->customer_type === 'individual' ? $record->phone : $record->pic_phone)
                    ->description(fn(Customer $record) => $record->email),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Tipe Customer')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'individual' => 'info',
                        'company' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'lost',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('npwp')
                    ->label('NPWP')
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
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Tipe Customer')
                    ->options([
                        'individual' => 'Perorangan (B2C)',
                        'company' => 'Perusahaan (B2B)',
                    ]),
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['created_until'], fn($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
