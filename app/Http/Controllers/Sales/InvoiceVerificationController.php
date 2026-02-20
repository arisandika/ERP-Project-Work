<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class InvoiceVerificationController extends Controller
{
    public function showVerifyForm(string $invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->firstOrFail();

        return view('invoice.verify', [
            'invoiceNumber' => $invoice->invoice_number,
        ]);
    }

    public function submitVerify(Request $request, string $invoiceNumber)
    {
        $request->validate([
            'phone_last_4' => ['required', 'numeric', 'digits:4'],
        ]);

        $invoice = Invoice::with('customer')
            ->where('invoice_number', $invoiceNumber)
            ->firstOrFail();

        $dbPhone = (string) ($invoice->customer->phone ?? '');

        $cleanDbPhone = preg_replace('/\D+/', '', $dbPhone);
        $dbLast4 = substr($cleanDbPhone, -4);

        if ($request->input('phone_last_4') !== $dbLast4) {
            return back()
                ->withErrors(['phone_last_4' => '4 digit terakhir tidak cocok dengan data kami.'])
                ->withInput();
        }

        $signedUrl = URL::temporarySignedRoute(
            'invoice.view',
            now()->addMinutes(30),
            ['number' => $invoice->invoice_number]
        );

        return redirect($signedUrl);
    }

    public function showInvoice(Request $request, string $invoiceNumber)
    {
        // Cek validitas URL
        if (!$request->hasValidSignature()) {
            abort(403, 'Link verifikasi kadaluarsa atau tidak valid.');
        }

        $invoice = Invoice::with(['customer', 'items'])
            ->where('invoice_number', $invoiceNumber)
            ->firstOrFail();

        return view('invoice.detail', compact('invoice'));
    }

    public function download($id)
    {
        $record = Invoice::findOrFail($id);

        try {
            $validationUrl = route('invoice.verify.form', ['number' => $record->invoice_number]);

            $qrCode = new QrCode(
                data: $validationUrl,
                encoding: new Encoding('UTF-8'),
                size: 200,
                margin: 10
            );
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            $qrBase64 = base64_encode($result->getString());

            $generator = new BarcodeGeneratorPNG();
            $barBase64 = base64_encode($generator->getBarcode($record->invoice_number, $generator::TYPE_CODE_128));

            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice' => $record,
                'qrCode' => $qrBase64,
                'barcode' => $barBase64,
            ]);

            $pdf->setPaper('a4', 'portrait');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'Invoice-' . Str::slug($record->invoice_number) . '.pdf');

        } catch (\Exception $e) {
            // Error handling jika terjadi masalah saat generate
            return back()->with('error', 'Gagal mengunduh PDF: ' . $e->getMessage());
        }
    }
}
