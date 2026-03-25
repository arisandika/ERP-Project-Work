<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\Finance\FinancialRecord;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_payment')
                ->label('Tambah Pembayaran')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->visible(fn(Invoice $record) => in_array($record->status, ['sent', 'partial']))
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Jumlah Bayar')
                        ->numeric()
                        ->prefix('IDR')
                        ->required()
                        ->maxValue(fn(Invoice $record) => $record->remaining_balance)
                        ->default(fn(Invoice $record) => $record->remaining_balance),

                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Tanggal Bayar')
                        ->default(now())
                        ->required(),

                    Forms\Components\Select::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->options([
                            'transfer' => 'Transfer Bank',
                            'cash' => 'Tunai',
                            'credit_card' => 'Kartu Kredit',
                            'qris' => 'QRIS',
                        ])
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(2),
                ])
                ->action(function (Invoice $record, array $data) {
                    DB::transaction(function () use ($record, $data) {

                        // 1. Buat record payment (payment_number di-generate oleh Model)
                        $payment = Payment::create([
                            'nx_invoice_id' => $record->id,
                            'amount' => $data['amount'],
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'notes' => $data['notes'],
                            'created_by' => auth()->id(),
                        ]);

                        // 2. Buat FinancialRecord
                        FinancialRecord::create([
                            'transaction_date' => $payment->payment_date,
                            'type'             => 'pemasukan',
                            'amount'           => $payment->amount,
                            'category'         => 'Sales Revenue',
                            'description'      => 'Pembayaran Invoice dari Klien: ' . ($record->customer->name ?? '-') . ' via ' . strtoupper($payment->payment_method),
                            'reference_number' => $payment->payment_number, // Panggil dari object $payment langsung
                            'reference_type'   => Invoice::class,
                            'reference_id'     => $record->id,
                            'created_by'       => auth()->id() ?? 1,
                        ]);

                    });

                    Notification::make()
                        ->title('Pembayaran Berhasil Dicatat')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('download_pdf')
                ->label('Cetak Invoice')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->action(function (Invoice $record) {
                    $validationUrl = route('invoice.verify.form', ['number' => $record->invoice_number]);
                    $qrCode = new QrCode(data: $validationUrl, encoding: new Encoding('UTF-8'), size: 200, margin: 10);
                    $writer = new PngWriter();
                    $qrBase64 = base64_encode($writer->write($qrCode)->getString());

                    $generator = new BarcodeGeneratorPNG();
                    $barBase64 = base64_encode($generator->getBarcode($record->invoice_number, $generator::TYPE_CODE_128));

                    $pdf = Pdf::loadView('pdf.invoice', [
                        'invoice' => $record,
                        'qrCode' => $qrBase64,
                        'barcode' => $barBase64,
                    ]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Invoice-' . Str::slug($record->invoice_number) . '.pdf');
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $record->update($data);

        if (!empty($items)) {
            $existingItemIds = [];

            foreach ($items as $item) {
                if (isset($item['id'])) {
                    $record->items()->where('id', $item['id'])->update([
                        'qty' => $item['qty'],
                        'unit_price' => $item['unit_price'],
                        'line_total' => $item['line_total'],
                    ]);
                    $existingItemIds[] = $item['id'];
                } else {
                    $newItem = $record->items()->create([
                        'item_type' => $item['item_type'] ?? null,
                        'item_id' => $item['item_id'] ?? null,
                        'item_code' => $item['item_code'] ?? null,
                        'item_name' => $item['item_name'] ?? null,
                        'qty' => $item['qty'] ?? 0,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'line_total' => $item['line_total'] ?? 0,
                    ]);
                    $existingItemIds[] = $newItem->id;
                }
            }

            $record->items()->whereNotIn('id', $existingItemIds)->delete();
        }

        return $record;
    }

}
