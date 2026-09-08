<?php

namespace App\Filament\Pages\SalesActivity;

use App\Filament\Concerns\BelongsToModule;
use App\Models\HR\Employee;
use App\Models\SalesActivity\VisitAssignment;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyVisitTasksPage extends Page implements HasTable
{
    use InteractsWithTable;

    use BelongsToModule;

    protected static ?string $module = 'sales';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Sales Activity';

    protected static ?string $navigationLabel = 'Tugas Kunjungan Saya';

    protected static ?string $title = 'Tugas Kunjungan Saya';

    protected static ?string $slug = 'sales/activity/my-visit-tasks';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.sales-activity.my-visit-tasks';

    /**
     * Hanya tampilkan di nav jika user punya employee record.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Employee::where('user_id', auth()->id())->exists();
    }

    // ── Badge jumlah tugas pending ──────────────────────────────────────
    public static function getNavigationBadge(): ?string
    {
        $employeeId = Employee::where('user_id', auth()->id())->value('id');
        if (!$employeeId)
            return null;

        $count = VisitAssignment::where('assigned_to_type', 'employee')
            ->where('assigned_to_id', $employeeId)
            ->where('status', VisitAssignment::STATUS_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ── Table ───────────────────────────────────────────────────────────
    public function table(Table $table): Table
    {
        $employeeId = Employee::where('user_id', auth()->id())->value('id');

        return $table
            ->query(
                VisitAssignment::query()
                    ->with(['deal.customer', 'deal.lead', 'deal.stage', 'visitRecords'])
                    ->where('assigned_to_type', 'employee')
                    ->where('assigned_to_id', $employeeId)
                    ->whereNotIn('status', [VisitAssignment::STATUS_CANCELLED])
                    ->latest('visit_date')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // ── Baris 1: Deal number + Status badge ──────────
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('deal.deal_number')
                            ->label('No. Deal')
                            ->weight('medium')
                            ->badge()
                            ->color('gray')
                            ->extraAttributes(['class' => 'font-mono text-white'])
                            ->searchable()
                            ->grow(false),

                        Tables\Columns\TextColumn::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn($state) => VisitAssignment::statusOptions()[$state] ?? $state)
                            ->color(fn($state) => match ($state) {
                                'pending' => 'warning',
                                'in_progress' => 'info',
                                'completed' => 'success',
                                default => 'gray',
                            })
                            ->alignEnd()
                    ])
                        ->extraAttributes(['class' => 'mb-2']),

                    // ── Baris 2: Nama client ─────────────────────────
                    Tables\Columns\TextColumn::make('client_name')
                        ->label('Client')
                        ->weight('semibold')
                        ->state(
                            fn(VisitAssignment $r) =>
                            $r->deal?->customer?->name ?? $r->deal?->lead?->name ?? '-'
                        ),

                    // ── Baris 3: Judul deal ──────────────────────────
                    Tables\Columns\TextColumn::make('deal.title')
                        ->label('Judul Deal')
                        ->extraAttributes(['class' => 'mb-2'])
                        ->limit(40),

                    // ── Baris 4: Tujuan + Tanggal ────────────────────
                    Tables\Columns\Layout\Split::make([
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
                            })
                            ->grow(false),

                        Tables\Columns\TextColumn::make('visit_date')
                            ->label('Tanggal')
                            ->date('d M Y')
                            ->icon('heroicon-o-calendar')
                            ->size('xs')
                            ->color(fn(VisitAssignment $r) => $r->isOverdue() ? 'danger' : 'warning')
                            ->alignEnd(),
                    ])
                        ->extraAttributes(['class' => 'mb-2']),

                    // ── Baris 5: Jumlah kunjungan sudah dilakukan ────
                    Tables\Columns\TextColumn::make('visit_records_count')
                        ->label('Sudah Dikunjungi')
                        ->counts('visitRecords')
                        ->size('xs')
                        ->formatStateUsing(fn($state) => $state . ' kunjungan tercatat'),
                ])->space(1),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(array_filter(
                        VisitAssignment::statusOptions(),
                        fn($key) => $key !== VisitAssignment::STATUS_CANCELLED,
                        ARRAY_FILTER_USE_KEY
                    ))
                    ->native(false),

                Tables\Filters\SelectFilter::make('purpose')
                    ->label('Tujuan')
                    ->options(VisitAssignment::purposeOptions())
                    ->native(false),

                Tables\Filters\Filter::make('is_overdue')
                    ->label('Overdue saja')
                    ->query(
                        fn(Builder $q) => $q
                            ->whereDate('deadline_date', '<', now())
                            ->whereNotIn('status', ['completed', 'cancelled'])
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-arrow-right')
                    ->iconPosition('after')
                    ->color('white')
                    ->url(fn(VisitAssignment $record) => MyVisitTaskDetailPage::getUrl([
                        'assignment' => $record->id,
                    ]))
                    ->extraAttributes(['class' => 'w-full mt-2 border-t pt-3 border-border-light dark:border-border-dark text-center']),
            ])
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading('Belum Ada Tugas Kunjungan')
            ->emptyStateDescription('Kamu belum memiliki tugas kunjungan yang aktif.')
            ->defaultSort('visit_date', 'asc')
            ->striped();
    }
}