<?php

namespace App\Actions;

use App\Models\CRM\Customer;
use App\Models\CustomerPortal\CustomerPortalToken;
use App\Models\Sales\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InvoiceGeneratePortalToken
{
    public static function generate(Invoice $invoice, ?Customer $customer = null, int $ttlMinutes = 10080, int $maxUses = 5): CustomerPortalToken
    {
        $customer = $customer ?? $invoice->customer;

        /** @var CustomerPortalToken $token */
        $token = CustomerPortalToken::create([
            'invoice_id'   => $invoice->id,
            'customer_id'  => $customer->id,
            'token'        => Str::random(64),
            'expires_at'   => Carbon::now()->addMinutes($ttlMinutes),
            'max_uses'     => $maxUses,
        ]);

        return $token;
    }

    public static function url(CustomerPortalToken $token): string
    {
        return route('customer-portal.invoice.show', ['token' => $token->token]);
    }
}
