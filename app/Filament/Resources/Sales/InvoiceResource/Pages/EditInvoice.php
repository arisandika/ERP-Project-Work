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

    protected function getHeaderActions(): array
    {
        return [
            // --- ACTION: INPUT PEMBAYARAN (PARTIAL/FULL) ---
            Actions\Action::make('add_payment')
                ->label('Input Pembayaran')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                // Hanya muncul jika status belum Lunas (paid) dan bukan Draft
                ->visible(fn (Invoice $record) => $record->status !== 'paid' && $record->status !== 'draft')
                ->form([
                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Tanggal Bayar')
                        ->default(now())
                        ->required(),

                    Forms\Components\Select::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->options([
                            'bank_transfer' => 'Transfer Bank',
                            'cash'          => 'Tunai',
                            'cheque'        => 'Cek/Giro',
                            'qris'          => 'QRIS',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('amount')
                        ->label('Jumlah Bayar')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        // Menampilkan sisa tagihan sebagai petunjuk
                        ->helperText(fn (Invoice $record) => 'Sisa Tagihan: Rp ' . number_format($record->remaining_balance ?? 0, 0, ',', '.'))
                        // Validasi: Tidak boleh bayar lebih dari sisa tagihan
                        ->maxValue(fn (Invoice $record) => $record->remaining_balance ?? 0),

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
                        'payment_date'   => $data['payment_date'],
                        'payment_method' => $data['payment_method'],
                        'amount'         => $data['amount'],
                        'notes'          => $data['notes'],
                    ]);

                    // Notifikasi Sukses
                    Notification::make()
                        ->title('Pembayaran Berhasil Disimpan')
                        ->success()
                        ->send();

                    // Refresh halaman agar status terbaru muncul
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                }),

            // --- ACTION: DOWNLOAD PDF ---
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function ($record) {
                    $generator = new BarcodeGeneratorPNG();
                    $barcodeData = $generator->getBarcode($record->invoice_number, $generator::TYPE_CODE_128);
                    $barcodeBase64 = base64_encode($barcodeData);

                    $pdf = Pdf::loadView('pdf.invoice', [
                        'invoice' => $record,
                        'barcode' => $barcodeBase64,
                    ]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Invoice-' . $record->invoice_number . '.pdf');
                }),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    // --- LOGIC 1: LOAD ITEMS KE FORM ---
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(function ($item) {
            return [
                'id'         => $item->id,
                'item_type'  => $item->item_type,
                'item_id'    => $item->item_id,
                'item_code'  => $item->item_code,
                'item_name'  => $item->item_name,
                'qty'        => (float) $item->qty,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ];
        })->toArray();

        return $data;
    }

    // --- LOGIC 2: SMART UPSERT (Update/Insert/Delete Items) ---
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

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
            if (isset($item['id']) && $item['id']) {
                // UPDATE: Jika item punya ID
                $record->items()->where('id', $item['id'])->update([
                    'qty'        => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            } else {
                // CREATE: Jika item baru (ID null)
                $record->items()->create([
                    'item_type'  => $item['item_type'] ?? null,
                    'item_id'    => $item['item_id'] ?? null,
                    'item_code'  => $item['item_code'] ?? null,
                    'item_name'  => $item['item_name'] ?? null,
                    'qty'        => $item['qty'] ?? 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'line_total' => $item['line_total'] ?? 0,
                ]);
            }
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
