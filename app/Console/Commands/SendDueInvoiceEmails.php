<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sales\Invoice;
use App\Mail\InvoiceSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendDueInvoiceEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails for invoices that are due today or overdue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for due invoices...');

        // Get invoices that are due today or overdue, and not fully paid or cancelled
        $invoices = Invoice::whereNotIn('status', ['paid', 'cancelled'])
            ->whereDate('due_date', '<=', now())
            ->whereHas('customer', function ($query) {
                $query->whereNotNull('email');
            })
            ->get();

        $count = 0;

        foreach ($invoices as $invoice) {
            try {
                Mail::to($invoice->customer->email)->queue(new InvoiceSent($invoice));

                // Update status if it was draft
                if ($invoice->status === 'draft') {
                    $invoice->update(['status' => 'sent']);
                }

                $count++;
                $this->info("Sent email for invoice: {$invoice->invoice_number}");
            } catch (\Exception $e) {
                Log::error("Failed to send email for invoice {$invoice->invoice_number}: " . $e->getMessage());
                $this->error("Failed to send email for invoice: {$invoice->invoice_number}");
            }
        }

        $this->info("Finished sending {$count} invoice emails.");
    }
}
