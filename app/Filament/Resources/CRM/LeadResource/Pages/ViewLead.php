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
                                    ->size('lg')
                                    ->copyable()
                                    ->icon('heroicon-o-user')
                                    ->color(function (Lead $record) {
                                        $record->withTrashed()->first();
                                        if ($record && $record->trashed())
                                            return 'danger';
                                        return '';
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('customer_type')
                                    ->label('Tipe Customer')
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-identification'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->url(fn($record) => $record->email ? "mailto:{$record->email}" : null)
                                    ->placeholder('—')
                                    ->color('warning'),

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
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status Deal')
                                    ->badge()
                                    ->getStateUsing(function (Lead $record) {
                                        $allDeals = $record->deals()->withTrashed()->get();
                                        $activeDeals = $allDeals->whereNull('deleted_at');

                                        if ($allDeals->isEmpty()) {
                                            return match ($record->status) {
                                                'new' => 'New',
                                                'contacted' => 'Contacted',
                                                'qualified' => 'Qualified',
                                                default => ucfirst($record->status),
                                            };
                                        }

                                        if ($activeDeals->isEmpty()) {
                                            return 'All Deals Deleted';
                                        }

                                        if ($activeDeals->contains('status', 'open')) {
                                            return 'Active Process';
                                        }
                                        if ($activeDeals->contains('status', 'won')) {
                                            return 'Existing Customer';
                                        }

                                        return 'Lost Prospect';
                                    })
                                    ->color(fn(string $state): string => match ($state) {
                                        'Active Process' => 'info',
                                        'Existing Customer' => 'success',
                                        'Lost Prospect' => 'danger',
                                        'All Deals Deleted' => 'danger',
                                        'New' => 'primary',
                                        'Contacted' => 'warning',
                                        'Qualified' => 'success',
                                        default => 'gray',
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('deals_count')
                                    ->label('Jumlah Deal')
                                    ->badge()
                                    ->getStateUsing(fn($record) => $record->deals()->withTrashed()->count())
                                    ->color(function (Lead $record, int $state): string {
                                        return $record->deals()->onlyTrashed()->exists() ? 'danger' : 'info';
                                    })
                                    ->formatStateUsing(function ($state, Lead $record) {
                                        $trashed = $record->deals()->onlyTrashed()->count();
                                        return $trashed > 0 ? "{$trashed} Deal Terhapus" : "{$state} Deal";
                                    })
                                    ->placeholder('—'),

                                TextEntry::make('source')
                                    ->label('Sumber')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                                    ->placeholder('—'),

                                TextEntry::make('convertedCustomer.name')
                                    ->label('Converted To')
                                    ->placeholder('Belum dikonversi')
                                    ->badge()
                                    ->color('success')
                                    ->visible(fn($record) => $record->status === 'converted')
                                    ->icon('heroicon-o-user-group'),
                            ]),
                    ]),

                Section::make('Daftar Deal Terkait')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        TextEntry::make('deals_list_view')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->html()
                            ->getStateUsing(function ($record) {
                                $deals = $record->deals()->withTrashed()->with('stage')->latest()->get();

                                if ($deals->isEmpty()) {
                                    return new HtmlString(
                                        '<p class="text-sm italic text-gray-500">Belum ada deal yang dibuat.</p>'
                                    );
                                }

                                $html = '<div class="grid w-full grid-cols-1 gap-4 card-repeater md:grid-cols-2">';

                                foreach ($deals as $deal) {
                                    $isDeleted = $deal->trashed();

                                    if ($isDeleted) {
                                        $containerClass = 'bg-danger-50 ring-danger-600/30 dark:bg-danger-900/20 dark:ring-danger-500/30 border-danger-200';
                                        $badgeClass = 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger';
                                        $textClass = 'text-gray-700 dark:text-white';
                                        $statusLabel = 'Terhapus';
                                    } else {
                                        $containerClass = match ($deal->status) {
                                            'won' => 'fi-color-success bg-success-100/40 text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                                            'lost' => 'fi-color-danger bg-danger-100/40 text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                                            default => 'fi-color-warning bg-warning-100/40 text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30 fi-color-warning',
                                        };

                                        $badgeClass = match ($deal->status) {
                                            'won' => 'fi-color-success bg-white text-success-600 ring-success-600/30 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                                            'lost' => 'fi-color-danger bg-white text-danger-600 ring-danger-600/30 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                                            default => 'fi-color-warning bg-white text-warning-600 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30 fi-color-warning',
                                        };

                                        $textClass = 'text-gray-700 dark:text-white';
                                        $statusLabel = ucfirst($deal->status);
                                    }

                                    $stageName = $deal->stage->name ?? '-';
                                    $dealNumber = $deal->deal_number;
                                    $value = 'IDR ' . number_format($deal->estimated_value, 0, ',', '.');
                                    $date = $deal->created_at->format('d M Y');
                                    $deletedDate = $isDeleted ? '<br><span class="text-xs font-semibold text-danger-600 dark:text-danger-400">Dihapus: ' . $deal->deleted_at->format('d M Y') . '</span>' : '';

                                    $html .= "
                                    <div class='flex flex-col justify-between p-4 rounded-lg ring-1 ring-inset shadow-sm {$containerClass} transition duration-150 ease-in-out'>
                                        <div class='flex items-start justify-between mb-2'>
                                            <div>
                                                <span class='font-bold text-sm block {$textClass}'>{$dealNumber}</span>
                                                <span class='text-xs opacity-75 {$textClass}'>{$date}</span>
                                                {$deletedDate}
                                            </div>
                                            <span class='inline-flex items-center rounded-md px-2 py-1 text-xs ring-1 ring-inset shadow-sm capitalize {$badgeClass}'>
                                                {$statusLabel}
                                            </span>
                                        </div>
                                        
                                        <div class='flex items-end justify-between pt-3 mt-3 border-t border-black/20 dark:border-white/20'>
                                            <div class='text-xs {$textClass}'>
                                                <p class='opacity-70 uppercase tracking-wider text-[10px]'>Stage</p>
                                                <p class='text-sm font-semibold'>{$stageName}</p>
                                            </div>
                                            <div class='text-xs text-right {$textClass}'>
                                                <p class='opacity-70 uppercase tracking-wider text-[10px]'>Est. Value</p>
                                                <p class='text-sm font-semibold'>{$value}</p>
                                            </div>
                                        </div>
                                    </div>";
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ])
                    ->visible(fn($record) => $record && $record->deals()->withTrashed()->exists()),

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
                            ->label('Masuk Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d M Y H:i')
                            ->visible(fn($record) => $record->trashed())
                            ->color('danger'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
