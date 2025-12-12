<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class DeliveryOrderPdfController extends Controller
{
    public function print(DeliveryOrder $record)
    {
        // 1. Load Relasi (Eager Loading)
        $record->load(['items', 'customer', 'salesOrder']);

        // 2. Generate Barcode Logic
        $generator = new BarcodeGeneratorPNG();
        $barcodeData = $generator->getBarcode($record->do_number, $generator::TYPE_CODE_128);
        $barcodeBase64 = base64_encode($barcodeData);

        // 3. Setup Nama File
        $safeNumber = str_replace(['/', '\\'], '-', $record->do_number);
        $filename   = "DO-{$safeNumber}.pdf";

        // 4. Return PDF Stream
        return Pdf::loadView('pdf.delivery-order', [
            'record'  => $record,
            'barcode' => $barcodeBase64,
        ])->stream($filename);
    }
}
