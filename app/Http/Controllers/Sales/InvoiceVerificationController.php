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
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
        ]);

        $invoice = Invoice::with('customer')
            ->where('invoice_number', $number)
            ->firstOrFail();

        $inputEmail = mb_strtolower(trim($validated['email']));
        $inputPhone = preg_replace('/\D+/', '', $validated['phone']);

        $dbEmail = mb_strtolower(trim((string) ($invoice->customer->email ?? '')));
        $dbPhone = preg_replace('/\D+/', '', (string) ($invoice->customer->phone ?? ''));

        if ($inputEmail !== $dbEmail || $inputPhone !== $dbPhone) {
            return back()
                ->withErrors(['email' => 'Email / No HP tidak cocok dengan data customer.'])
                ->withInput();
        }

        $signedUrl = URL::temporarySignedRoute(
            'invoice.view',
            now()->addMinutes(15),
            ['number' => $invoice->invoice_number]
        );

        return redirect($signedUrl);
    }

    public function showInvoice(Request $request, string $number)
    {
        $invoice = Invoice::with('customer')
            ->where('invoice_number', $number)
            ->firstOrFail();

        return view('invoice.detail', compact('invoice'));
    }
}
