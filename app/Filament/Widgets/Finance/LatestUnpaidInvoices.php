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
                Invoice::whereIn('status', ['sent', 'partial'])
                    ->orderBy('due_date', 'asc')
                    ->limit(5) // Dibatasi 5 dari database langsung
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice') // Teks dipendekkan
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm'), // Ukuran font dikecilkan agar muat

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Klien')
                    ->limit(15) // Jika nama PT kepanjangan, dipotong pake titik-titik
                    ->size('sm'), // Hapus searchable() agar bar pencarian hilang

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tempo')
                    ->date('d M y') // Format tanggal dipendekkan (ex: 12 Mar 26)
                    ->badge()
                    ->color(fn ($state) => Carbon::parse($state)->isPast() ? 'danger' : 'warning')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Sisa')
                    ->money('IDR', true)
                    ->color('danger')
                    ->weight('bold')
                    ->size('sm'),
            ])
            ->paginated(false) // HAPUS fungsi paginasi di bawah tabel
            ->striped(); // Tambahkan efek zebra / belang-belang
    }
}
