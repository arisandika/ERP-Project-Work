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
use Illuminate\Support\Carbon;

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
            ->schema([
                Forms\Components\Section::make('Informasi Status Ticket')
                    ->schema([
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
                            ->required()
                            ->default(function ($livewire) {

                                $lastOrder = $livewire->getOwnerRecord()
                                    ->ticketStatuses()
                                    ->max('sort_order');

                                return $lastOrder ? $lastOrder + 1 : 1;
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $livewire, ?Model $record) {
                                if (blank($state))
                                    return;

                                $projectId = $livewire->getOwnerRecord()->id;

                                $isTaken = function ($val) use ($projectId, $record) {
                                    return TicketStatus::where('nx_project_id', $projectId)
                                        ->where('sort_order', $val)
                                        ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                        ->exists();
                                };

                                if ($isTaken($state)) {
                                    $originalState = $state;

                                    while ($isTaken($state)) {
                                        $state++;
                                    }

                                    $set('sort_order', $state);

                                    Notification::make()
                                        ->title('Urutan Disesuaikan')
                                        ->body("Urutan {$originalState} sudah digunakan. Otomatis dialihkan ke urutan tersedia berikutnya ({$state}).")
                                        ->info()
                                        ->duration(3000)
                                        ->send();
                                }
                            })
                            ->helperText('Tentukan urutan tampilan (otomatis menyesuaikan jika nomor sudah terpakai).'),

                        Forms\Components\Toggle::make('is_completed')
                            ->label('Tandai sebagai completed')
                            ->helperText('Hanya satu status per project yang dapat ditandai sebagai completed')
                            ->default(false)
                            ->inline(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $get, $set, $record) {
                                if ($state) {
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
                    ])
                    ->columns(['default' => 1, 'md' => 2])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Status Ticket')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('semibold')
                    ->placeholder('—'),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Warna'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->alignCenter()
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
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('created_until')
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Status'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
