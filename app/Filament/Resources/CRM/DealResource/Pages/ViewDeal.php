<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use App\Filament\Resources\CRM\LeadResource;
use App\Models\CRM\Deal;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewDeal extends ViewRecord
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('Kembali')
                ->url(static::getResource()::getUrl())
                ->button()
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Deal';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── 1. TOP HEADER GRID (Info Cepat) ──────────────────────────────
                Grid::make(3)
                    ->schema([
                        // Card 1: Status Utama
                        Section::make('Status & Progress')
                            ->icon('heroicon-o-arrow-path')
                            ->schema([
                                TextEntry::make('deal_number')
                                    ->label('Nomor Deal')
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-hashtag')
                                    ->copyable(),

                                TextEntry::make('stage.name')
                                    ->label('Stage Saat Ini')
                                    ->badge()
                                    ->color(fn(?string $state): string => match (strtolower($state ?? '')) {
                                        'closed lost' => 'danger',
                                        'closed won' => 'success',
                                        'negosiasi' => 'warning',
                                        default => 'primary',
                                    }),

                                TextEntry::make('stage.probability')
                                    ->label('Probabilitas Closing')
                                    ->suffix('%')
                                    ->weight(FontWeight::Bold)
                                    ->color(fn(int $state) => $state >= 70 ? 'success' : ($state >= 40 ? 'warning' : 'danger')),
                            ])->columnSpan(1),

                        // Card 2: Informasi Finansial
                        Section::make('Nilai & Waktu')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                TextEntry::make('estimated_value')
                                    ->label('Estimasi Nilai Deal')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),

                                TextEntry::make('deal_date')
                                    ->label('Tanggal Mulai')
                                    ->date('d M Y'),

                                TextEntry::make('duration')
                                    ->label('Umur Deal (Aging)')
                                    ->state(function ($record): string {
                                        $start = \Carbon\Carbon::parse($record->created_at);
                                        $end = $record->close_date ? \Carbon\Carbon::parse($record->close_date) : now();
                                        return $start->diffForHumans($end, true, false, 2);
                                    })->icon('heroicon-o-clock'),
                            ])->columnSpan(1),

                        // Card 3: Penanggung Jawab
                        Section::make('Sales Officer')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                TextEntry::make('createdBy.full_name')
                                    ->label('Account Executive')
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('createdBy.position')
                                    ->label('Jabatan')
                                    ->size(TextEntry\TextEntrySize::Small),

                                TextEntry::make('status')
                                    ->label('Status Deal')
                                    ->badge()
                                    ->color(fn(string $state) => match ($state) {
                                        'open' => 'warning',
                                        'won' => 'success',
                                        'lost' => 'danger',
                                        default => 'gray',
                                    }),
                            ])->columnSpan(1),
                    ]),

                // ── 2. MAIN CONTENT TABS ──────────────────────────────────────────
                Tabs::make('Detail Informasi')
                    ->columnSpanFull()
                    ->tabs([

                        // TAB 1: INFORMASI LEAD / CLIENT
                        Tab::make('Data Client (Lead)')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Grid::make(2)->schema([
                                    Group::make([
                                        TextEntry::make('lead.name')
                                            ->label('Nama Perusahaan / Lead')
                                            ->weight(FontWeight::Bold)
                                            ->url(fn($record) => $record->lead_id ? LeadResource::getUrl('view', ['record' => $record->lead_id]) : null, true),

                                        TextEntry::make('lead.customer_type')
                                            ->label('Kategori')
                                            ->badge(),
                                    ])->columnSpan(1),

                                    Group::make([
                                        TextEntry::make('lead.phone')
                                            ->label('Kontak Utama')
                                            ->icon('heroicon-o-phone')
                                            ->url(fn($state) => $state ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $state) : null, true),

                                        TextEntry::make('lead.email')
                                            ->label('Email Official')
                                            ->icon('heroicon-o-envelope')
                                            ->copyable(),
                                    ])->columnSpan(1),

                                    TextEntry::make('lead.address')
                                        ->label('Alamat Lengkap')
                                        ->columnSpanFull()
                                        ->icon('heroicon-o-map-pin'),
                                ]),

                                Fieldset::make('Person in Charge (PIC)')
                                    ->visible(fn($record) => $record->lead?->customer_type === 'company')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextEntry::make('lead.pic_name')->label('Nama PIC'),
                                            TextEntry::make('lead.pic_position')->label('Jabatan'),
                                            TextEntry::make('lead.pic_phone')->label('WA PIC')->color('success'),
                                            TextEntry::make('lead.pic_email')->label('Email PIC')->color('primary'),
                                        ]),
                                    ]),
                            ]),

                        // TAB 2: AKTIVITAS & KUNJUNGAN
                        Tab::make('Aktivitas & Kunjungan')
                            ->icon('heroicon-o-map-pin')
                            ->badge(fn($record) => $record->visitAssignments()->count() ?: null)
                            ->schema([
                                // Di sini Anda bisa memanggil Visit Map atau Timeline yang kita buat tadi
                                // Contoh:
                                TextEntry::make('visit_summary')
                                    ->label('Ringkasan Aktivitas')
                                    ->state(fn($record) => $record->visitAssignments()->count() . " Jadwal Kunjungan Terdaftar"),

                                // Anda bisa menyisipkan View-Entry custom di sini jika mau
                            ]),

                        // TAB 3: DOKUMEN & PENAWARAN
                        Tab::make('Penawaran (Quotations)')
                            ->icon('heroicon-o-document-duplicate')
                            ->badge(fn($record) => $record->quotations()->count() ?: null)
                            ->schema([
                                TextEntry::make('quotations_count')
                                    ->label('Total Dokumen Penawaran')
                                    ->state(fn($record) => $record->quotations()->withTrashed()->count() . " Dokumen")
                                    ->hint('Termasuk dokumen yang sudah dihapus'),

                                // Anda bisa menambahkan Table Relation Manager di bawah infolist untuk detailnya
                            ]),

                        // TAB 4: LOG SISTEM
                        Tab::make('Audit Log')
                            ->icon('heroicon-o-finger-print')
                            ->schema([
                                Grid::make(2)->schema([
                                    Section::make('Riwayat Konversi Lead')
                                        ->schema([
                                            TextEntry::make('lead.convertedBy.full_name')
                                                ->label('Dikonversi Oleh')
                                                ->placeholder('Input Manual / Bukan Konversi'),
                                            TextEntry::make('lead.converted_at')
                                                ->label('Waktu Konversi')
                                                ->dateTime(),
                                        ])->columnSpan(1),

                                    Section::make('Jejak Audit Data')
                                        ->schema([
                                            TextEntry::make('created_at')
                                                ->label('Data Dibuat')
                                                ->dateTime('d M Y, H:i'),
                                            TextEntry::make('updated_at')
                                                ->label('Pembaruan Terakhir')
                                                ->dateTime('d M Y, H:i'),
                                        ])->columnSpan(1),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
