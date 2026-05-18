<?php

namespace App\Filament\Resources\SalesActivity;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\SalesActivity\VisitAssignmentResource\Pages;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use App\Models\HR\Employee;
use App\Models\Sales\SalesPerson;
use App\Models\SalesActivity\VisitAssignment;
use App\Models\SalesActivity\VisitRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class VisitAssignmentResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'sales';

    protected static ?string $model = VisitAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'Sales Activity';

    protected static ?string $navigationLabel = 'Monitoring Tugas Kunjungan';

    protected static ?string $pluralModelLabel = 'Monitoring Tugas Kunjungan';

    protected static ?string $modelLabel = 'Monitoring Tugas Kunjungan';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'sales-activity/visit-assignments';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', VisitAssignment::STATUS_PENDING)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    //----------------------------------------------------------------------
    // Form
    //----------------------------------------------------------------------

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Deal')
                    ->description('Pilih deal yang akan dikunjungi.')
                    ->schema([
                        Forms\Components\Select::make('nx_deal_id')
                            ->label('Deal')
                            ->options(function () {
                                return Deal::with(['customer', 'lead', 'stage'])
                                    ->whereIn('status', ['open', 'on_hold'])
                                    ->get()
                                    ->mapWithKeys(function ($deal) {
                                        $client = $deal->customer?->name ?? $deal->lead?->name ?? 'Unknown';
                                        $stage = $deal->stage?->name ?? '-';
                                        return [$deal->id => "[{$deal->deal_number}] {$deal->title} — {$client} ({$stage})"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->prefixIcon('heroicon-o-briefcase')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Reset assignee ketika deal diganti
                                $set('assigned_to_id', null);
                                $set('assigned_to_type', null);
                            })
                            ->helperText('Hanya deal berstatus Open atau On Hold yang ditampilkan.'),
                    ]),

                Forms\Components\Section::make('Penugasan')
                    ->description('Tentukan siapa yang akan melakukan kunjungan.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('assigned_to_type')
                                    ->label('Tipe Assignee')
                                    ->options([
                                        'employee' => 'Pegawai Internal',
                                        'salesperson' => 'Sales Person',
                                    ])
                                    ->required()
                                    ->live()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-users')
                                    ->afterStateUpdated(fn(Forms\Set $set) => $set('assigned_to_id', null)),

                                Forms\Components\Select::make('assigned_to_id')
                                    ->label('Ditugaskan Kepada')
                                    ->options(function (Forms\Get $get) {
                                        $type = $get('assigned_to_type');
                                        if ($type === 'employee') {
                                            return Employee::where('status', 'active')
                                                ->orderBy('full_name')
                                                ->pluck('full_name', 'id');
                                        }
                                        if ($type === 'salesperson') {
                                            return SalesPerson::where('status', 'active')
                                                ->orderBy('full_name')
                                                ->pluck('full_name', 'id');
                                        }
                                        return [];
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->prefixIcon('heroicon-o-user')
                                    ->disabled(fn(Forms\Get $get) => blank($get('assigned_to_type'))),

                                Forms\Components\Select::make('assigned_by')
                                    ->label('Ditugaskan Oleh')
                                    ->relationship('assignedBy', 'full_name')
                                    ->default(fn() => Employee::where('user_id', auth()->id())->value('id'))
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-user-circle'),
                            ]),
                    ]),

                Forms\Components\Section::make('Detail Kunjungan')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('purpose')
                                    ->label('Tujuan Kunjungan')
                                    ->options(VisitAssignment::purposeOptions())
                                    ->required()
                                    ->native(false)
                                    ->default(VisitAssignment::PURPOSE_FOLLOW_UP)
                                    ->prefixIcon('heroicon-o-flag'),

                                Forms\Components\Select::make('status')
                                    ->label('Status Tugas')
                                    ->options(VisitAssignment::statusOptions())
                                    ->required()
                                    ->native(false)
                                    ->default(VisitAssignment::STATUS_PENDING)
                                    ->prefixIcon('heroicon-o-check-circle'),

                                Forms\Components\DatePicker::make('visit_date')
                                    ->label('Tanggal Rencana Kunjungan')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->prefixIcon('heroicon-o-calendar'),

                                Forms\Components\TimePicker::make('visit_time')
                                    ->label('Jam Rencana Kunjungan')
                                    ->nullable()
                                    ->prefixIcon('heroicon-o-clock'),

                                Forms\Components\DatePicker::make('deadline_date')
                                    ->label('Batas Waktu Tugas')
                                    ->nullable()
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->prefixIcon('heroicon-o-exclamation-circle')
                                    ->helperText('Opsional. Jika diisi, tugas yang melewati tanggal ini akan ditandai overdue.'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan / Briefing untuk Pegawai')
                            ->placeholder('Tuliskan instruksi, poin yang harus disampaikan, atau hal yang perlu diperhatikan...')
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    //----------------------------------------------------------------------
    // Table
    //----------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('deal.deal_number')
                    ->label('No. Deal')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('deal.title')
                    ->label('Deal')
                    ->searchable()
                    ->limit(25)
                    ->description(fn(VisitAssignment $record) => $record->deal?->customer?->name
                        ?? $record->deal?->lead?->name
                        ?? '-'),

                Tables\Columns\TextColumn::make('assignee_name')
                    ->label('Ditugaskan Kepada')
                    ->state(function (VisitAssignment $record): string {
                        $assignee = $record->assignedTo;
                        if (!$assignee)
                            return '-';
                        return $assignee->full_name ?? '-';
                    })
                    ->description(function (VisitAssignment $record): string {
                        return match ($record->assigned_to_type) {
                            'employee' => 'Pegawai Internal',
                            'salesperson' => 'Sales Person',
                            default => '-',
                        };
                    })
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('purpose')
                    ->label('Tujuan')
                    ->badge()
                    ->formatStateUsing(fn($state) => VisitAssignment::purposeOptions()[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        'presentation' => 'info',
                        'follow_up' => 'warning',
                        'survey' => 'gray',
                        'negotiation' => 'purple',
                        'closing' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->date('d M Y')
                    ->sortable()
                    ->description(fn(VisitAssignment $record) => $record->visit_time
                        ? 'Jam ' . \Carbon\Carbon::parse($record->visit_time)->format('H:i')
                        : null),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => VisitAssignment::statusOptions()[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('visit_records_count')
                    ->label('Kunjungan')
                    ->badge()
                    ->counts('visitRecords')
                    ->color('info')
                    ->formatStateUsing(fn($state) => $state . 'x'),

                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->state(fn(VisitAssignment $record) => $record->isOverdue())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-check-circle')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('deadline_date')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn(VisitAssignment $record) => $record->isOverdue() ? 'danger' : null)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('assignedBy.full_name')
                    ->label('Ditugaskan Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(VisitAssignment::statusOptions())
                    ->native(false),

                Tables\Filters\SelectFilter::make('purpose')
                    ->label('Tujuan')
                    ->options(VisitAssignment::purposeOptions())
                    ->native(false),

                Tables\Filters\SelectFilter::make('assigned_to_type')
                    ->label('Tipe Assignee')
                    ->options([
                        'employee' => 'Pegawai Internal',
                        'salesperson' => 'Sales Person',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('is_overdue')
                    ->label('Hanya Overdue')
                    ->query(
                        fn(Builder $query) => $query
                            ->whereDate('deadline_date', '<', now())
                            ->whereNotIn('status', ['completed', 'cancelled'])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('visit_from')
                            ->label('Kunjungan Dari')
                            ->native(false)
                            ->displayFormat('d M Y'),
                        Forms\Components\DatePicker::make('visit_until')
                            ->label('Kunjungan Hingga')
                            ->native(false)
                            ->displayFormat('d M Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['visit_from'], fn($q, $d) => $q->whereDate('visit_date', '>=', $d))
                            ->when($data['visit_until'], fn($q, $d) => $q->whereDate('visit_date', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('mark_completed')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Tugas Selesai?')
                    ->modalDescription('Pastikan semua kunjungan sudah direkam sebelum menandai tugas ini selesai.')
                    ->visible(fn(VisitAssignment $r) => !in_array($r->status, ['completed', 'cancelled']))
                    ->action(function (VisitAssignment $record) {
                        $record->update(['status' => VisitAssignment::STATUS_COMPLETED]);
                        Notification::make()->title('Tugas ditandai selesai.')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_cancelled')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(VisitAssignment $r) => !in_array($r->status, ['completed', 'cancelled']))
                    ->action(function (VisitAssignment $record) {
                        $record->update(['status' => VisitAssignment::STATUS_CANCELLED]);
                        Notification::make()->title('Tugas dibatalkan.')->warning()->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('visit_date', 'asc');
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
            'index' => Pages\ListVisitAssignments::route('/'),
            'create' => Pages\CreateVisitAssignment::route('/create'),
            'view' => Pages\ViewVisitAssignment::route('/{record}'),
            'edit' => Pages\EditVisitAssignment::route('/{record}/edit'),
        ];
    }
}