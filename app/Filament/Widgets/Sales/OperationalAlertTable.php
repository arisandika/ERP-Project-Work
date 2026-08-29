<?php

namespace App\Filament\Widgets\Sales;

use App\Mail\InvoiceReminderMail;
use App\Models\Sales\Invoice;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class OperationalAlertTable extends BaseWidget
{
    protected static ?string $heading = 'Jatuh Tempo (Perlu Penagihan)';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->select('*')
                    ->whereDate('due_date', '<', now())
                    ->where(function (Builder $query) {
                        $query
                            ->where('status', 'unpaid')
                            ->orWhere('status', 'partial');
                    })
                    ->orderBy('due_date', 'asc')
                    ->limit(5)
            )
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
                    ->description(fn(Invoice $record) => $record->due_date->diffForHumans()),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
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

                        if (!$email) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Customer ini tidak memiliki alamat email.')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            Mail::to($email)->queue(new InvoiceReminderMail($record));

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
