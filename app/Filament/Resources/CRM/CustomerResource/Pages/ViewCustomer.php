<?php

namespace App\Filament\Resources\CRM\CustomerResource\Pages;

use App\Filament\Resources\CRM\CustomerResource;
use App\Models\CRM\Customer;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

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
        return 'Lihat Customer';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Utama Customer')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(fn(Customer $record) => $record->customer_type === 'company' ? 'Nama Perusahaan' : 'Nama Lengkap')
                                    ->weight('semibold')
                                    ->copyable()
                                    ->color(function (Customer $record) {
                                        if ($record && $record->trashed())
                                            return 'danger';
                                        return '';
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('customer_type')
                                    ->label('Tipe')
                                    ->formatStateUsing(fn(string $state): string => $state === 'company' ? 'Perusahaan (B2B)' : 'Perorangan (B2C)')
                                    ->badge()
                                    ->color(fn(string $state): string => $state === 'company' ? 'success' : 'info')
                                    ->placeholder('—'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->url(fn($record) => $record->email ? "mailto:{$record->email}" : null)
                                    ->placeholder('—')
                                    ->color('warning'),

                                TextEntry::make('phone')
                                    ->label('No. WhatsApp')
                                    ->url(fn($record) => $record->phone ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $record->phone) : null, true)
                                    ->placeholder('—')
                                    ->color('success'),

                                TextEntry::make('address')
                                    ->label(fn(Customer $record) => $record->customer_type === 'company' ? 'Alamat Kantor' : 'Alamat Domisili')
                                    ->columnSpanFull()
                                    ->placeholder('Tidak ada data alamat'),
                            ]),
                    ])
                    ->columns(1),

                // SECTION TAMBAHAN UNTUK CUSTOMER (NIK/NPWP)
                Section::make('Informasi Legalitas')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nik')
                                    ->label('NIK (KTP)')
                                    ->visible(fn(Customer $record) => $record->customer_type === 'individual')
                                    ->placeholder('—'),

                                TextEntry::make('npwp')
                                    ->label('NPWP Perusahaan')
                                    ->visible(fn(Customer $record) => $record->customer_type === 'company')
                                    ->placeholder('—'),
                            ]),
                    ])
                    ->visible(fn(Customer $record) => filled($record->nik) || filled($record->npwp)),

                // SECTION PIC (Hanya muncul jika company)
                Section::make('Informasi PIC (Person In Charge)')
                    ->description('Data narahubung dari pihak Perusahaan')
                    ->visible(fn(Customer $record) => $record->customer_type === 'company')
                    ->schema([
                        Grid::make(3) // Dibuat 3 karena tidak ada field pic_email di model customer
                            ->schema([
                                TextEntry::make('pic_name')
                                    ->label('Nama PIC')
                                    ->weight('medium')
                                    ->placeholder('—'),

                                TextEntry::make('pic_position')
                                    ->label('Jabatan PIC')
                                    ->placeholder('—'),

                                TextEntry::make('pic_phone')
                                    ->label('No. WhatsApp PIC')
                                    ->url(fn($record) => $record->pic_phone ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $record->pic_phone) : null, true)
                                    ->placeholder('—')
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('Status & Klasifikasi')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status Customer')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                    ->color(fn(string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'lost' => 'danger',
                                        default => 'gray',
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('deals_count')
                                    ->label('Jumlah Deal')
                                    ->getStateUsing(fn(Customer $record) => $record->deals()->withTrashed()->count())
                                    ->formatStateUsing(function ($state, Customer $record) {
                                        $trashed = $record->deals()->onlyTrashed()->count();
                                        return $trashed > 0 ? "{$trashed} Deal Terhapus" : "{$state} Deal";
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('source')
                                    ->label('Sumber')
                                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                                    ->placeholder('—'),
                            ]),
                    ]),

                // Section::make('Daftar Deal Terkait')
                //     ->icon('heroicon-o-briefcase')
                //     ->schema([
                //         TextEntry::make('deals_list_view')
                //             ->hiddenLabel()
                //             ->columnSpanFull()
                //             ->html()
                //             ->getStateUsing(function (Customer $record) {
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
                //                     <div class='flex flex-col justify-between p-4 rounded-lg ring-1 ring-inset shadow-sm {$containerClass} transition duration-150 ease-in-out'>
                //                         <div class='flex items-start justify-between mb-2'>
                //                             <div>
                //                                 <span class='font-bold text-sm block {$textClass}'>{$dealNumber}</span>
                //                                 <span class='text-xs opacity-75 {$textClass}'>{$date}</span>
                //                                 {$deletedDate}
                //                             </div>
                //                             <span class='inline-flex items-center rounded-md px-2 py-1 text-xs ring-1 ring-inset shadow-sm capitalize {$badgeClass}'>
                //                                 {$statusLabel}
                //                             </span>
                //                         </div>

                //                         <div class='flex items-end justify-between pt-3 mt-3 border-t border-black/20 dark:border-white/20'>
                //                             <div class='text-xs {$textClass}'>
                //                                 <p class='opacity-70 uppercase tracking-wider text-[10px]'>Stage</p>
                //                                 <p class='text-sm font-semibold'>{$stageName}</p>
                //                             </div>
                //                             <div class='text-xs text-right {$textClass}'>
                //                                 <p class='opacity-70 uppercase tracking-wider text-[10px]'>Est. Value</p>
                //                                 <p class='text-sm font-semibold'>{$value}</p>
                //                             </div>
                //                         </div>
                //                     </div>";
                //                 }

                //                 $html .= '</div>';

                //                 return new HtmlString($html);
                //             }),
                //     ])
                //     ->visible(fn(Customer $record) => $record && $record->deals()->withTrashed()->exists()),

                Section::make('Daftar Penawaran Terkait')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('quotations_list_view')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->html()
                            ->getStateUsing(function (Customer $record) {
                                $quotations = $record->quotations()->with('deal')->withTrashed()->latest()->get();

                                if ($quotations->isEmpty()) {
                                    return new HtmlString(
                                        '<p class="text-sm italic text-gray-500">Belum ada penawaran yang dibuat.</p>'
                                    );
                                }

                                $html = '<div class="grid w-full grid-cols-1 gap-4 card-repeater md:grid-cols-2">';

                                foreach ($quotations as $quotation) {
                                    $isDeleted = $quotation->trashed();
                                    $status = $isDeleted ? 'deleted' : $quotation->status;

                                    $containerClass = match ($status) {
                                        'accepted' => 'fi-color-success bg-success-100/40 text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
                                        'rejected', 'deleted' => 'fi-color-danger bg-danger-100/40 text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
                                        'sent', 'negotiation' => 'fi-color-warning bg-warning-100/40 text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
                                        default => 'fi-color-gray bg-gray-100/40 text-gray-600 ring-gray-600/30 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
                                    };

                                    $badgeClass = match ($status) {
                                        'accepted' => 'fi-color-success bg-white text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
                                        'rejected', 'deleted' => 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
                                        'sent', 'negotiation' => 'fi-color-warning bg-white text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
                                        default => 'fi-color-gray bg-white text-gray-600 ring-gray-600/30 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
                                    };

                                    $textClass = 'text-gray-700 dark:text-white';
                                    $statusLabel = $isDeleted ? 'Dihapus' : ucfirst($quotation->status);

                                    $quotationNumber = $quotation->quotation_number;
                                    $dealNumber = $quotation->deal?->deal_number ?? 'N/A';
                                    $grandTotal = 'IDR ' . number_format($quotation->grand_total, 0, ',', '.');
                                    $date = $quotation->quotation_date->format('d M Y');
                                    $deletedDate = $isDeleted ? '<br><span class="text-xs font-semibold text-danger-600 dark:text-danger-400">Dihapus: ' . $quotation->deleted_at->format('d M Y') . '</span>' : '';

                                    $html .= "
                                    <div class='flex flex-col justify-between p-4 rounded-lg ring-1 ring-inset shadow-sm {$containerClass} transition duration-150 ease-in-out'>
                                        <div class='flex items-start justify-between mb-2'>
                                            <div>
                                                <span class='font-bold text-sm block {$textClass}'>{$quotationNumber}</span>
                                                <span class='text-xs opacity-75 {$textClass}'>{$date}</span>
                                                {$deletedDate}
                                            </div>
                                            <span class='inline-flex items-center rounded-md px-2 py-1 text-xs ring-1 ring-inset shadow-sm capitalize {$badgeClass}'>
                                                {$statusLabel}
                                            </span>
                                        </div>
                                        
                                        <div class='flex items-end justify-between pt-3 mt-3 border-t border-black/20 dark:border-white/20'>
                                            <div class='text-xs {$textClass}'>
                                                <p class='opacity-70 uppercase tracking-wider text-[10px]'>Deal Ref.</p>
                                                <p class='text-sm font-semibold'>{$dealNumber}</p>
                                            </div>
                                            <div class='text-xs text-right {$textClass}'>
                                                <p class='opacity-70 uppercase tracking-wider text-[10px]'>Grand Total</p>
                                                <p class='text-sm font-semibold'>{$grandTotal}</p>
                                            </div>
                                        </div>
                                    </div>";
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ])
                    ->visible(fn(Customer $record) => $record && $record->quotations()->withTrashed()->exists()),

                Section::make('Pengelolaan Data')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Masuk Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d M Y H:i')
                            ->visible(fn(Customer $record) => $record->trashed())
                            ->color('danger'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
