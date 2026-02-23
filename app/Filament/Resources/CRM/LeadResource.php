<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\LeadResource\Pages;
use App\Filament\Resources\CRM\LeadResource\RelationManagers;
use App\Filament\Resources\CRM\LeadResource\RelationManagers\QuotationsRelationManager;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                Forms\Components\Section::make('Informasi Calon Customer')
                    ->description('Lengkapi data untuk lead baru')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Calon Customer/Perusahaan')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user'),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-envelope'),

                                Forms\Components\TextInput::make('phone')
                                    ->label('No. HP (WhatsApp)')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-device-phone-mobile'),

                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat Domisili/Kantor')
                                    ->rows(3)
                                    ->maxLength(255),
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
                    ->label('Nama Calon Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->description(fn(Lead $record) => $record->email)
                    ->icon('heroicon-o-phone')
                    ->searchable(['phone', 'email']),

                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'info' => 'new',
                        'warning' => 'contacted',
                        'success' => ['qualified', 'converted'],
                        'danger' => 'lost',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('quotations_count')
                    ->label('Jumlah Penawaran')
                    ->counts('quotations')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn(?int $state): string => ($state ?? 0) . ' Penawaran')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Masuk Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            ])
            ->actions([
                Tables\Actions\Action::make('create_quotation')
                    ->label('Buat Penawaran')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn(Lead $record): string => QuotationResource::getUrl('create', ['nx_lead_id' => $record->id]))
                    ->openUrlInNewTab(),
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

    public static function getRelations(): array
    {
        return [
            QuotationsRelationManager::class,
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
}
