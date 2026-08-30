<?php

namespace App\Mail;

use App\Models\AfterSales\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RmaStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public ReturnRequest $returnRequest;

    public function __construct(ReturnRequest $returnRequest)
    {
        $this->returnRequest = $returnRequest;
    }

    public function envelope(): Envelope
    {
        $statusLabel = ReturnRequest::getStatusLabels()[$this->returnRequest->status] ?? $this->returnRequest->status;

        return new Envelope(
            subject: "Update Status RMA {$this->returnRequest->rma_number} — {$statusLabel}",
        );
    }

    public function content(): Content
    {
        $statusLabel = ReturnRequest::getStatusLabels()[$this->returnRequest->status] ?? $this->returnRequest->status;
        $portalUrl = route('customer-portal.return.view', $this->returnRequest->rma_number);

        return new Content(
            view: 'emails.rma.status-updated',
            with: [
                'returnRequest' => $this->returnRequest,
                'statusLabel'   => $statusLabel,
                'portalUrl'     => $portalUrl,
            ],
        );
    }
}