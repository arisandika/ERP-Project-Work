<?php

namespace App\Mail;

use App\Models\Sales\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;

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
        return new Content(
            view: 'emails.invoice',
        );
    }

    public function attachments(): array
    {
        try {
            // ==========================================
            // 1. Generate QR Code (SYNTAX BARU)
            // ==========================================
            $validationUrl = route('invoice.verify', $this->invoice->invoice_number);

            // Buat Object QR Manual
            $qrCode = new QrCode(
                data: $validationUrl,
                encoding: new Encoding('UTF-8'),
                size: 200,
                margin: 10
            );

            // Tulis ke PNG
            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            // Encode ke Base64
            $qrBase64 = base64_encode($result->getString());

            // ==========================================
            // 2. Generate Barcode
            // ==========================================
            $generator = new BarcodeGeneratorPNG();
            $barcodeData = $generator->getBarcode($this->invoice->invoice_number, $generator::TYPE_CODE_128);
            $barBase64 = base64_encode($barcodeData);

            // ==========================================
            // 3. Render PDF
            // ==========================================
            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice' => $this->invoice,
                'qrCode'  => $qrBase64,
                'barcode' => $barBase64,
            ]);

            return [
                Attachment::fromData(fn () => $pdf->output(), 'Invoice-' . $this->invoice->invoice_number . '.pdf')
                    ->withMime('application/pdf'),
            ];

        } catch (\Exception $e) {
            // Log error agar ketahuan di storage/logs/laravel.log
            \Log::error('PDF Generation Error di Email: ' . $e->getMessage());
            return [];
        }
    }
}
