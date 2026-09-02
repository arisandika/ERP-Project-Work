<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\CustomerPortal\CustomerPortalToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class InvoiceTokenController extends Controller
{
    public function show(string $token, Request $request)
    {
        $tokenModel = CustomerPortalToken::where('token', $token)->first();

        if (! $tokenModel || ! $tokenModel->isValid()) {
            abort(404, 'Link tidak valid atau sudah kadaluarsa.');
        }

        $tokenModel->consume();

        // auto-login customer portal
        Session::regenerate();
        Session::put([
            'customer_portal_id'         => $tokenModel->customer_id,
            'customer_portal_authenticated' => true,
        ]);

        return redirect()->route('customer-portal.return.create', [
            'invoice_id' => $tokenModel->invoice_id,
        ]);
    }
}
