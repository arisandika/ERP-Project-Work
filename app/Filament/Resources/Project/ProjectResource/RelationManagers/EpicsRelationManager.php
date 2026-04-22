<?php

namespace App\Filament\Resources\Project\ProjectResource\RelationManagers;

use App\Models\Project\Epic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class EpicsRelationManager extends RelationManager
{
    protected static string $relationship = 'epics';

    protected static ?string $title = 'Epic';

    protected static ?string $modelLabel = 'Epic';

    protected static ?string $pluralModelLabel = 'Epic';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->epics_count ?? $ownerRecord->epics()->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Epic')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Epic')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->required()
                            ->default(function ($livewire) {
                                $lastOrder = $livewire->getOwnerRecord()
                                    ->epics()
                                    ->max('sort_order');

                                return $lastOrder ? $lastOrder + 1 : 1;
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $livewire, ?Model $record) {
                                if (blank($state))
                                    return;

                                $project = $livewire->getOwnerRecord();

                                $isTaken = function ($val) use ($project, $record) {
                                    return $project->epics()
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
                                        ->body("Urutan {$originalState} sudah dipakai Epic lain. Otomatis diganti ke {$state}.")
                                        ->info()
                                        ->duration(3000)
                                        ->send();
                                }
                            })
                            ->helperText('Otomatis menyesuaikan jika nomor sudah terpakai'),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default(now())
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\RichEditor::make('description')
                            ->label('Deskripsi Epic')
                            ->columnSpanFull()
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
                            ->fileAttachmentsDirectory('attachments/epic-descriptions')
                            ->fileAttachmentsVisibility('public'),

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn() => auth()->user()->employee?->id),
                    ])
                    ->columns(2)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Epic')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Epic')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->placeholder('—'),

                // Tables\Columns\TextColumn::make('sort_order')
                //     ->label('Urutan')
                //     ->alignCenter()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('tickets_count')
                    ->label('Ticket')
                    ->counts('tickets')
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state . ' Ticket')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('creator.full_name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

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
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Lihat Epic'),
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
                    ->label('Tambah Epic'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Epic')
                    ->description('Detail durasi dan urutan pengerjaan Epic.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Epic')
                            ->weight('semibold')
                            ->size('lg')
                            ->placeholder('—'),

                        TextEntry::make('sort_order')
                            ->label('Urutan Tampil')
                            ->formatStateUsing(fn($state): string => 'Urutan ke-' . $state)
                            ->placeholder('—'),

                        TextEntry::make('creator.full_name')
                            ->label('Dibuat Oleh')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->date('d M Y')
                            ->placeholder('—'),

                        TextEntry::make('end_date')
                            ->label('Tanggal Selesai')
                            ->date('d M Y')
                            ->placeholder('—')
                            ->color(fn($state) => $state < now() ? 'danger' : 'success'),

                        TextEntry::make('duration')
                            ->label('Durasi Pengerjaan')
                            ->getStateUsing(function ($record) {
                                if (!$record->start_date || !$record->end_date)
                                    return '-';
                                return $record->start_date->diffInDays($record->end_date) . ' Hari';
                            }),
                    ]),

                Section::make('Statistik Ticket')
                    ->description('Ringkasan jumlah ticket yang terhubung dengan Epic ini.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('tickets_count')
                            ->label('Total Ticket')
                            ->getStateUsing(fn($record) => $record->tickets()->count())
                            ->formatStateUsing(fn($state) => $state . ' Ticket'),

                        TextEntry::make('last_ticket_update')
                            ->label('Aktivitas Terakhir')
                            ->getStateUsing(fn($record) => $record->tickets()->latest('updated_at')->first()?->updated_at)
                            ->since()
                            ->placeholder('Belum ada aktivitas'),
                    ]),

                Section::make('Deskripsi Epic')
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->placeholder('Tidak ada deskripsi'),
                    ])
                    ->collapsible(),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),
                    ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
