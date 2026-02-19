<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class InvoiceVerificationController extends Controller
{
    public function showVerifyForm(string $number)
    {
        $invoice = Invoice::where('invoice_number', $number)->firstOrFail();

        return view('invoice.verify', [
            'invoiceNumber' => $invoice->invoice_number,
        ]);
    }

    public function submitVerify(Request $request, string $number)
    {
        $request->validate([
            'phone_last_4' => ['required', 'numeric', 'digits:4'],
        ]);

        $invoice = Invoice::with('customer')
            ->where('invoice_number', $number)
            ->firstOrFail();

        $dbPhone = (string) ($invoice->customer->phone ?? '');

        $cleanDbPhone = preg_replace('/\D+/', '', $dbPhone);

        // Ambil 4 karakter dari belakang
        $dbLast4 = substr($cleanDbPhone, -4);

        if ($request->phone_last_4 !== $dbLast4) {
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

    public function showInvoice(Request $request, string $number)
    {
        // Cek validitas URL
        if (! $request->hasValidSignature()) {
            abort(403, 'Link verifikasi kadaluarsa atau tidak valid.');
        }

        $invoice = Invoice::with(['customer', 'items'])
            ->where('invoice_number', $number)
            ->firstOrFail();

        return view('invoice.detail', compact('invoice'));
    }
}
