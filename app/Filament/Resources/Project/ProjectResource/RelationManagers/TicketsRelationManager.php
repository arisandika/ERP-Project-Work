<?php

namespace App\Filament\Resources\Project\ProjectResource\RelationManagers;

use App\Filament\Resources\Project\TicketResource;
use App\Models\Project\Epic;
use App\Models\Project\Ticket;
use App\Models\Project\TicketPriority;
use App\Models\Project\TicketStatus;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'Ticket';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?string $pluralModelLabel = 'Ticket';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->tickets_count ?? $ownerRecord->tickets()->count();
    }

    public function form(Form $form): Form
    {
        $projectId = $this->getOwnerRecord()->id;

        $defaultStatus = TicketStatus::where('project_id', $projectId)->first();
        $defaultStatusId = $defaultStatus ? $defaultStatus->id : null;

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Ticket')
                    ->description('Detail project, epic, status dan informasi ticket.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Ticket')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('epic_id')
                            ->label('Nama Epic')
                            ->options(function () use ($projectId) {
                                return Epic::where('project_id', $projectId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->native(false)
                            ->nullable(),
                        Forms\Components\Select::make('ticket_status_id')
                            ->label('Status Pengerjaan')
                            ->options(function () use ($projectId) {
                                return TicketStatus::where('project_id', $projectId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default($defaultStatusId)
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('priority_id')
                            ->label('Prioritas Ticket')
                            ->options(TicketPriority::pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
                Forms\Components\Section::make('Jadwal Pengerjaan')
                    ->description('Timeline target penyelesaian ticket.')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default(now())
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
                Forms\Components\Section::make('Member Ticket')
                    ->schema([
                        Forms\Components\Select::make('assignees')
                            ->label('Ditugaskan Kepada')
                            ->multiple()
                            ->relationship(
                                name: 'assignees',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: function ($query) {
                                    $projectId = $this->getOwnerRecord()->id;
                                    return $query->whereHas('projects', function ($query) use ($projectId) {
                                        $query->where('nx_projects.id', $projectId);
                                    });
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->default(function ($record) {
                                if ($record && $record->exists) {
                                    return $record->assignees->pluck('id')->toArray();
                                }

                                $project = $this->getOwnerRecord();
                                $isCurrentUserMember = $project
                                    ->members()
                                    ->where('nx_employees.id', auth()->user()->employee->id)
                                    ->exists();

                                return $isCurrentUserMember ? [auth()->user()->employee->id] : [];
                            })
                            ->helperText('Pilih beberapa member untuk ditugaskan Ticket ini. Hanya anggota project yang dapat ditugaskan.'),
                        Forms\Components\Select::make('created_by')
                            ->label('Dibuat Oleh')
                            ->relationship('creator', 'full_name')
                            ->disabled()
                            ->hiddenOn('create'),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
                Forms\Components\Section::make('Deskripsi Ticket')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Deskripsi Ticket')
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
                            ->fileAttachmentsDirectory('attachments/ticket-description')
                            ->fileAttachmentsVisibility('public'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Ticket')
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Ticket ID')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->placeholder('—')
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Ticket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('epic.name')
                    ->label('Nama Epic')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => match ($record->status?->name) {
                        'To Do' => 'warning',
                        'In Progress' => 'info',
                        'Review' => 'primary',
                        'Done' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('priority.name')
                    ->label('Prioritas Ticket')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        'Low' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->default('—')
                    ->placeholder('No Priority'),
                Tables\Columns\TextColumn::make('assignees.full_name')
                    ->label('Ditugaskan')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tanggal Selesai')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('Sisa Hari')
                    ->getStateUsing(function (Ticket $record): ?string {
                        if (!$record->due_date) {
                            return '—';
                        }

                        if ($record->status?->name === 'Done') {
                            return 'Selesai';
                        }

                        if ($record->remaining_days < 0) {
                            return 'Terlambat';
                        }

                        return $record->remaining_days . ' Hari';
                    })
                    ->color(function (Ticket $record): string {
                        if (!$record->due_date) {
                            return 'gray';
                        }

                        if ($record->status?->name === 'Done') {
                            return 'success';
                        }

                        if ($record->remaining_days < 0) {
                            return 'danger';
                        }

                        if ($record->remaining_days <= 7) {
                            return 'warning';
                        }

                        return 'success';
                    }),
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
                Tables\Filters\SelectFilter::make('ticket_status_id')
                    ->label('Status')
                    ->options(function () {
                        $projectId = $this->getOwnerRecord()->id;

                        return TicketStatus::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('epic_id')
                    ->label('Nama Epic')
                    ->options(function () {
                        $projectId = $this->getOwnerRecord()->id;
                        return Epic::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('assignees')
                    ->label('Ditugaskan')
                    ->relationship('assignees', 'full_name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->relationship('creator', 'full_name')
                    ->searchable()
                    ->preload(),
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
                    ->modalHeading('Lihat Ticket')
                    ->url(fn($record) => TicketResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('ticket_status_id')
                                ->label('Status')
                                ->options(function (RelationManager $livewire) {
                                    $projectId = $livewire->getOwnerRecord()->id;

                                    return TicketStatus::where('project_id', $projectId)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'ticket_status_id' => $data['ticket_status_id'],
                                ]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Status updated')
                                ->body(count($records) . ' tickets have been updated.')
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('assignUsers')
                        ->label('Tugaskan Member')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Select::make('assignees')
                                ->label('Member')
                                ->multiple()
                                ->options(function (RelationManager $livewire) {
                                    return $livewire
                                        ->getOwnerRecord()
                                        ->members()
                                        ->pluck('full_name', 'nx_employees.id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(),
                            Radio::make('assignment_mode')
                                ->label('Mode Penugasan')
                                ->options([
                                    'replace' => 'Akan mengganti semua member',
                                    'add' => 'Tambahkan member baru',
                                ])
                                ->default('add')
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            foreach ($records as $record) {
                                if ($data['assignment_mode'] === 'replace') {
                                    $record->assignees()->sync($data['assignees']);
                                } else {
                                    $record->assignees()->syncWithoutDetaching($data['assignees']);
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Users assigned')
                                ->body(count($records) . ' ticket berhasil diperbarui dengan member ditugaskan')
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('updatePriority')
                        ->label('Update Prioritas')
                        ->icon('heroicon-o-flag')
                        ->form([
                            Select::make('priority_id')
                                ->label('Prioritas Ticket')
                                ->options(TicketPriority::pluck('name', 'id')->toArray())
                                ->nullable(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'priority_id' => $data['priority_id'],
                                ]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('assignToEpic')
                        ->label('Tandai ke Epic')
                        ->icon('heroicon-o-bookmark')
                        ->form([
                            Select::make('epic_id')
                                ->label('Nama Epic')
                                ->options(function (RelationManager $livewire) {
                                    $projectId = $livewire->getOwnerRecord()->id;
                                    return Epic::where('project_id', $projectId)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->helperText('Pilih Epic yang akan dikaitkan dengan ticket, biarkan kosong jika ingin menghapus dari Epic saat ini'),
                        ])
                        ->action(function (array $data, Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'epic_id' => $data['epic_id'],
                                ]);
                            }

                            $epicName = $data['epic_id']
                                ? Epic::find($data['epic_id'])->name
                                : 'No Epic';

                            Notification::make()
                                ->success()
                                ->title('Epic assignment updated')
                                ->body(count($records) . ' ticket berhasil ditugaskan untuk: ' . $epicName)
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Ticket'),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
