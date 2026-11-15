<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Filament\Actions;
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

// trait/helper yang dipakai CreateInvoice & EditInvoice
    protected function recalculateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = (float) $invoice->items()
            ->selectRaw('COALESCE(SUM(qty * unit_price), 0) as total')
            ->value('total');

        $discount = min((float) ($invoice->discount ?? 0), $subtotal);
        $tax      = max(0, min((float) ($invoice->tax ?? 0), 100));

        $afterDiscount = $subtotal - $discount;

        $invoice->forceFill([
            'subtotal'    => round($subtotal, 2),
            'discount'    => round($discount, 2),
            'tax'         => round($tax, 2),
            'grand_total' => round($afterDiscount + ($afterDiscount * $tax / 100), 2),
        ])->saveQuietly();
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $this->recalculateInvoiceTotals($record);
        $record->refresh()->recalculateStatus();
    }
}

