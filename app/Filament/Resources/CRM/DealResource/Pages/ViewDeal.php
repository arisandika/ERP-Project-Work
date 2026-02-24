<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use Filament\Actions;
use Filament\Actions\Action;
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
            Action::make('back')
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
                Section::make('Informasi Deal')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('deal_number')
                                    ->label('No. Deal')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->icon('heroicon-o-hashtag')
                                    ->copyable(),

                                TextEntry::make('stage.name')
                                    ->label('Stage Deal')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-queue-list'),

                                TextEntry::make('customer_or_lead')
                                    ->label('Lead / Customer')
                                    ->getStateUsing(function ($record) {
                                        if ($record->customer) {
                                            return $record->customer->name . ' (Customer)';
                                        }
                                        if ($record->lead) {
                                            return $record->lead->name . ' (Lead)';
                                        }
                                        return '-';
                                    })
                                    ->weight('semibold')
                                    ->icon('heroicon-o-user')
                                    ->color('primary'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'open' => 'warning',
                                        'won' => 'success',
                                        'lost' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                    ->icon(fn(string $state): string => match ($state) {
                                        'open' => 'heroicon-o-clock',
                                        'won' => 'heroicon-o-check-circle',
                                        'lost' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),

                                TextEntry::make('deal_date')
                                    ->label('Tanggal Deal Dibuat')
                                    ->date('d M Y')
                                    ->icon('heroicon-o-calendar'),

                                TextEntry::make('close_date')
                                    ->label('Tanggal Penutupan')
                                    ->date('d M Y')
                                    ->placeholder('Belum ditutup')
                                    ->icon('heroicon-o-calendar-days'),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Nilai & Statistik')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('estimated_value')
                                    ->label('Estimasi Nilai')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success'),

                                TextEntry::make('quotations_count')
                                    ->label('Jumlah Penawaran')
                                    ->getStateUsing(fn($record) => $record->quotations()->count())
                                    ->badge()
                                    ->color('info'),

                                // Menghitung durasi deal (mirip Remaining Days di Project)
                                TextEntry::make('duration')
                                    ->label('Durasi Proses')
                                    ->getStateUsing(function ($record): string {
                                        $start = \Carbon\Carbon::parse($record->created_at);
                                        $end = $record->close_date ? \Carbon\Carbon::parse($record->close_date) : now();
                                        
                                        if(!$start) return '-';
                                        
                                        $diff = $start->diff($end);
                                        
                                        if($diff->days > 0) {
                                            return "{$diff->days} Hari";
                                        } elseif($diff->h > 0) {
                                            return "{$diff->h} Jam {$diff->i} Menit";
                                        } else {
                                            return "{$diff->i} Menit";
                                        }
                                    })
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('stage.order')
                                    ->label('Urutan Stage')
                                    ->badge()
                                    ->color('primary')
                                    ->formatStateUsing(fn($state) => 'Tahap ke-' . $state),
                            ]),
                    ]),

                Section::make('Informasi Kontak Lead')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('lead.name')
                                ->label('Nama Kontak')
                                ->placeholder('—'),

                            TextEntry::make('lead.company')
                                ->label('Perusahaan')
                                ->placeholder('Perorangan / Tidak ada data'),

                            TextEntry::make('lead.phone')
                                ->label('Nomor Telepon')
                                ->icon('heroicon-o-phone')
                                ->url(fn($record) => $record->lead?->phone ? "tel:{$record->lead->phone}" : null)
                                ->placeholder('—')
                                ->color('primary'),

                            TextEntry::make('lead.email')
                                ->label('Email')
                                ->icon('heroicon-o-envelope')
                                ->url(fn($record) => $record->lead?->email ? "mailto:{$record->lead->email}" : null)
                                ->placeholder('—')
                                ->color('primary'),

                            TextEntry::make('lead.address')
                                ->label('Alamat')
                                ->columnSpanFull()
                                ->placeholder('Tidak ada alamat tersimpan'),
                        ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(), // Agar status collapse tersimpan
                    
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
