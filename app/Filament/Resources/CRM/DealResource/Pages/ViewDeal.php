<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use App\Filament\Resources\CRM\LeadResource;
use App\Models\CRM\Deal;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

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
                // BAGIAN 1: Ringkasan Deal (Sedikit di-tata ulang untuk alur yang lebih baik)
                Section::make('Ringkasan Deal')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('deal_number')
                            ->label('No. Deal')
                            ->weight('semibold')
                            ->icon('heroicon-o-hashtag')
                            ->copyable(),

                        TextEntry::make('stage.name')
                            ->label('Stage Deal')
                            ->badge()
                            ->color(fn(?string $state): string => match (strtolower($state ?? '')) {
                                'closed lost' => 'danger',
                                'closed won' => 'success',
                                'lead baru' => 'gray',
                                'kualifikasi' => 'primary',
                                'presentasi', 'penawaran' => 'info',
                                'negosiasi' => 'warning',
                                default => 'primary',
                            })
                            ->formatStateUsing(fn($state) => ucwords($state)),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => ucfirst($state))
                            ->color(fn(string $state): string => match ($state) {
                                'open' => 'warning',
                                'won' => 'success',
                                'lost' => 'danger',
                                default => 'gray',
                            }),

                        // Nilai dan tanggal dikelompokkan bersama
                        TextEntry::make('estimated_value')
                            ->label('Estimasi Nilai')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success'),

                        TextEntry::make('deal_date')
                            ->label('Tanggal Deal')
                            ->date('d M Y'),

                        TextEntry::make('closed_at')
                            ->label('Tanggal Penutupan')
                            ->date('d M Y H:i')
                            ->placeholder('Belum Closing')
                    ]),

                // BARU: Section khusus untuk menampilkan siapa yang bertanggung jawab
                Section::make('Penanggung Jawab Deal')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('createdBy.full_name')
                            ->label('Ditangani Oleh (Sales)')
                            ->icon('heroicon-o-user')
                            ->placeholder('Tidak diketahui'),

                        TextEntry::make('createdBy.position')
                            ->label('Posisi Sales')
                            ->placeholder('Tidak ada data posisi'),

                        // Menampilkan siapa yang mengkonversi Lead menjadi Deal, jika ada
                        TextEntry::make('lead.convertedBy.full_name')
                            ->label('Dikonversi Oleh')
                            ->placeholder('Lead belum dikonversi')
                            ->visible(fn(Deal $record) => $record->lead?->converted_by !== null),

                        // Menampilkan kapan Lead dikonversi
                        TextEntry::make('lead.converted_at')
                            ->label('Dikonversi Pada')
                            ->dateTime('d M Y H:i')
                            ->visible(fn(Deal $record) => $record->lead?->converted_at !== null),
                    ]),

                // BAGIAN 2: Informasi Pihak Terkait (Lead)
                Section::make('Informasi Pihak Terkait (Lead)')
                    ->description(fn(Deal $record) => $record->lead()->withTrashed()->first()?->trashed() ? 'PERINGATAN: Data Lead ini telah dihapus.' : 'Detail kontak dan narahubung dari Lead.')
                    ->collapsible()
                    ->schema([
                        // Informasi Utama Lead
                        Grid::make(2)->schema([
                            TextEntry::make('lead.name')
                                ->label('Nama Lead')
                                ->weight('semibold')
                                ->icon('heroicon-o-building-office-2')
                                ->url(function (Deal $record) {
                                    $lead = $record->lead()->withTrashed()->first();
                                    return $lead ? LeadResource::getUrl('view', ['record' => $lead->id]) : null;
                                }, shouldOpenInNewTab: true)
                                ->color(fn(Deal $record) => $record->lead()->withTrashed()->first()?->trashed() ? 'danger' : null),

                            TextEntry::make('lead.customer_type')
                                ->label('Tipe')
                                ->badge()
                                ->formatStateUsing(fn($state) => ucfirst($state)),

                            TextEntry::make('lead.email')
                                ->label('Email Lead')
                                ->url(fn($state) => $state ? "mailto:{$state}" : null)
                                ->color('warning'),

                            TextEntry::make('lead.phone')
                                ->label('No. WhatsApp Lead')
                                ->url(fn($state) => $state ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $state) : null, true)
                                ->color('success'),

                            TextEntry::make('lead.address')
                                ->label('Alamat Lead')
                                ->icon('heroicon-o-map-pin')
                                ->columnSpanFull()
                                ->placeholder('Tidak ada data alamat.'),
                        ]),

                        Fieldset::make('PIC Lead')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('lead.pic_name')->label('Nama PIC'),
                                    TextEntry::make('lead.pic_position')->label('Jabatan PIC'),
                                    TextEntry::make('lead.pic_email')
                                        ->label('Email PIC')
                                        ->url(fn($state) => $state ? "mailto:{$state}" : null)
                                        ->color('warning'),
                                    TextEntry::make('lead.pic_phone')
                                        ->label('No. WhatsApp PIC')
                                        ->url(fn($state) => $state ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $state) : null, true)
                                        ->color('success'),
                                ]),
                            ])
                            ->visible(fn(Deal $record) => $record->lead?->customer_type === 'company'),
                    ])
                    ->visible(fn(Deal $record) => $record->lead()->withTrashed()->exists()),

                // BAGIAN 3: Aktivitas & Statistik Deal
                Section::make('Aktivitas & Statistik Deal')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('quotations_count')
                            ->label('Jumlah Penawaran')
                            ->badge()
                            ->state(fn(Deal $record) => $record->quotations()->withTrashed()->count())
                            ->formatStateUsing(function (int $state, Deal $record) {
                                $trashedCount = $record->quotations()->onlyTrashed()->count();
                                $activeCount = $state - $trashedCount;
                                $parts = [];
                                if ($activeCount > 0)
                                    $parts[] = "$activeCount Aktif";
                                if ($trashedCount > 0)
                                    $parts[] = "$trashedCount Terhapus";
                                return empty($parts) ? '0 Penawaran' : implode(' & ', $parts);
                            })
                            ->color(function (int $state, Deal $record): string {
                                if ($state === 0)
                                    return 'gray';
                                return $record->quotations()->onlyTrashed()->exists() ? 'danger' : 'info';
                            }),

                        TextEntry::make('duration')
                            ->label('Durasi Proses')
                            ->getStateUsing(function ($record): string {
                                $start = \Carbon\Carbon::parse($record->created_at);
                                $end = $record->close_date ? \Carbon\Carbon::parse($record->close_date) : now();
                                if (!$start)
                                    return '-';
                                return $start->diffForHumans($end, true, false, 2);
                            }),

                        TextEntry::make('stage.probability')
                            ->label('Probabilitas (%)')
                            ->badge()
                            ->color(fn(int $state): string => match (true) {
                                $state <= 30 => 'danger',
                                $state <= 70 => 'warning',
                                default => 'success',
                            })
                            ->formatStateUsing(fn($state) => $state . '%'),
                    ]),

                // BAGIAN 4: Pengelolaan Data (TETAP SAMA SESUAI PERMINTAAN)
                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
