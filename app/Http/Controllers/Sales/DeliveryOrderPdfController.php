<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

class DeliveryOrderPdfController extends Controller
{
    public function print(DeliveryOrder $record)
    {
        // 1. Load Relasi
        $record->load(['items', 'customer', 'salesOrder', 'employee']);

        // 2. Update Status jadi On Delivery (jika baru pertama dicetak)
        if (in_array($record->status, ['draft', 'ready'])) {
            $record->update(['status' => 'on_delivery']);
        }

        // 3. Generate QR Code ke halaman public tracking
        $trackingUrl = route('tracking.delivery-order', ['do_number' => $record->do_number]);
        $qrCode = new QrCode(data: $trackingUrl, encoding: new Encoding('UTF-8'), size: 150, margin: 0);
        $writer = new PngWriter();
        $qrBase64 = base64_encode($writer->write($qrCode)->getString());

        // 4. Setup Nama File
        $safeNumber = str_replace(['/', '\\'], '-', $record->do_number);
        $filename   = "DO-{$safeNumber}.pdf";

        // 5. Return PDF Stream (buka di tab baru)
        return Pdf::loadView('pdf.delivery-order', [
            'record' => $record,
            'qrCode' => $qrBase64,
            'do'     => $record,
        ])->stream($filename);
    }
}
