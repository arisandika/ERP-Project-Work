<?php

namespace App\Filament\Resources\Procurement\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts'; // Nama relasi di model Supplier

    protected static ?string $title = 'Kontak Person / Cabang';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Tipe Kontak')
                    ->options([
                        'contact' => 'Person (Sales/Finance)',
                        'delivery' => 'Alamat Pengiriman',
                        'invoice' => 'Alamat Penagihan',
                        'other' => 'Lainnya',
                    ])
                    ->default('contact')
                    ->required()
                    ->native(false),

                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('job_position')
                    ->label('Jabatan'),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->label('No. Telepon/WA')
                    ->tel()
                    ->maxLength(255),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan Internal')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'contact' => 'info',
                        'delivery' => 'success',
                        'invoice' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->description(fn ($record) => $record->job_position),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('phone'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
