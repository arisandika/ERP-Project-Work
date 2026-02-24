<?php

namespace App\Filament\Resources\CRM\LeadResource\Pages;

use App\Filament\Resources\CRM\LeadResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

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
        return 'Lihat Lead';
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Utama Lead')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Lead')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->icon('heroicon-o-user')
                                    ->copyable(),

                                TextEntry::make('customer_type')
                                    ->label('Tipe Customer')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                    ->color(fn(string $state): string => match ($state) {
                                        'company' => 'primary',
                                        'individual' => 'success',
                                        default => 'gray',
                                    })
                                    ->icon('heroicon-o-identification'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->url(fn($record) => $record->email ? "mailto:{$record->email}" : null)
                                    ->placeholder('—')
                                    ->color('primary'),

                                TextEntry::make('phone')
                                    ->label('No. WhatsApp')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->url(fn($record) => $record->phone ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $record->phone) : null, true)
                                    ->placeholder('—')
                                    ->color('success'),

                                TextEntry::make('address')
                                    ->label('Alamat Domisili/Kantor')
                                    ->icon('heroicon-o-map-pin')
                                    ->columnSpanFull()
                                    ->placeholder('Tidak ada data alamat'),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Status & Klasifikasi')
                    ->schema([
                        Grid::make(4) // Menggunakan 4 kolom agar compact seperti statistik
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status Lead')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                                    ->color(fn(string $state): string => match ($state) {
                                        'new', 'contacted' => 'warning',
                                        'qualified', 'converted' => 'success',
                                        'lost' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon(fn(string $state): string => match ($state) {
                                        'new' => 'heroicon-o-sparkles',
                                        'converted' => 'heroicon-o-check-badge',
                                        'lost' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-arrow-path',
                                    }),

                                TextEntry::make('source')
                                    ->label('Sumber')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                                    ->icon('heroicon-o-globe-alt'),

                                TextEntry::make('deals_count')
                                    ->label('Total Deal')
                                    ->getStateUsing(fn($record) => $record->deals()->count())
                                    ->badge()
                                    ->color(fn(int $state): string => $state > 0 ? 'info' : 'gray')
                                    ->formatStateUsing(fn($state) => $state . ' Deal'),

                                // Hanya muncul jika status converted, tapi kita handle displaynya
                                TextEntry::make('convertedCustomer.name')
                                    ->label('Converted To')
                                    ->placeholder('Belum dikonversi')
                                    ->badge()
                                    ->color('success')
                                    ->visible(fn($record) => $record->status === 'converted')
                                    ->icon('heroicon-o-user-group'),
                            ]),
                    ]),

                Section::make('Catatan Sales')
                    ->schema([
                        TextEntry::make('notes')
                            ->hiddenLabel()
                            ->html()
                            ->prose() // Agar format list/bold dari RichEditor terbaca rapi
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan sales.'),
                    ])
                    ->collapsible(),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Masuk Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->collapsible()
                    ->collapsed(), // Default tertutup agar rapi
            ]);
    }
}
