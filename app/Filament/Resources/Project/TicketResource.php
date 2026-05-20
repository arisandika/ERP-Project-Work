<?php

namespace App\Filament\Resources\Project;

use App\Filament\Resources\Project\TicketResource\Pages;
use App\Models\HR\Employee;
use App\Models\Project\Epic;
use App\Models\Project\Project;
use App\Models\Project\Ticket;
use App\Models\Project\TicketPriority;
use App\Models\Project\TicketStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use App\Filament\Concerns\BelongsToModule;

class TicketResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'project';
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Manajemen Project';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'pm/tickets';

    protected static ?string $pluralModelLabel = 'Ticket';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        $projectId = request()->query('project_id') ?? request()->input('project_id');
        $statusId = request()->query('ticket_status_id') ?? request()->input('ticket_status_id');

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Ticket')
                    ->description('Detail project, epic, status dan informasi ticket.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Ticket')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('project_id')
                            ->label('Nama Project')
                            ->options(function () {
                                if (auth()->user()->hasRole(['super_admin'])) {
                                    return Project::pluck('name', 'id')->toArray();
                                }

                                return auth()->user()
                                    ->employee
                                    ->projects()
                                    ->pluck('name', 'nx_projects.id')
                                    ->toArray();
                            })
                            ->default(fn() => request()->query('project_id'))
                            ->afterStateHydrated(function ($state, callable $set) {
                                if (!$state && request()->query('project_id')) {
                                    $set('project_id', request()->query('project_id'));
                                }
                            })
                            ->disabled(fn() => request()->has('project_id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('ticket_status_id', null);
                                $set('assignees', []);
                                $set('epic_id', null);
                            }),

                        Forms\Components\Select::make('epic_id')
                            ->label('Nama Epic')
                            ->options(function (Forms\Get $get) {
                                $projectId = $get('project_id');

                                if (!$projectId) {
                                    return [];
                                }

                                return Epic::where('project_id', $projectId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->hidden(fn(Forms\Get $get): bool => !$get('project_id')),

                        Forms\Components\Select::make('ticket_status_id')
                            ->label('Status Pengerjaan')
                            ->options(function (Forms\Get $get) {
                                $projectId = $get('project_id');

                                if (!$projectId) {
                                    return [];
                                }

                                return TicketStatus::where('project_id', $projectId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default($statusId)
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('priority_id')
                            ->label('Prioritas Ticket')
                            ->options(TicketPriority::pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

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
                    ->columns(2),

                Forms\Components\Section::make('Member Ticket')
                    ->schema([
                        Forms\Components\Select::make('assignees')
                            ->label('Ditugaskan Kepada')
                            ->multiple()
                            ->relationship(
                                name: 'assignees',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                    $projectId = $get('project_id');

                                    if (!$projectId) {
                                        return $query->whereRaw('1 = 0');
                                    }

                                    return $query->whereHas('projects', function (Builder $query) use ($projectId) {
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

                                return [];
                            })
                            ->helperText(
                                'Pilih beberapa member untuk ditugaskan Ticket ini. Hanya anggota project yang dapat ditugaskan.'
                            ),

                        Forms\Components\Select::make('created_by')
                            ->label('Dibuat Oleh')
                            ->relationship('creator', 'full_name')
                            ->disabled()
                            ->hidden(fn($record) => $record === null),
                    ])
                    ->columns(2),

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
                            ->fileAttachmentsDirectory('attachments/ticket-descriptions')
                            ->fileAttachmentsVisibility('public'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(function () {
                        if (auth()->user()->hasRole(['super_admin'])) {
                            return Project::pluck('name', 'id')->toArray();
                        }

                        return auth()->user()->employee->projects()->pluck('name', 'nx_projects.id')->toArray();
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('ticket_status_id')
                    ->label('Status')
                    ->options(function (\Livewire\Component $livewire) {
                        $projectId = data_get($livewire, 'tableFilters.project_id.value');

                        if (!$projectId) {
                            return TicketStatus::pluck('name', 'id')->toArray();
                        }

                        return TicketStatus::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('epic_id')
                    ->label('Nama Epic')
                    ->options(function (\Livewire\Component $livewire) {
                        $projectId = data_get($livewire, 'tableFilters.project_id.value');

                        if (!$projectId) {
                            return Epic::pluck('name', 'id')->toArray();
                        }

                        return Epic::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

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
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
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
                Tables\Actions\ViewAction::make(),
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
                            Forms\Components\Select::make('ticket_status_id')
                                ->label('Status')
                                ->options(function (\Livewire\Component $livewire) {
                                    if (method_exists($livewire, 'getOwnerRecord')) {
                                        $projectId = $livewire->getOwnerRecord()->id;
                                        return TicketStatus::where('project_id', $projectId)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    }

                                    return TicketStatus::pluck('name', 'id')->toArray();
                                })
                                ->searchable()
                                ->preload()
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
                            Forms\Components\Select::make('assignees')
                                ->label('Member')
                                ->multiple()
                                ->options(function (\Livewire\Component $livewire) {
                                    if (method_exists($livewire, 'getOwnerRecord')) {
                                        return $livewire->getOwnerRecord()
                                            ->members()
                                            ->pluck('full_name', 'nx_employees.id')
                                            ->toArray();
                                    }

                                    return Employee::pluck('full_name', 'id')->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\Radio::make('assignment_mode')
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
                            Forms\Components\Select::make('priority_id')
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
                            Forms\Components\Select::make('epic_id')
                                ->label('Nama Epic')
                                ->options(function (\Livewire\Component $livewire) {
                                    if (method_exists($livewire, 'getOwnerRecord')) {
                                        $projectId = $livewire->getOwnerRecord()->id;
                                        return Epic::where('project_id', $projectId)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    }

                                    return Epic::pluck('name', 'id')->toArray();
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
