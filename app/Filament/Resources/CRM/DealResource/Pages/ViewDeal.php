<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\Deal;
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
                Section::make('Informasi Deal')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('deal_number')
                                    ->label('No. Deal')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->icon('heroicon-o-hashtag')
                                    ->copyable()
                                    ->color(function (Deal $record) {
                                        $record->withTrashed()->first();
                                        if ($record && $record->trashed())
                                            return 'danger';
                                        return 'primary';
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('stage.name')
                                    ->label('Stage Deal')
                                    ->icon('heroicon-o-queue-list')
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                    ->color(fn(Deal $record): string => match ($record->status) {
                                        'won' => 'success',
                                        'lost' => 'danger',
                                        default => 'warning',
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('customer_or_lead')
                                    ->label('Lead')
                                    ->getStateUsing(function (Deal $record) {
                                        if ($record->customer) {
                                            return $record->customer->name . ' (Customer)';
                                        }

                                        $lead = $record->lead()->withTrashed()->first();

                                        if ($lead) {
                                            $suffix = $lead->trashed() ? ' (Terhapus)' : ' (Lead)';
                                            return $lead->name . $suffix;
                                        }
                                        return '-';
                                    })
                                    ->weight('semibold')
                                    ->icon('heroicon-o-user')
                                    ->color(
                                        fn(Deal $record) =>
                                        ($record->lead()->withTrashed()->first()?->trashed()) ? 'danger' : ''
                                    )
                                    ->placeholder('—'),

                                TextEntry::make('status')
                                    ->label('Status')
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
                                    })
                                    ->placeholder('—'),

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
                                    ->color('success')
                                    ->placeholder('—'),

                                TextEntry::make('quotations_count')
                                    ->label('Jumlah Penawaran')
                                    ->formatStateUsing(fn($state) => $state . ' Penawaran')
                                    ->badge()
                                    
                                    ->state(function (Deal $record) {
                                        return $record->quotations()->withTrashed()->count();
                                    })
                                    ->color(function (Deal $record, int $state): string {
                                        if ($state === 0)
                                            return 'gray';

                                        $hasTrashed = $record->quotations()->onlyTrashed()->exists();

                                        return $hasTrashed ? 'danger' : 'info';
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('duration')
                                    ->label('Durasi Proses')
                                    ->getStateUsing(function ($record): string {
                                        $start = \Carbon\Carbon::parse($record->created_at);
                                        $end = $record->close_date ? \Carbon\Carbon::parse($record->close_date) : now();

                                        if (!$start)
                                            return '-';

                                        $diff = $start->diff($end);

                                        if ($diff->days > 0) {
                                            return "{$diff->days} Hari";
                                        } elseif ($diff->h > 0) {
                                            return "{$diff->h} Jam {$diff->i} Menit";
                                        } else {
                                            return "{$diff->i} Menit";
                                        }
                                    })
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('—'),

                                TextEntry::make('stage.order')
                                    ->label('Urutan Stage')
                                    ->badge()
                                    ->color('primary')
                                    ->formatStateUsing(fn($state) => 'Tahap ke-' . $state)
                                    ->placeholder('—'),
                            ]),
                    ]),

                Section::make('Informasi Kontak Lead')
                    ->description(fn(Deal $record) => $record->lead()->withTrashed()->first()?->trashed() ? 'Data Lead ini telah dihapus.' : null)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('lead_name_manual')
                                ->label('Nama Lead')
                                ->getStateUsing(fn(Deal $record) => $record->lead()->withTrashed()->first()?->name)
                                ->placeholder('—')
                                ->icon('heroicon-o-user')
                                ->weight('bold')
                                ->size('lg')
                                ->color(function (Deal $record) {
                                    $record->withTrashed()->first();
                                    if ($record && $record->trashed())
                                        return 'danger';
                                    return '';
                                }),

                            TextEntry::make('lead_company_manual')
                                ->label('Tipe Customer')
                                ->getStateUsing(fn(Deal $record) => ucfirst($record->lead()->withTrashed()->first()?->customer_type ?? ''))
                                ->placeholder('—')
                                ->icon('heroicon-o-identification'),

                            TextEntry::make('lead_email_manual')
                                ->label('Email')
                                ->icon('heroicon-o-envelope')
                                ->getStateUsing(fn(Deal $record) => $record->lead()->withTrashed()->first()?->email)
                                ->url(function ($record) {
                                    $email = $record->lead()->withTrashed()->first()?->email;
                                    return $email ? "mailto:{$email}" : null;
                                })
                                ->placeholder('—')
                                ->color('warning'),

                            TextEntry::make('lead_phone_manual')
                                ->label('No. WhatsApp')
                                ->icon('heroicon-o-phone')
                                ->getStateUsing(fn(Deal $record) => $record->lead()->withTrashed()->first()?->phone)
                                ->url(function ($record) {
                                    $phone = $record->lead()->withTrashed()->first()?->phone;
                                    return $phone ? "tel:{$phone}" : null;
                                })
                                ->placeholder('—')
                                ->color('success'),

                            TextEntry::make('lead_address_manual')
                                ->label('Alamat')
                                ->getStateUsing(fn(Deal $record) => $record->lead()->withTrashed()->first()?->address)
                                ->columnSpanFull()
                                ->icon('heroicon-o-map-pin')
                                ->placeholder('Tidak ada alamat tersimpan'),
                        ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

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
