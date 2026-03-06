<?php

namespace App\Filament\Resources\Project\ProjectResource\RelationManagers;

use App\Filament\Resources\HR\EmployeeResource;
use App\Models\HR\Employee;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Member Project';

    protected static ?string $modelLabel = 'Member Project';

    protected static ?string $pluralModelLabel = 'Member Project';

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
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color(function (Employee $record) {
                        $record->withTrashed()->first();
                        if ($record && $record->trashed())
                            return 'danger';
                        return '';
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Kontak')
                    ->description(fn(Employee $record) => $record->email)
                    ->searchable(['phone', 'user.email'])
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->searchable(['phone_number', 'user.email'])
                    ->color(function (Employee $record) {
                        $record->withTrashed()->first();
                        return ($record && $record->trashed()) ? 'danger' : 'success';
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->native(false),
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
                    ->modalSubmitActionLabel('Tambahkan')
                    ->color('primary'),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
