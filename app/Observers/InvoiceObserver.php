<?php

namespace App\Observers;

use App\Models\Sales\Invoice;
use App\Services\ProjectBillingService;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        app(ProjectBillingService::class)
            ->syncFromInvoice($invoice);
    }

    public function updated(Invoice $invoice): void
    {
        app(ProjectBillingService::class)
            ->syncFromInvoice($invoice);
    }
}
