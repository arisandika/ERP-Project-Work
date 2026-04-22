<?php

namespace App\Filament\Resources\CRM\LeadResource\Pages;

use App\Filament\Resources\CRM\LeadResource;
use App\Models\CRM\Lead;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

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
                                    ->weight('semibold')
                                    ->copyable()
                                    ->color(fn(Lead $record) => $record->trashed() ? 'danger' : '')
                                    ->placeholder('—'),

                                TextEntry::make('customer_type')
                                    ->label('Tipe')
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                    ->placeholder('—'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->url(fn($record) => $record->email ? "mailto:{$record->email}" : null)
                                    ->placeholder('—')
                                    ->copyable() // Memudahkan sales copy email
                                    ->color('warning'),

                                TextEntry::make('phone')
                                    ->label('No. WhatsApp')
                                    ->url(fn($record) => $record->phone ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $record->phone) : null, true)
                                    ->placeholder('—')
                                    ->copyable()
                                    ->color('success'),

                                TextEntry::make('address')
                                    ->label('Alamat Domisili/Kantor')
                                    ->placeholder('Tidak ada data alamat'),

                                TextEntry::make('createdBy.full_name')
                                    ->label('Dibuat Oleh')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('Sistem / Tidak diketahui'),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Informasi PIC (Person In Charge)')
                    ->description('Data narahubung dari pihak Perusahaan')
                    ->visible(fn($record) => $record?->customer_type === 'company')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('pic_name')
                                    ->label('Nama PIC')
                                    ->weight('medium')
                                    ->placeholder('—'),

                                TextEntry::make('pic_email')
                                    ->label('Email PIC')
                                    ->url(fn($record) => $record->pic_email ? "mailto:{$record->pic_email}" : null)
                                    ->placeholder('—')
                                    ->copyable()
                                    ->color('warning'),

                                TextEntry::make('pic_phone')
                                    ->label('No. WhatsApp PIC')
                                    ->url(fn($record) => $record->pic_phone ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $record->pic_phone) : null, true)
                                    ->placeholder('—')
                                    ->copyable()
                                    ->color('success'),

                                TextEntry::make('pic_position')
                                    ->label('Jabatan PIC')
                                    ->placeholder('—'),
                            ]),
                    ]),

                Section::make('Status & Klasifikasi')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status Lead')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        Lead::STATUS_NEW => 'info',
                                        Lead::STATUS_CONTACTED => 'warning',
                                        Lead::STATUS_QUALIFIED => 'success',
                                        Lead::STATUS_UNQUALIFIED => 'danger',
                                        Lead::STATUS_CONVERTED => 'success',
                                        Lead::STATUS_DEAD => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                                TextEntry::make('deal_progress')
                                    ->label('Progress Deal')
                                    ->badge()
                                    ->getStateUsing(function (Lead $record) {
                                        $allDeals = $record->deals()->withTrashed()->get();
                                        $activeDeals = $allDeals->whereNull('deleted_at');

                                        if ($allDeals->isEmpty())
                                            return 'Belum Ada Deal';
                                        if ($activeDeals->isEmpty())
                                            return 'Semua Deal Terhapus';
                                        if ($activeDeals->contains('status', 'open'))
                                            return 'Sedang Aktif Proses';
                                        if ($activeDeals->contains('status', 'won'))
                                            return 'Terkonversi';

                                        return 'Prospek Hilang';
                                    })
                                    ->color(fn(string $state): string => match ($state) {
                                        'Belum Ada Deal' => 'gray',
                                        'Semua Deal Terhapus' => 'danger',
                                        'Sedang Aktif Proses' => 'info',
                                        'Terkonversi' => 'success',
                                        'Prospek Hilang' => 'danger',
                                        default => 'gray',
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('deals_count')
                                    ->label('Jumlah Deal')
                                    ->getStateUsing(fn($record) => $record->deals()->withTrashed()->count())
                                    ->formatStateUsing(function ($state, Lead $record) {
                                        $trashed = $record->deals()->onlyTrashed()->count();
                                        return $trashed > 0 ? "{$trashed} Deal Terhapus" : "{$state} Deal";
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('source')
                                    ->label('Sumber')
                                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                                    ->placeholder('—'),
                            ]),

                        Grid::make(3)
                            ->visible(fn($record) => $record->status === Lead::STATUS_CONVERTED)
                            ->schema([
                                // Customer tetap pakai 'name' karena model Customer pakai kolom name
                                TextEntry::make('convertedCustomer.name')
                                    ->label('Dikonversi Ke (Customer)')
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-check-badge'),

                                // PERBAIKAN: Berubah jadi full_name karena ambil dari model Employee
                                TextEntry::make('convertedBy.full_name')
                                    ->label('Dikonversi Oleh')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('converted_at')
                                    ->label('Waktu Konversi')
                                    ->dateTime('d M Y H:i'),
                            ])->extraAttributes(['class' => 'mt-4 pt-4 border-t border-gray-200 dark:border-white/10']),
                    ]),

                // Section::make('Daftar Deal Terkait')
                //     ->icon('heroicon-o-briefcase')
                //     ->schema([
                //         TextEntry::make('deals_list_view')
                //             ->hiddenLabel()
                //             ->columnSpanFull()
                //             ->html()
                //             ->getStateUsing(function ($record) {
                //                 $deals = $record->deals()->withTrashed()->with('stage')->latest()->get();

                //                 if ($deals->isEmpty()) {
                //                     return new HtmlString(
                //                         '<p class="text-sm italic text-gray-500">Belum ada deal yang dibuat.</p>'
                //                     );
                //                 }

                //                 $html = '<div class="grid w-full grid-cols-1 gap-4 card-repeater md:grid-cols-2">';

                //                 foreach ($deals as $deal) {
                //                     $isDeleted = $deal->trashed();

                //                     if ($isDeleted) {
                //                         $containerClass = 'bg-danger-50 ring-danger-600/30 dark:bg-danger-900/20 dark:ring-danger-500/30 border-danger-200';
                //                         $badgeClass = 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger';
                //                         $textClass = 'text-gray-700 dark:text-white';
                //                         $statusLabel = 'Terhapus';
                //                     } else {
                //                         $containerClass = match ($deal->status) {
                //                             'won' => 'fi-color-success bg-success-100/40 text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                //                             'lost' => 'fi-color-danger bg-danger-100/40 text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                //                             default => 'fi-color-warning bg-warning-100/40 text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30 fi-color-warning',
                //                         };

                //                         $badgeClass = match ($deal->status) {
                //                             'won' => 'fi-color-success bg-white text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                //                             'lost' => 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                //                             default => 'fi-color-warning bg-white text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30 fi-color-warning',
                //                         };

                //                         $textClass = 'text-gray-700 dark:text-white';
                //                         $statusLabel = ucfirst($deal->status);
                //                     }

                //                     $stageName = $deal->stage->name ?? '-';
                //                     $dealNumber = $deal->deal_number;
                //                     $value = 'IDR ' . number_format($deal->estimated_value, 0, ',', '.');
                //                     $date = $deal->created_at->format('d M Y');
                //                     $deletedDate = $isDeleted ? '<br><span class="text-xs font-semibold text-danger-600 dark:text-danger-400">Dihapus: ' . $deal->deleted_at->format('d M Y') . '</span>' : '';

                //                     $html .= "
                //                 <div class='flex flex-col justify-between p-4 rounded-lg ring-1 ring-inset shadow-sm {$containerClass} transition duration-150 ease-in-out'>
                //                     <div class='flex items-start justify-between mb-2'>
                //                         <div>
                //                             <span class='font-bold text-sm block {$textClass}'>{$dealNumber}</span>
                //                             <span class='text-xs opacity-75 {$textClass}'>{$date}</span>
                //                             {$deletedDate}
                //                         </div>
                //                         <span class='inline-flex items-center rounded-md px-2 py-1 text-xs ring-1 ring-inset shadow-sm capitalize {$badgeClass}'>
                //                             {$statusLabel}
                //                         </span>
                //                     </div>
                                    
                //                     <div class='flex items-end justify-between pt-3 mt-3 border-t border-black/20 dark:border-white/20'>
                //                         <div class='text-xs {$textClass}'>
                //                             <p class='opacity-70 uppercase tracking-wider text-[10px]'>Stage</p>
                //                             <p class='text-sm font-semibold'>{$stageName}</p>
                //                         </div>
                //                         <div class='text-xs text-right {$textClass}'>
                //                             <p class='opacity-70 uppercase tracking-wider text-[10px]'>Est. Value</p>
                //                             <p class='text-sm font-semibold'>{$value}</p>
                //                         </div>
                //                     </div>
                //                 </div>";
                //                 }

                //                 $html .= '</div>';

                //                 return new HtmlString($html);
                //             }),
                //     ])
                //     ->visible(fn($record) => $record && $record->deals()->withTrashed()->exists()),

                Section::make('Catatan Sales')
                    ->schema([
                        TextEntry::make('notes')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan sales.'),
                    ])
                    ->collapsible(),

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
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
