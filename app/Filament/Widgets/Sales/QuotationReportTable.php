<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\Quotation;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class QuotationReportTable extends BaseWidget
{
    // use HasPageShield;
    
    public $filters = [];

    protected function getTableQuery(): Builder
    {
        $startDate = $this->filters['start_date'] ?? now()->startOfMonth();
        $endDate = $this->filters['end_date'] ?? now();

        return Quotation::query()
            ->whereBetween('quotation_date', [$startDate, $endDate])
            ->latest('quotation_date');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('quotation_date')
                ->label('Tanggal')
                ->date('d M Y')
                ->sortable(),

            Tables\Columns\TextColumn::make('quotation_number')
                ->label('No. Quotation')
                ->searchable(),

            Tables\Columns\TextColumn::make('customer.name')
                ->label('Customer')
                ->searchable(),

            Tables\Columns\TextColumn::make('grand_total')
                ->label('Nilai')
                ->money('IDR')
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'sent' => 'warning',
                    'accepted' => 'success',
                    'rejected' => 'danger',
                }),

            Tables\Columns\TextColumn::make('valid_until')
                ->label('Berlaku Sampai')
                ->date('d M Y')
                ->color('danger'),
        ];
    }
}
