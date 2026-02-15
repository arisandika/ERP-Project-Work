<?php

namespace App\Filament\Resources\Project\ProjectResource\RelationManagers;

use App\Filament\Resources\HR\EmployeeResource;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->members_count ?? $ownerRecord->members()->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->heading('Member yang Ditugaskan')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Tambah Member')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns([
                        'full_name',
                        'email',
                    ])
                    ->recordTitle(
                        fn(Model $record) =>
                        "{$record->full_name} ({$record->email})"
                    )
                    ->attachAnother(false)
                    ->modalHeading('Tambah Member ke Project')
                    ->modalSubmitActionLabel('Tambahkan'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Hapus')
                    ->modalHeading('Keluarkan Member')
                    ->modalDescription('Member akan dikeluarkan dari project ini.')
                    ->modalSubmitActionLabel('Ya, Keluarkan')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Keluarkan Member Terpilih'),
                ]),
            ]);
    }
}
