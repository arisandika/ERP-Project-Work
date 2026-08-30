<?php
namespace App\Mail;

use App\Models\Sales\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Picqer\Barcode\BarcodeGeneratorPNG;

class InvoiceSent extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice #' . $this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        $portalToken = \App\Actions\InvoiceGeneratePortalToken::generate($this->invoice);

        return new Content(
            view: 'emails.invoice',
            with: [
                'verifyUrl'        => route('invoice.verify.form', ['number' => $this->invoice->invoice_number]),
                'downloadUrl'      => route('invoice.download', ['record' => $this->invoice->id]),
                'complaintUrl'     => \App\Actions\InvoiceGeneratePortalToken::url($portalToken),
            ],
        );
    }

    public function attachments(): array
    {
        try {
            $validationUrl = route('invoice.verify.form', ['number' => $this->invoice->invoice_number]);

            $qrCode = new QrCode(
                data: $validationUrl,
                encoding: new Encoding('UTF-8'),
                size: 200,
                margin: 10
            );

            $writer   = new PngWriter();
            $result   = $writer->write($qrCode);
            $qrBase64 = base64_encode($result->getString());

            $generator   = new BarcodeGeneratorPNG();
            $barcodeData = $generator->getBarcode($this->invoice->invoice_number, $generator::TYPE_CODE_128);
            $barBase64   = base64_encode($barcodeData);

            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice' => $this->invoice,
                'qrCode'  => $qrBase64,
                'barcode' => $barBase64,
            ]);

            return [
                Attachment::fromData(fn() => $pdf->output(), 'Invoice-' . $this->invoice->invoice_number . '.pdf')
                    ->withMime('application/pdf'),
            ];

        } catch (\Exception $e) {
            \Log::error('PDF Generation Error di Email: ' . $e->getMessage());
            return [];
        }
    }
}
