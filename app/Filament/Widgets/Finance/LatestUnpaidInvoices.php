<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Sales\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class LatestUnpaidInvoices extends BaseWidget
{
    protected static ?string $heading = 'Outstanding Receivables';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'xl' => 6,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->whereIn('status', ['sent', 'partial'])
                    ->orderBy('due_date')
                    ->limit(5)
            )

            ->striped()

            ->columns([

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->weight('semibold')
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->limit(20),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->badge()
                    ->icon(
                        fn ($state) =>
                        Carbon::parse($state)->isPast()
                            ? 'heroicon-m-exclamation-triangle'
                            : 'heroicon-m-clock'
                    )
                    ->color(
                        fn ($state) =>
                        Carbon::parse($state)->isPast()
                            ? 'danger'
                            : 'warning'
                    ),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Outstanding')
                    ->money('IDR', locale: 'id')
                    ->alignRight()
                    ->weight('bold')
                    ->color('danger'),
            ])

            ->paginated(false);
    }
}
