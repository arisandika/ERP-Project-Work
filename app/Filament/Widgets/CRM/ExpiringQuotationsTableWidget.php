<?php
namespace App\Filament\Widgets\CRM;

use App\Models\Sales\Quotation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpiringQuotationsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Penawaran Segera Kedaluwarsa';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quotation::query()
                    ->whereIn('status', ['new', 'sent', 'negotiation'])
                    ->whereDate('valid_until', '<=', now()->addDays(3))
                    ->orderBy('valid_until')
            )
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->label('No. Penawaran')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Klien')
                    ->state(function (Quotation $record) {
                        $deal = $record->deal()->withTrashed()->first();
                        return $deal?->customer?->name ?? $deal?->lead()->withTrashed()->first()?->name ?? '-';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'new'         => 'gray',
                        'sent'        => 'warning',
                        'negotiation' => 'info',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Berlaku Hingga')
                    ->date('d M Y')
                    ->color(fn($record) => \Carbon\Carbon::parse($record->valid_until)->isPast() ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Nilai')
                    ->money('IDR')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('internalPic.full_name')
                    ->label('PIC')
                    ->icon('heroicon-o-user'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Follow Up')
                    ->icon('heroicon-o-phone')
                    ->color('warning')
                    ->url(fn(Quotation $record) => \App\Filament\Resources\Sales\QuotationResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Tidak ada penawaran yang segera expired');
    }
}
