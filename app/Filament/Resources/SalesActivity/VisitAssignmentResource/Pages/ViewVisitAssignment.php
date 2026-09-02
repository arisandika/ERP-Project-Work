<?php

namespace App\Filament\Resources\SalesActivity\VisitAssignmentResource\Pages;

use App\Filament\Resources\SalesActivity\VisitAssignmentResource;
use App\Infolists\Components\VisitMapEntry;
use App\Infolists\Components\VisitPhotosEntry;
use App\Infolists\Components\VisitTimelineEntry;
use App\Models\SalesActivity\VisitAssignment;
use App\Models\SalesActivity\VisitRecord;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Filament\Actions;

class ViewVisitAssignment extends ViewRecord
{
    protected static string $resource = VisitAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('mark_completed')
                ->label('Tandai Selesai')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => !in_array($this->record->status, ['completed', 'cancelled']))
                ->action(function () {
                    $this->record->update(['status' => VisitAssignment::STATUS_COMPLETED]);
                    \Filament\Notifications\Notification::make()->title('Tugas ditandai selesai.')->success()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── 1. HEADER INFO (DIKATEGORIKAN DENGAN GRID & SECTION) ──────────
                Grid::make(3)
                    ->schema([
                        // Kolom 1: Info Deal
                        Section::make('Informasi Deal')
                            ->schema([
                                TextEntry::make('deal.deal_number')
                                    ->label('No. Deal')
                                    ->weight(FontWeight::Bold)
                                    ->copyable()
                                    ->icon('heroicon-o-hashtag'),
                                TextEntry::make('deal.title')
                                    ->label('Judul Deal'),
                                TextEntry::make('client_name')
                                    ->label('Client')
                                    ->state(
                                        fn(VisitAssignment $record) =>
                                            $record->deal?->customer?->name ?? $record->deal?->lead?->name ?? '-'
                                    )
                                    ->icon('heroicon-o-building-office'),
                            ])
                            ->columnSpan(1),
                        // Kolom 2: Penugasan
                        Section::make('Target Kunjungan')
                            ->schema([
                                TextEntry::make('assignee_name')
                                    ->label('Ditugaskan Kepada')
                                    ->state(fn(VisitAssignment $record) => $record->assignedTo?->full_name ?? '-')
                                    ->icon('heroicon-o-user'),
                                TextEntry::make('purpose')
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
                                TextEntry::make('status')
                                    ->label('Status Kunjungan')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => VisitAssignment::statusOptions()[$state] ?? $state)
                                    ->color(fn($state) => match ($state) {
                                        'pending' => 'warning',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->columnSpan(1),
                        // Kolom 3: Jadwal
                        Section::make('Jadwal & Tenggat Waktu')
                            ->schema([
                                TextEntry::make('visit_date')
                                    ->label('Tanggal Rencana')
                                    ->date('d M Y')
                                    ->icon('heroicon-o-calendar'),
                                TextEntry::make('visit_time')
                                    ->label('Jam Rencana')
                                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('H:i') : '-')
                                    ->icon('heroicon-o-clock'),
                                TextEntry::make('deadline_date')
                                    ->label('Deadline')
                                    ->date('d M Y')
                                    ->color(fn(VisitAssignment $record) => $record->isOverdue() ? 'danger' : null)
                                    ->placeholder('-')
                                    ->icon('heroicon-o-exclamation-circle'),
                            ])
                            ->columnSpan(1),
                    ]),
                // Bagian Catatan (Full Width)
                Section::make('Briefing / Catatan Tugas')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')  // Label dikosongkan karena sudah diwakili judul section
                            ->placeholder('Tidak ada catatan briefing.')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(fn($record) => blank($record->notes)),  // Otomatis tutup jika tidak ada notes
                // ── 2. DATA PELAKSANAAN (MENGGUNAKAN TABS AGAR RAPI) ──────────────
                Tabs::make('Data Pelaksanaan')
                    ->columnSpanFull()
                    ->tabs([
                        // TAB 1: PETA
                        Tab::make('Peta Kunjungan')
                            ->icon('heroicon-o-map')
                            ->badge(fn($record) => $record->visitRecords()->whereNotNull('latitude')->count() ?: null)
                            ->schema([
                                VisitMapEntry::make('map')
                                    ->label('')
                                    ->columnSpanFull(),
                            ]),
                        // TAB 2: TIMELINE RIWAYAT KUNJUNGAN
                        Tab::make('Timeline Riwayat Kunjungan')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                VisitTimelineEntry::make('timeline_riwayat')  // Gunakan komponen yg baru kita buat
                                    ->label('')
                                    ->columnSpanFull(),
                            ]),
                        // TAB 3: DETAIL LENGKAP (REPEATABLE)
                        Tab::make('Detail Rekaman')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->badge(fn($record) => $record->visitRecords()->count() ?: null)
                            ->schema([
                                RepeatableEntry::make('visitRecords')
                                    ->label('')
                                    ->schema([
                                        // Header tiap record kunjungan
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('visit_order')
                                                    ->label('Kunjungan Ke')
                                                    ->formatStateUsing(fn($state) => "#{$state}")
                                                    ->weight(FontWeight::ExtraBold)
                                                    ->size(TextEntry\TextEntrySize::Large),
                                                TextEntry::make('visited_at')
                                                    ->label('Waktu Check-In')
                                                    ->dateTime('d M Y, H:i')
                                                    ->icon('heroicon-o-clock'),
                                                TextEntry::make('visit_result')
                                                    ->label('Hasil')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => VisitRecord::resultOptions()[$state] ?? $state)
                                                    ->color(fn($state) => VisitRecord::resultColors()[$state] ?? 'gray'),
                                            ]),
                                        // Detail Lokasi & Jarak
                                        Fieldset::make('Informasi Lokasi & Perjalanan')
                                            ->columns(['default' => 12, 'md' => 3])
                                            ->schema([
                                                TextEntry::make('location_address')
                                                    ->label('Titik Lokasi (Maps)')
                                                    ->placeholder('Lokasi tidak terdeteksi')
                                                    ->icon('heroicon-o-map-pin')
                                                    ->url(fn(VisitRecord $record) => $record->googleMapsUrl())
                                                    ->openUrlInNewTab()
                                                    ->columnSpan(1),
                                                TextEntry::make('distance_from_prev')
                                                    ->label('Jarak dari Sblmnya')
                                                    ->state(function (VisitRecord $record): string {
                                                        $prev = VisitRecord::where('nx_visit_assignment_id', $record->nx_visit_assignment_id)
                                                            ->where('visit_order', $record->visit_order - 1)
                                                            ->first();
                                                        return ($prev && $prev->hasCoordinates() && $record->hasCoordinates())
                                                            ? $record->distanceTo($prev->latitude, $prev->longitude) . ' km'
                                                            : '-';
                                                    }),
                                                TextEntry::make('duration_from_prev')
                                                    ->label('Durasi Perjalanan')
                                                    ->state(function (VisitRecord $record): string {
                                                        $minutes = $record->durationFromPrevious();
                                                        if (!$minutes)
                                                            return '-';
                                                        if ($minutes < 60)
                                                            return "{$minutes} menit";
                                                        $h = intdiv($minutes, 60);
                                                        $m = $minutes % 60;
                                                        return $m > 0 ? "{$h} jam {$m} menit" : "{$h} jam";
                                                    }),
                                            ]),
                                        // Laporan Eksekusi
                                        Fieldset::make('Laporan Kunjungan')
                                            ->columns(['default' => 12, 'md' => 2])
                                            ->schema([
                                                TextEntry::make('description')
                                                    ->label('Deskripsi / Notulensi')
                                                    ->placeholder('Tidak ada deskripsi.')
                                                    ->columnSpan(1),
                                                Group::make([
                                                    TextEntry::make('followup_notes')
                                                        ->label('Catatan Follow Up')
                                                        ->placeholder('-'),
                                                    TextEntry::make('next_followup_date')
                                                        ->label('Tanggal Follow Up Berikutnya')
                                                        ->date('d M Y')
                                                        ->placeholder('Tidak dijadwalkan')
                                                        ->icon('heroicon-o-calendar'),
                                                ])->columnSpan(1),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
