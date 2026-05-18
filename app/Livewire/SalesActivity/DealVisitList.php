<?php

namespace App\Livewire\SalesActivity;

use App\Filament\Resources\SalesActivity\VisitAssignmentResource;
use App\Models\SalesActivity\VisitAssignment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class DealVisitList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $dealId; // Menangkap ID Deal dari parent

    public function table(Table $table): Table
    {
        return $table
            // 1. Query hanya untuk Deal yang sedang diklik
            ->query(VisitAssignment::where('nx_deal_id', $this->dealId)->latest())
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
                        DatePicker::make('visit_from')
                            ->label('Kunjungan Dari')
                            ->native(false)
                            ->displayFormat('d M Y'),
                        DatePicker::make('visit_until')
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
            // 2. Tombol Create (Muncul di atas tabel, di dalam modal)
            ->headerActions([
                Tables\Actions\CreateAction::make('create_visit')
                    ->label('Tambah Kunjungan')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('6xl')
                    // ->slideOver() // Lebih rapi menggunakan slide-over agar tidak modal numpuk modal
                    ->form(fn($form) => VisitAssignmentResource::form($form)->getComponents())
                    ->mutateFormDataUsing(function (array $data) {
                        // Paksa ID Deal sesuai dengan context saat ini
                        $data['nx_deal_id'] = $this->dealId;
                        return $data;
                    }),
            ])
            // 3. Tombol Edit & Delete (Di setiap baris tabel)
            ->actions([
                Tables\Actions\EditAction::make('edit_visit')
                    // ->slideOver()
                    ->modalWidth('6xl')
                    ->form(fn($form) => VisitAssignmentResource::form($form)->getComponents()),

                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public function render()
    {
        return view('livewire.sales-activity.deal-visit-list');
    }
}