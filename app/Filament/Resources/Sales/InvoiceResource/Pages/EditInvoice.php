<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use Filament\Actions; // Perhatikan: Ini Actions Halaman, bukan Table
use Filament\Resources\Pages\EditRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Custom Action: Download PDF (Sama seperti di Table)
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success') // Memberi warna hijau biar menonjol
                ->action(function ($record) {
                    // $record otomatis mengambil data Invoice yang sedang diedit

                    // Generate Barcode
                    $generator = new BarcodeGeneratorPNG();
                    $barcodeData = $generator->getBarcode($record->invoice_number, $generator::TYPE_CODE_128);
                    $barcodeBase64 = base64_encode($barcodeData);

                    // Load PDF View
                    $pdf = Pdf::loadView('pdf.invoice', [
                        'invoice' => $record,
                        'barcode' => $barcodeBase64,
                    ]);

                    // Download
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Invoice-' . $record->invoice_number . '.pdf');
                }),

            // 2. Default Actions
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Opsional: Redirect ke halaman List setelah selesai Edit (Save)
     * Agar admin tidak 'stuck' di halaman form.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
