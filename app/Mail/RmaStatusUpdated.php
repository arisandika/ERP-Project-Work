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
    public string $statusLabel;

    public function __construct(ReturnRequest $returnRequest)
    {
        $this->returnRequest = $returnRequest;
        $this->statusLabel = ReturnRequest::getStatusLabels()[$returnRequest->status] ?? $returnRequest->status;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update Status RMA ' . $this->returnRequest->rma_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rma.status-updated',
            with: [
                'rma_number'  => $this->returnRequest->rma_number,
                'statusLabel' => $this->statusLabel,
                'resolution'  => $this->returnRequest->resolution_type,
                'notes'       => $this->returnRequest->internal_notes,
                'loginUrl'    => route('customer-portal.login'),
            ],
        );
    }
}
