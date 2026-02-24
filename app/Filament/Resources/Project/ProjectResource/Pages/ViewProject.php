<?php

namespace App\Filament\Resources\Project\ProjectResource\Pages;

use App\Filament\Pages\Project\ProjectBoard;
use App\Filament\Resources\Project\ProjectResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;


class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Action::make('board')
                ->label('Project Board')
                ->icon('heroicon-o-view-columns')
                ->color('warning')
                ->url(fn () => ProjectBoard::getUrl(['project_id' => $this->record->id]))
            ,
            Action::make('external_access')
                ->label('External Dashboard')
                ->icon('heroicon-o-globe-alt')
                ->color('success')
                ->visible(fn() => auth()->user()->hasRole('super_admin'))
                ->modalHeading('Akses External Dashboard')
                ->modalDescription('Bagikan kredensial ini ke user eksternal atau client untuk mengakses dashboard project ini')
                ->modalContent(function () {
                    $record = $this->record;
                    $externalAccess = $record->externalAccess;

                    if (!$externalAccess) {
                        $externalAccess = $record->generateExternalAccess();
                    }

                    $dashboardUrl = url('/external/' . $externalAccess->access_token);

                    return view('filament.components.external-access-modal', [
                        'dashboardUrl' => $dashboardUrl,
                        'password' => $externalAccess->password,
                        'lastAccessed' => $externalAccess->last_accessed_at ? $externalAccess->last_accessed_at->format('d M Y H:i') : null,
                        'isActive' => $externalAccess->is_active,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('back')
                ->url(static::getResource()::getUrl())
                ->button()
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Project';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Project')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Project')
                                    ->weight('bold')
                                    ->size('lg'),
                                TextEntry::make('ticket_prefix')
                                    ->label('Prefix Ticket')
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('start_date')
                                    ->label('Tanggal Mulai')
                                    ->date('d M Y')
                                    ->placeholder('Not set'),
                                TextEntry::make('end_date')
                                    ->label('Tanggal Selesai')
                                    ->date('d M Y')
                                    ->placeholder('Not set'),
                                TextEntry::make('remaining_days')
                                    ->label('Sisa Hari')
                                    ->getStateUsing(function ($record): ?string {
                                        if (!$record->end_date) {
                                            return 'Not set';
                                        }
                                        return $record->remaining_days . ' hari';
                                    })
                                    ->badge()
                                    ->color(
                                        fn($record): string =>
                                        !$record->end_date ? 'gray' :
                                        ($record->remaining_days <= 0 ? 'danger' :
                                            ($record->remaining_days <= 7 ? 'warning' : 'success'))
                                    ),
                                TextEntry::make('pinned_date')
                                    ->label('Pinned Status')
                                    ->getStateUsing(function ($record): string {
                                        return $record->pinned_date ? 'Pinned on ' . $record->pinned_date->format('d M Y') : 'Not pinned';
                                    })
                                    ->badge()
                                    ->color(fn($record): string => $record->pinned_date ? 'success' : 'gray'),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Statistik Project')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('members_count')
                                    ->label('Total Member')
                                    ->getStateUsing(fn($record) => $record->members()->count())
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('tickets_count')
                                    ->label('Total Ticket')
                                    ->getStateUsing(fn($record) => $record->tickets()->count())
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('epics_count')
                                    ->label('Total Epic')
                                    ->getStateUsing(fn($record) => $record->epics()->count())
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('statuses_count')
                                    ->label('Status Ticket')
                                    ->getStateUsing(fn($record) => $record->ticketStatuses()->count())
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('Deskripsi Project')
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ])
                    ->columnSpanFull(),

                Section::make('Informasi Billing & Invoice')
                    ->schema([
                        Grid::make(2)->schema([

                            TextEntry::make('invoice.invoice_number')
                                ->label('No. Sales Invoice')
                                ->weight('bold')
                                ->placeholder('Belum ditautkan'),

                            TextEntry::make('invoice.status')
                                ->label('Status Billing')
                                ->badge()
                                ->color(fn(?string $state) => match ($state) {
                                    'paid' => 'success',
                                    'partial' => 'warning',
                                    'draft' => 'gray',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn(?string $state): string => match ($state) {
                                    'draft' => 'Draft',
                                    'sent' => 'Terkirim',
                                    'partial' => 'Terbayar Sebagian',
                                    'paid' => 'Lunas',
                                    'cancelled' => 'Dibatalkan',
                                    default => ucwords($state ?? '-'),
                                })
                                ->placeholder('—'),

                            TextEntry::make('invoice.customer.name')
                                ->label('Customer')
                                ->placeholder('—'),

                            TextEntry::make('invoice.employee.full_name')
                                ->label('Sales PIC')
                                ->placeholder('—'),

                            TextEntry::make('invoice.invoice_date')
                                ->label('Tanggal Invoice')
                                ->date('d M Y')
                                ->placeholder('—'),

                            TextEntry::make('invoice.due_date')
                                ->label('Jatuh Tempo')
                                ->date('d M Y')
                                ->placeholder('—'),

                            TextEntry::make('invoice.grand_total')
                                ->label('Nilai Kontrak')
                                ->money('IDR')
                                ->placeholder('—'),

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
                    ->persistCollapsed()
            ]);
    }
}
