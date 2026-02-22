<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return 'Edit Invoice';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_payment')
                ->label('Input Pembayaran')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                // Hanya muncul jika status belum Lunas (paid) dan bukan Draft
                ->visible(fn(Invoice $record) => $record->status !== 'paid' && $record->status !== 'draft')
                ->form([
                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Tanggal Bayar')
                        ->default(now())
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->required()
                        ->displayFormat('d M Y')
                        ->native(false),

                    Forms\Components\Select::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->options([
                            'bank_transfer' => 'Transfer Bank',
                            'cash' => 'Tunai',
                            'cheque' => 'Cek/Giro',
                            'qris' => 'QRIS',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('amount')
                        ->label('Jumlah Bayar')
                        ->numeric()
                        ->prefix('IDR')
                        ->required()
                        // Menampilkan sisa tagihan sebagai petunjuk
                        ->helperText(fn(Invoice $record) => 'Sisa Tagihan: IDR ' . number_format($record->remaining_balance ?? 0, 0, ',', '.'))
                        // Validasi: Tidak boleh bayar lebih dari sisa tagihan
                        ->maxValue(fn(Invoice $record) => $record->remaining_balance ?? 0),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan'),
                ])
                ->action(function (Invoice $record, array $data) {
                    // Generate Nomor Pembayaran Unik
                    $count = $record->payments()->count() + 1;
                    $paymentNo = 'PAY-' . str_replace('/', '-', $record->invoice_number) . '-' . $count;

                    // Simpan ke Tabel Payments
                    $record->payments()->create([
                        'payment_number' => $paymentNo,
                        'payment_date' => $data['payment_date'],
                        'payment_method' => $data['payment_method'],
                        'amount' => $data['amount'],
                        'notes' => $data['notes'],
                    ]);

                    // Notifikasi Sukses
                    Notification::make()
                        ->title('Pembayaran Berhasil Disimpan')
                        ->success()
                        ->send();

                    // Refresh halaman agar status terbaru muncul
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                }),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_type' => $item->item_type,
                'item_id' => $item->item_id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'qty' => (float) $item->qty,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Update data header invoice
        $record->update($data);

        // 1. Kumpulkan ID item yang ada di form (yang tidak dihapus user)
        $keepIds = collect($items)
            ->pluck('id')
            ->filter()
            ->toArray();

        // 2. Hapus item di DB yang tidak ada di form
        $record->items()->whereNotIn('id', $keepIds)->delete();

        // 3. Loop items untuk update atau create
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $lineTotal = $qty * $price;

            if (isset($item['id']) && $item['id']) {
                // UPDATE: Jika item punya ID
                $record->items()->where('id', $item['id'])->update([
                    'qty' => $qty,
                    'unit_price' => $price,
                    'line_total' => $lineTotal, // Gunakan hasil hitung server
                ]);
            } else {
                // CREATE: Jika item baru (ID null)
                $record->items()->create([
                    'item_type' => $item['item_type'] ?? null,
                    'item_id' => $item['item_id'] ?? null,
                    'item_code' => $item['item_code'] ?? null,
                    'item_name' => $item['item_name'] ?? null,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'line_total' => $lineTotal, // Gunakan hasil hitung server
                ]);
            }
        }

        return $record;
    }
}
