<?php

namespace App\Filament\Resources\Project\ProjectResource\Pages;

use App\Filament\Pages\Project\ProjectBoard;
use App\Filament\Resources\Project\ProjectResource;
use App\Infolists\Components\ProjectDocumentList;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
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
            Action::make('board')
                ->label('Project Board')
                ->icon('heroicon-o-view-columns')
                ->color('warning')
                ->url(fn() => ProjectBoard::getUrl(['project_id' => $this->record->id])),

            Action::make('external_access')
                ->label('External Dashboard')
                ->icon('heroicon-o-globe-alt')
                ->color('success')
                // ->visible(fn() => auth()->user()->hasRole('super_admin'))
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

            Actions\EditAction::make(),

            Action::make('Kembali')
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
                    ->description('Detail informasi mengenai project')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Project')
                            ->weight('semibold')
                            ->size('lg')
                            ->placeholder('—'),

                        TextEntry::make('ticket_prefix')
                            ->label('Prefix Ticket')
                            ->placeholder('—'),

                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->date('D, d M Y')
                            ->placeholder('—'),

                        TextEntry::make('end_date')
                            ->label('Tanggal Selesai')
                            ->date('D, d M Y')
                            ->placeholder('—'),

                        TextEntry::make('remaining_days')
                            ->label('Sisa Hari')
                            ->getStateUsing(function ($record): ?string {
                                if (!$record->end_date) {
                                    return '—';
                                }

                                return $record->remaining_days . ' Hari';
                            })
                            ->color(
                                fn($record): string =>
                                !$record->end_date ? 'gray' :
                                ($record->remaining_days <= 0 ? 'danger' :
                                    ($record->remaining_days <= 7 ? 'warning' : 'success'))
                            ),

                        TextEntry::make('pinned_date')
                            ->label('Status Pin')
                            ->getStateUsing(
                                fn($record) =>
                                $record->pinned_date
                                ? 'Di-pin pada ' . $record->pinned_date->format('d M Y')
                                : 'Tidak di-pin'
                            ),
                    ]),

                Section::make('Statistik Project')
                    ->description('Ringkasan aktivitas project.')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('members_count')
                            ->label('Total Member')
                            ->getStateUsing(fn($record) => $record->members()->count())
                            ->formatStateUsing(fn($state) => $state . ' Member')
                            ->placeholder('—'),

                        TextEntry::make('tickets_count')
                            ->label('Total Ticket')
                            ->getStateUsing(fn($record) => $record->tickets()->count())
                            ->formatStateUsing(fn($state) => $state . ' Ticket')
                            ->placeholder('—'),

                        TextEntry::make('epics_count')
                            ->label('Total Epic')
                            ->getStateUsing(fn($record) => $record->epics()->count())
                            ->formatStateUsing(fn($state): string => $state . ' Epic')
                            ->placeholder('—'),

                        TextEntry::make('statuses_count')
                            ->label('Status Ticket')
                            ->getStateUsing(fn($record) => $record->ticketStatuses()->count())
                            ->formatStateUsing(fn($state): string => $state . ' Status Ticket')
                            ->placeholder('—'),
                    ]),

                Section::make('Deskripsi Project')
                    ->description('Penjelasan lengkap mengenai project.')
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->placeholder('Tidak ada deskripsi')
                            ->columnSpanFull(),
                    ]),

                Section::make('Informasi Kontrak Project')
                    ->description('Project ini berasal dari Sales Order.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('salesOrder.order_number')
                            ->label('No. Sales Order')
                            ->weight('semibold')
                            ->placeholder('—'),

                        TextEntry::make('salesOrder.status')
                            ->label('Status Order')
                            ->badge()
                            ->color(fn(?string $state) => match ($state) {
                                'draft' => 'gray',
                                'confirmed' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',

                                default => 'warning',
                            })
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'draft' => 'Draft',
                                'processing' => 'Sedang Diproses',
                                'confirmed' => 'Dikonfirmasi',
                                'shipped' => 'Dalam Pengiriman',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',

                                default => ucwords(
                                    str_replace('_', ' ', $state)
                                ),
                            }),

                        TextEntry::make('salesOrder.customer.name')
                            ->label('Customer')
                            ->weight('semibold')
                            ->icon('heroicon-o-user-group')
                            ->placeholder('—'),

                        TextEntry::make('salesOrder.employee.full_name')
                            ->label('Sales PIC')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                        TextEntry::make('salesOrder.order_date')
                            ->label('Tanggal Order')
                            ->date('D, d M Y')
                            ->placeholder('—'),

                        TextEntry::make('salesOrder.grand_total')
                            ->label('Nilai Kontrak')
                            ->money('IDR')
                            ->weight('semibold')
                            ->color('success')
                            ->placeholder('—'),
                    ]),

                Section::make('Estimasi Budget Project')
                    ->description('Perbandingan estimasi biaya dengan pengeluaran aktual.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('estimated_cost')
                            ->label('Estimasi Biaya')
                            ->money('IDR')
                            ->weight('semibold')
                            ->placeholder('—'),

                        TextEntry::make('actual_cost')
                            ->label('Pengeluaran Aktual')
                            ->money('IDR')
                            ->color('danger')
                            ->weight('semibold')
                            ->placeholder('—'),

                        TextEntry::make('cost_difference')
                            ->label('Selisih Budget')
                            ->getStateUsing(
                                fn($record) =>
                                ($record->estimated_cost ?? 0) - ($record->actual_cost ?? 0)
                            )
                            ->money('IDR')
                            ->color(
                                fn($state) =>
                                $state >= 0 ? 'success' : 'danger'
                            )
                            ->weight('semibold')
                            ->placeholder('—'),
                    ]),

                Section::make('Dokumen Project')
                    ->description('Dokumen kontrak, BAST, dan file teknis project.')
                    ->schema([
                        ProjectDocumentList::make('documents')
                            ->label('Daftar Dokumen')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d M Y H:i')
                            ->visible(fn($record) => $record->trashed()),
                    ]),
            ]);
    }
}
