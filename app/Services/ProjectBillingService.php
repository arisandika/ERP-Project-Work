<?php

namespace App\Services;

use App\Models\Sales\Invoice;

class ProjectBillingService
{
    public function syncFromInvoice(Invoice $invoice): void
    {
        $project = $invoice->project;

        if (!$project) {
            return;
        }

        $totalInvoiced = (float) $invoice->grand_total;
        $totalPaid = (float) ($invoice->total_paid ?? 0);
        $outstanding = max($totalInvoiced - $totalPaid, 0);

        // Tentukan billing status project
        $billingStatus = match (true) {
            $invoice->status === 'cancelled' => 'cancelled',
            $totalPaid <= 0 => 'invoiced',
            $outstanding > 0 => 'partially_paid',
            default => 'fully_paid',
        };

        $project->update([
            'nx_invoice_id' => $invoice->id,
            'sales_invoice_number' => $invoice->invoice_number,

            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'outstanding_balance' => $outstanding,

            'billing_status' => $billingStatus,
        ]);
    }
}
