<?php

namespace App\Filament\Resources\Project\ProjectResource\RelationManagers;

use App\Models\Project\TicketStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketStatusesRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketStatuses';

    protected static ?string $title = 'Status Ticket';

    protected static ?string $modelLabel = 'Status Ticket';

    protected static ?string $pluralModelLabel = 'Status Ticket';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->ticket_statuses_count ?? $ownerRecord->ticketStatuses()->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Status Ticket')
                    ->required()
                    ->maxLength(255),

                Forms\Components\ColorPicker::make('color')
                    ->label('Warna')
                    ->required()
                    ->default('#3490dc')
                    ->helperText('Pilih warna untuk status ini'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(1)
                    ->helperText('Tentukan urutan tampilan di project board (nilai yang lebih rendah ditampilkan terlebih dahulu)'),

                Forms\Components\Toggle::make('is_completed')
                    ->label('Tandai sebagai completed')
                    ->helperText('Hanya satu status per project yang dapat ditandai sebagai completed')
                    ->default(false)
                    ->reactive()
                    ->afterStateUpdated(function ($state, $get, $set, $record) {
                        if ($state) {
                            // Check if another status in this project is already marked as completed
                            $projectId = $this->getOwnerRecord()->id;
                            $existingCompleted = TicketStatus::where('project_id', $projectId)
                                ->where('is_completed', true)
                                ->when($record, fn($query) => $query->where('id', '!=', $record->id))
                                ->first();

                            if ($existingCompleted) {
                                $set('is_completed', false);
                                Notification::make()
                                    ->warning()
                                    ->title('Tidak dapat menandai sebagai completed')
                                    ->body("Status '{$existingCompleted->name}' sudah ditandai sebagai selesai untuk project ini. Hanya satu status yang dapat ditandai sebagai selesai")
                                    ->send();
                            }
                        }
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Status Ticket')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Status Ticket'),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Warna'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_completed')
                    ->label('Completed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

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
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Status'),
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
