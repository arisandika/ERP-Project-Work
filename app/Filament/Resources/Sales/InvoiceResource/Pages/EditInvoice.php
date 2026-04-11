<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;

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
                ->visible(fn (Invoice $record) => in_array($record->status, ['sent', 'partial'], true))
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Jumlah Bayar')
                        ->numeric()
                        ->prefix('IDR')
                        ->required()
                        ->minValue(0.01)
                        ->maxValue(fn (Invoice $record) => $record->remaining_balance)
                        ->default(fn (Invoice $record) => $record->remaining_balance),

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
                        Payment::create([
                            'nx_invoice_id' => $record->id,
                            'amount' => (float) $data['amount'],
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'status' => 'paid',
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
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

                    $qrCode = new QrCode(
                        data: $validationUrl,
                        encoding: new Encoding('UTF-8'),
                        size: 200,
                        margin: 10
                    );

                    $writer = new PngWriter();
                    $qrBase64 = base64_encode($writer->write($qrCode)->getString());

                    $generator = new BarcodeGeneratorPNG();
                    $barBase64 = base64_encode(
                        $generator->getBarcode($record->invoice_number, $generator::TYPE_CODE_128)
                    );

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
        return DB::transaction(function () use ($record, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $subtotal = collect($items)->sum(function ($item) {
                $qty = (float) ($item['qty'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);

                return $qty * $price;
            });

            $discount = min((float) ($data['discount'] ?? 0), $subtotal);
            $tax = max(0, min((float) ($data['tax'] ?? 0), 100));
            $afterDiscount = $subtotal - $discount;
            $grandTotal = $afterDiscount + ($afterDiscount * ($tax / 100));

            $data['subtotal'] = round($subtotal, 2);
            $data['discount'] = round($discount, 2);
            $data['tax'] = round($tax, 2);
            $data['grand_total'] = round($grandTotal, 2);

            $record->update($data);

            $existingItemIds = [];

            foreach ($items as $item) {
                $qty = (float) ($item['qty'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);

                if (isset($item['id']) && $item['id']) {
                    $record->items()->where('id', $item['id'])->update([
                        'qty' => (int) $qty,
                        'unit_price' => round($price, 2),
                        'line_total' => round($qty * $price, 2),
                    ]);

                    $existingItemIds[] = $item['id'];
                } else {
                    $newItem = $record->items()->create([
                        'item_type' => $item['item_type'] ?? null,
                        'item_id' => $item['item_id'] ?? null,
                        'item_code' => $item['item_code'] ?? null,
                        'item_name' => $item['item_name'] ?? null,
                        'qty' => (int) $qty,
                        'unit_price' => round($price, 2),
                        'line_total' => round($qty * $price, 2),
                    ]);

                    $existingItemIds[] = $newItem->id;
                }
            }

            if (!empty($existingItemIds)) {
                $record->items()->whereNotIn('id', $existingItemIds)->delete();
            }

            $record->refresh()->recalculateStatus();

            return $record;
        });
    }
}
