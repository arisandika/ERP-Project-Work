<?php
namespace App\Filament\Widgets\Sales;

use App\Models\Sales\Invoice;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class InvoiceReportTable extends BaseWidget
{
    protected static ?string $heading = 'Invoice Terbaru';
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 122,
        'xl' => 12,
    ];

    public $filters = [];

    protected function getTableQuery(): Builder
    {
        $startDate = $this->filters['start_date'] ?? now()->startOfMonth();
        $endDate = $this->filters['end_date'] ?? now();

        return Invoice::query()
            ->with('customer')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->latest('invoice_date');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('invoice_number')
                ->label('No. Invoice')
                ->searchable()
                ->weight('bold')
                ->copyable(),
            Tables\Columns\TextColumn::make('customer.name')
                ->label('Customer')
                ->limit(20)
                ->icon('heroicon-m-user'),
            Tables\Columns\TextColumn::make('invoice_date')
                ->label('Tanggal')
                ->date('d M Y')
                ->sortable(),
            Tables\Columns\TextColumn::make('grand_total')
                ->label('Total Tagihan')
                ->money('IDR', locale: 'id')
                ->alignRight()
                ->weight('bold')
                ->color(fn($state) => $state > 0 ? 'success' : 'danger'),
            Tables\Columns\TextColumn::make('remaining_balance')
                ->label('Sisa Bayar')
                ->money('IDR', locale: 'id')
                ->alignRight()
                ->color(fn($state) => $state > 0 ? 'danger' : 'success'),
            Tables\Columns\TextColumn::make('due_date')
                ->label('Jatuh Tempo')
                ->date('d M Y')
                ->badge()
                ->icon(fn($record) => $record->due_date < now() && $record->status !== 'paid'
                    ? 'heroicon-m-exclamation-triangle'
                    : 'heroicon-m-clock')
                ->color(fn($record) => $record->due_date < now() && $record->status !== 'paid'
                    ? 'danger'
                    : 'warning'),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'draft' => 'gray',
                    'sent' => 'info',
                    'unpaid' => 'danger',
                    'partial' => 'warning',
                    'paid' => 'success',
                    default => 'gray',
                })
                ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->label('Lihat')
                ->icon('heroicon-m-eye')
                ->url(fn($record) => route('filament.admin.resources.sales.invoices.view', $record)),
        ];
    }
}
