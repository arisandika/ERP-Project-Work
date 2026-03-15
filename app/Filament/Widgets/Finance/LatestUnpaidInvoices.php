<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Sales\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class LatestUnpaidInvoices extends BaseWidget
{
    protected static ?string $heading = '🔴 Piutang Klien (A/R) Jatuh Tempo';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // FIX: Cukup gunakan filter status, hapus filter remaining_balance
                Invoice::whereIn('status', ['sent', 'partial'])
                    ->orderBy('due_date', 'asc') // Urutkan dari tanggal jatuh tempo paling lama!
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->weight('bold')
                    ->color('primary')
                    // Bisa diklik langsung menuju halaman Invoice
                    ->url(fn (Invoice $record): string => \App\Filament\Resources\Sales\InvoiceResource::getUrl('view', ['record' => $record])),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Nama Klien')
                    ->searchable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->badge()
                    // MAGIC: Warnai Merah kalau hari ini sudah melewati jatuh tempo!
                    ->color(fn ($state) => Carbon::parse($state)->isPast() ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Sisa Tagihan')
                    ->money('IDR', true)
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->paginated([5]) // Batasi 5 baris saja agar Dashboard rapi
            ->defaultPaginationPageOption(5);
    }
}
