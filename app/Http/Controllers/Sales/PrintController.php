<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PrintController extends Controller
{
    /**
     * Cetak Delivery Order (Surat Jalan) ke PDF
     */
    public function deliveryOrder($record)
    {
        $deliveryOrder = DeliveryOrder::with(['salesOrder', 'customer', 'items', 'employee'])
            ->findOrFail($record);

        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcodeData = $generator->getBarcode($deliveryOrder->do_number, $generator::TYPE_CODE_128);
        $barcodeBase64 = base64_encode($barcodeData);

        $pdf = Pdf::loadView('pdf.delivery-order', [
            'record'  => $deliveryOrder,
            'do'      => $deliveryOrder,
            'barcode' => $barcodeBase64,
        ]);

        $safeNumber = Str::slug($deliveryOrder->do_number);
        return $pdf->stream('DeliveryOrder-' . $safeNumber . '.pdf');
    }

    /**
     * GET: tampilkan form verifikasi (public)
     */
    public function showVerifyForm(string $number)
    {
        $invoice = Invoice::where('invoice_number', $number)->firstOrFail();

        return view('verification.invoice', [
            'invoiceNumber' => $invoice->invoice_number,
        ]);
    }

    /**
     * POST: proses verifikasi email + no hp (public, tapi wajib throttle di route)
     */
    public function submitVerify(Request $request, string $number)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
        ]); // validate() otomatis redirect balik dengan error kalau gagal [web:40]

        $invoice = Invoice::with('customer')
            ->where('invoice_number', $number)
            ->firstOrFail();

        // Normalisasi biar input 08 / +62 / spasi tidak bikin false negative
        $inputEmail = mb_strtolower(trim($validated['email']));
        $inputPhone = preg_replace('/\D+/', '', $validated['phone']);

        $dbEmail = mb_strtolower(trim((string) ($invoice->customer->email ?? '')));
        $dbPhone = preg_replace('/\D+/', '', (string) ($invoice->customer->phone ?? ''));

        if ($inputEmail !== $dbEmail || $inputPhone !== $dbPhone) {
            return back()
                ->withErrors(['email' => 'Email atau No HP tidak cocok dengan data customer.'])
                ->withInput();
        }

        // Generate signed URL sementara (misal 15 menit)
        $signedUrl = URL::temporarySignedRoute(
            'invoice.view',
            now()->addMinutes(15),
            ['number' => $invoice->invoice_number]
        ); // signed URL + expiry [web:9]

        return redirect($signedUrl);
    }

    /**
     * GET: tampilkan detail invoice (WAJIB middleware 'signed' di route)
     */
    public function showInvoice(Request $request, string $number)
    {
        $invoice = Invoice::with('customer')
            ->where('invoice_number', $number)
            ->firstOrFail();

        return view('invoice.detail', compact('invoice'));
    }
}
