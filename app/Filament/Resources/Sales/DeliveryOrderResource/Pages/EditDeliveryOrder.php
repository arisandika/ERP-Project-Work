<?php

namespace App\Filament\Resources\Sales\DeliveryOrderResource\Pages;

use App\Filament\Resources\Sales\DeliveryOrderResource;
use App\Models\Sales\DeliveryOrder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class EditDeliveryOrder extends EditRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Tombol Cetak Surat Jalan (PENTING)
            Actions\Action::make('print_do')
                ->label('Cetak Surat Jalan')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function (DeliveryOrder $record) {

                    // Generate Barcode (Agar gudang penerima bisa scan tanda terima)
                    $generator = new BarcodeGeneratorPNG();
                    $barcodeData = $generator->getBarcode($record->do_number, $generator::TYPE_CODE_128);
                    $barcodeBase64 = base64_encode($barcodeData);

                    // Load PDF (Pastikan Anda buat view 'pdf.delivery-order')
                    $pdf = Pdf::loadView('pdf.delivery_order', [
                        'do'      => $record,
                        'barcode' => $barcodeBase64,
                    ]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Surat-Jalan-' . $record->do_number . '.pdf');
                }),

            // 2. Action Standar
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
