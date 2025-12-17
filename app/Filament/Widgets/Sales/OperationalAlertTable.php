<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceReminderMail;
use Filament\Notifications\Notification;

class OperationalAlertTable extends BaseWidget
{
    protected static ?string $heading = '⚠️ Jatuh Tempo (Perlu Penagihan)';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    // [PENTING] Select semua kolom agar 'id' terbawa.
                    // Ini kunci agar Filament tidak error "null returned".
                    ->select('*')

                    ->whereDate('due_date', '<', now())
                    ->where(function (Builder $query) {
                        $query->where('status', 'unpaid')
                              ->orWhere('status', 'partial');
                    })
                    ->orderBy('due_date', 'asc')
                    ->limit(5)
            )
            // [HAPUS BARIS INI] ->recordKey('id') // Method ini tidak valid di sini.

            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer'),

                Tables\Columns\TextColumn::make('due_date')
                    ->date('d M Y')
                    ->label('Jatuh Tempo')
                    ->color('danger')
                    ->description(fn (Invoice $record) => $record->due_date->diffForHumans()),

                Tables\Columns\TextColumn::make('grand_total')
                    ->money('IDR')
                    ->label('Total Tagihan'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('sendReminder')
                    ->icon('heroicon-o-envelope')
                    ->label('Email')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Reminder Tagihan?')
                    ->modalDescription('Email reminder akan dikirim ke alamat email customer ini.')
                    ->modalSubmitActionLabel('Ya, Kirim Sekarang')
                    ->action(function (Invoice $record) {
                        $email = $record->customer->email;

                        if (! $email) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Customer ini tidak memiliki alamat email.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Kirim Email
                        try {
                            Mail::to($email)->send(new InvoiceReminderMail($record));

                            Notification::make()
                                ->title('Terkirim')
                                ->body("Reminder untuk Invoice #{$record->invoice_number} berhasil dikirim.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Kirim Email')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->paginated(false);
    }
}
