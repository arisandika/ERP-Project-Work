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
                                    ->columnSpanFull(), // Disesuaikan tipe Text (hapus maxLength)
                            ]),
                    ]),

                Forms\Components\Section::make('Status & Klasifikasi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status Lead')
                                    ->options([
                                        'new' => 'New (Baru)',
                                        'contacted' => 'Contacted (Dihubungi)',
                                        'qualified' => 'Qualified (Potensial)',
                                        'converted' => 'Converted (Jadi Deal)',
                                        'lost' => 'Lost (Gagal)',
                                    ])
                                    ->default('new')
                                    ->required()
                                    ->live()
                                    ->native(false),

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

                                // Field ini muncul jika statusnya 'converted'
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
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lead')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->description(fn(Lead $record) => $record->email)
                    ->searchable(['phone', 'email'])
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->searchable(['lead.phone', 'lead.email'])
                    ->color('success'),

                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'company' => 'primary',
                        'individual' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'warning',
                        'contacted' => 'warning',
                        'qualified', 'converted' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('deals_count')
                    ->label('Jumlah Deal')
                    ->counts('deals')
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state . ' Deal'),

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
