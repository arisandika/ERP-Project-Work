<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrintController extends Controller
{
    /**
     * Cetak Delivery Order (Surat Jalan) ke PDF
     */

    public function deliveryOrder($record) // Parameter $record adalah ID
    {
        // Ambil data
        $deliveryOrder = DeliveryOrder::with(['salesOrder', 'customer', 'items', 'employee']) // Tambahkan 'employee' jika ada relasinya
            ->findOrFail($record);

        // Generate Barcode (Opsional, karena di View ada logic @if isset($barcode))
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcodeData = $generator->getBarcode($deliveryOrder->do_number, $generator::TYPE_CODE_128);
        $barcodeBase64 = base64_encode($barcodeData);

        // Load View
        $pdf = Pdf::loadView('pdf.delivery-order', [
            // [PENTING] Gunakan key 'record' agar cocok dengan view Anda
            'record'  => $deliveryOrder,
            'do'      => $deliveryOrder, // (Opsional) Saya kirim dua-duanya biar aman kalau ada view yang pakai $do
            'barcode' => $barcodeBase64,
        ]);

        $safeNumber = Str::slug($deliveryOrder->do_number);
        return $pdf->stream('DeliveryOrder-' . $safeNumber . '.pdf');
    }


    /**
     * Halaman Validasi QR Code Invoice
     */
    public function verifyInvoice($number)
    {
        $invoice = Invoice::with('customer')->where('invoice_number', $number)->firstOrFail();

        // Anda bisa return View cantik di sini nanti.
        // Untuk sekarang return text sederhana dulu:
        return response()->json([
            'status' => 'VALID',
            'message' => 'Dokumen Invoice Asli Terverifikasi',
            'data' => [
                'invoice_number' => $invoice->invoice_number,
                'customer' => $invoice->customer->name,
                'date' => $invoice->invoice_date->format('d M Y'),
                'status_payment' => $invoice->status,
                'total' => 'Rp ' . number_format($invoice->grand_total, 0, ',', '.'),
            ]
        ]);
    }
}
